@extends('layouts.app')

@section('page_title', 'Make')

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
<link rel="stylesheet" href="{{ crm_asset('css/product-categories.css') }}">
@endpush

@section('content')
    @include('crm.inventory-masters.index', [
        'moduleKey' => 'make',
        'pageTitle' => 'Manage Make',
        'resourceTitle' => 'Make',
        'resourcePlural' => 'Makes',
        'fieldName' => 'name',
        'fieldLabel' => 'Name',
        'hasDescription' => false,
        'hasImage' => true,
        'permissions' => [
            'view' => auth()->user()?->hasMatrixPermission('view_make'),
            'create' => auth()->user()?->hasMatrixPermission('create_make'),
            'edit' => auth()->user()?->hasMatrixPermission('edit_make'),
            'delete' => auth()->user()?->hasMatrixPermission('delete_make'),
        ]
    ])
@endsection

@push('scripts')
<script>
    window.inventoryMasterConfig = {
        moduleKey: 'make',
        resourceTitle: 'Make',
        resourcePlural: 'Makes',
        fieldName: 'name',
        fieldLabel: 'Name',
        hasDescription: false,
        hasImage: true,
        dummyImageUrl: @json(url((config('frontend_assets.public_prefix') ? rtrim(config('frontend_assets.public_prefix'), '/') . '/' : '') . 'images/template/faveicone.png')),
        indexUrl: @json(route('api.make.index')),
        storeUrl: @json(route('api.make.store')),
        showUrlTemplate: @json(route('api.make.show', ['make' => '__ID__'])),
        updateUrlTemplate: @json(route('api.make.update', ['make' => '__ID__'])),
        destroyUrlTemplate: @json(route('api.make.destroy', ['make' => '__ID__'])),
        permissions: {
            view: @json(auth()->user()?->hasMatrixPermission('view_make')),
            create: @json(auth()->user()?->hasMatrixPermission('create_make')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_make')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_make')),
        }
    };
</script>
<script src="{{ crm_asset('js/inventory-master.js') }}"></script>
@endpush
