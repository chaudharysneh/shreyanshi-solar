<script>
    (function () {
        function initQuickEstimateWizard() {
            if (!document.getElementById('quickEstimateForm')) {
                return;
            }

            let currentStep = 1;
            const totalSteps = 3;

            function updateWizardUI() {
                if (window.innerWidth >= 768) {
                    $('#quickEstimateModal .quick-step-1, #quickEstimateModal .quick-step-2, #quickEstimateModal .quick-step-3').removeClass('active-step');
                    $('.quick-step-indicator').addClass('d-none');
                    $('.quick-prev-btn, .quick-next-btn').addClass('d-none');
                    $('.quick-submit-btn').removeClass('d-none');
                    return;
                }

                $('.quick-step-indicator').removeClass('d-none');
                $('#quickEstimateModal .quick-step-1, #quickEstimateModal .quick-step-2, #quickEstimateModal .quick-step-3').removeClass('active-step');
                $('#quickEstimateModal .quick-step-' + currentStep).addClass('active-step');

                $('.quick-step-dot').removeClass('active');
                for (let i = 1; i <= currentStep; i++) {
                    $('#qdot-' + i).addClass('active');
                }

                if (currentStep === 1) {
                    $('.quick-prev-btn').attr('style', 'display: none !important;');
                    $('.quick-next-btn').attr('style', 'display: inline-block !important;');
                    $('.quick-submit-btn').attr('style', 'display: none !important;');
                } else if (currentStep === 2) {
                    $('.quick-prev-btn').attr('style', 'display: inline-block !important;');
                    $('.quick-next-btn').attr('style', 'display: inline-block !important;');
                    $('.quick-submit-btn').attr('style', 'display: none !important;');
                } else {
                    $('.quick-prev-btn').attr('style', 'display: inline-block !important;');
                    $('.quick-next-btn').attr('style', 'display: none !important;');
                    $('.quick-submit-btn').attr('style', 'display: inline-block !important;');
                }
            }

            $('.quick-next-btn').off('click.quickEstimateWizard').on('click.quickEstimateWizard', function () {
                const isValid = typeof window.validateQuickEstimateWizardStep === 'function'
                    ? window.validateQuickEstimateWizardStep(currentStep)
                    : true;

                if (isValid && currentStep < totalSteps) {
                    currentStep++;
                    updateWizardUI();
                }
            });

            $('.quick-prev-btn').off('click.quickEstimateWizard').on('click.quickEstimateWizard', function () {
                if (currentStep > 1) {
                    currentStep--;
                    updateWizardUI();
                }
            });

            $('#quickEstimateForm').off('change.quickEstimateWizard input.quickEstimateWizard')
                .on('change.quickEstimateWizard input.quickEstimateWizard', '[required], .quick-bom-select, .quick-bom-make-select, .quick-bom-qty, .quick-bom-price', function () {
                    const val = $(this).val();
                    if (val || $(this).hasClass('quick-bom-qty') || $(this).hasClass('quick-bom-price')) {
                        if (typeof window.markQuickEstimateFieldInvalid === 'function') {
                            window.markQuickEstimateFieldInvalid(this, false);
                        } else {
                            $(this).removeClass('is-invalid');
                        }
                        if ($(this).hasClass('quick-bom-select') && typeof window.updateQuickBomErrorVisibility === 'function') {
                            window.updateQuickBomErrorVisibility(document.getElementById('quickEstimateForm'));
                        }
                        if ($(this).hasClass('quick-bom-make-select')) {
                            $(this).closest('.quick-bom-row').find('.quick-bom-make-error').removeClass('d-block');
                        }
                    }
                });

            $('#quickEstimateModal').off('hidden.bs.modal.quickEstimateWizard').on('hidden.bs.modal.quickEstimateWizard', function () {
                currentStep = 1;
                updateWizardUI();
            });

            updateWizardUI();
            $(window).off('resize.quickEstimateWizard').on('resize.quickEstimateWizard', updateWizardUI);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initQuickEstimateWizard);
        } else {
            initQuickEstimateWizard();
        }
    })();
</script>
