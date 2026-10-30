<?php

return [
    'actions' => [
        'view' => 'View',
        'create' => 'Insert',
        'edit' => 'Edit',
        'delete' => 'Delete',
    ],

    'modules' => [
        'customers' => ['label' => 'Customers', 'icon' => 'bi-people'],
        'partners' => ['label' => 'Partners', 'icon' => 'bi-person-badge'],
        'followups' => ['label' => 'Followup', 'icon' => 'bi-person-lines-fill'],
        'leads' => ['label' => 'Leads', 'icon' => 'bi-megaphone-fill'],
        'tickets' => ['label' => 'Tickets', 'icon' => 'bi-ticket-perforated'],
        'tasks' => ['label' => 'Tasks', 'icon' => 'bi-list-task'],
        // 'projects' => ['label' => 'Projects', 'icon' => 'bi-diagram-3-fill'],
        // 'services' => ['label' => 'Services', 'icon' => 'bi-wrench'],
        'deals' => ['label' => 'Deals', 'icon' => 'bi-award-fill'],
        'meetings' => ['label' => 'Meetings', 'icon' => 'bi-camera-video-fill'],
        // 'pipeline' => ['label' => 'Pipeline', 'icon' => 'bi-funnel'],
        // 'stages' => ['label' => 'Stages', 'icon' => 'bi-layers'],
        
        'bom' => ['label' => 'BOM', 'icon' => 'bi-list-check'],
        'make' => ['label' => 'Make', 'icon' => 'bi-hammer'],
        'warranty' => ['label' => 'Warranty', 'icon' => 'bi-patch-check'],
        'technology' => ['label' => 'Technology', 'icon' => 'bi-cpu'],

        'estimates' => ['label' => 'Estimates', 'icon' => 'bi-calculator'],
        'invoices' => ['label' => 'Invoices', 'icon' => 'bi-receipt'],
        'templates' => ['label' => 'Templates', 'icon' => 'bi-layout-text-window'],

        'sales' => ['label' => 'Material OUT', 'icon' => 'bi-box-arrow-up-right'],
        'purchases' => ['label' => 'Material IN', 'icon' => 'bi-box-arrow-in-down'],
        'inventory' => ['label' => 'Inventory', 'icon' => 'bi-box-seam'],
        'products' => ['label' => 'Products', 'icon' => 'bi-box'],
        'categories' => ['label' => 'Categories', 'icon' => 'bi-tags'],
        'vendors' => ['label' => 'Vendors', 'icon' => 'bi-shop'],
        'handover_persons' => ['label' => 'Handover Person', 'icon' => 'bi-person-badge'],
        'reports' => [
            'label' => 'Reports',
            'icon' => 'bi-file-earmark-text',
            'actions' => ['view'],
        ],
    ],
];
