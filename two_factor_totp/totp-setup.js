(function () {
    'use strict';

    var target = document.getElementById('totp-qrcode');
    if (!target || typeof QRCode === 'undefined') {
        return;
    }

    var uri = target.getAttribute('data-uri');
    if (!uri || uri.indexOf('otpauth://totp/') !== 0) {
        return;
    }

    new QRCode(target, {
        text: uri,
        width: 224,
        height: 224,
        colorDark: '#111827',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
}());
