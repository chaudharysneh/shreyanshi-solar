<?php

use App\Http\Controllers\Api\BomProductController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\MakeController;
use App\Http\Controllers\Api\ProductCategoryController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\StatusHistoryController;
use App\Http\Controllers\Api\TechnologyController;
use App\Http\Controllers\Api\WarrantyController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\FollowUpController;
use App\Http\Controllers\Api\Masters;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\DealController;
use App\Http\Controllers\Api\PipelineController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\HandoverPersonController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\PdfbuilderApiController;
use App\Http\Controllers\Api\ProductInventoryController;
use App\Http\Controllers\Api\EstimateController;
use App\Http\Controllers\UserLogController;




/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('index-status/{module}/{id}', [\App\Http\Controllers\Api\IndexStatusController::class, 'show'])->whereNumber('id');
    Route::patch('index-status/{module}/{id}', [\App\Http\Controllers\Api\IndexStatusController::class, 'update'])->whereNumber('id');
    Route::post('masters/lead-statuses', [Masters\LeadStatusController::class, 'store']);
    Route::get('estimates/{id}/equipment', [\App\Http\Controllers\Api\EstimateController::class,'equipment'])->middleware('matrix_permission:edit_estimates');
    Route::get('equipment/estimates/{estimate}',[\App\Http\Controllers\Api\EquipmentController::class,'show']);
    Route::patch('equipment/estimates/{estimate}',[\App\Http\Controllers\Api\EquipmentController::class,'update']);
    Route::post('masters/lead-sources', [\App\Http\Controllers\Api\LeadSourceMasterController::class, 'store']);
    Route::put('masters/lead-sources/{leadSource}', [\App\Http\Controllers\Api\LeadSourceMasterController::class, 'update']);
    Route::delete('masters/lead-sources/{leadSource}', [\App\Http\Controllers\Api\LeadSourceMasterController::class, 'destroy']);
    Route::get('channel-partners', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'index']);
    Route::post('channel-partners', [\App\Http\Controllers\Api\ChannelPartnerController::class, 'store']);
    Route::put('masters/lead-statuses/{leadStatus}', [Masters\LeadStatusController::class, 'update']);
    Route::delete('masters/lead-statuses/{leadStatus}', [Masters\LeadStatusController::class, 'destroy']);

    Route::get('user-logs', [UserLogController::class, 'apiIndex'])->middleware('main_admin')->name('api.user-logs.index');
    Route::get('user-logs/{notification}', [UserLogController::class, 'apiShow'])->middleware('main_admin')->name('api.user-logs.show');
    Route::delete('user-logs/{notification}', [UserLogController::class, 'apiDestroy'])->middleware('main_admin')->name('api.user-logs.destroy');
    Route::delete('user-logs', [UserLogController::class, 'apiDestroyAll'])->middleware('main_admin')->name('api.user-logs.destroy_all');

    Route::get('dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'apiStats'])->name('api.dashboard.stats');
    Route::get('dashboard/lead-board', [\App\Http\Controllers\DashboardController::class, 'apiLeadBoard'])->name('api.dashboard.lead_board');
    Route::get('dashboard/tasks-widget', [\App\Http\Controllers\DashboardController::class, 'apiTasksWidget'])->name('api.dashboard.tasks_widget');
    Route::get('dashboard/trend', [\App\Http\Controllers\DashboardController::class, 'apiTrend'])->name('api.dashboard.trend');
    Route::get('dashboard/customer-report', [\App\Http\Controllers\DashboardController::class, 'apiCustomerReport'])->name('api.dashboard.customer_report');
    Route::get('dashboard/deals-widget', [\App\Http\Controllers\DashboardController::class, 'apiDealsWidget'])->name('api.dashboard.deals_widget');

    Route::get('leads', [LeadController::class, 'index'])->middleware('matrix_permission:view_leads')->name('api.leads.index');
    Route::post('leads', [LeadController::class, 'store'])->middleware('matrix_permission:create_leads')->name('api.leads.store');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->middleware('matrix_permission:view_leads')->name('api.leads.show');
    Route::put('leads/{lead}', [LeadController::class, 'update'])->middleware('matrix_permission:edit_leads')->name('api.leads.update');
    Route::patch('leads/{lead}', [LeadController::class, 'update'])->middleware('matrix_permission:edit_leads');
    Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->middleware('matrix_permission:delete_leads')->name('api.leads.destroy');

    Route::get('deals', [DealController::class, 'index'])->middleware('matrix_permission:view_deals')->name('api.deals.index');
    Route::get('deals/customer-invoices', [DealController::class, 'customerInvoices']);
    Route::get('deals/customer-estimates', [DealController::class, 'customerEstimates'])->name('api.deals.customer-estimates');
    Route::post('deals', [DealController::class, 'store'])->middleware('matrix_permission:create_deals')->name('api.deals.store');
    Route::get('deals/{deal}', [DealController::class, 'show'])->middleware('matrix_permission:view_deals')->name('api.deals.show');
    Route::put('deals/{deal}', [DealController::class, 'update'])->middleware('matrix_permission:edit_deals')->name('api.deals.update');
    Route::patch('deals/{deal}', [DealController::class, 'update'])->middleware('matrix_permission:edit_deals');
    Route::delete('deals/{deal}', [DealController::class, 'destroy'])->middleware('matrix_permission:delete_deals')->name('api.deals.destroy');
    Route::patch('deals/{deal}/status', [DealController::class, 'updateStatus'])->middleware('matrix_permission:edit_deals')->name('api.deals.status');

    Route::get('estimates', [EstimateController::class, 'index'])->middleware('matrix_permission:view_estimates')->name('api.estimates.index');
    Route::post('estimates', [EstimateController::class, 'store'])->middleware('matrix_permission:create_estimates')->name('api.estimates.store');
    Route::get('estimates/{estimate}', [EstimateController::class, 'show'])->middleware('matrix_permission:view_estimates')->name('api.estimates.show');
    Route::put('estimates/{estimate}', [EstimateController::class, 'update'])->middleware('matrix_permission:edit_estimates')->name('api.estimates.update');
    Route::patch('estimates/{estimate}', [EstimateController::class, 'update'])->middleware('matrix_permission:edit_estimates');
    Route::patch('estimates/{estimate}/status', [EstimateController::class, 'updateStatus'])->middleware('matrix_permission:edit_estimates')->name('api.estimates.status');
    Route::delete('estimates/{estimate}', [EstimateController::class, 'destroy'])->middleware('matrix_permission:delete_estimates')->name('api.estimates.destroy');
    Route::get('estimates/{estimate}/customer-docs', [EstimateController::class, 'customerDocuments'])->middleware('matrix_permission:view_estimates')->name('api.estimates.customer-docs.index');
    Route::post('estimates/{estimate}/customer-docs', [EstimateController::class, 'uploadCustomerDocuments'])->middleware('matrix_permission:edit_estimates')->name('api.estimates.customer-docs.store');
    Route::delete('estimates/{estimate}/customer-docs/{docIndex}', [EstimateController::class, 'deleteCustomerDocument'])->middleware('matrix_permission:edit_estimates')->name('api.estimates.customer-docs.destroy');

    Route::get('pipelines', [PipelineController::class, 'index'])->middleware('matrix_permission:view_pipeline')->name('api.pipelines.index');
    Route::post('pipelines', [PipelineController::class, 'store'])->middleware('matrix_permission:create_pipeline')->name('api.pipelines.store');
    Route::get('pipelines/{pipeline}', [PipelineController::class, 'show'])->middleware('matrix_permission:view_pipeline')->name('api.pipelines.show');
    Route::put('pipelines/{pipeline}', [PipelineController::class, 'update'])->middleware('matrix_permission:edit_pipeline')->name('api.pipelines.update');
    Route::patch('pipelines/{pipeline}', [PipelineController::class, 'update'])->middleware('matrix_permission:edit_pipeline');
    Route::delete('pipelines/{pipeline}', [PipelineController::class, 'destroy'])->middleware('matrix_permission:delete_pipeline')->name('api.pipelines.destroy');

    Route::get('masters/stages', [Masters\StageController::class, 'index'])->middleware('matrix_permission:view_stages')->name('api.masters.stages');
    Route::post('masters/stages', [Masters\StageController::class, 'store'])->middleware('matrix_permission:create_stages');
    Route::get('masters/stages/{id}', [Masters\StageController::class, 'show'])->middleware('matrix_permission:view_stages');
    Route::put('masters/stages/{id}', [Masters\StageController::class, 'update'])->middleware('matrix_permission:edit_stages');
    Route::patch('masters/stages/{id}', [Masters\StageController::class, 'update'])->middleware('matrix_permission:edit_stages');
    Route::delete('masters/stages/{id}', [Masters\StageController::class, 'destroy'])->middleware('matrix_permission:delete_stages');

    Route::get('projects', [ProjectController::class, 'index'])->middleware('matrix_permission:view_projects')->name('api.projects.index');
    Route::post('projects', [ProjectController::class, 'store'])->middleware('matrix_permission:create_projects')->name('api.projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->middleware('matrix_permission:view_projects')->name('api.projects.show');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->middleware('matrix_permission:edit_projects')->name('api.projects.update');
    Route::patch('projects/{project}', [ProjectController::class, 'update'])->middleware('matrix_permission:edit_projects');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->middleware('matrix_permission:delete_projects')->name('api.projects.destroy');

    Route::get('meetings', [MeetingController::class, 'index'])->middleware('matrix_permission:view_meetings')->name('api.meetings.index');
    Route::post('meetings', [MeetingController::class, 'store'])->middleware('matrix_permission:create_meetings')->name('api.meetings.store');
    Route::get('meetings/{meeting}', [MeetingController::class, 'show'])->middleware('matrix_permission:view_meetings')->whereNumber('meeting')->name('api.meetings.show');
    Route::put('meetings/{meeting}', [MeetingController::class, 'update'])->middleware('matrix_permission:edit_meetings')->whereNumber('meeting')->name('api.meetings.update');
    Route::patch('meetings/{meeting}', [MeetingController::class, 'update'])->middleware('matrix_permission:edit_meetings')->whereNumber('meeting');
    Route::delete('meetings/{meeting}', [MeetingController::class, 'destroy'])->middleware('matrix_permission:delete_meetings')->whereNumber('meeting')->name('api.meetings.destroy');
    Route::get('meetings/customers', [MeetingController::class, 'getCustomers'])->name('api.meetings.customers');
    Route::get('meetings/users', [MeetingController::class, 'getUsers'])->name('api.meetings.users');

    Route::get('customers', [CustomerController::class, 'index'])->name('api.customers.index');
    Route::post('customers', [CustomerController::class, 'store'])->name('api.customers.store');
    Route::get('customers/search', [\App\Http\Controllers\CustomerController::class, 'apiSearch'])->name('customers.search.api');
    Route::get('customers/search-estimate-customers', [\App\Http\Controllers\CustomerController::class, 'apiEstimateCustomerSearch'])->name('customers.search.estimate.api');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('api.customers.show');
    Route::put('customers/{customer}', [CustomerController::class, 'update'])->whereNumber('customer')->name('api.customers.update');
    Route::patch('customers/{customer}', [CustomerController::class, 'update'])->whereNumber('customer');
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->middleware('matrix_permission:delete_customers')->whereNumber('customer')->name('api.customers.destroy');

    Route::patch('tasks/{task}/workflow-step', [TaskController::class, 'updateWorkflowStep'])->middleware('matrix_permission:edit_tasks');
    Route::get('document-name', [\App\Http\Controllers\Api\DocumentNameController::class, 'show']);
    Route::get('tasks', [TaskController::class, 'index'])->middleware('matrix_permission:view_tasks')->name('api.tasks.index');
    Route::post('tasks', [TaskController::class, 'store'])->middleware('matrix_permission:create_tasks')->name('api.tasks.store');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->middleware('matrix_permission:view_tasks')->name('api.tasks.show');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->middleware('matrix_permission:edit_tasks')->name('api.tasks.update');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->middleware('matrix_permission:edit_tasks');
    Route::patch('tasks/{task}/quick-status', [TaskController::class, 'quickStatusUpdate'])->middleware('matrix_permission:edit_tasks')->name('api.tasks.quick-status');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->middleware('matrix_permission:delete_tasks')->name('api.tasks.destroy');
    Route::post('status-history', [StatusHistoryController::class, 'store']);

    Route::get('follow-ups', [FollowUpController::class, 'index'])->middleware('matrix_permission:view_followups')->name('api.followups.index');
    Route::post('follow-ups', [FollowUpController::class, 'store'])->middleware('matrix_permission:create_followups')->name('api.followups.store');
    Route::get('follow-ups/lead/{lead}/assigned-user', [FollowUpController::class, 'getLeadAssignedUser'])->middleware('matrix_permission:view_followups')->name('api.followups.lead-assigned-user');
    Route::get('follow-ups/{follow_up}', [FollowUpController::class, 'show'])->middleware('matrix_permission:view_followups')->name('api.followups.show');
    Route::put('follow-ups/{follow_up}', [FollowUpController::class, 'update'])->middleware('matrix_permission:edit_followups')->name('api.followups.update');
    Route::patch('follow-ups/{follow_up}', [FollowUpController::class, 'update'])->middleware('matrix_permission:edit_followups');
    Route::delete('follow-ups/{follow_up}', [FollowUpController::class, 'destroy'])->middleware('matrix_permission:delete_followups')->name('api.followups.destroy');

    Route::get('calendar/events', [\App\Http\Controllers\CalendarController::class, 'apiEvents'])->name('api.calendar.events');

    Route::get('invoices', [InvoiceController::class, 'index'])->middleware('matrix_permission:view_invoices')->name('api.invoices.index');
    Route::post('invoices', [InvoiceController::class, 'store'])->middleware('matrix_permission:create_invoices')->name('api.invoices.store');
    Route::get('invoices/price-suggestions', [InvoiceController::class, 'priceSuggestions'])->name('api.invoices.price-suggestions');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->middleware('matrix_permission:view_invoices')->name('api.invoices.show');
    Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->middleware('matrix_permission:edit_invoices')->name('api.invoices.update');
    Route::patch('invoices/{invoice}', [InvoiceController::class, 'update'])->middleware('matrix_permission:edit_invoices');
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->middleware('matrix_permission:delete_invoices')->name('api.invoices.destroy');
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->middleware('matrix_permission:edit_invoices')->name('api.invoices.status');

    Route::apiResource('tickets', SupportTicketController::class)->middleware('matrix_permission:view_tickets')->names([
        'index' => 'api.tickets.index',
        'store' => 'api.tickets.store',
        'show' => 'api.tickets.show',
        'update' => 'api.tickets.update',
        'destroy' => 'api.tickets.destroy',
    ]);
    Route::post('tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->middleware('matrix_permission:edit_tickets')->name('api.tickets.reply');
    Route::patch('tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->middleware('matrix_permission:edit_tickets')->name('api.tickets.status');

    Route::get('products', [ProductController::class, 'index'])->middleware('matrix_permission:view_products')->name('api.products.index');
    Route::post('products', [ProductController::class, 'store'])->middleware('matrix_permission:create_products')->name('api.products.store');
    Route::post('products/import', [ProductController::class, 'import'])->middleware('matrix_permission:create_products')->name('api.products.import');
    Route::get('products/by-serial/{serialNo}', [ProductController::class, 'getBySerialNo'])->middleware('matrix_permission:view_products')->name('api.products.by-serial');
    Route::get('products/{product}', [ProductController::class, 'show'])->middleware('matrix_permission:view_products')->name('api.products.show');
    Route::put('products/{product}', [ProductController::class, 'update'])->middleware('matrix_permission:edit_products')->name('api.products.update');
    Route::patch('products/{product}', [ProductController::class, 'update'])->middleware('matrix_permission:edit_products');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('matrix_permission:delete_products')->name('api.products.destroy');

    Route::get('make', [MakeController::class, 'index'])->middleware('matrix_permission:view_make')->name('api.make.index');
    Route::get('makes/search', [MakeController::class, 'search'])->middleware('matrix_permission:view_make')->name('api.makes.search');
    Route::get('make/{id}/image', [MakeController::class, 'image'])->middleware('matrix_permission:view_make')->name('make.image');
    Route::post('make', [MakeController::class, 'store'])->middleware('matrix_permission:create_make')->name('api.make.store');
    Route::get('make/{make}', [MakeController::class, 'show'])->middleware('matrix_permission:view_make')->name('api.make.show');
    Route::put('make/{make}', [MakeController::class, 'update'])->middleware('matrix_permission:edit_make')->name('api.make.update');
    Route::patch('make/{make}', [MakeController::class, 'update'])->middleware('matrix_permission:edit_make');
    Route::delete('make/{make}', [MakeController::class, 'destroy'])->middleware('matrix_permission:delete_make')->name('api.make.destroy');
    
    Route::prefix('v1')->group(function () {
        Route::get('categories', [CategoriesController::class, 'index'])->middleware('matrix_permission:view_categories')->name('api.categories.index');
        Route::post('categories', [CategoriesController::class, 'store'])->middleware('matrix_permission:create_categories')->name('api.categories.store');
        Route::get('categories/{category}', [CategoriesController::class, 'show'])->middleware('matrix_permission:view_categories')->name('api.categories.show');
        Route::put('categories/{category}', [CategoriesController::class, 'update'])->middleware('matrix_permission:edit_categories')->name('api.categories.update');
        Route::patch('categories/{category}', [CategoriesController::class, 'update'])->middleware('matrix_permission:edit_categories');
        Route::delete('categories/{category}', [CategoriesController::class, 'destroy'])->middleware('matrix_permission:delete_categories')->name('api.categories.destroy');
    });
    Route::get('warranty', [WarrantyController::class, 'index'])->middleware('matrix_permission:view_warranty')->name('api.warranty.index');
    Route::post('warranty', [WarrantyController::class, 'store'])->middleware('matrix_permission:create_warranty')->name('api.warranty.store');
    Route::get('warranty/{warranty}', [WarrantyController::class, 'show'])->middleware('matrix_permission:view_warranty')->name('api.warranty.show');
    Route::put('warranty/{warranty}', [WarrantyController::class, 'update'])->middleware('matrix_permission:edit_warranty')->name('api.warranty.update');
    Route::patch('warranty/{warranty}', [WarrantyController::class, 'update'])->middleware('matrix_permission:edit_warranty');
    Route::delete('warranty/{warranty}', [WarrantyController::class, 'destroy'])->middleware('matrix_permission:delete_warranty')->name('api.warranty.destroy');
    Route::get('technology', [TechnologyController::class, 'index'])->middleware('matrix_permission:view_technology')->name('api.technology.index');
    Route::post('technology', [TechnologyController::class, 'store'])->middleware('matrix_permission:create_technology')->name('api.technology.store');
    Route::get('technology/{technology}', [TechnologyController::class, 'show'])->middleware('matrix_permission:view_technology')->name('api.technology.show');
    Route::put('technology/{technology}', [TechnologyController::class, 'update'])->middleware('matrix_permission:edit_technology')->name('api.technology.update');
    Route::patch('technology/{technology}', [TechnologyController::class, 'update'])->middleware('matrix_permission:edit_technology');
    Route::delete('technology/{technology}', [TechnologyController::class, 'destroy'])->middleware('matrix_permission:delete_technology')->name('api.technology.destroy');
    Route::get('vendors', [VendorController::class, 'index'])->middleware('matrix_permission:view_vendors')->name('api.vendors.index');
    Route::post('vendors', [VendorController::class, 'store'])->middleware('matrix_permission:create_vendors')->name('api.vendors.store');
    Route::get('vendors/{vendor}', [VendorController::class, 'show'])->middleware('matrix_permission:view_vendors')->name('api.vendors.show');
    Route::put('vendors/{vendor}', [VendorController::class, 'update'])->middleware('matrix_permission:edit_vendors')->name('api.vendors.update');
    Route::patch('vendors/{vendor}', [VendorController::class, 'update'])->middleware('matrix_permission:edit_vendors');
    Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->middleware('matrix_permission:delete_vendors')->name('api.vendors.destroy');
    Route::get('handover-persons', [HandoverPersonController::class, 'index'])->middleware('matrix_permission:view_handover_persons')->name('api.handover-persons.index');
    Route::post('handover-persons', [HandoverPersonController::class, 'store'])->middleware('matrix_permission:create_handover_persons')->name('api.handover-persons.store');
    Route::get('handover-persons/{handoverPerson}', [HandoverPersonController::class, 'show'])->middleware('matrix_permission:view_handover_persons')->name('api.handover-persons.show');
    Route::put('handover-persons/{handoverPerson}', [HandoverPersonController::class, 'update'])->middleware('matrix_permission:edit_handover_persons')->name('api.handover-persons.update');
    Route::patch('handover-persons/{handoverPerson}', [HandoverPersonController::class, 'update'])->middleware('matrix_permission:edit_handover_persons');
    Route::delete('handover-persons/{handoverPerson}', [HandoverPersonController::class, 'destroy'])->middleware('matrix_permission:delete_handover_persons')->name('api.handover-persons.destroy');
    Route::get('bom-products', [BomProductController::class, 'index'])->middleware('matrix_permission:view_bom')->name('api.bom-products.index');
    Route::post('bom-products', [BomProductController::class, 'store'])->middleware('matrix_permission:create_bom')->name('api.bom-products.store');
    Route::get('bom-products/{bomProduct}', [BomProductController::class, 'show'])->middleware('matrix_permission:view_bom')->name('api.bom-products.show');
    Route::put('bom-products/{bomProduct}', [BomProductController::class, 'update'])->middleware('matrix_permission:edit_bom')->name('api.bom-products.update');
    Route::patch('bom-products/{bomProduct}', [BomProductController::class, 'update'])->middleware('matrix_permission:edit_bom');
    Route::delete('bom-products/{bomProduct}', [BomProductController::class, 'destroy'])->middleware('matrix_permission:delete_bom')->name('api.bom-products.destroy');
    Route::get('products/categories', [ProductCategoryController::class, 'index'])->middleware('matrix_permission:view_products')->name('api.products.categories.index');
    Route::post('products/categories', [ProductCategoryController::class, 'store'])->middleware('matrix_permission:create_products')->name('api.products.categories.store');
    Route::get('products/categories/{productCategory}', [ProductCategoryController::class, 'show'])->middleware('matrix_permission:view_products')->name('api.products.categories.show');
    Route::put('products/categories/{productCategory}', [ProductCategoryController::class, 'update'])->middleware('matrix_permission:edit_products')->name('api.products.categories.update');
    Route::patch('products/categories/{productCategory}', [ProductCategoryController::class, 'update'])->middleware('matrix_permission:edit_products');
    Route::delete('products/categories/{productCategory}', [ProductCategoryController::class, 'destroy'])->middleware('matrix_permission:delete_products')->name('api.products.categories.destroy');
    Route::patch('products/categories/{productCategory}/toggle-status', [ProductCategoryController::class, 'toggleStatus'])
        ->middleware('matrix_permission:edit_products')
        ->name('api.products.categories.toggle_status');

    Route::get('product-inventory', [ProductInventoryController::class, 'index'])->middleware('matrix_permission:view_inventory')->name('api.product-inventory.index');
    Route::post('product-inventory', [ProductInventoryController::class, 'store'])->middleware('matrix_permission:edit_inventory')->name('api.product-inventory.store');
    Route::get('product-inventory/{productInventory}', [ProductInventoryController::class, 'show'])->middleware('matrix_permission:view_inventory')->name('api.product-inventory.show');
    Route::put('product-inventory/{productInventory}', [ProductInventoryController::class, 'update'])->middleware('matrix_permission:edit_inventory')->name('api.product-inventory.update');
    Route::patch('product-inventory/{productInventory}', [ProductInventoryController::class, 'update'])->middleware('matrix_permission:edit_inventory');
    Route::delete('product-inventory/{productInventory}', [ProductInventoryController::class, 'destroy'])->middleware('matrix_permission:delete_inventory')->name('api.product-inventory.destroy');
    Route::get('product-inventory/history/{product}', [ProductInventoryController::class, 'history'])->middleware('matrix_permission:view_inventory')->name('api.product-inventory.history');

    Route::get('v1/purchases', [PurchaseController::class, 'index'])->middleware('matrix_permission:view_purchases')->name('api.v1.purchases.index');
    Route::post('v1/purchases', [PurchaseController::class, 'store'])->middleware('matrix_permission:create_purchases')->name('api.v1.purchases.store');
    Route::get('v1/purchases/{purchase}', [PurchaseController::class, 'show'])->middleware('matrix_permission:view_purchases')->name('api.v1.purchases.show');
    Route::put('v1/purchases/{purchase}', [PurchaseController::class, 'update'])->middleware('matrix_permission:edit_purchases')->name('api.v1.purchases.update');
    Route::patch('v1/purchases/{purchase}', [PurchaseController::class, 'update'])->middleware('matrix_permission:edit_purchases');
    Route::delete('v1/purchases/{purchase}', [PurchaseController::class, 'destroy'])->middleware('matrix_permission:delete_purchases')->name('api.v1.purchases.destroy');

    Route::get('v1/sales', [SalesController::class, 'index'])->middleware('matrix_permission:view_sales')->name('api.v1.sales.index');
    Route::post('v1/sales', [SalesController::class, 'store'])->middleware('matrix_permission:create_sales')->name('api.v1.sales.store');
    Route::get('v1/sales/{sale}', [SalesController::class, 'show'])->middleware('matrix_permission:view_sales')->name('api.v1.sales.show');
    Route::put('v1/sales/{sale}', [SalesController::class, 'update'])->middleware('matrix_permission:edit_sales')->name('api.v1.sales.update');
    Route::patch('v1/sales/{sale}', [SalesController::class, 'update'])->middleware('matrix_permission:edit_sales');
    Route::delete('v1/sales/{sale}', [SalesController::class, 'destroy'])->middleware('matrix_permission:delete_sales')->name('api.v1.sales.destroy');

    Route::get('v1/estimates', [EstimateController::class, 'index'])->middleware('matrix_permission:view_estimates')->name('api.v1.estimates.index');
    Route::post('v1/estimates', [EstimateController::class, 'store'])->middleware('matrix_permission:create_estimates')->name('api.v1.estimates.store');
    Route::get('v1/estimates/{estimate}', [EstimateController::class, 'show'])->middleware('matrix_permission:view_estimates')->name('api.v1.estimates.show');
    Route::put('v1/estimates/{estimate}', [EstimateController::class, 'update'])->middleware('matrix_permission:edit_estimates')->name('api.v1.estimates.update');
    Route::patch('v1/estimates/{estimate}', [EstimateController::class, 'update'])->middleware('matrix_permission:edit_estimates');
    Route::patch('v1/estimates/{estimate}/status', [EstimateController::class, 'updateStatus'])->middleware('matrix_permission:edit_estimates')->name('api.v1.estimates.status');
    Route::delete('v1/estimates/{estimate}', [EstimateController::class, 'destroy'])->middleware('matrix_permission:delete_estimates')->name('api.v1.estimates.destroy');
    Route::get('v1/estimates/{estimate}/customer-docs', [EstimateController::class, 'customerDocuments'])->middleware('matrix_permission:view_estimates')->name('api.v1.estimates.customer-docs.index');
    Route::post('v1/estimates/{estimate}/customer-docs', [EstimateController::class, 'uploadCustomerDocuments'])->middleware('matrix_permission:edit_estimates')->name('api.v1.estimates.customer-docs.store');
    Route::delete('v1/estimates/{estimate}/customer-docs/{docIndex}', [EstimateController::class, 'deleteCustomerDocument'])->middleware('matrix_permission:edit_estimates')->name('api.v1.estimates.customer-docs.destroy');

    Route::apiResource('services', ServiceController::class)->middleware('matrix_permission:view_services')->names([
        'index' => 'api.services.index',
        'store' => 'api.services.store',
        'show' => 'api.services.show',
        'update' => 'api.services.update',
        'destroy' => 'api.services.destroy',
    ]);

    Route::get('documents', [DocumentController::class, 'index'])->name('api.documents');
    Route::post('documents', [DocumentController::class, 'store']);
    Route::get('documents/{document}', [DocumentController::class, 'show']);
    Route::put('documents/{document}', [DocumentController::class, 'update']);
    Route::patch('documents/{document}', [DocumentController::class, 'update']);
    Route::delete('documents/{document}', [DocumentController::class, 'destroy']);

    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'apiShow'])->name('api.profile.show');
    Route::post('profile', [\App\Http\Controllers\ProfileController::class, 'apiUpdate'])->name('api.profile.update');
    Route::post('profile/password', [\App\Http\Controllers\ProfileController::class, 'apiUpdatePassword'])->name('api.profile.password.update');

    Route::get('users', [UserController::class, 'index'])->middleware('main_admin')->name('api.users.index');
    Route::post('users', [UserController::class, 'store'])->middleware('main_admin')->name('api.users.store');
    Route::get('users/search', [UserController::class, 'search'])->name('api.users.search');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware('main_admin')->name('api.users.show');
    Route::put('users/{user}', [UserController::class, 'update'])->middleware('main_admin')->name('api.users.update');
    Route::patch('users/{user}', [UserController::class, 'update'])->middleware('main_admin');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('main_admin')->name('api.users.destroy');
    Route::patch('users/{user}/status', [UserController::class, 'updateStatus'])->middleware('main_admin')->name('api.users.status');
    Route::put('users/{user}/permissions', [UserController::class, 'updatePermissions'])->middleware('main_admin')->name('api.users.permissions.update');

    Route::prefix('v1')->group(function () {
        Route::get('settings', [SettingController::class, 'apiIndex'])->name('api.settings.index');
        Route::match(['put', 'post'], 'settings', [SettingController::class, 'apiUpdate'])->name('api.settings.update');
    });

    // Meetings module routes
    Route::apiResource('meetings', MeetingController::class)->names([
        'index' => 'api.meetings.index',
        'store' => 'api.meetings.store',
        'show' => 'api.meetings.show',
        'update' => 'api.meetings.update',
        'destroy' => 'api.meetings.destroy',
    ]);
    Route::post('meetings/{meeting}/status', [MeetingController::class, 'updateStatus'])->name('api.meetings.status');

    // Meeting helper routes
    Route::get('meetings/customers', [MeetingController::class, 'getCustomers'])->name('api.meetings.customers');
    Route::get('meetings/users', [MeetingController::class, 'getUsers'])->name('api.meetings.users');

    // Google Calendar routes
    Route::get('meetings/google/auth-status', [MeetingController::class, 'googleAuthStatus'])->name('api.meetings.google.status');
    Route::get('meetings/google/auth-url', [MeetingController::class, 'googleAuthUrl'])->name('api.meetings.google.auth_url');
    Route::post('meetings/google/callback', [MeetingController::class, 'googleCallback'])->name('api.meetings.google.callback');
    Route::post('meetings/google/disconnect', [MeetingController::class, 'googleDisconnect'])->name('api.meetings.google.disconnect');
    Route::get('meetings/google/events', [MeetingController::class, 'googleEvents'])->name('api.meetings.google.events');

    // Meeting sync routes
    Route::post('meetings/{id}/sync-to-google', [MeetingController::class, 'syncToGoogle'])->name('api.meetings.sync_to_google');
    Route::post('meetings/{id}/remove-from-google', [MeetingController::class, 'removeFromGoogle'])->name('api.meetings.remove_from_google');

    // Workflow modules
    Route::prefix('workflow')->group(function () {
        Route::get('stages', [\App\Http\Controllers\WorkflowStageController::class, 'index']);
        Route::post('stages', [\App\Http\Controllers\WorkflowStageController::class, 'store']);
        Route::put('stages/{id}', [\App\Http\Controllers\WorkflowStageController::class, 'update']);
        Route::delete('stages/{id}', [\App\Http\Controllers\WorkflowStageController::class, 'destroy']);
        Route::post('stages/order', [\App\Http\Controllers\WorkflowStageController::class, 'updateOrder']);
        
        Route::get('customer-history/{customerId}', [\App\Http\Controllers\WorkflowController::class, 'customerHistory']);
        Route::apiResource('document-verification', \App\Http\Controllers\Workflow\DocumentVerificationController::class);
        Route::apiResource('registration', \App\Http\Controllers\Workflow\RegistrationController::class);
        Route::apiResource('feasibility', \App\Http\Controllers\Workflow\FeasibilityController::class);
        Route::apiResource('finance', \App\Http\Controllers\Workflow\FinanceController::class);
        Route::apiResource('waiting-floor', \App\Http\Controllers\Workflow\WaitingFloorController::class);
        Route::apiResource('dispatch', \App\Http\Controllers\Workflow\DispatchController::class);
        Route::apiResource('installations', \App\Http\Controllers\Workflow\InstallationController::class);
        Route::post('intimation/{id}/upload', [\App\Http\Controllers\Workflow\IntimationController::class, 'uploadFile']);
        Route::apiResource('intimation', \App\Http\Controllers\Workflow\IntimationController::class);
        Route::apiResource('net-metering', \App\Http\Controllers\Workflow\NetMeteringController::class);
        Route::apiResource('subsidy', \App\Http\Controllers\Workflow\SubsidyController::class);
    }); 

});

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        // Profile
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show']);
            Route::post('update', [ProfileController::class, 'update']);
            Route::post('password', [ProfileController::class, 'updatePassword']);
        });

        // Staff/Users
        Route::apiResource('users', UserController::class)->names([
            'index' => 'api.v1.users.index',
            'store' => 'api.v1.users.store',
            'show' => 'api.v1.users.show',
            'update' => 'api.v1.users.update',
            'destroy' => 'api.v1.users.destroy',
        ]);

        // RBAC
        Route::get('permissions', [RoleController::class, 'permissions']);
        Route::apiResource('roles', RoleController::class)->names([
            'index' => 'api.v1.roles.index',
            'store' => 'api.v1.roles.store',
            'show' => 'api.v1.roles.show',
            'update' => 'api.v1.roles.update',
            'destroy' => 'api.v1.roles.destroy',
        ]);

        // Settings (Moved to session-auth group above)

        // Masters (11 Modules)
        Route::prefix('masters')->group(function () {
            Route::apiResource('countries', Masters\CountryController::class)->names([
                'index' => 'api.v1.masters.countries.index',
                'store' => 'api.v1.masters.countries.store',
                'show' => 'api.v1.masters.countries.show',
                'update' => 'api.v1.masters.countries.update',
                'destroy' => 'api.v1.masters.countries.destroy',
            ]);
            Route::apiResource('cities', Masters\CityController::class)->names([
                'index' => 'api.v1.masters.cities.index',
                'store' => 'api.v1.masters.cities.store',
                'show' => 'api.v1.masters.cities.show',
                'update' => 'api.v1.masters.cities.update',
                'destroy' => 'api.v1.masters.cities.destroy',
            ]);
            Route::apiResource('lead-sources', Masters\LeadSourceController::class)->names([
                'index' => 'api.v1.masters.lead_sources.index',
                'store' => 'api.v1.masters.lead_sources.store',
                'show' => 'api.v1.masters.lead_sources.show',
                'update' => 'api.v1.masters.lead_sources.update',
                'destroy' => 'api.v1.masters.lead_sources.destroy',
            ]);
            Route::apiResource('stages', Masters\StageController::class)->names([
                'index' => 'api.v1.masters.stages.index',
                'store' => 'api.v1.masters.stages.store',
                'show' => 'api.v1.masters.stages.show',
                'update' => 'api.v1.masters.stages.update',
                'destroy' => 'api.v1.masters.stages.destroy',
            ]);
            Route::apiResource('travel-types', Masters\TravelTypeController::class)->names([
                'index' => 'api.v1.masters.travel_types.index',
                'store' => 'api.v1.masters.travel_types.store',
                'show' => 'api.v1.masters.travel_types.show',
                'update' => 'api.v1.masters.travel_types.update',
                'destroy' => 'api.v1.masters.travel_types.destroy',
            ]);
            Route::apiResource('transport-types', Masters\TransportTypeController::class)->names([
                'index' => 'api.v1.masters.transport_types.index',
                'store' => 'api.v1.masters.transport_types.store',
                'show' => 'api.v1.masters.transport_types.show',
                'update' => 'api.v1.masters.transport_types.update',
                'destroy' => 'api.v1.masters.transport_types.destroy',
            ]);
            Route::apiResource('room-categories', Masters\RoomCategoryController::class)->names([
                'index' => 'api.v1.masters.room_categories.index',
                'store' => 'api.v1.masters.room_categories.store',
                'show' => 'api.v1.masters.room_categories.show',
                'update' => 'api.v1.masters.room_categories.update',
                'destroy' => 'api.v1.masters.room_categories.destroy',
            ]);
            Route::apiResource('currencies', Masters\CurrencyController::class)->names([
                'index' => 'api.v1.masters.currencies.index',
                'store' => 'api.v1.masters.currencies.store',
                'show' => 'api.v1.masters.currencies.show',
                'update' => 'api.v1.masters.currencies.update',
                'destroy' => 'api.v1.masters.currencies.destroy',
            ]);
            Route::apiResource('suppliers', Masters\SupplierController::class)->names([
                'index' => 'api.v1.masters.suppliers.index',
                'store' => 'api.v1.masters.suppliers.store',
                'show' => 'api.v1.masters.suppliers.show',
                'update' => 'api.v1.masters.suppliers.update',
                'destroy' => 'api.v1.masters.suppliers.destroy',
            ]);
            Route::apiResource('hotels', Masters\HotelController::class)->names([
                'index' => 'api.v1.masters.hotels.index',
                'store' => 'api.v1.masters.hotels.store',
                'show' => 'api.v1.masters.hotels.show',
                'update' => 'api.v1.masters.hotels.update',
                'destroy' => 'api.v1.masters.hotels.destroy',
            ]);
            Route::apiResource('agents', Masters\AgentController::class)->names([
                'index' => 'api.v1.masters.agents.index',
                'store' => 'api.v1.masters.agents.store',
                'show' => 'api.v1.masters.agents.show',
                'update' => 'api.v1.masters.agents.update',
                'destroy' => 'api.v1.masters.agents.destroy',
            ]);
            // Route::apiResource('customers', CustomerController::class);
        });

        // Additional Core
        Route::post('products/import', [ProductController::class, 'import'])->name('api.v1.products.import');
        Route::apiResource('products', ProductController::class)->names([
            'index' => 'api.v1.products.index',
            'store' => 'api.v1.products.store',
            'show' => 'api.v1.products.show',
            'update' => 'api.v1.products.update',
            'destroy' => 'api.v1.products.destroy',
        ]);
        Route::apiResource('services', ServiceController::class)->names([
            'index' => 'api.v1.services.index',
            'store' => 'api.v1.services.store',
            'show' => 'api.v1.services.show',
            'update' => 'api.v1.services.update',
            'destroy' => 'api.v1.services.destroy',
        ]);
        Route::apiResource('documents', DocumentController::class)->names([
            'index' => 'api.v1.documents.index',
            'store' => 'api.v1.documents.store',
            'show' => 'api.v1.documents.show',
            'update' => 'api.v1.documents.update',
            'destroy' => 'api.v1.documents.destroy',
        ]);
        Route::get('documents/{document}/download', [DocumentController::class, 'download']);

        Route::apiResource('leads', LeadController::class)->names([
            'index' => 'api.v1.leads.index',
            'store' => 'api.v1.leads.store',
            'show' => 'api.v1.leads.show',
            'update' => 'api.v1.leads.update',
            'destroy' => 'api.v1.leads.destroy',
        ]);
        Route::get('/leads/{id}/activities', [FollowUpController::class, 'apiByLead']);
        Route::apiResource('deals', DealController::class)->names([
            'index' => 'api.v1.deals.index',
            'store' => 'api.v1.deals.store',
            'show' => 'api.v1.deals.show',
            'update' => 'api.v1.deals.update',
            'destroy' => 'api.v1.deals.destroy',
        ]);
        Route::apiResource('projects', ProjectController::class)->names([
            'index' => 'api.v1.projects.index',
            'store' => 'api.v1.projects.store',
            'show' => 'api.v1.projects.show',
            'update' => 'api.v1.projects.update',
            'destroy' => 'api.v1.projects.destroy',
        ]);
        // Route::apiResource('meetings', MeetingController::class);
        Route::apiResource('tasks', TaskController::class)->names([
            'index' => 'api.v1.tasks.index',
            'store' => 'api.v1.tasks.store',
            'show' => 'api.v1.tasks.show',
            'update' => 'api.v1.tasks.update',
            'destroy' => 'api.v1.tasks.destroy',
        ]);
        Route::apiResource('pipelines', PipelineController::class)->names([
            'index' => 'api.v1.pipelines.index',
            'store' => 'api.v1.pipelines.store',
            'show' => 'api.v1.pipelines.show',
            'update' => 'api.v1.pipelines.update',
            'destroy' => 'api.v1.pipelines.destroy',
        ]);



        // Follow-ups APIs
        // Route::get('/follow-ups', [FollowUpController::class, 'index']);
        // Route::post('/follow-ups/store', [FollowUpController::class, 'store']);
        // Route::get('/follow-ups/{id}', [FollowUpController::class, 'show']);
        // Route::put('/follow-ups/{id}', [FollowUpController::class, 'update']);
        // Route::patch('/follow-ups/{id}', [FollowUpController::class, 'update']);
        // Route::delete('/follow-ups/{id}', [FollowUpController::class, 'destroy']);

        // SupportTicket module APIs
        Route::apiResource('tickets', SupportTicketController::class)->names([
            'index' => 'api.v1.tickets.index',
            'store' => 'api.v1.tickets.store',
            'show' => 'api.v1.tickets.show',
            'update' => 'api.v1.tickets.update',
            'destroy' => 'api.v1.tickets.destroy',
        ]);
        Route::post('tickets/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('api.v1.tickets.reply');
        Route::patch('tickets/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('api.v1.tickets.status');

        Route::apiResource('invoices', InvoiceController::class)->names([
            'index' => 'api.v1.invoices.index',
            'store' => 'api.v1.invoices.store',
            'show' => 'api.v1.invoices.show',
            'update' => 'api.v1.invoices.update',
            'destroy' => 'api.v1.invoices.destroy',
        ]);
        Route::apiResource('customers', CustomerController::class)->names([
            'index' => 'api.v1.customers.index',
            'store' => 'api.v1.customers.store',
            'show' => 'api.v1.customers.show',
            'update' => 'api.v1.customers.update',
            'destroy' => 'api.v1.customers.destroy',
        ]);

    });
    // PDF Builder API routes
    Route::prefix('pdfbuilderApi')->name('pdfbuilder.api.')->group(function () {
        Route::post('generate', [PdfbuilderApiController::class, 'generate'])->name('generate');
        Route::post('delete/{id}', [PdfbuilderApiController::class, 'delete'])->name('delete');
        Route::post('update/{id}', [PdfbuilderApiController::class, 'update'])->name('update');
        Route::get('pdftemplateview/{id}', [PdfbuilderApiController::class, 'pdftemplateview'])->name('pdftemplateview');
        Route::get('create-form-url', [PdfbuilderApiController::class, 'createForm'])->name('create-form-url');
        Route::get('templet', [PdfbuilderApiController::class, 'index'])->name('templet');
        Route::get('edit-form-url/{id}', [PdfbuilderApiController::class, 'editForm'])->name('edit-form-url');
        Route::get('create-form-html', [PdfbuilderApiController::class, 'createFormHtml'])->name('create-form-html');
        Route::get('createFormHtmlLink', [PdfbuilderApiController::class, 'createFormHtmlLink'])->name('createFormHtmlLink');
        Route::get('editFormHtmlLink', [PdfbuilderApiController::class, 'editFormHtmlLink'])->name('editFormHtmlLink');
        Route::get('edit-form-html/{id}', [PdfbuilderApiController::class, 'editFormHtml'])->name('edit-form-html');
    });

});
