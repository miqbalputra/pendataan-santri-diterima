/* Guided document capture. No camera frames leave the device before confirmation. */
window.SpsbScanner = (() => {
    let stream, input, shot, previousFocus, opening = 0;
    const labels = { ayah: 'KTP Bapak', ibu: 'KTP Ibu', kk: 'Kartu Keluarga', akta: 'Akta Anak', foto: 'Foto Anak' };
    const ratios = { ayah: 1.586, ibu: 1.586, kk: 1.414, akta: 0.707, foto: 0.75 };
    const tasks = new Map();
    const manualTargets = new Set();
    let modal;

    function init() {
        const style = document.createElement('style');
        style.textContent = `.scan-dialog{border:0;border-radius:20px;padding:20px;width:min(640px,94vw);max-height:94dvh;overflow:auto;color:#1e293b}.scan-dialog::backdrop{background:#020617db}.scan-stage{position:relative;height:min(52dvh,460px);background:#020617;overflow:hidden;border-radius:12px;margin:12px 0}.scan-stage video{width:100%;height:100%;object-fit:cover}.scan-frame{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);border:3px solid #34d399;border-radius:10px;box-shadow:0 0 0 999px #0008;pointer-events:none}.scan-stage img{width:100%;height:100%;object-fit:contain}.scan-actions{display:flex;gap:8px;flex-wrap:wrap}.scan-actions button,.scan-close{padding:10px 14px;border-radius:10px;background:#e2e8f0;font-weight:700}.scan-actions .scan-primary{background:#047857;color:white}.scan-actions button:disabled{opacity:.45}.scan-progress{height:6px;background:#d1fae5;border-radius:8px;overflow:hidden;margin:8px 0}.scan-progress span{display:block;height:100%;background:#059669;transition:width .2s}.scan-progress.indeterminate span{width:40%!important;animation:scan-wait 1.2s infinite alternate}@keyframes scan-wait{to{transform:translateX(150%)}}`;
        document.head.append(style);
        modal = document.createElement('dialog');
        modal.className = 'scan-dialog';
        modal.setAttribute('aria-labelledby', 'scan-title');
        modal.innerHTML = `<div style="display:flex;justify-content:space-between;align-items:center"><h2 id="scan-title" style="font-weight:800;font-size:20px"></h2><button type="button" class="scan-close" aria-label="Tutup kamera">Tutup</button></div>
            <p id="scan-help" style="font-size:14px;margin-top:10px">Letakkan seluruh dokumen di dalam bingkai. Hindari pantulan lampu, pegang kamera sejajar, dan ketuk dokumen pada layar bila kamera mendukung fokus.</p>
            <div class="scan-stage"><video autoplay playsinline muted></video><div class="scan-frame"></div><img hidden alt="Pratinjau foto yang akan dikirim"></div>
            <p id="scan-quality" role="status" aria-live="polite" style="margin:12px 0;font-size:14px"></p>
            <div class="scan-actions"><button type="button" class="scan-primary" id="scan-take">Ambil Foto</button><button type="button" class="scan-primary" id="scan-send" hidden>Gunakan & Baca OCR</button><button type="button" id="scan-retake" hidden>Foto Ulang</button><button type="button" id="scan-manual">Isi Manual</button></div>
            <p style="font-size:12px;margin-top:12px">Isi manual tetap membutuhkan kelima lampiran. Foto yang kurang jelas perlu diperiksa kembali oleh panitia.</p>`;
        document.body.append(modal);
        modal.querySelector('.scan-close').onclick = close;
        modal.addEventListener('cancel', e => { e.preventDefault(); close(); });
        modal.addEventListener('close', stopCamera);
        modal.querySelector('#scan-take').onclick = capture;
        modal.querySelector('#scan-retake').onclick = () => open(input);
        modal.querySelector('#scan-send').onclick = () => accept(false);
        modal.querySelector('#scan-manual').onclick = () => {
            if (shot) accept(true);
            else {
                manualTargets.add(input.dataset.target);
                const selected = input;
                close();
                selected.removeAttribute('capture');
                selected.click();
                window.dispatchEvent(new CustomEvent('scan:manual'));
            }
        };
        new ResizeObserver(fitFrame).observe(modal.querySelector('.scan-stage'));
        document.querySelectorAll('.file-input').forEach(el => {
            const manual = document.createElement('button');
            manual.type = 'button';
            manual.textContent = 'Isi manual / pilih berkas';
            manual.className = 'text-xs font-bold text-emerald-700 underline mt-3';
            el.closest('label').after(manual);
            manual.onclick = () => {
                manualTargets.add(el.dataset.target);
                tasks.get(el.dataset.target)?.abort();
                window.dispatchEvent(new CustomEvent('scan:manual'));
                if (!tasks.has(el.dataset.target)) el.click();
                document.getElementById(`hint-${el.dataset.target}`).textContent = el.files.length ? 'Terlampir • isi data manual' : 'Lampiran tetap wajib dipilih';
            };
            el.closest('label').addEventListener('click', event => {
                if (event.target === el) return;
                event.preventDefault();
                if (!tasks.has(el.dataset.target)) open(el);
            });
        });
    }

    function stopCamera() { if (stream) stream.getTracks().forEach(track => track.stop()); stream = null; }
    function close() {
        opening++;
        stopCamera();
        const image = modal.querySelector('img');
        if (image.src.startsWith('blob:')) URL.revokeObjectURL(image.src);
        modal.close();
        previousFocus?.focus();
    }
    function fitFrame() {
        if (!input || !modal.open) return;
        const stage = modal.querySelector('.scan-stage');
        const ratio = ratios[input.dataset.target];
        const width = Math.min(stage.clientWidth * .88, stage.clientHeight * .82 * ratio);
        Object.assign(modal.querySelector('.scan-frame').style, { width: `${width}px`, height: `${width / ratio}px` });
    }
    async function open(el) {
        const generation = ++opening;
        stopCamera();
        input = el;
        shot = null;
        if (!modal.open) previousFocus = document.activeElement;
        modal.querySelector('#scan-title').textContent = `Foto ${labels[el.dataset.target]}`;
        modal.querySelector('#scan-help').textContent = ['ayah', 'ibu', 'kk'].includes(el.dataset.target)
            ? 'Posisikan ponsel mendatar untuk dokumen melebar. Sesuaikan seluruh dokumen dengan bingkai, hindari pantulan cahaya, dan tunggu kamera fokus.'
            : 'Sesuaikan seluruh dokumen atau wajah anak dengan bingkai. Hindari pantulan cahaya dan tunggu kamera fokus.';
        const video = modal.querySelector('video');
        const preview = modal.querySelector('img');
        if (preview.src.startsWith('blob:')) URL.revokeObjectURL(preview.src);
        preview.hidden = true;
        video.hidden = false;
        modal.querySelector('.scan-frame').hidden = false;
        modal.querySelector('#scan-take').hidden = false;
        modal.querySelector('#scan-take').disabled = true;
        modal.querySelector('#scan-send').hidden = true;
        modal.querySelector('#scan-send').textContent = el.dataset.target === 'foto' ? 'Gunakan Foto' : 'Gunakan & Baca OCR';
        modal.querySelector('#scan-retake').hidden = true;
        modal.querySelector('#scan-quality').textContent = 'Membuka kamera belakang…';
        if (!modal.open) modal.showModal();
        fitFrame();
        try {
            if (!navigator.mediaDevices?.getUserMedia) throw new Error('unavailable');
            const nextStream = await navigator.mediaDevices.getUserMedia({ audio: false, video: { facingMode: { ideal: 'environment' }, width: { ideal: 3840 }, height: { ideal: 2160 } } });
            if (generation !== opening) { nextStream.getTracks().forEach(track => track.stop()); return; }
            stream = nextStream;
            video.srcObject = stream;
            await video.play();
            if (generation !== opening) return;
            modal.querySelector('#scan-take').disabled = false;
            modal.querySelector('#scan-quality').textContent = 'Sesuaikan posisi dengan bingkai, tunggu fokus, lalu ambil foto.';
        } catch (_) {
            if (generation !== opening) return;
            stopCamera();
            modal.querySelector('#scan-quality').textContent = 'Kamera tidak tersedia atau izinnya ditolak. Izinkan kamera pada browser (memerlukan HTTPS), atau pilih Isi Manual untuk melampirkan foto dari perangkat.';
        }
    }
    async function capture() {
        const generation = opening;
        const target = input.dataset.target;
        const video = modal.querySelector('video');
        if (!video.videoWidth) return;
        modal.querySelector('#scan-take').disabled = true;
        const stage = modal.querySelector('.scan-stage');
        const frame = modal.querySelector('.scan-frame');
        // Map the visible object-fit:cover frame back onto source camera pixels.
        const scale = Math.max(stage.clientWidth / video.videoWidth, stage.clientHeight / video.videoHeight);
        const width = frame.offsetWidth / scale, height = frame.offsetHeight / scale;
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(width); canvas.height = Math.round(height);
        canvas.getContext('2d').drawImage(video, (video.videoWidth - width) / 2, (video.videoHeight - height) / 2, width, height, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .95));
        if (generation !== opening) return;
        if (!blob) { modal.querySelector('#scan-take').disabled = false; return; }
        shot = new File([blob], `${target}-${Date.now()}.jpg`, { type: 'image/jpeg' });
        stopCamera();
        video.hidden = true;
        frame.hidden = true;
        const preview = modal.querySelector('img');
        preview.src = URL.createObjectURL(shot); preview.hidden = false;
        modal.querySelector('#scan-take').hidden = true;
        modal.querySelector('#scan-retake').hidden = false;
        modal.querySelector('#scan-send').hidden = false;
        modal.querySelector('#scan-send').disabled = true;
        modal.querySelector('#scan-quality').textContent = 'Memeriksa ketajaman dan pencahayaan…';
        try {
            const issues = await quality(shot, target);
            if (generation !== opening) return;
            modal.querySelector('#scan-quality').textContent = issues.length ? `${issues.join(' ')} Foto ulang atau pilih Isi Manual untuk tetap melampirkan foto ini.` : 'Foto siap. Periksa semua tulisan terlihat jelas, lalu gunakan foto ini.';
            modal.querySelector('#scan-send').disabled = issues.length > 0;
        } catch (_) {
            if (generation === opening) modal.querySelector('#scan-quality').textContent = 'Foto tidak dapat diperiksa. Foto ulang atau pilih Isi Manual.';
        }
    }
    function accept(manual) {
        const transfer = new DataTransfer();
        transfer.items.add(shot);
        input.files = transfer.files;
        if (manual) manualTargets.add(input.dataset.target);
        else manualTargets.delete(input.dataset.target);
        const selected = input;
        close();
        selected.dispatchEvent(new Event('change', { bubbles: true }));
    }
    async function quality(file, target) {
        const bitmap = await createImageBitmap(file);
        try {
            const issues = [];
            if (Math.max(bitmap.width, bitmap.height) < (target === 'kk' ? 1600 : 1000) || Math.min(bitmap.width, bitmap.height) < 550) issues.push('Resolusi foto terlalu rendah.');
            const canvas = document.createElement('canvas');
            const scale = Math.min(1, 800 / Math.max(bitmap.width, bitmap.height));
            canvas.width = Math.round(bitmap.width * scale); canvas.height = Math.round(bitmap.height * scale);
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            const pixels = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
            const gray = new Float32Array(pixels.length / 4);
            let brightness = 0, dark = 0, sum = 0, squares = 0, count = 0;
            for (let i = 0; i < gray.length; i++) { const value = pixels[i*4]*.299 + pixels[i*4+1]*.587 + pixels[i*4+2]*.114; gray[i] = value; brightness += value; if (value < 35) dark++; }
            for (let y = 1; y < canvas.height-1; y++) for (let x = 1; x < canvas.width-1; x++) {
                const i = y*canvas.width+x;
                const lap = gray[i-1]+gray[i+1]+gray[i-canvas.width]+gray[i+canvas.width]-4*gray[i];
                sum += lap; squares += lap*lap; count++;
            }
            if (brightness/gray.length < 65 || dark/gray.length > .65) issues.push('Foto terlalu gelap.');
            if (brightness/gray.length > 247) issues.push('Foto terlalu terang; tulisan mungkin hilang.');
            if (target !== 'foto' && squares/count-(sum/count)**2 < 65) issues.push('Tulisan tampak buram atau kurang kontras.');
            return issues;
        } finally { bitmap.close(); }
    }
    function progress(target, text, percent = null) {
        const status = document.getElementById(`status-${target}`);
        status.textContent = text;
        let meter = document.getElementById(`ocr-progress-${target}`);
        if (!meter) {
            meter = document.createElement('div'); meter.id = `ocr-progress-${target}`;
            meter.className = 'scan-progress'; meter.style.width = '100%';
            meter.setAttribute('role', 'progressbar'); meter.setAttribute('aria-label', `Progres ${labels[target]}`);
            meter.setAttribute('aria-valuemin', '0'); meter.setAttribute('aria-valuemax', '100');
            meter.innerHTML = '<span></span>'; status.after(meter);
        }
        status.setAttribute('aria-live', 'polite');
        meter.classList.toggle('indeterminate', percent === null);
        if (percent === null) meter.removeAttribute('aria-valuenow');
        else { meter.setAttribute('aria-valuenow', String(percent)); meter.firstElementChild.style.width = `${percent}%`; }
    }
    async function manualChoice(message) {
        const result = await Swal.fire({ icon: 'warning', title: 'Foto belum terbaca jelas', text: `${message} Anda dapat foto ulang atau isi data manual. Lampiran tetap dibutuhkan.`, showCancelButton: true, confirmButtonText: 'Isi Manual', cancelButtonText: 'Foto Ulang', confirmButtonColor: '#047857' });
        return result.isConfirmed;
    }
    function request(file, target, signal, input, runOcr = true) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const abort = () => xhr.abort();
            signal.addEventListener('abort', abort, { once: true });
            xhr.open('POST', '/ocr/scans'); xhr.timeout = 90000;
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('input[name="_token"]').value);
            xhr.upload.onprogress = e => { if (e.lengthComputable) progress(target, `Mengirim foto: ${Math.round(e.loaded/e.total*100)}%`, Math.round(e.loaded/e.total*100)); };
            xhr.upload.onload = () => progress(target, 'Foto diterima. Masuk antrean OCR…');
            xhr.onloadend = () => signal.removeEventListener('abort', abort);
            xhr.onload = () => {
                try {
                    const data = JSON.parse(xhr.responseText);
                    if (xhr.status < 200 || xhr.status >= 300) throw new Error(data.message || 'Foto belum dapat disimpan.');
                    if (input) { input.dataset.stagingId = data.id; input.dataset.stagingToken = data.scan_token; }
                    resolve(data);
                } catch (error) { reject(error); }
            };
            xhr.onerror = () => reject(new Error('Koneksi terputus.'));
            xhr.ontimeout = () => reject(new Error('Unggahan terlalu lama.'));
            xhr.onabort = () => reject(new DOMException('Dibatalkan', 'AbortError'));
            const form = new FormData(); form.append('file', file); form.append('target', target); form.append('run_ocr', runOcr ? '1' : '0');
            if (signal.aborted) { reject(new DOMException('Dibatalkan', 'AbortError')); return; }
            xhr.send(form);
        }).then(async scan => {
            if (!runOcr || target === 'foto') return scan;
            const labels = {
                queued: 'Menunggu giliran OCR…',
                processing: 'Menyiapkan foto…',
                preparing: 'Meluruskan dan meningkatkan keterbacaan…',
                recognizing: 'Memperbaiki foto dan membaca teks di VPS…',
                complete: 'Memeriksa hasil bacaan…',
                manual: 'OCR tidak selesai. Foto tetap tersimpan untuk isi manual.',
            };
            const deadline = Date.now() + 90000;
            let transientErrors = 0;
            while (Date.now() < deadline) {
                if (signal.aborted) throw new DOMException('Dibatalkan', 'AbortError');
                await new Promise((resolve, reject) => {
                    const onAbort = () => { clearTimeout(timer); reject(new DOMException('Dibatalkan', 'AbortError')); };
                    const timer = setTimeout(() => { signal.removeEventListener('abort', onAbort); resolve(); }, 850);
                    signal.addEventListener('abort', onAbort, { once: true });
                });
                try {
                    const response = await fetch(`/ocr/scans/${encodeURIComponent(scan.id)}`, {
                        credentials: 'same-origin', signal,
                        headers: { 'Accept': 'application/json', 'X-OCR-Token': scan.scan_token },
                    });
                    const current = await response.json();
                    if (!response.ok) throw new Error(current.message || 'Sesi foto berakhir.');
                    transientErrors = 0;
                    const percent = ['complete', 'manual'].includes(current.stage) ? 100 : null;
                    progress(target, labels[current.stage] || 'Memproses dokumen di VPS…', percent);
                    if (['ready', 'failed'].includes(current.status)) return { ...scan, ...current };
                } catch (error) {
                    if (signal.aborted || error.name === 'AbortError') throw error;
                    if (++transientErrors >= 6) return { ...scan, status: 'failed', result: { fields: {}, document_check: { is_expected_document: false }, warnings: ['Status OCR belum dapat dimuat. Foto tetap tersimpan; lanjutkan dengan isi manual.'] } };
                }
            }
            return { ...scan, status: 'failed', result: { fields: {}, document_check: { is_expected_document: false }, warnings: ['Proses OCR melewati batas waktu. Foto tetap tersimpan; lanjutkan dengan isi manual.'] } };
        });
    }
    async function discard(input) {
        const id = input?.dataset.stagingId;
        const token = input?.dataset.stagingToken;
        if (!id || !token) return;
        delete input.dataset.stagingId;
        delete input.dataset.stagingToken;
        try {
            await fetch(`/ocr/scans/${encodeURIComponent(id)}`, {
                method: 'DELETE', credentials: 'same-origin', keepalive: true,
                headers: {
                    'Accept': 'application/json', 'X-OCR-Token': token,
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                },
            });
        } catch (_) {}
    }
    document.addEventListener('DOMContentLoaded', init);
    window.addEventListener('pagehide', stopCamera);
    return { labels, tasks, manualTargets, quality, progress, request, discard, manualChoice, open };
})();
