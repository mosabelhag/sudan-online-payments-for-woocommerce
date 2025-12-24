/* sudan-online-payments/assets/js/admin.js */
jQuery(document).ready(function ($) {
    var wrapper = $('#sudan-online-payments-accounts-list');
    var addBtn = $('.sudan-online-payments-add-account');
    var removeBtn = $('.sudan-online-payments-remove-account');
    var hiddenInput = $('input[name="woocommerce_sudan_online_payments_accounts"]');

    if (hiddenInput.length === 0) {
        hiddenInput = $('input[type="hidden"][name*="accounts"]');
    }

    // Bank Options
    var bankOptions = {
        'bankak': 'Bankak | بنكك',
        'o-cash': 'O-Cash | اوو-كاش',
        'fawry': 'Fawry SD | فوري',
        'syberpay': 'SyberPay | سايبر باي',
        'mycashi': 'MyCashi | ماي كاشي',
        'bravo': 'Bravo | برافو',
        'other': 'Other Bank | بنك آخر'
    };

    // Helper to get icon URL
    function getIconUrl(bankKey) {
        var iconMap = {
            'bankak': 'bankak.png',
            'o-cash': 'o-cash.png',
            'fawry': 'fawery.png',
            'syberpay': 'syberpay.png',
            'mycashi': 'mycashi.png',
            'bravo': 'bravo.png',
            'other': 'other.png'
        };
        var file = iconMap[bankKey] || 'other.png';
        // sudan_online_payments_admin_params is defined via wp_localize_script
        return sudan_online_payments_admin_params.plugin_url + 'assets/images/' + file;
    }

    function getBankSelectHtml(selectedKey) {
        var html = '<div class="sudan-online-payments-admin-bank-select-wrapper">';

        // Icon Preview
        html += '<img src="' + getIconUrl(selectedKey || 'bankak') + '" class="sudan-online-payments-admin-bank-icon" />';

        html += '<select class="sudan-online-payments-input sudan-online-payments-bank-select" data-key="bank_name">';
        $.each(bankOptions, function (key, label) {
            var selected = (key === selectedKey) ? 'selected' : '';
            html += '<option value="' + key + '" ' + selected + '>' + label + '</option>';
        });
        html += '</select>';
        html += '</div>';
        return html;
    }

    function renderRows() {
        var data = [];
        try {
            data = JSON.parse(hiddenInput.val());
        } catch (e) {
            data = [];
        }

        wrapper.empty();

        if (!Array.isArray(data)) data = [];

        data.forEach(function (account, index) {
            var currentBank = account.bank_name || 'bankak';

            var row = `
                <tr data-index="${index}" class="sudan-online-payments-account-row">
                    <td class="sort" width="1%">
                        <div class="wc_input_table_sort_handle"></div>
                        <input type="checkbox" class="sudan-online-payments-select-row" />
                    </td>
                    <td style="width: 25%;">${getBankSelectHtml(currentBank)}</td>
                    <td><input type="text" class="input-text sudan-online-payments-input" data-key="account_name" value="${account.account_name || ''}" placeholder="Name"></td>
                    <td><input type="text" class="input-text sudan-online-payments-input" data-key="account_number" value="${account.account_number || ''}" placeholder="Account No"></td>
                    <td><input type="text" class="input-text sudan-online-payments-input" data-key="branch" value="${account.branch || ''}" placeholder="Branch (Opt)"></td>
                    <td><input type="text" class="input-text sudan-online-payments-input" data-key="phone" value="${account.phone || ''}" placeholder="Phone (Opt)"></td>
                </tr>
            `;
            wrapper.append(row);
        });
    }

    function updateData() {
        var newData = [];
        wrapper.find('tr').each(function () {
            var row = $(this);
            var item = {
                bank_name: row.find('select[data-key="bank_name"]').val(),
                account_name: row.find('input[data-key="account_name"]').val(),
                account_number: row.find('input[data-key="account_number"]').val(),
                branch: row.find('input[data-key="branch"]').val(),
                phone: row.find('input[data-key="phone"]').val(),
            };
            newData.push(item);
        });
        hiddenInput.val(JSON.stringify(newData));
    }

    // Initial Render
    renderRows();

    // Add Account
    addBtn.on('click', function (e) {
        e.preventDefault();
        var data = [];
        try { data = JSON.parse(hiddenInput.val()); } catch (e) { data = []; }
        if (!Array.isArray(data)) data = [];

        data.push({ bank_name: 'bankak', account_name: '', account_number: '', branch: '', phone: '' });
        hiddenInput.val(JSON.stringify(data));
        renderRows();
    });

    // Remove Account
    removeBtn.on('click', function (e) {
        e.preventDefault();
        var rows = wrapper.find('tr');
        var newData = [];
        rows.each(function (index) {
            if (!$(this).find('.sudan-online-payments-select-row').is(':checked')) {
                // Keep
                newData.push({
                    bank_name: $(this).find('select[data-key="bank_name"]').val(),
                    account_name: $(this).find('input[data-key="account_name"]').val(),
                    account_number: $(this).find('input[data-key="account_number"]').val(),
                    branch: $(this).find('input[data-key="branch"]').val(),
                    phone: $(this).find('input[data-key="phone"]').val(),
                });
            }
        });
        hiddenInput.val(JSON.stringify(newData));
        renderRows();
    });

    // Update Icon on Select Change
    $(document).on('change', '.sudan-online-payments-bank-select', function () {
        var select = $(this);
        var val = select.val();
        var wrapper = select.closest('.sudan-online-payments-admin-bank-select-wrapper');
        var img = wrapper.find('.sudan-online-payments-admin-bank-icon');

        img.attr('src', getIconUrl(val));
        updateData(); // Also trigger data update
    });

    // On Change of any input
    $(document).on('change keyup', '.sudan-online-payments-input', function () {
        updateData();
    });

    // Make sortable if available
    if ($.fn.sortable) {
        $('#sudan-online-payments-accounts-list').sortable({
            cursor: 'move',
            axis: 'y',
            handle: '.wc_input_table_sort_handle',
            scrollSensitivity: 40,
            forcePlaceholderSize: true,
            helper: 'clone',
            opacity: 0.65,
            placeholder: 'wc-metabox-sortable-placeholder',
            start: function (event, ui) {
                ui.item.css('background-color', '#f6f6f6');
            },
            stop: function (event, ui) {
                ui.item.removeAttr('style');
                updateData();
            }
        });
    }
});
