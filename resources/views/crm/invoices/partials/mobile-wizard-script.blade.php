<script>
    $(document).ready(function () {
        const $form = $(@json($formId));
        if (!$form.length) {
            return;
        }

        let currentStep = 1;
        const totalSteps = 3;

        function updateWizardUI() {
            if (window.innerWidth >= 768) {
                $form.find('.create-step-1, .create-step-2, .create-step-3').addClass('active-step');
                $form.find('.create-step-indicator').addClass('d-md-none');
                $form.find('.create-prev-btn, .create-next-btn').addClass('d-none');
                $form.find('.create-submit-btn').removeClass('d-none');
                return;
            }

            $form.find('.create-step-indicator').removeClass('d-md-none');
            $form.find('.create-step-1, .create-step-2, .create-step-3').removeClass('active-step');
            $form.find('.create-step-' + currentStep).addClass('active-step');

            $form.find('.create-step-dot').removeClass('active');
            for (let i = 1; i <= currentStep; i++) {
                $form.find('#cdot-' + i).addClass('active');
            }

            if (currentStep === 1) {
                $form.find('.create-prev-btn').attr('style', 'display: none !important;');
                $form.find('.create-next-btn').attr('style', 'display: inline-block !important;');
                $form.find('.create-submit-btn').attr('style', 'display: none !important;');
            } else if (currentStep === 2) {
                $form.find('.create-prev-btn').attr('style', 'display: inline-block !important;');
                $form.find('.create-next-btn').attr('style', 'display: inline-block !important;');
                $form.find('.create-submit-btn').attr('style', 'display: none !important;');
            } else {
                $form.find('.create-prev-btn').attr('style', 'display: inline-block !important;');
                $form.find('.create-next-btn').attr('style', 'display: none !important;');
                $form.find('.create-submit-btn').attr('style', 'display: inline-block !important;');
            }
        }

        function validateStep2Bom() {
            if (typeof window.validateEstimateBomRows === 'function') {
                return window.validateEstimateBomRows().isValid;
            }
            return true;
        }

        $form.find('.create-next-btn').on('click', function () {
            let isValid = true;

            if (currentStep === 2) {
                isValid = validateStep2Bom();
            } else {
                $form.find('.create-step-' + currentStep + ' [required]').each(function () {
                    if (!$(this).val() && $(this).is(':visible')) {
                        isValid = false;
                        $(this).addClass('is-invalid');
                    } else {
                        $(this).removeClass('is-invalid');
                    }
                });
            }

            if (isValid && currentStep < totalSteps) {
                currentStep++;
                updateWizardUI();
            }
        });

        $form.find('.create-prev-btn').on('click', function () {
            if (currentStep > 1) {
                currentStep--;
                updateWizardUI();
            }
        });

        $form.on('change input', '[required], .product-select, .product-make, input[name="product_qty[]"], input[name="product_price[]"], .product-price', function () {
            const val = $(this).val();
            if (val || $(this).attr('name') === 'product_qty[]' || $(this).hasClass('product-price')) {
                if (typeof window.markEstimateBomFieldInvalid === 'function') {
                    window.markEstimateBomFieldInvalid(this, false);
                } else {
                    $(this).removeClass('is-invalid');
                }
                if ($(this).hasClass('product-make')) {
                    $(this).closest('.bom-row').find('.bom-make-error').removeClass('d-block');
                }
                if ($(this).hasClass('product-select')) {
                    $('#products-error').removeClass('d-block').hide();
                }
            }
        });

        updateWizardUI();
        $(window).on('resize', updateWizardUI);
    });
</script>
