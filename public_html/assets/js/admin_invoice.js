var planSelect = document.getElementById('planSelect');
var amountInput = document.getElementById('amountInput');

if (planSelect && amountInput) {
    planSelect.addEventListener('change', function () {
        var option = planSelect.options[planSelect.selectedIndex];
        var price = option.getAttribute('data-price');
        if (price) {
            amountInput.value = price;
        }
    });
}
