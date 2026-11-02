import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';

const appShell = document.querySelector('.app-shell');
const menuButton = document.querySelector('.mobile-menu-button');
const menuOverlay = document.querySelector('.sidebar-overlay');

if (appShell && menuButton && menuOverlay) {
    const closeMenu = () => {
        appShell.classList.remove('menu-open');
        menuButton.setAttribute('aria-expanded', 'false');
        menuButton.setAttribute('aria-label', 'Buka menu navigasi');
    };

    menuButton.addEventListener('click', () => {
        const isOpen = appShell.classList.toggle('menu-open');
        menuButton.setAttribute('aria-expanded', String(isOpen));
        menuButton.setAttribute('aria-label', isOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        menuOverlay.setAttribute('aria-hidden', String(!isOpen));
    });

    menuOverlay.addEventListener('click', () => {
        closeMenu();
        menuOverlay.setAttribute('aria-hidden', 'true');
    });
    appShell.querySelectorAll('.sidebar-nav a, .sidebar-profile-link').forEach((link) => {
        link.addEventListener('click', () => {
            closeMenu();
            menuOverlay.setAttribute('aria-hidden', 'true');
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeMenu();
            menuOverlay.setAttribute('aria-hidden', 'true');
        }
    });
}

const scanner = document.querySelector('[data-qr-scanner]');

if (scanner) {
    const video = scanner.querySelector('video');
    const message = scanner.querySelector('#scanner-message');
    const startButton = scanner.querySelector('#scan-start');
    const stopButton = scanner.querySelector('#scan-stop');
    const photoButton = scanner.querySelector('#scan-photo');
    const fileInput = scanner.querySelector('#qr-file-input');
    const form = document.querySelector('#scan-form');
    const codeInput = document.querySelector('#asset-code-input');
    const reader = new BrowserMultiFormatReader(new Map([
        [DecodeHintType.TRY_HARDER, true],
        [DecodeHintType.POSSIBLE_FORMATS, [
            BarcodeFormat.AZTEC,
            BarcodeFormat.CODABAR,
            BarcodeFormat.CODE_39,
            BarcodeFormat.CODE_93,
            BarcodeFormat.CODE_128,
            BarcodeFormat.DATA_MATRIX,
            BarcodeFormat.EAN_8,
            BarcodeFormat.EAN_13,
            BarcodeFormat.ITF,
            BarcodeFormat.MAXICODE,
            BarcodeFormat.MICRO_QR_CODE,
            BarcodeFormat.PDF_417,
            BarcodeFormat.QR_CODE,
            BarcodeFormat.RSS_14,
            BarcodeFormat.RSS_EXPANDED,
            BarcodeFormat.UPC_A,
            BarcodeFormat.UPC_E,
        ]],
    ]));
    let controls;

    const setMessage = (state, text) => {
        const icons = {
            idle:    `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" y1="12" x2="17" y2="12"/></svg>`,
            loading: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10" stroke-dasharray="32" stroke-dashoffset="10"/></svg>`,
            active:  `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>`,
            success: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>`,
            error:   `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
        };
        message.dataset.state = state;
        message.innerHTML = `${icons[state] ?? icons.idle}<span>${text}</span>`;
    };

    setMessage('idle', 'Kamera belum dimulai.');

    const stopCamera = () => {
        controls?.stop();
        controls = undefined;
        video.hidden = true;
        const placeholder = document.getElementById('scan-video-placeholder');
        if (placeholder) placeholder.hidden = false;
        startButton.disabled = false;
        stopButton.disabled = true;
    };

    const submitCode = (code) => {
        codeInput.value = code.trim();
        stopCamera();
        form.requestSubmit();
    };

    const decodeCanvas = (image, crop = null, enhanceContrast = false) => {
        const source = crop ?? {
            x: 0,
            y: 0,
            width: image.naturalWidth,
            height: image.naturalHeight,
        };
        const maxDimension = 2800;
        const scale = Math.min(1, maxDimension / Math.max(source.width, source.height));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(source.width * scale));
        canvas.height = Math.max(1, Math.round(source.height * scale));

        const context = canvas.getContext('2d', { willReadFrequently: true });

        if (!context) {
            throw new Error('Browser tidak mendukung pemrosesan gambar.');
        }

        if (enhanceContrast) {
            context.filter = 'grayscale(1) contrast(1.6)';
        }

        context.drawImage(
            image,
            source.x,
            source.y,
            source.width,
            source.height,
            0,
            0,
            canvas.width,
            canvas.height,
        );

        return reader.decodeFromCanvas(canvas);
    };

    const decodePhoto = async (image) => {
        try {
            return await reader.decodeFromImageElement(image);
        } catch {
            const width = image.naturalWidth;
            const height = image.naturalHeight;
            const centerCrop = {
                x: Math.round(width * 0.1),
                y: Math.round(height * 0.15),
                width: Math.round(width * 0.8),
                height: Math.round(height * 0.7),
            };

            for (const [crop, enhanceContrast] of [
                [null, false],
                [null, true],
                [centerCrop, false],
                [centerCrop, true],
            ]) {
                try {
                    return decodeCanvas(image, crop, enhanceContrast);
                } catch {
                    continue;
                }
            }
        }

        throw new Error('Kode tidak ditemukan dalam gambar.');
    };

    const decodeImage = async (event) => {
        const [file] = event.target.files;
        event.target.value = '';

        if (!file) {
            return;
        }

        if (file.type && !file.type.startsWith('image/')) {
            setMessage('error', 'Pilih file gambar untuk memindai QR code atau barcode.');
            return;
        }

        stopCamera();
        setMessage('loading', 'Membaca QR code atau barcode dari gambar...');
        const imageUrl = URL.createObjectURL(file);
        const image = new Image();
        let imageLoaded = false;

        try {
            image.src = imageUrl;
            await image.decode();
            imageLoaded = true;
            const result = await decodePhoto(image);
            submitCode(result.getText());
        } catch (error) {
            setMessage('error', imageLoaded
                ? 'Kode tidak terbaca. Pastikan QR/barcode terlihat jelas dan tidak buram. Coba foto lain atau masukkan kode manual.'
                : `Browser tidak dapat membuka foto${file.type ? ` (${file.type})` : ''}. Pilih JPG/PNG.`);
            console.warn('Pemindaian gambar gagal:', error);
        } finally {
            URL.revokeObjectURL(imageUrl);
        }
    };

    // Detect if running on a secure context (HTTPS or localhost)
    const isSecureContext = window.isSecureContext;
    const isLocalhost = ['localhost', '127.0.0.1', '[::1]'].includes(location.hostname);

    // Show HTTPS badge if not secure
    const httpsBadge = document.getElementById('scan-https-badge');
    if (!isSecureContext && httpsBadge) {
        httpsBadge.hidden = false;
    }

    // If on 127.0.0.1, inject localhost tip into the message area
    if (isLocalhost && location.hostname === '127.0.0.1') {
        const localhostUrl = location.href.replace('127.0.0.1', 'localhost');
        setMessage('idle',
            `Kamera live lebih stabil di <a href="${localhostUrl}" class="scan-localhost-link">localhost:${location.port}${location.pathname}</a>. ` +
            `Klik tombol "Mulai Kamera" untuk mencoba, atau buka via localhost jika gagal.`
        );
    }

    startButton.addEventListener('click', async () => {
        startButton.disabled = true;
        setMessage('loading', 'Meminta izin kamera...');

        // Show video, hide placeholder
        const placeholder = document.getElementById('scan-video-placeholder');
        if (placeholder) placeholder.hidden = true;
        video.hidden = false;

        try {
            controls = await reader.decodeFromVideoDevice(undefined, video, (result) => {
                if (!result) { return; }
                submitCode(result.getText());
            });
            stopButton.disabled = false;
            setMessage('active', 'Kamera aktif — arahkan ke QR code atau barcode laptop.');
        } catch (error) {
            video.hidden = true;
            if (placeholder) placeholder.hidden = false;
            startButton.disabled = false;

            let hint = '';
            if (error.name === 'NotAllowedError') {
                hint = 'Izin kamera ditolak. Klik ikon 🔒 di address bar → "Izin situs" → Izinkan Kamera → muat ulang halaman.';
            } else if (error.name === 'NotFoundError') {
                hint = 'Tidak ada kamera yang terdeteksi. Pastikan kamera terhubung dan tidak dinonaktifkan.';
            } else if (error.name === 'NotReadableError') {
                hint = 'Kamera sedang dipakai aplikasi lain (Zoom, Teams, dll). Tutup dulu, lalu coba lagi.';
            } else if (error.name === 'OverconstrainedError') {
                hint = 'Kamera tidak mendukung konfigurasi yang diminta. Coba browser lain.';
            } else if (!isSecureContext) {
                hint = 'Kamera live memerlukan HTTPS atau localhost. Gunakan fitur foto sebagai alternatif.';
            } else {
                hint = `Kamera gagal: ${error.message || error.name || 'error tidak diketahui'}`;
            }

            setMessage('error', hint);
        }
    });

    photoButton.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', decodeImage);

    stopButton.addEventListener('click', () => {
        stopCamera();
        setMessage('idle', 'Pemindaian dihentikan.');
    });
}
