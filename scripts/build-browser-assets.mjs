import { copyFile, mkdir, readdir } from 'node:fs/promises';

const files = [
    ['node_modules/signature_pad/dist/signature_pad.umd.min.js', 'public/vendor/signature-pad.min.js'],
    ['node_modules/sweetalert2/dist/sweetalert2.all.min.js', 'public/vendor/sweetalert2.min.js'],
    ['node_modules/tesseract.js/dist/tesseract.min.js', 'public/vendor/tesseract/tesseract.min.js'],
    ['node_modules/tesseract.js/dist/worker.min.js', 'public/vendor/tesseract/worker.min.js'],
];
await mkdir('public/vendor/tesseract/core', { recursive: true });
for (const file of await readdir('node_modules/tesseract.js-core')) {
    if (/\.wasm(?:\.js)?$/.test(file)) files.push([`node_modules/tesseract.js-core/${file}`, `public/vendor/tesseract/core/${file}`]);
}
await Promise.all(files.map(([from, to]) => copyFile(from, to)));
console.log('Browser libraries copied; OCR loads on demand.');
