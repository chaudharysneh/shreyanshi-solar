(function () {
    function showToast(type, message, redirectUrl = null) {
        if (typeof window.showAlert === "function") {
            window.showAlert(type, message, type === "success" ? "Success!" : "Error!", redirectUrl);
            return;
        }

        alert(message);
        if (redirectUrl) {
            window.location.href = redirectUrl;
        }
    }

    function clearErrors($form) {
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find(".invalid-feedback").html("");
    }

    function showErrors($form, errors) {
        $.each(errors, function (field, messages) {
            const $input = $form.find("#" + field);
            const $error = $form.find("#" + field + "-error");

            if ($input.length) {
                $input.addClass("is-invalid");
            }

            if ($error.length) {
                $error.html(messages[0]);
            }
        });
    }

    $(document).on("submit", ".ajax-document-form", function (e) {
        e.preventDefault();

        const $form = $(this);
        const $button = $form.find("#submitBtn");
        const originalHtml = $button.html();
        const formData = new FormData(this);

        clearErrors($form);
        $button.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

        $.ajax({
            url: $form.attr("action"),
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                Accept: "application/json",
            },
            success: function (response) {
                $button.prop("disabled", false).html(originalHtml);
                showToast("success", response.message || "Document saved successfully.", response.redirect || "/documents");
            },
            error: function (xhr) {
                $button.prop("disabled", false).html(originalHtml);

                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    showErrors($form, xhr.responseJSON.errors);
                    return;
                }

                showToast("error", xhr.responseJSON?.message || "Unable to save the document right now.");
            },
        });
    });

    $(document).on("click", ".ajax-document-delete", function () {
        const button = this;
        const documentId = button.dataset.id;

        window.showDeleteConfirm("This document will be deleted!").then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            const originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            button.disabled = true;

            $.ajax({
                url: "/api/documents/" + documentId,
                type: "DELETE",
                dataType: "json",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    Accept: "application/json",
                },
                success: function (response) {
                    showToast("success", response.message || "Document deleted successfully.");
                    const row = button.closest("tr");
                    if (row) {
                        row.remove();
                    }
                    if (!document.querySelector("tbody tr")) {
                        window.location.reload();
                    }
                },
                error: function (xhr) {
                    button.innerHTML = originalHtml;
                    button.disabled = false;
                    showToast("error", xhr.responseJSON?.message || "Unable to delete the document.");
                },
            });
        });
    });

    $(document).on("input change", ".ajax-document-form input", function () {
        $(this).removeClass("is-invalid");
        $("#" + this.id + "-error").html("");
    });
})();
