@extends('layouts.app')

@section('page_title', 'Warranty')

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
<link rel="stylesheet" href="{{ crm_asset('css/product-categories.css') }}">
@endpush

@section('content')
    @include('crm.inventory-masters.index', [
        'moduleKey' => 'warranty',
        'pageTitle' => 'Manage Warranty',
        'resourceTitle' => 'Warranty',
        'resourcePlural' => 'Warranties',
        'fieldName' => 'title',
        'fieldLabel' => 'Name',
        'hasDescription' => true,
        'hasImage' => false,
        'permissions' => [
            'view' => auth()->user()?->hasMatrixPermission('view_warranty'),
            'create' => auth()->user()?->hasMatrixPermission('create_warranty'),
            'edit' => auth()->user()?->hasMatrixPermission('edit_warranty'),
            'delete' => auth()->user()?->hasMatrixPermission('delete_warranty'),
        ]
    ])
@endsection

@push('scripts')
<script>
    window.inventoryMasterConfig = {
        moduleKey: 'warranty',
        resourceTitle: 'Warranty',
        resourcePlural: 'Warranties',
        fieldName: 'title',
        fieldLabel: 'Name',
        hasDescription: true,
        hasImage: false,
        indexUrl: @json(route('api.warranty.index')),
        storeUrl: @json(route('api.warranty.store')),
        showUrlTemplate: @json(route('api.warranty.show', ['warranty' => '__ID__'])),
        updateUrlTemplate: @json(route('api.warranty.update', ['warranty' => '__ID__'])),
        destroyUrlTemplate: @json(route('api.warranty.destroy', ['warranty' => '__ID__'])),
        permissions: {
            view: @json(auth()->user()?->hasMatrixPermission('view_warranty')),
            create: @json(auth()->user()?->hasMatrixPermission('create_warranty')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_warranty')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_warranty')),
        }
    };
</script>
<script src="{{ crm_asset('js/inventory-master.js') }}"></script>
@endpush
