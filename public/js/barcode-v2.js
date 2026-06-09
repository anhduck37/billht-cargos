(function (window, document) {
    'use strict';

    var stream = null;
    var detector = null;
    var scanTimer = null;
    var selectedDeviceId = null;
    var scanning = false;

    function element(id) {
        return document.getElementById(id);
    }

    function setStatus(message, type) {
        var status = element('barcode-scanner-v2-status');

        if (!status) {
            return;
        }

        status.className = 'alert py-2 ' + (
            type === 'error' ? 'alert-danger' :
            type === 'success' ? 'alert-success' :
            'alert-info'
        );
        status.textContent = message;
    }

    function stopScanner() {
        scanning = false;

        if (scanTimer) {
            window.clearTimeout(scanTimer);
            scanTimer = null;
        }

        if (stream) {
            stream.getTracks().forEach(function (track) {
                track.stop();
            });
            stream = null;
        }

        var video = element('barcode-scanner-v2-video');
        if (video) {
            video.pause();
            video.srcObject = null;
        }
    }

    function hideModal() {
        if (window.jQuery && window.jQuery.fn.modal) {
            window.jQuery('#modal-camera-scanner-v2').modal('hide');
        }
    }

    function applyCameraEnhancements(track) {
        if (!track || typeof track.getCapabilities !== 'function' ||
            typeof track.applyConstraints !== 'function') {
            return;
        }

        var capabilities = track.getCapabilities();
        var settings = {};

        if (Array.isArray(capabilities.focusMode) &&
            capabilities.focusMode.indexOf('continuous') !== -1) {
            settings.focusMode = 'continuous';
        }

        if (capabilities.zoom && typeof capabilities.zoom.min === 'number') {
            settings.zoom = Math.min(
                capabilities.zoom.max,
                Math.max(capabilities.zoom.min, 1.5)
            );
        }

        if (Object.keys(settings).length) {
            track.applyConstraints({ advanced: [settings] }).catch(function () {
                // Camera vẫn hoạt động nếu thiết bị từ chối autofocus hoặc zoom.
            });
        }
    }

    function cameraScore(device) {
        var label = (device.label || '').toLowerCase();
        var score = 0;

        if (/back|rear|environment|mặt sau|camera 0/.test(label)) {
            score += 20;
        }
        if (/front|user|mặt trước/.test(label)) {
            score -= 30;
        }
        if (/ultra|wide|tele|macro|depth/.test(label)) {
            score -= 10;
        }

        return score;
    }

    function populateCameras() {
        return navigator.mediaDevices.enumerateDevices().then(function (devices) {
            var cameras = devices.filter(function (device) {
                return device.kind === 'videoinput';
            }).sort(function (left, right) {
                return cameraScore(right) - cameraScore(left);
            });

            var select = element('barcode-scanner-v2-camera');
            var group = document.querySelector('.barcode-scanner-v2-camera-group');

            if (!select || !group) {
                return;
            }

            select.innerHTML = '';
            cameras.forEach(function (camera, index) {
                var option = document.createElement('option');
                option.value = camera.deviceId;
                option.textContent = camera.label || ('Camera ' + (index + 1));
                select.appendChild(option);
            });

            if (!selectedDeviceId && cameras.length) {
                selectedDeviceId = cameras[0].deviceId;
            }

            if (selectedDeviceId) {
                select.value = selectedDeviceId;
            }

            group.style.display = cameras.length > 1 ? '' : 'none';
        });
    }

    function scanFrame() {
        if (!scanning || !detector) {
            return;
        }

        var video = element('barcode-scanner-v2-video');

        if (!video || video.readyState < 2) {
            scanTimer = window.setTimeout(scanFrame, 150);
            return;
        }

        detector.detect(video).then(function (barcodes) {
            if (!scanning) {
                return;
            }

            if (barcodes && barcodes.length && barcodes[0].rawValue) {
                var code = String(barcodes[0].rawValue).toUpperCase().replace(/\s/g, '');
                var invoiceCode = element('invoice_code');

                if (invoiceCode) {
                    invoiceCode.value = code;
                    invoiceCode.dispatchEvent(new Event('change', { bubbles: true }));
                }

                setStatus('Đã đọc mã: ' + code, 'success');
                stopScanner();
                window.setTimeout(hideModal, 250);
                return;
            }

            scanTimer = window.setTimeout(scanFrame, 120);
        }).catch(function () {
            scanTimer = window.setTimeout(scanFrame, 250);
        });
    }

    function createDetector() {
        if (!('BarcodeDetector' in window)) {
            return Promise.reject(new Error('BARCODE_DETECTOR_UNSUPPORTED'));
        }

        if (typeof window.BarcodeDetector.getSupportedFormats !== 'function') {
            detector = new window.BarcodeDetector();
            return Promise.resolve();
        }

        return window.BarcodeDetector.getSupportedFormats().then(function (formats) {
            if (formats.indexOf('code_128') === -1) {
                throw new Error('CODE_128_UNSUPPORTED');
            }

            detector = new window.BarcodeDetector({ formats: ['code_128'] });
        });
    }

    function startScanner() {
        stopScanner();
        setStatus('Đang mở camera...', 'info');

        if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
            setStatus('Trình duyệt không hỗ trợ mở camera. Vui lòng dùng Chrome mới nhất.', 'error');
            return;
        }

        createDetector().then(function () {
            var videoConstraints = {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            };

            if (selectedDeviceId) {
                delete videoConstraints.facingMode;
                videoConstraints.deviceId = { exact: selectedDeviceId };
            }

            return navigator.mediaDevices.getUserMedia({
                audio: false,
                video: videoConstraints
            });
        }).then(function (cameraStream) {
            stream = cameraStream;

            var video = element('barcode-scanner-v2-video');
            video.srcObject = stream;

            return video.play().then(function () {
                scanning = true;
                setStatus('Camera đã sẵn sàng. Đang tìm mã vạch...', 'info');
                applyCameraEnhancements(stream.getVideoTracks()[0]);
                populateCameras();
                scanFrame();
            });
        }).catch(function (error) {
            stopScanner();

            if (error && error.message === 'BARCODE_DETECTOR_UNSUPPORTED') {
                setStatus('Thiết bị này chưa hỗ trợ bộ quét mã vạch 2. Vui lòng dùng nút quét cũ.', 'error');
            } else if (error && error.message === 'CODE_128_UNSUPPORTED') {
                setStatus('Trình duyệt chưa hỗ trợ đọc mã Code 128. Vui lòng dùng nút quét cũ.', 'error');
            } else if (error && error.name === 'NotAllowedError') {
                setStatus('Chưa được cấp quyền camera. Vui lòng cho phép truy cập camera.', 'error');
            } else {
                setStatus('Không mở được camera. Hãy thử chọn camera khác hoặc tải lại trang.', 'error');
                console.error(error);
            }
        });
    }

    function initialize() {
        var button = element('barcode-scanner-v2');
        var modal = element('modal-camera-scanner-v2');
        var select = element('barcode-scanner-v2-camera');

        if (!button || !modal) {
            return;
        }

        button.addEventListener('click', function () {
            selectedDeviceId = null;

            if (window.jQuery && window.jQuery.fn.modal) {
                window.jQuery(modal).modal('show');
            }

            startScanner();
        });

        if (select) {
            select.addEventListener('change', function () {
                selectedDeviceId = select.value || null;
                startScanner();
            });
        }

        if (window.jQuery) {
            window.jQuery(modal).on('hidden.bs.modal', function () {
                stopScanner();
                setStatus('Đang mở camera...', 'info');
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
})(window, document);
