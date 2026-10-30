@extends('layouts.app')

@section('page_title', 'Technology')

@push('styles')
<link rel="stylesheet" href="{{ crm_asset('css/users.css') }}">
<link rel="stylesheet" href="{{ crm_asset('css/product-categories.css') }}">
@endpush

@section('content')
    @include('crm.inventory-masters.index', [
        'moduleKey' => 'technology',
        'pageTitle' => 'Manage Technology',
        'resourceTitle' => 'Technology',
        'resourcePlural' => 'Technologies',
        'fieldName' => 'title',
        'fieldLabel' => 'Name',
        'hasDescription' => true,
        'hasImage' => false,
        'permissions' => [
            'view' => auth()->user()?->hasMatrixPermission('view_technology'),
            'create' => auth()->user()?->hasMatrixPermission('create_technology'),
            'edit' => auth()->user()?->hasMatrixPermission('edit_technology'),
            'delete' => auth()->user()?->hasMatrixPermission('delete_technology'),
        ]
    ])
@endsection

@push('scripts')
<script>
    window.inventoryMasterConfig = {
        moduleKey: 'technology',
        resourceTitle: 'Technology',
        resourcePlural: 'Technologies',
        fieldName: 'title',
        fieldLabel: 'Name',
        hasDescription: true,
        hasImage: false,
        indexUrl: @json(route('api.technology.index')),
        storeUrl: @json(route('api.technology.store')),
        showUrlTemplate: @json(route('api.technology.show', ['technology' => '__ID__'])),
        updateUrlTemplate: @json(route('api.technology.update', ['technology' => '__ID__'])),
        destroyUrlTemplate: @json(route('api.technology.destroy', ['technology' => '__ID__'])),
        permissions: {
            view: @json(auth()->user()?->hasMatrixPermission('view_technology')),
            create: @json(auth()->user()?->hasMatrixPermission('create_technology')),
            edit: @json(auth()->user()?->hasMatrixPermission('edit_technology')),
            delete: @json(auth()->user()?->hasMatrixPermission('delete_technology')),
        }
    };
</script>
<script src="{{ crm_asset('js/inventory-master.js') }}"></script>
@endpush
