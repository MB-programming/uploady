document.querySelectorAll('input[name="publish_mode"]').forEach(function (radio) {
    radio.addEventListener('change', function () {
        document.getElementById('scheduleField').style.display = this.value === 'schedule' ? 'block' : 'none';
    });
});
