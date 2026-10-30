(function () {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initVendorForm);
    } else {
        initVendorForm();
    }

    function initVendorForm() {
        const form = document.querySelector('.ajax-vendor-form');
        if (!form) return;

        const submitBtn = document.getElementById('submitBtn');
        const spinner = submitBtn?.querySelector('.spinner-border');
        const btnText = document.getElementById('btnText');
        const imageInput = document.getElementById('image');
        const imagePreviewWrap = document.getElementById('vendor-image-preview-wrap');
        const imagePreview = document.getElementById('vendor-image-preview');

        function clearErrors() {
            ['name', 'email', 'phone', 'address', 'image'].forEach(function (field) {
                document.getElementById(field)?.classList.remove('is-invalid');
                const error = document.getElementById(`${field}-error`);
                if (error) error.textContent = '';
            });
        }

        function showErrors(errors) {
            Object.entries(errors || {}).forEach(function ([field, messages]) {
                document.getElementById(field)?.classList.add('is-invalid');
                const error = document.getElementById(`${field}-error`);
                if (error) error.textContent = Array.isArray(messages) ? messages[0] : messages;
            });
        }

        function notify(message, type, redirectUrl) {
            if (typeof window.showAlert === 'function') {
                window.showAlert(type || 'info', message, '', redirectUrl || null);
                return;
            }

            alert(message);
            if (redirectUrl) window.location.href = redirectUrl;
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            clearErrors();

            const formData = new FormData(form);

            submitBtn.disabled = true;
            spinner?.classList.remove('d-none');
            if (btnText) btnText.textContent = 'Please wait...';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                const payload = await response.json().catch(() => null);

                if (!response.ok) {
                    if (payload?.errors) {
                        showErrors(payload.errors);
                        return;
                    }

                    notify(payload?.message || 'Unable to save vendor.', 'error');
                    return;
                }

                notify(payload?.message || 'Vendor saved successfully.', 'success', payload?.redirect || '/all-vendor');
            } catch (_) {
                notify('Something went wrong. Please try again.', 'error');
            } finally {
                submitBtn.disabled = false;
                spinner?.classList.add('d-none');
                if (btnText) btnText.textContent = form.querySelector('input[name="_method"]') ? 'Update' : 'Submit';
            }
        });

        ['name', 'email', 'phone', 'address', 'image'].forEach(function (field) {
            document.getElementById(field)?.addEventListener('input', function () {
                this.classList.remove('is-invalid');
                const error = document.getElementById(`${field}-error`);
                if (error) error.textContent = '';
            });
        });

        imageInput?.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file || !imagePreviewWrap || !imagePreview) return;

            const objectUrl = URL.createObjectURL(file);
            imagePreview.src = objectUrl;
            imagePreviewWrap.classList.remove('d-none');
            imagePreview.onload = function () {
                URL.revokeObjectURL(objectUrl);
            };
        });
    }
})();
