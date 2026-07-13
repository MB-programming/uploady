document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        if (!confirm(form.dataset.confirm)) {
            e.preventDefault();
        }
    });
});

document.querySelectorAll('.js-print').forEach(function (btn) {
    btn.addEventListener('click', function () {
        window.print();
    });
});
