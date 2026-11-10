const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');
const template = fs.readFileSync(path.join(root, 'resources/views/pendaftaran.blade.php'), 'utf8');
const runSource = template.slice(template.indexOf('        async function runOCR('), template.indexOf('        function triggerHighlight'));

function setup(response, engine = 'direct_ai', localData = {}) {
    const calls = { applied: [], parsed: [], manual: [] };
    const context = vm.createContext({
        OCR_ENGINE: engine, setTimeout, clearTimeout, DOMException,
        window: { SpsbScanner: {
            request: async () => response, progress() {},
            ensureOcr: async () => {},
            manualChoice: async message => { calls.manual.push(message); return true; },
        } },
        Tesseract: { createWorker: async () => ({ recognize: async () => ({ data: localData }), terminate: async () => {} }) },
        SpsbForm: { applyOcr: (_, callback) => callback() },
        applyOcrFields: fields => calls.applied.push(fields),
        parseAndFill: text => calls.parsed.push(text),
    });
    vm.runInContext(runSource, context);
    return { calls, run: signal => context.runOCR({}, 'ayah', signal || new AbortController().signal) };
}

test('structured identity is not overwritten by a second regex parse of JSON', async () => {
    const fields = { nama: 'CONTOH', nik: '1234567890123456' };
    const { calls, run } = setup({ fields, extracted_text: JSON.stringify(fields) });
    await run();
    assert.deepEqual(calls.applied, [fields]);
    assert.equal(calls.parsed.length, 0);
});

test('unreadable or mismatched documents offer manual entry without autofill', async () => {
    for (const check of [{ confidence: 'low' }, { is_expected_document: false }]) {
        const { calls, run } = setup({ fields: { nama: 'UNTRUSTED' }, document_check: check });
        assert.equal(await run(), true);
        assert.equal(calls.manual.length, 1);
        assert.equal(calls.applied.length, 0);
    }
});

test('low-confidence local OCR also offers manual entry', async () => {
    const { calls, run } = setup(null, 'local', { text: 'NIK 1234567890123456', confidence: 40 });
    await run();
    assert.equal(calls.manual.length, 1);
    assert.equal(calls.parsed.length, 0);
});

test('switching to manual ignores a late OCR response', async () => {
    const controller = new AbortController();
    controller.abort();
    const { calls, run } = setup({ fields: { nama: 'LATE RESPONSE' } });
    await run(controller.signal);
    assert.equal(calls.applied.length, 0);
});

test('all embedded scripts have valid JavaScript syntax', () => {
    for (const match of template.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)) {
        if (match[1].trim()) new vm.Script(match[1]);
    }
    new vm.Script(fs.readFileSync(path.join(root, 'public/js/document-scanner.js'), 'utf8'));
});
