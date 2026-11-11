const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
const template = fs.readFileSync(path.join(root, 'resources/views/pendaftaran.blade.php'), 'utf8');
const runSource = template.slice(template.indexOf('        const OCR_FIELD_LABELS'), template.indexOf('        function triggerHighlight'));

function setup(scan, options = {}) {
    const calls = { applied: [], manual: [], requested: [] };
    const checked = options.checked || [];
    const context = vm.createContext({
        setTimeout, clearTimeout, DOMException,
        window: { SpsbScanner: {
            manualTargets: new Set(),
            request: async (...args) => { calls.requested.push(args); return scan; },
            progress() {},
            manualChoice: async message => { calls.manual.push(message); return true; },
        } },
        document: { querySelectorAll: () => checked.map(key => ({ dataset: { ocrKey: key } })) },
        Swal: { fire: async config => config.preConfirm
            ? { isConfirmed: true, value: config.preConfirm() }
            : { isConfirmed: true } },
        SpsbForm: {
            applyOcr: (_, callback) => callback(),
            escape: value => String(value).replace(/[&<>"']/g, ''),
        },
        applyOcrFields: fields => calls.applied.push(fields),
    });
    vm.runInContext(runSource, context);
    return {
        calls,
        run: (signal, target = 'ayah') => context.runOCR({}, target, signal || new AbortController().signal, { dataset: {} }),
    };
}

test('medium-confidence values are suggestions and fill only after explicit selection', async () => {
    const fields = {
        nama: { value: 'BUDI SANTOSO', confidence: 0.88, decision: 'confirm' },
        nik: { value: '1234567890123456', confidence: 0.91, decision: 'confirm' },
    };
    const { calls, run } = setup({ status: 'ready', result: {
        document_check: { is_expected_document: true, confidence: 'high' }, fields,
    } }, { checked: ['nik'] });
    await run();
    assert.deepEqual(JSON.parse(JSON.stringify(calls.applied)), [{ nik: '1234567890123456' }]);
    assert.equal(calls.requested[0][4], true);
});

test('high-confidence values are applied only when the server has enabled the gate', async () => {
    const values = { nik: { value: '1234567890123456', confidence: 0.99, decision: 'auto' } };
    const { calls, run } = setup({ status: 'ready', result: {
        document_check: { is_expected_document: true, confidence: 'high' }, fields: values,
    } });
    await run();
    assert.deepEqual(JSON.parse(JSON.stringify(calls.applied)), [{ nik: '1234567890123456' }]);
});

test('mismatched and failed documents offer manual entry without autofill', async () => {
    for (const scan of [
        { status: 'ready', result: { fields: { nama: { value: 'SALAH', decision: 'confirm' } }, document_check: { is_expected_document: false } } },
        { status: 'failed', result: { fields: {}, warnings: ['OCR gagal'], document_check: { is_expected_document: false } } },
    ]) {
        const { calls, run } = setup(scan);
        assert.equal(await run(), true);
        assert.equal(calls.manual.length, 1);
        assert.equal(calls.applied.length, 0);
    }
});

test('KK suggestions remain limited to address fields', async () => {
    const { calls, run } = setup({ status: 'ready', result: {
        document_check: { is_expected_document: true, confidence: 'high' },
        fields: { alamat: { value: 'JALAN CONTOH', confidence: 0.9, decision: 'auto' } },
    } });
    await run(undefined, 'kk');
    assert.deepEqual(JSON.parse(JSON.stringify(calls.applied)), [{ alamat: 'JALAN CONTOH' }]);
});

test('an aborted scan ignores late OCR results', async () => {
    const controller = new AbortController();
    controller.abort();
    const { calls, run } = setup({ status: 'ready', result: {
        document_check: { is_expected_document: true, confidence: 'high' },
        fields: { nama: { value: 'LATE RESPONSE', confidence: 0.99, decision: 'auto' } },
    } });
    await run(controller.signal);
    assert.equal(calls.applied.length, 0);
});

test('embedded JavaScript and scanner source have valid syntax', () => {
    for (const match of template.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)) {
        if (match[1].trim()) new vm.Script(match[1]);
    }
    new vm.Script(fs.readFileSync(path.join(root, 'public/js/document-scanner.js'), 'utf8'));
});
