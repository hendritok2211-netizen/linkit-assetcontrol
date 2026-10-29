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
    appShell.querySelectorAll('.side-nav a, .profile-link').forEach((link) => {
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

    const stopCamera = () => {
        controls?.stop();
        controls = undefined;
        video.hidden = true;
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
            message.textContent = 'Pilih file gambar untuk memindai QR code atau barcode.';
            return;
        }

        stopCamera();
        message.textContent = 'Membaca QR code atau barcode dari gambar...';
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
            message.textContent = imageLoaded
                ? 'Kode tidak terbaca. Letakkan barcode memenuhi bagian tengah foto, pastikan fokus dan tidak silau. Coba foto lain atau masukkan kode/nomor seri manual.'
                : `Browser tidak dapat membuka foto${file.type ? ` (${file.type})` : ''}. Pilih JPG/PNG atau ubah format foto kamera ke kompatibel.`;
            console.warn('Pemindaian gambar gagal:', error);
        } finally {
            URL.revokeObjectURL(imageUrl);
        }
    };

    startButton.addEventListener('click', async () => {
        startButton.disabled = true;
        message.textContent = 'Meminta izin kamera...';
        video.hidden = false;

        try {
            controls = await reader.decodeFromVideoDevice(undefined, video, (result) => {
                if (!result) {
                    return;
                }

                submitCode(result.getText());
            });
            stopButton.disabled = false;
            message.textContent = 'Arahkan kamera ke QR code atau barcode pada laptop.';
        } catch (error) {
            video.hidden = true;
            startButton.disabled = false;
            message.textContent = `Kamera tidak dapat digunakan: ${error.message || 'periksa izin kamera atau gunakan input manual.'}`;
        }
    });

    photoButton.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', decodeImage);

    stopButton.addEventListener('click', () => {
        stopCamera();
        message.textContent = 'Pemindaian dihentikan.';
    });
}
