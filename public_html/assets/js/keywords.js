// Limit-reached sweet alert: close button + clicking the backdrop.
var sweetAlert = document.getElementById('sweetAlert');
if (sweetAlert) {
    sweetAlert.addEventListener('click', function (e) {
        if (e.target === sweetAlert || e.target.hasAttribute('data-close-sweet')) {
            sweetAlert.remove();
        }
    });
}

// Copy-to-clipboard for the keyword/hashtag lists (CSP forbids inline handlers).
document.querySelectorAll('[data-copy]').forEach(function (button) {
    button.addEventListener('click', function () {
        var original = button.textContent;
        var done = function () {
            button.textContent = button.getAttribute('data-copied-label') || original;
            setTimeout(function () { button.textContent = original; }, 1500);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(button.getAttribute('data-copy')).then(done);
        } else {
            var area = document.createElement('textarea');
            area.value = button.getAttribute('data-copy');
            document.body.appendChild(area);
            area.select();
            document.execCommand('copy');
            document.body.removeChild(area);
            done();
        }
    });
});
