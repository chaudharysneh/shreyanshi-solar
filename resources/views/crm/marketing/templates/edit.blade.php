@extends('layouts.app')

@section('page_title', 'Edit Template')

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .marketing-template-page {
            padding-top: 1rem !important;
        }

        .marketing-template-header {
            flex-direction: column;
            align-items: stretch !important;
            gap: 1rem;
        }

        .marketing-template-header .btn {
            width: 100%;
        }

        .marketing-template-carousel .carousel-control-prev,
        .marketing-template-carousel .carousel-control-next {
            width: 12%;
        }

        .marketing-template-actions {
            flex-direction: column;
        }

        .marketing-template-actions .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $selectedTemplateName = in_array($template->template_name, ['template_1', 'template_2'], true)
        ? $template->template_name
        : 'template_1';
@endphp
<div class="container-fluid p-0 marketing-template-page">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="p-4">

                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 marketing-template-header">
                        <div>
                            <h1 class="h4 mb-1">Edit Message Template</h1>
                            <p class="text-muted small mb-0">Design a reusable layout for automated and manual campaigns.</p>
                        </div>
                        <a href="{{ route('marketing.templates.index') }}" class="btn btn-dark-blue"><i class="fa-solid fa-angle-left pe-2"></i>Back</a>
                    </div>
                </div>

                <div class="card-body px-4 pb-4">
                    <form id="marketingTemplateForm">
                        @csrf
                        @method('put')
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label">Select Template</label>
            
                                <div id="carouselExampleControlsNoTouching" class="carousel slide marketing-template-carousel" data-bs-touch="false" data-current-template="{{ $selectedTemplateName }}">
                                    <div class="carousel-inner">
                                        <div class="carousel-item {{ $selectedTemplateName === 'template_1' ? 'active' : '' }}" data-template-name="template_1">
                                            <h4 class="text-center">Template 1</h4>
                                            <div class="card template-card crm-template-card {{ $selectedTemplateName === 'template_1' ? 'selected' : '' }}">
                                                @include('crm.marketing.email.template.template_1')
                                            </div>
                                        </div>
                                        <div class="carousel-item {{ $selectedTemplateName === 'template_2' ? 'active' : '' }}" data-template-name="template_2">
                                            <h4 class="text-center">Template 2</h4>
                                            <div class="card template-card crm-template-card {{ $selectedTemplateName === 'template_2' ? 'selected' : '' }}">
                                                @include('crm.marketing.email.template.template_2')
                                            </div>
                                        </div>
                                    </div>
                                    <button class="carousel-control-prev" type="button"
                                        data-bs-target="#carouselExampleControlsNoTouching" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon bg-dark" aria-hidden="true"></span>
                                        <span class="visually-hidden">Previous</span>
                                    </button>
                                    <button class="carousel-control-next" type="button"
                                        data-bs-target="#carouselExampleControlsNoTouching" data-bs-slide="next">
                                        <span class="carousel-control-next-icon bg-dark" aria-hidden="true"></span>
                                        <span class="visually-hidden">Next</span>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label fw-bold small text-muted text-uppercase">Template Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $template->name }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted text-uppercase">Template Status</label>
                                <select name="status" id="template_type" class="form-select">
                                    <option value="active" {{ $template->status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ $template->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Imaje 1</label>
                                <input class="form-control" type="file" name="image_1" id="image_1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Imaje 2</label>
                                <input class="form-control" type="file" name="image_2" id="image_2">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Imaje 3</label>
                                <input class="form-control" type="file" name="image_3" id="image_3">
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                                <a href="{{ route('marketing.templates.index') }}" class="btn btn-outline-dark-blue">Cancel</a>
                                <button type="submit" class="btn btn-dark-blue">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>

    $(document).ready(function() {
        const carousel = $('#carouselExampleControlsNoTouching');

        function syncSelectedTemplate() {
            const $activeItem = carousel.find('.carousel-item.active');
            $('.template-card.selected').removeClass('selected');
            $activeItem.find('.template-card').addClass('selected');
        }

        syncSelectedTemplate();

        carousel.on('slid.bs.carousel', function () {
            syncSelectedTemplate();
        });

        $(".template-card").on('click', function () {
            const $item = $(this).closest('.carousel-item');
            const itemIndex = $item.index();
            const carouselInstance = bootstrap.Carousel.getOrCreateInstance(document.getElementById('carouselExampleControlsNoTouching'));
            carouselInstance.to(itemIndex);
            syncSelectedTemplate();
        });

        $("#marketingTemplateForm").submit(function (e) {
            e.preventDefault();
            var formData = new FormData(this);

            // data append in formdata
            syncSelectedTemplate();
            let templateName = $('.carousel-item.active').data('template-name') || $('.template-card.selected').closest('.carousel-item').data('template-name');
            formData.append('template_name', templateName);

            // remove previous errors
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $.ajax({
                type: "post",
                url: "{{ route('marketing.templates.update', $template) }}",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    showAlert('success', response.message, 'success');
                    window.location.href = "{{ route('marketing.templates.index') }}";
                },
                error: function (error) {
                    if (error.status === 422) {
                        $.each(error.responseJSON.errors, function (key, value) {
                            var input = $('[name="' + key + '"]');
                            input.addClass('is-invalid');
                            input.after('<div class="invalid-feedback">' + value[0] + '</div>');
                        });
                    }
                }
            });
        });
    });
</script>
@endpush
