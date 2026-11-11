import { copyFile } from 'node:fs/promises';

const files = [
    ['node_modules/signature_pad/dist/signature_pad.umd.min.js', 'public/vendor/signature-pad.min.js'],
    ['node_modules/sweetalert2/dist/sweetalert2.all.min.js', 'public/vendor/sweetalert2.min.js'],
];
await Promise.all(files.map(([from, to]) => copyFile(from, to)));
console.log('Browser libraries copied. OCR runs in the private VPS service.');
