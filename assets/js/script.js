/* sudan-online-payments/assets/js/script.js */
jQuery(document).ready(function ($) {

    // Copy Button Logic
    $(document.body).on('click', '.sudan-online-payments-copy-btn', function (e) {
        e.preventDefault();
        var btn = $(this);
        var textToCopy = btn.data('clipboard-text');

        if (!textToCopy) return;

        // Use modern Clipboard API
        if (navigator.clipboard) {
            navigator.clipboard.writeText(textToCopy).then(function () {
                showTooltip(btn);
            }, function (err) {
                fallbackCopyText(textToCopy, btn);
            });
        } else {
            fallbackCopyText(textToCopy, btn);
        }
    });

    function fallbackCopyText(text, btn) {
        var textArea = document.createElement("textarea");
        textArea.value = text;

        // Avoid scrolling to bottom
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            var successful = document.execCommand('copy');
            if (successful) showTooltip(btn);
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
        }

        document.body.removeChild(textArea);
    }

    function showTooltip(btn) {
        btn.addClass('copied');
        setTimeout(function () {
            btn.removeClass('copied');
        }, 2000);
    }


    /* --- File Upload Logic (Existing) --- */

    // Delegate to body/form because checkout DOM updates ( AJAX updates )
    $(document.body).on('change', '.sudan-online-payments-file-input', function (e) {
        var fileInput = $(this);
        var file = this.files[0];
        var container = fileInput.closest('.sudan-online-payments-upload-container');
        var hiddenInput = container.find('input[name="sudan_online_payments_receipt_id"]');
        var status = container.find('.sudan-online-payments-upload-status');
        var preview = container.find('.sudan-online-payments-upload-preview');
        var previewImg = container.find('#sudan-online-payments-preview-img');
        var nonceInput = container.find('input[name="sudan_online_payments_upload_nonce_field"]');
        var nonce = nonceInput.length ? nonceInput.val() : '';
        
        // Fallback: try to find nonce in the form if not found in container
        if (!nonce) {
            nonceInput = $('input[name="sudan_online_payments_upload_nonce_field"]');
            nonce = nonceInput.length ? nonceInput.val() : '';
        }

        if (!file) return;

        // Check if nonce exists
        if (!nonce) {
            alert('Security token missing. Please refresh the page and try again.');
            fileInput.val('');
            return;
        }

        // Frontend Verification
        // 1. Size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('File is too large. Maximum size is 5MB.');
            fileInput.val('');
            return;
        }

        // 2. Type (Images Only)
        var allowed = ['image/jpeg', 'image/png', 'image/jpg'];
        if ($.inArray(file.type, allowed) === -1) {
            alert('Invalid file type. Please upload a specific image format (JPG, PNG).');
            fileInput.val('');
            return;
        }

        // Prepare Upload
        var formData = new FormData();
        formData.append('action', 'sudan_online_payments_upload_receipt');
        formData.append('file', file);
        formData.append('security', nonce);

        // UI Updates
        fileInput.hide();
        status.show();
        $('.checkout-button').prop('disabled', true); // Block checkout

        // AJAX Request
        $.ajax({
            url: sudan_online_payments_params.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            timeout: 30000, // 30 second timeout
            success: function (response) {
                status.hide();

                if (response && response.success) {
                    // Success
                    hiddenInput.val(response.data.attachment_id);
                    previewImg.attr('src', response.data.image_url);
                    preview.show();
                    $('.checkout-button').prop('disabled', false); // Enable checkout

                    // Trigger update checkout to ensure validation state clears (optional, but good practice)
                    $('body').trigger('update_checkout');
                } else {
                    // Error
                    var errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Upload failed. Please try again.';
                    alert(errorMsg);
                    fileInput.show();
                    fileInput.val('');
                    $('.checkout-button').prop('disabled', false);
                }
            },
            error: function (xhr, status, error) {
                status.hide();
                var errorMsg = 'Connection error. Please try again.';
                
                // Try to get error message from response
                if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response && response.data && response.data.message) {
                            errorMsg = response.data.message;
                        }
                    } catch (e) {
                        // If response is not JSON, use default message
                    }
                }
                
                alert(errorMsg);
                fileInput.show();
                fileInput.val('');
                $('.checkout-button').prop('disabled', false);
            }
        });
    });

    // Remove / Change Upload
    $(document.body).on('click', '.sudan-online-payments-remove-upload', function () {
        var container = $(this).closest('.sudan-online-payments-upload-container');
        var fileInput = container.find('.sudan-online-payments-file-input');
        var hiddenInput = container.find('input[name="sudan_online_payments_receipt_id"]');
        var preview = container.find('.sudan-online-payments-upload-preview');

        // Reset
        hiddenInput.val('');
        preview.hide();
        fileInput.val('').show();
    });

});
