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
