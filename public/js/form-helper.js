window.SpsbForm = (() => {
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
    let sending = false;
    const form = () => document.getElementById('form-pendaftaran');
    const label = input => {
        if (input.type === 'file') return window.SpsbScanner.labels[input.dataset.target] || 'Lampiran';
        if (input.type === 'checkbox') return 'Pernyataan kebenaran data';
        if (input.id === 'ttd-role') return 'Pilihan penandatangan';
        if (input.id === 'ttd-nama') return 'Nama penandatangan';
        return input.labels?.[0]?.textContent.trim() || input.parentElement?.querySelector('label')?.textContent.trim() || input.name.replaceAll('_', ' ');
    };

    function error(input, message) {
        if (!input) return;
        let node = document.getElementById(`${input.id}-error`);
        if (!node) {
            node = document.createElement('p'); node.id = `${input.id}-error`;
            node.className = 'text-sm text-red-700 font-semibold mt-2';
            node.setAttribute('role', 'alert');
            (input.type === 'file' ? input.closest('[id^="dropzone-"]') : input.parentElement).append(node);
        }
        node.textContent = message;
        input.setAttribute('aria-invalid', 'true'); input.setAttribute('aria-describedby', node.id);
    }
    function clearError(input) {
        document.getElementById(`${input.id}-error`)?.remove();
        input.removeAttribute('aria-invalid'); input.removeAttribute('aria-describedby');
    }
    function focus(input) {
        if (!input) return;
        const target = input.type === 'file' ? input.closest('[id^="dropzone-"]') : input;
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (input.type === 'file') { target.tabIndex = -1; target.focus({ preventScroll: true }); }
        else input.focus({ preventScroll: true });
    }
    function validate(signature) {
        let first;
        for (const input of form().querySelectorAll('input,select,textarea')) {
            clearError(input);
            if (input.type === 'hidden' || input.disabled) continue;
            if (!input.checkValidity()) {
                let message = input.validity.valueMissing ? `${label(input)} wajib diisi.` : `Periksa kembali ${label(input)}.`;
                if (input.name.startsWith('nik') && input.validity.patternMismatch) message = 'NIK harus terdiri dari tepat 16 angka, sesuai dokumen.';
                if (input.type === 'email' && input.validity.typeMismatch) message = 'Alamat email belum benar. Contoh: nama@gmail.com';
                error(input, message); first ||= input;
            }
        }
        const nik = form().elements.namedItem('nik').value;
        const ayah = form().elements.namedItem('nik_ayah');
        const ibu = form().elements.namedItem('nik_ibu');
        if (ayah.value && ayah.value === nik) { error(ayah, 'NIK Bapak harus berbeda dari NIK anak.'); first ||= ayah; }
        if (ibu.value && (ibu.value === nik || ibu.value === ayah.value)) { error(ibu, 'NIK Ibu harus berbeda dari NIK anak dan Bapak.'); first ||= ibu; }
        if (signature.isEmpty()) { const name = document.getElementById('ttd-nama'); error(name, 'Silakan tanda tangan pada kotak di bawah.'); first ||= name; }
        if (first) {
            Swal.fire({ icon: 'info', title: 'Ada data yang perlu dilengkapi', text: 'Kolom yang perlu diperbaiki sudah ditandai. Data dan foto Anda tetap ada.', confirmButtonText: 'Perbaiki Data', returnFocus: false }).then(() => focus(first));
            return false;
        }
        return true;
    }
    function review() {
        const groups = [
            ['Data anak', ['nama_lengkap', 'nik', 'jenis_kelamin', 'tanggal_lahir']],
            ['Data Bapak', ['nama_ayah', 'nik_ayah', 'no_wa_ayah', 'email_ayah']],
            ['Data Ibu', ['nama_ibu', 'nik_ibu', 'no_wa_ibu', 'email_ibu']],
            ['Alamat anak', ['alamat_lengkap', 'rt_rw', 'kelurahan_desa', 'kecamatan', 'kabupaten_kota', 'propinsi', 'kode_pos']],
        ];
        return '<div class="text-left text-sm max-h-72 overflow-y-auto">' + groups.map(([title, names]) =>
            `<section class="mb-4"><h3 class="font-bold text-emerald-800 mb-2">${title}</h3>` + names.map(name => {
                const input = form().elements.namedItem(name);
                return `<p class="mb-1"><span class="text-slate-500">${escape(label(input))}:</span> <strong>${escape(input.value || 'Belum diisi')}</strong></p>`;
            }).join('') + '</section>'
        ).join('') + '<p class="font-bold">Kelima lampiran sudah dipilih. Periksa kembali NIK, nama, tanggal lahir, dan nomor WhatsApp sebelum mengirim.</p></div>';
    }
    function applyOcr(target, callback) {
        const inputs = [...form().querySelectorAll('input,select,textarea')];
        const before = new Map(inputs.map(input => [input, input.value]));
        callback();
        for (const input of inputs) {
            if (input.value === before.get(input)) continue;
            if (input.dataset.userEdited === 'true') { input.value = before.get(input); continue; }
            input.dataset.ocrSource = target; input.dataset.ocrValue = input.value;
            input.classList.add('bg-emerald-50');
        }
    }
    function clearOcr(target) {
        form().querySelectorAll('[data-ocr-source]').forEach(input => {
            if (input.dataset.ocrSource !== target) return;
            if (input.value === input.dataset.ocrValue) input.value = '';
            delete input.dataset.ocrSource; delete input.dataset.ocrValue;
        });
    }
    function submit(signature) {
        if (sending) return;
        if (!navigator.onLine) {
            Swal.fire({ icon: 'info', title: 'Koneksi internet terputus', text: 'Data dan foto tetap di halaman ini. Sambungkan internet lalu kirim kembali.' }); return;
        }
        sending = true;
        document.getElementById('ttd-base64').value = signature.toDataURL('image/png');
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form().action); xhr.timeout = 120000;
        xhr.setRequestHeader('Accept', 'application/json');
        Swal.fire({ title: 'Mengirim data…', html: '<p id="send-progress" role="status" aria-live="polite">Menyiapkan lampiran…</p>', allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading() });
        xhr.upload.onprogress = e => {
            const node = document.getElementById('send-progress');
            if (node && e.lengthComputable) node.textContent = e.loaded === e.total ? 'Lampiran terkirim. Menyimpan data…' : `Mengirim lampiran: ${Math.round(e.loaded/e.total*100)}%`;
        };
        const retry = message => { sending = false; Swal.fire({ icon: 'warning', title: 'Belum dapat mengirim', text: message + ' Data dan foto tetap ada di halaman ini.' }); };
        xhr.onload = () => {
            let data;
            try { data = JSON.parse(xhr.responseText); } catch (_) { retry('Server belum memberikan konfirmasi. Cek status pendaftaran sebelum mencoba lagi.'); return; }
            if (xhr.status >= 200 && xhr.status < 300 && data.redirect_url) {
                try { localStorage.removeItem('spsb_form_data'); } catch (_) {}
                window.location.assign(data.redirect_url); return;
            }
            sending = false;
            if (xhr.status === 422 && data.errors) {
                let first;
                for (const [name, messages] of Object.entries(data.errors)) {
                    const input = name === 'tanda_tangan_base64' ? document.getElementById('ttd-nama') : form().elements.namedItem(name);
                    if (input) { error(input, messages[0]); first ||= input; }
                }
                Swal.fire({ icon: 'info', title: 'Periksa kembali data', text: Object.values(data.errors)[0][0], confirmButtonText: 'Perbaiki Data', returnFocus: false }).then(() => focus(first));
            } else retry(xhr.status === 419 ? 'Sesi berakhir. Simpan data lalu muat ulang halaman; foto perlu dipilih kembali.' : (xhr.status === 429 ? 'Terlalu banyak percobaan. Tunggu satu menit lalu coba lagi.' : 'Server sedang mengalami gangguan. Cek status pendaftaran sebelum mencoba lagi.'));
        };
        xhr.onerror = xhr.ontimeout = () => retry('Koneksi terputus atau waktu tunggu habis. Cek status pendaftaran sebelum mengirim ulang.');
        xhr.send(new FormData(form()));
    }
    document.addEventListener('DOMContentLoaded', () => {
        if (!form()) return;
        form().noValidate = true;
        form().querySelectorAll('input,select,textarea').forEach((input, index) => {
            if (input.type === 'hidden') return;
            if (!input.id) input.id = `field-${index}`;
            const text = input.parentElement.querySelector('label');
            if (text && !text.contains(input)) text.htmlFor = input.id;
            if (input.type === 'date') input.max = new Date(Date.now() - new Date().getTimezoneOffset()*60000).toISOString().slice(0,10);
            if (input.name === 'kode_pos') { input.pattern = '[0-9]{5}'; input.inputMode = 'numeric'; input.maxLength = 5; }
            if (['nisn', 'npsn_sekolah_asal', 'nik_wali'].includes(input.name)) {
                const length = { nisn: 10, npsn_sekolah_asal: 8, nik_wali: 16 }[input.name];
                input.pattern = `[0-9]{${length}}`; input.inputMode = 'numeric'; input.maxLength = length;
            }
            if (['tinggi_badan', 'berat_badan', 'jumlah_saudara_kandung'].includes(input.name)) {
                input.min = input.name === 'jumlah_saudara_kandung' ? '0' : '1';
                input.max = { tinggi_badan: '300', berat_badan: '500', jumlah_saudara_kandung: '100' }[input.name];
            }
            if (/^rt_rw/.test(input.name)) { input.pattern = '[0-9]{1,3}/[0-9]{1,3}'; input.placeholder = 'Contoh: 008/002'; }
            input.addEventListener('input', () => { clearError(input); input.dataset.userEdited = 'true'; });
        });
        document.getElementById('fill-manual').onclick = () => {
            window.dispatchEvent(new CustomEvent('scan:manual'));
            focus(form().elements.namedItem('nama_lengkap'));
        };
    });
    return { validate, review, submit, applyOcr, clearOcr, escape };
})();
