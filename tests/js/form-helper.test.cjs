const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '../..');

function helpers(inputs) {
    const form = { querySelectorAll: () => inputs };
    const context = vm.createContext({
        window: {}, document: { getElementById: () => form, addEventListener() {} },
    });
    vm.runInContext(fs.readFileSync(path.join(root, 'public/js/form-helper.js'), 'utf8'), context);
    return context.window.SpsbForm;
}
const input = (value, dataset = {}) => ({ value, dataset, classList: { add() {} } });

test('OCR preserves manual corrections while filling untouched fields', () => {
    const manual = input('NAMA YANG BENAR', { userEdited: 'true' });
    const untouched = input('');
    const helper = helpers([manual, untouched]);
    helper.applyOcr('ayah', () => { manual.value = 'SALAH BACA'; untouched.value = 'JALAN CONTOH'; });
    assert.equal(manual.value, 'NAMA YANG BENAR');
    assert.equal(untouched.value, 'JALAN CONTOH');
    assert.equal(untouched.dataset.ocrSource, 'ayah');
});

test('replacing a document clears only unchanged OCR values from that document', () => {
    const old = input('OCR LAMA', { ocrSource: 'ayah', ocrValue: 'OCR LAMA' });
    const corrected = input('KOREKSI MANUAL', { ocrSource: 'ayah', ocrValue: 'SALAH' });
    const mother = input('DATA IBU', { ocrSource: 'ibu', ocrValue: 'DATA IBU' });
    helpers([old, corrected, mother]).clearOcr('ayah');
    assert.equal(old.value, '');
    assert.equal(corrected.value, 'KOREKSI MANUAL');
    assert.equal(mother.value, 'DATA IBU');
});

test('review safely displays names containing HTML characters', () => {
    const helper = helpers([]);
    assert.equal(helper.escape('<img src=x onerror="alert(1)">'), '&lt;img src=x onerror=&quot;alert(1)&quot;&gt;');
});

test('signature strokes survive a change of canvas size', () => {
    const source = fs.readFileSync(path.join(root, 'resources/views/pendaftaran.blade.php'), 'utf8');
    const method = source.slice(source.indexOf('        function resizeCanvas()'), source.indexOf('        window.addEventListener("resize"'));
    let restored, clears = 0;
    const strokes = [{ points: [{ x: 1, y: 2 }, { x: 9, y: 4 }] }];
    const context = vm.createContext({
        window: { devicePixelRatio: 2 },
        canvas: { width: 0, height: 0, offsetWidth: 300, offsetHeight: 150, getContext: () => ({ scale() {} }) },
        signaturePad: { toData: () => strokes, clear: () => clears++, fromData: data => restored = data },
    });
    vm.runInContext(method, context);
    context.resizeCanvas();
    assert.equal(context.canvas.width, 600);
    assert.equal(restored, strokes);
    context.resizeCanvas();
    assert.equal(clears, 1);
});
