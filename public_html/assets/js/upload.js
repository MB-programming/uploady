// Local preview of the chosen video before anything is uploaded anywhere — plays straight
// from the file on the user's device (object URL), no server round-trip involved.
(function () {
    var input = document.getElementById('videoInput');
    var wrap = document.getElementById('videoPreviewWrap');
    var video = document.getElementById('videoPreview');
    var meta = document.getElementById('videoPreviewMeta');
    if (!input || !wrap || !video) return;

    var currentUrl = null;
    input.addEventListener('change', function () {
        if (currentUrl) { URL.revokeObjectURL(currentUrl); currentUrl = null; }
        var file = input.files && input.files[0];
        if (!file) { wrap.style.display = 'none'; return; }

        currentUrl = URL.createObjectURL(file);
        video.src = currentUrl;
        wrap.style.display = 'block';

        video.onloadedmetadata = function () {
            var mins = Math.floor(video.duration / 60);
            var secs = Math.round(video.duration % 60);
            var sizeMb = (file.size / (1024 * 1024)).toFixed(1);
            meta.textContent = file.name + ' — ' + sizeMb + ' MB — ' +
                mins + ':' + (secs < 10 ? '0' : '') + secs + ' — ' +
                video.videoWidth + '×' + video.videoHeight;
        };
    });
})();

// Same for the thumbnail image.
(function () {
    var input = document.getElementById('thumbnailInput');
    var wrap = document.getElementById('thumbPreviewWrap');
    var img = document.getElementById('thumbPreview');
    if (!input || !wrap || !img) return;

    var currentUrl = null;
    input.addEventListener('change', function () {
        if (currentUrl) { URL.revokeObjectURL(currentUrl); currentUrl = null; }
        var file = input.files && input.files[0];
        if (!file) { wrap.style.display = 'none'; return; }
        currentUrl = URL.createObjectURL(file);
        img.src = currentUrl;
        wrap.style.display = 'block';
    });
})();

document.querySelectorAll('.platform-toggle').forEach(function (checkbox) {
    var fieldset = document.querySelector('.platform-fieldset[data-key="' + checkbox.dataset.key + '"]');
    if (!fieldset) return;

    checkbox.addEventListener('change', function () {
        fieldset.style.display = checkbox.checked ? 'block' : 'none';
    });
});

document.querySelectorAll('.platform-fieldset').forEach(function (fieldset) {
    var scheduleField = fieldset.querySelector('.schedule-field');
    if (!scheduleField) return;

    fieldset.querySelectorAll('.publish-mode-toggle').forEach(function (radio) {
        radio.addEventListener('change', function () {
            scheduleField.style.display = radio.value === 'schedule' && radio.checked ? 'block' : 'none';
        });
    });
});
