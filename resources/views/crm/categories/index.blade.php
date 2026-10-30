@extends('layouts.app')

@section('page_title', 'Categories')

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
<link rel="stylesheet" href="{{ crm_asset('css/product-categories.css') }}">
@endpush

@section('content')
    @include('crm.inventory-masters.index', [
        'moduleKey' => 'category',
        'pageTitle' => 'Manage Categories',
        'resourceTitle' => 'Category',
        'resourcePlural' => 'Categories',
        'fieldName' => 'name',
        'fieldLabel' => 'Name',
        'hasDescription' => false,
        'hasImage' => true,
        'permissions' => [
            'view' => auth()->user()?->hasMatrixPermission('view_categories'),
            'create' => auth()->user()?->hasMatrixPermission('create_categories'),
            'edit' => auth()->user()?->hasMatrixPermission('edit_categories'),
            'delete' => auth()->user()?->hasMatrixPermission('delete_categories'),
        ]
    ])
@endsection

@push('scripts')
<script>
    window.inventoryMasterConfig = {
        moduleKey: 'category',
        resourceTitle: 'Category',
        resourcePlural: 'Categories',
        fieldName: 'name',
        fieldLabel: 'Name',
        hasDescription: false,
        hasImage: true,
        indexUrl: @json(route('api.categories.index')),
        storeUrl: @json(route('api.categories.store')),
        showUrlTemplate: @json(route('api.categories.show', ['category' => '__ID__'])),
        updateUrlTemplate: @json(route('api.categories.update', ['category' => '__ID__'])),
        destroyUrlTemplate: @json(route('api.categories.destroy', ['category' => '__ID__'])),
        permissions: {
            view: @json(auth()->user()?->hasMatrixPermission('view_categories')),
            create: @json(auth()->user()?->hasMatrixPermission('create_categories')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_categories')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_categories')),
        }
    };
</script>
<script src="{{ crm_asset('js/inventory-master.js') }}"></script>
@endpush
