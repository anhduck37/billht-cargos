(function ($, window, document) {
    'use strict';

    var selectedDeviceId = null;
    var detectedHandler = null;
    var scannerRunning = false;
    var isRestarting = false;

    function setStatus(message, type) {
        var statusClass = 'alert-info';

        if (type === 'error') {
            statusClass = 'alert-danger';
        } else if (type === 'success') {
            statusClass = 'alert-success';
        }

        $('#barcode-scanner-v2-status')
            .removeClass('alert-info alert-danger alert-success')
            .addClass(statusClass)
            .text(message);
    }

    function stopScannerV2() {
        if (detectedHandler && window.Quagga && typeof window.Quagga.offDetected === 'function') {
            window.Quagga.offDetected(detectedHandler);
        }

        detectedHandler = null;

        if (window.Quagga && scannerRunning) {
            try {
                window.Quagga.stop();
            } catch (error) {
                console.warn('Không thể dừng camera quét mã vạch 2.', error);
            }
        }

        scannerRunning = false;
        $('#camera-scanner-v2').empty();
    }

    function getVideoTrack() {
        var video = document.querySelector('#camera-scanner-v2 video');

        if (!video || !video.srcObject || typeof video.srcObject.getVideoTracks !== 'function') {
            return null;
        }

        return video.srcObject.getVideoTracks()[0] || null;
    }

    function improveCameraFocus() {
        var track = getVideoTrack();

        if (!track || typeof track.getCapabilities !== 'function' || typeof track.applyConstraints !== 'function') {
            return;
        }

        var capabilities = track.getCapabilities();
        var advanced = {};

        if (Array.isArray(capabilities.focusMode) && capabilities.focusMode.indexOf('continuous') !== -1) {
            advanced.focusMode = 'continuous';
        }

        if (capabilities.zoom && typeof capabilities.zoom.min === 'number') {
            var preferredZoom = Math.max(capabilities.zoom.min, 1.5);
            advanced.zoom = Math.min(capabilities.zoom.max, preferredZoom);
        }

        if (!Object.keys(advanced).length) {
            return;
        }

        track.applyConstraints({ advanced: [advanced] }).catch(function (error) {
            console.warn('Thiết bị không áp dụng được autofocus/zoom nâng cao.', error);
        });
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

    function populateCameraOptions(devices) {
        var cameras = devices.filter(function (device) {
            return device.kind === 'videoinput';
        });

        cameras.sort(function (left, right) {
            return cameraScore(right) - cameraScore(left);
        });

        var $select = $('#barcode-scanner-v2-camera');
        $select.empty();

        cameras.forEach(function (camera, index) {
            $('<option>')
                .val(camera.deviceId)
                .text(camera.label || ('Camera ' + (index + 1)))
                .appendTo($select);
        });

        if (!selectedDeviceId && cameras.length) {
            selectedDeviceId = cameras[0].deviceId;
        }

        if (selectedDeviceId) {
            $select.val(selectedDeviceId);
        }

        $('.barcode-scanner-v2-camera-group').toggle(cameras.length > 1);
    }

    function refreshCameraList() {
        if (!window.Quagga || !window.Quagga.CameraAccess ||
            typeof window.Quagga.CameraAccess.enumerateVideoDevices !== 'function') {
            return Promise.resolve();
        }

        return window.Quagga.CameraAccess.enumerateVideoDevices()
            .then(function (devices) {
                populateCameraOptions(devices || []);
            })
            .catch(function (error) {
                console.warn('Không thể lấy danh sách camera.', error);
            });
    }

    function buildConstraints() {
        var constraints = {
            facingMode: { ideal: 'environment' },
            width: { ideal: 1920, min: 640 },
            height: { ideal: 1080, min: 480 }
        };

        if (selectedDeviceId) {
            constraints.deviceId = { exact: selectedDeviceId };
            delete constraints.facingMode;
        }

        return constraints;
    }

    function startScannerV2() {
        if (!window.Quagga) {
            setStatus('Không tải được thư viện quét mã vạch. Vui lòng tải lại trang.', 'error');
            return;
        }

        stopScannerV2();
        setStatus('Đang mở camera...', 'info');

        window.Quagga.init({
            locate: true,
            inputStream: {
                name: 'Live',
                type: 'LiveStream',
                target: document.querySelector('#camera-scanner-v2'),
                constraints: buildConstraints()
            },
            locator: {
                patchSize: 'medium',
                halfSample: true
            },
            frequency: 10,
            decoder: {
                readers: ['code_128_reader'],
                multiple: false
            }
        }, function (error) {
            if (error) {
                console.error(error);
                setStatus('Không mở được camera. Hãy cấp quyền camera hoặc chọn camera khác.', 'error');
                return;
            }

            window.Quagga.start();
            scannerRunning = true;
            setStatus('Camera đã sẵn sàng. Đang tìm mã vạch...', 'info');

            window.setTimeout(improveCameraFocus, 400);
            refreshCameraList();
        });

        detectedHandler = function (result) {
            var code = result && result.codeResult && result.codeResult.code;

            if (!code) {
                return;
            }

            $('#invoice_code').val(String(code).toUpperCase().replace(/\s/g, '')).trigger('change');
            setStatus('Đã đọc mã: ' + code, 'success');
            stopScannerV2();

            window.setTimeout(function () {
                $('#modal-camera-scanner-v2').modal('hide');
            }, 250);
        };

        window.Quagga.onDetected(detectedHandler);
    }

    $(document).on('click', '#barcode-scanner-v2', function () {
        selectedDeviceId = null;
        $('#modal-camera-scanner-v2').modal('show');
        startScannerV2();
    });

    $(document).on('change', '#barcode-scanner-v2-camera', function () {
        if (isRestarting) {
            return;
        }

        selectedDeviceId = $(this).val() || null;
        isRestarting = true;
        startScannerV2();
        window.setTimeout(function () {
            isRestarting = false;
        }, 500);
    });

    $('#modal-camera-scanner-v2').on('hidden.bs.modal', function () {
        stopScannerV2();
        setStatus('Đang mở camera...', 'info');
    });

    $('<style>')
        .text(
            '#camera-scanner-v2{position:relative;overflow:hidden;background:#111;min-height:260px;border-radius:4px}' +
            '#camera-scanner-v2 video,#camera-scanner-v2 canvas{width:100%;height:auto;display:block}' +
            '#camera-scanner-v2 canvas.drawingBuffer{position:absolute;top:0;left:0}' +
            '@media (max-width:575.98px){#modal-camera-scanner-v2 .modal-dialog{margin:.5rem}' +
            '#camera-scanner-v2{min-height:220px}}'
        )
        .appendTo(document.head);
})(jQuery, window, document);
