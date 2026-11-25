document.addEventListener('DOMContentLoaded', function () {
    var sidebar = document.getElementById('sidebar');
    var toggle = document.getElementById('menuToggle');
    var overlay = document.getElementById('overlay');

    function closeMenu() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
    }
    if (toggle) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        });
    }
    if (overlay) overlay.addEventListener('click', closeMenu);

    document.querySelectorAll('.delete-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm('Are you sure you want to delete this record? This action cannot be undone.')) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('.alert-close').forEach(function (button) {
        button.addEventListener('click', function () {
            var alertBox = button.closest('.alert');
            if (alertBox) alertBox.remove();
        });
    });

    // Client-side quick filtering for any table that has data-searchable="true".
    document.querySelectorAll('[data-table-search]').forEach(function (input) {
        var table = document.querySelector(input.getAttribute('data-table-search'));
        if (!table) return;
        input.addEventListener('input', function () {
            var term = input.value.toLowerCase();
            table.querySelectorAll('tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(term) !== -1 ? '' : 'none';
            });
        });
    });

    // Order total preview. Final amount is recalculated securely by PHP from MySQL.
    var productSelect = document.getElementById('orderProduct');
    var quantityInput = document.getElementById('orderQuantity');
    var totalOutput = document.getElementById('orderTotal');

    function updateOrderTotal() {
        if (!productSelect || !quantityInput || !totalOutput) return;
        var option = productSelect.options[productSelect.selectedIndex];
        var price = option && option.getAttribute('data-price') ? parseFloat(option.getAttribute('data-price')) : 0;
        var stock = option && option.getAttribute('data-stock') ? parseInt(option.getAttribute('data-stock'), 10) : 0;
        var quantity = parseInt(quantityInput.value, 10) || 0;
        totalOutput.textContent = '₹' + (price * quantity).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        quantityInput.max = stock > 0 ? stock : '';
    }
    if (productSelect) productSelect.addEventListener('change', updateOrderTotal);
    if (quantityInput) quantityInput.addEventListener('input', updateOrderTotal);

    var orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', function (event) {
            if (productSelect && quantityInput) {
                var selected = productSelect.options[productSelect.selectedIndex];
                var stock = selected ? parseInt(selected.getAttribute('data-stock'), 10) : 0;
                var quantity = parseInt(quantityInput.value, 10) || 0;
                if (!productSelect.value || quantity < 1 || quantity > stock) {
                    event.preventDefault();
                    window.alert('Please select a product and enter a quantity within available stock.');
                }
            }
        });
    }

    // Basic browser-side validation feedback for forms.
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            form.classList.add('was-submitted');
        });
    });
});
