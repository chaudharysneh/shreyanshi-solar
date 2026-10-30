<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\LeadSourceController;
use App\Http\Controllers\LeadStageController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\StageController;
use App\Http\Controllers\TravelTypeController;
use App\Http\Controllers\RoomCategoryController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\TransportTypeController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Api\CustomerController as ApiCustomerController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Api\InvoiceController as ApiInvoiceController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\Api\LeadController as ApiLeadController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\Api\DealController as ApiDealController;
use App\Http\Controllers\Api\PipelineController as ApiPipelineController;
use App\Http\Controllers\Api\Masters\StageController as ApiStageController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\Api\FollowUpController as ApiFollowUpController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\Api\SupportTicketController as ApiSupportTicketController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductInventoryController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\MakeController;
use App\Http\Controllers\UserLogController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\TechnologyController;
use App\Http\Controllers\HandoverPersonController;
use App\Http\Controllers\VendorController;
use App\Http\Controllers\BomProductController;
use App\Http\Controllers\Api\ProductController as ApiProductController;
use App\Http\Controllers\Api\MakeController as ApiMakeController;
use App\Http\Controllers\Api\WarrantyController as ApiWarrantyController;
use App\Http\Controllers\Api\TechnologyController as ApiTechnologyController;
use App\Http\Controllers\Api\BomProductController as ApiBomProductController;
use App\Http\Controllers\Api\TaskController as ApiTaskController;
use App\Http\Controllers\Api\MeetingController as ApiMeetingController;
use App\Http\Controllers\Api\ProjectController as ApiProjectController;
use App\Http\Controllers\Api\UserController as ApiUserController;
use App\Http\Controllers\Api\DocumentController as ApiDocumentController;
use App\Http\Controllers\Api\ProductCategoryController as ApiProductCategoryController;
use App\Http\Controllers\Api\StatusHistoryController as ApiStatusHistoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Api\ServiceController as ApiServiceController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TourPackageController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\WhatsappConfigController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Http\Controllers\DefaultEmailTemplateController;
use App\Http\Controllers\EmailMarketingTemplateController;
use App\Http\Controllers\SmsMarketingController;
use App\Http\Controllers\FinanceMasterController;
use App\Http\Controllers\InstallationMasterController;
use App\Http\Controllers\PdfbuilderController;



use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Custom CRM routes with a bespoke login flow.
|
*/

Route::get('/', [AuthController::class, 'showLoginForm'])->middleware(['guest', 'no.cache'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/whatsapp-configration/webhook', [WhatsappWebhookController::class, 'verify']);
Route::post('/whatsapp-configration/webhook', [WhatsappWebhookController::class, 'handle']);

// Google Calendar OAuth routes - must be outside auth middleware
Route::get('/auth/google', [MeetingController::class, 'redirectToGoogle'])->name('google.auth');
Route::get('/auth/google/callback', [MeetingController::class, 'handleGoogleCallback'])->name('google.callback');
Route::get('/google/calendar/callback', [MeetingController::class, 'handleGoogleCallback']); // Alias for user's configured URI

Route::middleware(['auth', 'no.cache'])->group(function () {
    Route::redirect('/sms-marketing', '/marketing/sms-marketing');
    Route::redirect('/sms-marketing/logs', '/marketing/sms-marketing/logs');
    Route::redirect('/sms-marketing/templates/create', '/marketing/sms-marketing/templates/create');
    Route::redirect('/sms-marketing/templates/{sms_template}/edit', '/marketing/sms-marketing/templates/{sms_template}/edit');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/masters/customers/{customer}/form-documents/{document}', [\App\Http\Controllers\CustomerFormDocumentController::class, 'show'])->whereNumber('document')->name('customers.form-documents.show');
    Route::get('/masters/customers/{customer}/form-documents/{document}/versions/{version}/download', [\App\Http\Controllers\CustomerFormDocumentController::class, 'download'])->whereNumber(['document','version'])->name('customers.form-documents.download');
    Route::prefix('dashboard/documents')->controller(\App\Http\Controllers\DashboardDocumentController::class)->group(function () {
        Route::get('/customers', 'customers')->name('dashboard.documents.customers');
        Route::post('/customers', 'createCustomer')->middleware('throttle:20,1');
        Route::get('/', 'index')->name('dashboard.documents.index');
        Route::post('/', 'save')->middleware('throttle:30,1');
        Route::get('/{id}/aadhaar', 'aadhaar')->whereNumber('id');
        Route::get('/{id}', 'show')->whereNumber('id');
    });
    Route::post('/dashboard/documents/solar/export', [\App\Http\Controllers\SolarDocumentController::class, 'export'])->middleware('throttle:20,1')->name('dashboard.solar.export');
    Route::post('/dashboard/documents/subsidy-bond/export', [\App\Http\Controllers\SubsidyBondController::class, 'export'])->middleware('throttle:20,1')->name('dashboard.subsidy-bond.export');
    Route::post('/dashboard/filters', [DashboardController::class, 'saveFilters'])->name('dashboard.filters');

    Route::get('/user-logs', [UserLogController::class, 'index'])->middleware('main_admin')->name('user-logs.index');
    Route::delete('/user-logs/{notification}', [UserLogController::class, 'destroy'])->middleware('main_admin')->name('user-logs.destroy');
    Route::delete('/user-logs', [UserLogController::class, 'destroyAll'])->middleware('main_admin')->name('user-logs.destroy_all');

    // Notifications & Push Subscriptions
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/list', [NotificationController::class, 'index'])->name('list');
        Route::get('/poll', [NotificationController::class, 'poll'])->name('poll');
        Route::patch('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::delete('/delete-all', [NotificationController::class, 'deleteAll'])->name('deleteAll');
        Route::delete('/{id}', [NotificationController::class, 'deleteNotification'])->name('delete');
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/subscribe', [NotificationController::class, 'subscribe'])->name('subscribe');
        Route::get('/vapid-key', [NotificationController::class, 'vapidKey'])->name('vapid-key');
    });

    // Core CRM web routes (view only)
    // Route::resource('leads', LeadController::class)->except(['store', 'update', 'destroy'])->middleware('matrix_permission:view_leads');
    Route::get('/leads', [LeadController::class, 'index'])->middleware('matrix_permission:view_leads')->name('leads.index');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->middleware('matrix_permission:edit_leads')->name('leads.edit');
    Route::get('/leads/create', [LeadController::class, 'create'])->middleware('matrix_permission:create_leads')->name('leads.create');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware('matrix_permission:view_leads')->name('leads.show');

    Route::get('leads-export', [LeadController::class, 'export'])->middleware('matrix_permission:view_leads')->name('leads.export');
    Route::post('leads/{lead}/convert', [LeadController::class, 'convertToCustomer'])->middleware('matrix_permission:edit_leads')->name('leads.convert');
    Route::get('leads/{lead}/image', [LeadController::class, 'image'])->middleware('matrix_permission:view_leads')->name('leads.image');

    Route::resource('deals', DealController::class)->except(['store', 'update', 'destroy'])->middleware('matrix_permission:view_deals');
    Route::get('deals-export', [DealController::class, 'export'])->middleware('matrix_permission:view_deals')->name('deals.export');
    Route::get('deals/{id}/pdf', [DealController::class, 'pdf'])->middleware('matrix_permission:view_deals')->name('deals.pdf');
    Route::get('deals/{id}/signature-image', [DealController::class, 'signatureImage'])->middleware('matrix_permission:view_deals')->name('deals.signature.image');
    Route::get('/pipeline', [PipelineController::class, 'index'])->middleware('matrix_permission:view_pipeline')->name('pipeline.index');
    Route::get('pipeline-export', [PipelineController::class, 'export'])->middleware('matrix_permission:view_pipeline')->name('pipeline.export');
    Route::get('/pipeline/create', [DealController::class, 'pipelineCreate'])->middleware('matrix_permission:create_pipeline')->name('pipeline.create');
    Route::get('/pipeline/{pipeline}/edit', [PipelineController::class, 'edit'])->middleware('matrix_permission:edit_pipeline')->name('pipeline.edit');
    Route::get('/pipeline/{pipeline}', [PipelineController::class, 'show'])->middleware('matrix_permission:view_pipeline')->name('pipeline.show');
    Route::patch('deals/{deal}/status', [DealController::class, 'updateStatus'])->middleware('matrix_permission:edit_deals')->name('deals.status');

    // Web routes for views (redirects to API)
    Route::get('/projects', [ProjectController::class, 'index'])->middleware('matrix_permission:view_projects')->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->middleware('matrix_permission:create_projects')->name('projects.create');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->middleware('matrix_permission:view_projects')->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->middleware('matrix_permission:edit_projects')->name('projects.edit');
    Route::get('projects-export', [ProjectController::class, 'export'])->middleware('matrix_permission:view_projects')->name('projects.export');

    // meetings module routes
    Route::get('/meetings', [MeetingController::class, 'index'])->middleware('matrix_permission:view_meetings')->name('meetings.index');
    Route::get('meetings-export', [MeetingController::class, 'export'])->middleware('matrix_permission:view_meetings')->name('meetings.export');
    Route::get('/meetings/create', [MeetingController::class, 'create'])->middleware('matrix_permission:create_meetings')->name('meetings.create');
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])->middleware('matrix_permission:view_meetings')->name('meetings.show');
    Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->middleware('matrix_permission:edit_meetings')->name('meetings.edit');

    // Settings web routes
    Route::get('/settings', [SettingController::class, 'index'])->middleware('main_admin')->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->middleware('main_admin')->name('settings.update');
    Route::post('/settings/toggle-integration', [SettingController::class, 'toggleIntegration'])->middleware('main_admin')->name('settings.toggle_integration');
    
    // Table Truncate routes
    Route::post('/settings/table-truncate-all', [\App\Http\Controllers\TableTruncateController::class, 'truncateAll'])->middleware('main_admin')->name('settings.table-truncate-all');
    Route::post('/settings/table-truncate/{table}', [\App\Http\Controllers\TableTruncateController::class, 'truncate'])->middleware('main_admin')->name('settings.table-truncate');

    // Tax management routes
    Route::post('/settings/taxes', [SettingController::class, 'storeTax'])->middleware('main_admin')->name('settings.taxes.store');
    Route::put('/settings/taxes/{tax}', [SettingController::class, 'updateTax'])->middleware('main_admin')->name('settings.taxes.update');
    Route::delete('/settings/taxes/{tax}', [SettingController::class, 'destroyTax'])->middleware('main_admin')->name('settings.taxes.destroy');
    Route::get('/api/taxes', [SettingController::class, 'getTaxes'])->middleware('main_admin')->name('api.taxes.index');

    // Subsidy management routes
    Route::put('/settings/subsidies/{subsidy}', [SettingController::class, 'updateSubsidy'])->middleware('main_admin')->name('settings.subsidies.update');
    Route::get('/api/subsidies', [SettingController::class, 'getSubsidies'])->middleware('main_admin')->name('api.subsidies.index');


    Route::resource('tasks', TaskController::class)->only(['index', 'create', 'show', 'edit'])->middleware('matrix_permission:view_tasks');
    Route::get('tasks/{task}/workflow-events/{event}/attachments/{attachment}', [TaskController::class, 'workflowAttachment'])->whereNumber('event')->whereNumber('attachment')->middleware('matrix_permission:view_tasks')->name('tasks.workflow-attachment');
    Route::get('tasks-export', [TaskController::class, 'export'])->middleware('matrix_permission:view_tasks')->name('tasks.export');

    // Settings & Dynamic Form Builder
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
        Route::resource('custom-fields', CustomFieldController::class);
    });

    Route::resource('follow-ups', FollowUpController::class)
        ->only(['index', 'create', 'show', 'edit'])
        ->names('followups')
        ->middleware('matrix_permission:view_followups');
    Route::get('/follow-ups-export', [FollowUpController::class, 'export'])->middleware('matrix_permission:view_followups')->name('followups.export');
    Route::post('follow-ups/{followup}/toggle', [FollowUpController::class, 'toggle'])->middleware('matrix_permission:edit_followups')->name('followups.toggle');


    Route::resource('packages', TourPackageController::class);
    Route::resource('quotations', QuotationController::class);
    Route::post('quotations/{quotation}/convert', [QuotationController::class, 'convertToBooking'])->name('quotations.convert');

    // Bookings module routes
    Route::resource('bookings', BookingController::class);
    Route::post('bookings/{booking}/amend', [BookingController::class, 'amend'])->name('bookings.amend');

    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::resource('invoices', InvoiceController::class)->middleware('matrix_permission:view_invoices');
    // update status
    Route::post('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus'])->middleware('matrix_permission:edit_invoices')->name('invoices.status');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->middleware('matrix_permission:view_invoices')->name('invoices.pdf');
    Route::get('invoices-export', [InvoiceController::class, 'export'])->middleware('matrix_permission:view_invoices')->name('invoices.export');

    Route::get('bookings/{booking}/invoice/create', [InvoiceController::class, 'createByBooking'])->name('bookings.invoice.create');

    // Travel Operations & Financials
    Route::get('packages/{package}/itinerary', [ItineraryController::class, 'editByPackage'])->name('packages.itinerary');
    Route::post('packages/{package}/itinerary', [ItineraryController::class, 'updateByPackage'])->name('packages.itinerary.update');

    Route::get('quotations/{quotation}/itinerary', [ItineraryController::class, 'editByQuotation'])->name('quotations.itinerary');
    Route::post('quotations/{quotation}/itinerary', [ItineraryController::class, 'updateByQuotation'])->name('quotations.itinerary.update');

    Route::get('bookings/{booking}/itinerary', [ItineraryController::class, 'editByBooking'])->name('bookings.itinerary');
    Route::post('bookings/{booking}/itinerary', [ItineraryController::class, 'updateByBooking'])->name('bookings.itinerary.update');

    Route::post('checklists/{checklist}/toggle', [BookingController::class, 'toggleChecklist'])->name('checklists.toggle');

    // Refunds & Cancellations
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('bookings/{booking}/refunds', [BookingController::class, 'refunds'])->name('bookings.refunds');
    Route::post('bookings/{booking}/refunds', [BookingController::class, 'storeRefund'])->name('bookings.refunds.store');

    // Supplier Payments & Costs
    Route::get('suppliers/{supplier}/payables', [SupplierController::class, 'payables'])->name('suppliers.payables');
    Route::post('payables/{payable}/payments', [SupplierController::class, 'storePayment'])->name('payables.payments.store');

    Route::get('bookings/{booking}/costs', [BookingController::class, 'costs'])->name('bookings.costs');
    Route::post('bookings/{booking}/costs', [BookingController::class, 'storeCost'])->name('bookings.costs.store');
    Route::delete('costs/{payable}', [BookingController::class, 'destroyCost'])->name('bookings.costs.destroy');

    Route::get('bookings/{booking}/voucher', [BookingController::class, 'voucher'])->name('bookings.voucher');

    Route::get('finance-banks', [\App\Http\Controllers\FinanceBankController::class,'index'])->middleware('matrix_permission:view_invoices');
    Route::post('finance-banks', [\App\Http\Controllers\FinanceBankController::class,'store'])->middleware('matrix_permission:edit_invoices');
    Route::get('invoice-receivables', [\App\Http\Controllers\InvoiceFinanceController::class,'index'])->middleware('matrix_permission:view_invoices')->name('invoices.receivables');
    Route::get('invoices/{invoice}/finance', [\App\Http\Controllers\InvoiceFinanceController::class,'show'])->middleware('matrix_permission:view_invoices');
    Route::post('invoices/{invoice}/finance', [\App\Http\Controllers\InvoiceFinanceController::class,'store'])->middleware('matrix_permission:edit_invoices');
    Route::post('invoice-payments/{payment}/void', [\App\Http\Controllers\InvoiceFinanceController::class,'void'])->middleware('matrix_permission:edit_invoices');
    Route::resource('payments', PaymentController::class)->only(['index', 'store', 'destroy']);
    Route::get('invoices/{invoice}/payments', [PaymentController::class, 'indexByInvoice'])->name('invoices.payments');

    // Support Ticket routes (page-only web + API CRUD)
    Route::resource('tickets', SupportTicketController::class)->only(['index', 'create', 'show', 'edit'])->middleware('matrix_permission:view_tickets');
    Route::get('tickets-export', [SupportTicketController::class, 'export'])->middleware('matrix_permission:view_tickets')->name('tickets.export');
    Route::get('support-export', [SupportTicketController::class, 'export'])->middleware('matrix_permission:view_tickets')->name('supportticket.export');


    // Additional Core
    Route::get('products-export', [ProductController::class, 'export'])->middleware('matrix_permission:view_products')->name('products.export');
    Route::get('all-products', [ProductController::class, 'index'])->middleware('matrix_permission:view_products')->name('all-products.index');
    Route::prefix('products')->name('products.')->group(function () {
        Route::resource('categories', ProductCategoryController::class)
            ->names('categories')
            ->only(['index']);
    });
    Route::get('products/{product}/image', [ProductController::class, 'image'])->middleware('matrix_permission:view_products')->name('products.image');
    Route::resource('products', ProductController::class)->only(['index', 'create', 'edit'])->middleware('matrix_permission:view_products');
    Route::get('products/{product}', [ProductController::class, 'show'])->middleware('matrix_permission:view_products')->name('products.show');

    // Inventory Routes
    Route::get('inventory', [ProductInventoryController::class, 'index'])->middleware('matrix_permission:view_inventory')->name('inventory.index');
    Route::get('inventory/history/{product}', [ProductInventoryController::class, 'history'])->middleware('matrix_permission:view_inventory')->name('inventory.history');

    // Purchase Routes
    Route::redirect('purchase', 'purchases');
    Route::get('purchases', [PurchaseController::class, 'index'])->middleware('matrix_permission:view_products')->name('purchases.index');
    Route::get('purchases-export', [PurchaseController::class, 'export'])->middleware('matrix_permission:view_products')->name('purchases.export');
    Route::get('purchases/create', [PurchaseController::class, 'create'])->middleware('matrix_permission:create_products')->name('purchases.create');
    Route::get('purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->middleware('matrix_permission:edit_products')->name('purchases.edit');
    Route::get('purchases/{purchase}/pdf', [PurchaseController::class, 'downloadPdf'])->middleware('matrix_permission:view_products')->name('purchases.pdf');
    Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->middleware('matrix_permission:view_products')->name('purchases.show');

    // Sales Routes
    Route::redirect('sale', 'sales');
    Route::get('sales', [SalesController::class, 'index'])->middleware('matrix_permission:view_products')->name('sales.index');
    Route::get('sales-export', [SalesController::class, 'export'])->middleware('matrix_permission:view_products')->name('sales.export');
    Route::get('sales/create', [SalesController::class, 'create'])->middleware('matrix_permission:create_products')->name('sales.create');
    Route::get('sales/{sale}/edit', [SalesController::class, 'edit'])->middleware('matrix_permission:edit_products')->name('sales.edit');
    Route::get('sales/{sale}/pdf', [SalesController::class, 'downloadPdf'])->middleware('matrix_permission:view_products')->name('sales.pdf');
    Route::get('sales/{sale}', [SalesController::class, 'show'])->middleware('matrix_permission:view_products')->name('sales.show');

    // Estimates Routes
    Route::redirect('estimate', 'estimates');
    Route::get('estimates', [EstimateController::class, 'index'])->middleware('matrix_permission:view_estimates')->name('estimates.index');
    Route::get('estimates/create', [EstimateController::class, 'create'])->middleware('matrix_permission:create_estimates')->name('estimates.create');
    Route::get('estimates/{estimate}/edit', [EstimateController::class, 'edit'])->middleware('matrix_permission:edit_estimates')->name('estimates.edit');
    Route::get('estimates/{estimate}/pdf', [EstimateController::class, 'generate_estimate_pdf'])->middleware('matrix_permission:view_estimates')->name('estimates.pdf');
    Route::get('estimates/preview/qt-000150-pdf', [EstimateController::class, 'preview_qt_000150_pdf'])->name('estimates.preview.qt-000150');
    Route::get('estimates/{estimate}/qt-000150-pdf', [EstimateController::class, 'generate_qt_000150_pdf'])->middleware('matrix_permission:view_estimates')->name('estimates.qt-000150');
    Route::get('estimates/{estimate}/customer-docs/{docIndex}/download', [EstimateController::class, 'downloadCustomerDocument'])->name('estimates.customer-docs.download');
    Route::get('estimates/{estimate}', [EstimateController::class, 'show'])->middleware('matrix_permission:view_estimates')->name('estimates.show');

    Route::get('all_product', [BomProductController::class, 'index'])->middleware('matrix_permission:view_bom')->name('bom-products.index');
    Route::get('add-product', [BomProductController::class, 'create'])->middleware('matrix_permission:create_bom')->name('bom-products.create');
    Route::get('all_product/{bomProduct}', [BomProductController::class, 'show'])->middleware('matrix_permission:view_bom')->name('bom-products.show');
    Route::get('all_product/{bomProduct}/edit', [BomProductController::class, 'edit'])->middleware('matrix_permission:edit_bom')->name('bom-products.edit');
    Route::get('all_product/{bomProduct}/image', [BomProductController::class, 'image'])->middleware('matrix_permission:view_bom')->name('bom-products.image');
    Route::get('all-categories', [CategoriesController::class, 'index'])->middleware('matrix_permission:view_categories')->name('categories.index');
    Route::get('all-categories/{category}/image', [CategoriesController::class, 'image'])->middleware('matrix_permission:view_categories')->name('categories.image');
    Route::get('make', [MakeController::class, 'index'])->middleware('matrix_permission:view_make')->name('make.index');
    Route::get('make/{make}/image', [MakeController::class, 'image'])->middleware('matrix_permission:view_make')->name('makes.image');
    Route::get('warranty', [WarrantyController::class, 'index'])->middleware('matrix_permission:view_warranty')->name('warranty.index');
    Route::get('technology', [TechnologyController::class, 'index'])->middleware('matrix_permission:view_technology')->name('technology.index');
    Route::get('all-vendor', [VendorController::class, 'index'])->middleware('matrix_permission:view_vendors')->name('vendors.index');
    Route::get('all-vendor/export', [VendorController::class, 'export'])->middleware('matrix_permission:view_vendors')->name('vendors.export');
    Route::get('all-vendor/{vendor}', [VendorController::class, 'show'])->middleware('matrix_permission:view_vendors')->name('vendors.show');
    Route::get('add-vendor', [VendorController::class, 'create'])->middleware('matrix_permission:create_vendors')->name('vendors.create');
    Route::get('add-vendor/{vendor}/edit', [VendorController::class, 'edit'])->middleware('matrix_permission:edit_vendors')->name('vendors.edit');
    Route::get('vendors/{vendor}/image', [VendorController::class, 'image'])->middleware('matrix_permission:view_vendors')->name('vendors.image');
    Route::get('add-handover-person', [HandoverPersonController::class, 'index'])->middleware('matrix_permission:view_handover_persons')->name('handover-persons.index');
    Route::get('services-export', [ServiceController::class, 'export'])->middleware('matrix_permission:view_services')->name('services.export');
    Route::resource('services', ServiceController::class)->only(['index', 'create', 'show', 'edit'])->middleware('matrix_permission:view_services');

    Route::get('masters/customers/{customer}/related-documents/{documentKey}', [CustomerController::class, 'downloadRelatedDocument'])->middleware('matrix_permission:view_customers')->where('documentKey', '[a-f0-9]{64}')->name('masters.customers.related-documents.download');
    Route::get('partners/export', [\App\Http\Controllers\PartnerController::class, 'export'])->middleware('matrix_permission:view_partners')->name('partners.export');
    Route::post('partners/{partner}/payments', [\App\Http\Controllers\PartnerController::class, 'recordPayment'])->middleware('matrix_permission:edit_partners')->name('partners.payments.store');
    Route::post('partners/{partner}/payments/{payment}/void', [\App\Http\Controllers\PartnerController::class, 'voidPayment'])->middleware('matrix_permission:edit_partners')->name('partners.payments.void');
    Route::get('partners/{partner}/agreement', [\App\Http\Controllers\PartnerController::class, 'agreement'])->middleware('matrix_permission:view_partners')->name('partners.agreement');
    Route::resource('partners', \App\Http\Controllers\PartnerController::class)->only(['create', 'store'])->middleware('matrix_permission:create_partners');
    Route::resource('partners', \App\Http\Controllers\PartnerController::class)->only(['edit', 'update'])->middleware('matrix_permission:edit_partners');
    Route::resource('partners', \App\Http\Controllers\PartnerController::class)->only(['destroy'])->middleware('matrix_permission:delete_partners');
    Route::resource('partners', \App\Http\Controllers\PartnerController::class)->only(['index', 'show'])->middleware('matrix_permission:view_partners');
    Route::get('masters/customers/{customer}/customer-docs', [CustomerController::class, 'customerDocuments'])->middleware('matrix_permission:view_customers')->name('masters.customers.docs.index');
    Route::post('masters/customers/{customer}/customer-docs', [CustomerController::class, 'uploadCustomerDocuments'])->middleware('matrix_permission:edit_customers')->name('masters.customers.docs.store');
    Route::delete('masters/customers/{customer}/related-documents/{documentKey}', [CustomerController::class, 'deleteRelatedDocument'])->middleware('matrix_permission:view_customers')->where('documentKey', '[a-f0-9]{64}')->name('masters.customers.related-documents.destroy');
    Route::resource('documents', DocumentController::class)->only(['index', 'create', 'edit']);
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::get('/profile/company-logo-image', [ProfileController::class, 'companyLogoImage'])->name('profile.company_logo.image');
    Route::get('/profile/sidebar-icon-image', [ProfileController::class, 'sidebarIconImage'])->name('profile.sidebar_icon.image');
    Route::get('/profile/company-qr-code-image', [ProfileController::class, 'companyQrCodeImage'])->name('profile.company_qr_code.image');
    Route::get('/profile/company-signature-image', [ProfileController::class, 'companySignatureImage'])->name('profile.company_signature.image');

    // Users & Roles
    Route::resource('users', UserController::class)->except(['store', 'update', 'destroy'])->middleware('main_admin');
    Route::get('users/{user}/image', [UserController::class, 'image'])->middleware('auth')->name('users.image');
    Route::post('/users/import', [UserController::class, 'import'])->middleware('main_admin')->name('users.import');
    Route::get('users-export', [UserController::class, 'export'])->middleware('main_admin')->name('users.export');
    Route::resource('roles', RoleController::class);

    Route::group(['prefix' => 'masters', 'as' => 'masters.'], function () {
        Route::resource('countries', CountryController::class)->except(['show']);
        Route::resource('cities', CityController::class)->except(['show']);
        Route::resource('lead-sources', LeadSourceController::class)->names('lead_sources')->except(['show']);
        Route::resource('finance-masters', FinanceMasterController::class)->names('finance_masters')->except(['show']);
        Route::resource('installation-masters', InstallationMasterController::class)->names('installation_masters')->except(['show']);
        Route::get('stages', [StageController::class, 'index'])->middleware('matrix_permission:view_stages')->name('stages.index');

        Route::resource('travel-types', TravelTypeController::class)->names('travel_types')->except(['show']);
        Route::resource('room-categories', RoomCategoryController::class)->names('room_categories')->except(['show']);
        Route::resource('product-categories', ProductCategoryController::class)->names('product_categories')->only(['index']);
        Route::resource('transport-types', TransportTypeController::class)->names('transport_types')->except(['show']);
        Route::resource('currencies', CurrencyController::class)->names('currencies')->except(['show']);
        Route::resource('suppliers', SupplierController::class)->names('suppliers')->except(['show']);
        Route::resource('hotels', HotelController::class)->names('hotels')->except(['show']);
        Route::resource('agents', AgentController::class)->names('agents')->except(['show']);
        
        // Route::resource('customers', CustomerController::class)->names('customers')->except(['store', 'update', 'destroy']);
        Route::get('/customers', [CustomerController::class, 'index'])->middleware('matrix_permission:view_customers')->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->middleware('matrix_permission:create_customers')->name('customers.create');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('matrix_permission:view_customers')->name('customers.show');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->middleware('matrix_permission:edit_customers')->name('customers.edit');
        Route::patch('/customers/{customer}/pipeline-stage', [CustomerController::class, 'updatePipelineStage'])->middleware('matrix_permission:edit_customers')->name('customers.pipeline_stage');
        Route::get('/customers/{customer}/image', [CustomerController::class, 'image'])->middleware('matrix_permission:view_customers')->name('customers.image');
        Route::get('customers-export', [CustomerController::class, 'export'])->name('customers.export');

        
        Route::get('cities-by-country/{country}', [CityController::class, 'apiByCountry'])->name('cities.by_country');

        Route::resource('default-email-templates', DefaultEmailTemplateController::class)->names('default_email_templates');
        Route::post('default-email-templates/{email_template}/default', [DefaultEmailTemplateController::class, 'setDefault'])
            ->name('default_email_templates.set_default');
        Route::get('default-email-templates-list', [DefaultEmailTemplateController::class, 'apiIndex'])
            ->name('default_email_templates.api_index');
    });

    // Reports & Analytics
    Route::group(['prefix' => 'reports', 'as' => 'reports.'], function () {
        Route::get('/', [ReportController::class, 'index'])->middleware('matrix_permission:view_reports')->name('index');
        // report route
        Route::get('/customers_report', [ReportController::class, 'customersReport'])->middleware('matrix_permission:view_reports')->name('customers');
        Route::get('/leads_report', [ReportController::class, 'leadsReport'])->middleware('matrix_permission:view_reports')->name('leads');
        Route::get('/leads_report/export', [ReportController::class, 'leadsExport'])->middleware('matrix_permission:view_reports')->name('leads_report.export');

        Route::get('/deals_report', [ReportController::class, 'dealsReport'])->middleware('matrix_permission:view_reports')->name('deals');
        Route::get('/deals_report/export', [ReportController::class, 'dealsExport'])->middleware('matrix_permission:view_reports')->name('deals_report.export');

        Route::get('/projects_report', [ReportController::class, 'projectsReport'])->middleware('matrix_permission:view_reports')->name('projects');
        Route::get('/projects_report/export', [ReportController::class, 'projectsExport'])->middleware('matrix_permission:view_reports')->name('projects_report.export');

        Route::get('/tasks_report', [ReportController::class, 'tasksReport'])->middleware('matrix_permission:view_reports')->name('tasks');
        Route::get('/tasks_report/export', [ReportController::class, 'tasksExport'])->middleware('matrix_permission:view_reports')->name('tasks_report.export');

        Route::get('/followups_report', [ReportController::class, 'followupsReport'])->middleware('matrix_permission:view_reports')->name('followups');
        Route::get('/followups_report/export', [ReportController::class, 'followupsExport'])->middleware('matrix_permission:view_reports')->name('followups_report.export');

        Route::get('/profit-loss', [ReportController::class, 'profitAndLoss'])->middleware('matrix_permission:view_reports')->name('profit_loss');
        Route::get('/sales', [ReportController::class, 'salesPerformance'])->middleware('matrix_permission:view_reports')->name('sales');
        Route::get('/pending', [ReportController::class, 'pendingAccounts'])->middleware('matrix_permission:view_reports')->name('pending');
    });

    // Old Report Routes (without /reports/ prefix) - Backward Compatibility
    Route::get('/customers_report', [ReportController::class, 'customersReport'])->middleware('matrix_permission:view_reports')->name('customers_report_old');
    Route::get('/leads_report', [ReportController::class, 'leadsReport'])->middleware('matrix_permission:view_reports')->name('leads_report_old');
    Route::get('/leads_report/export', [ReportController::class, 'leadsExport'])->middleware('matrix_permission:view_reports')->name('leads_report_export_old');
    Route::get('/deals_report', [ReportController::class, 'dealsReport'])->middleware('matrix_permission:view_reports')->name('deals_report_old');
    Route::get('/deals_report/export', [ReportController::class, 'dealsExport'])->middleware('matrix_permission:view_reports')->name('deals_report_export_old');
    Route::get('/projects_report', [ReportController::class, 'projectsReport'])->middleware('matrix_permission:view_reports')->name('projects_report_old');
    Route::get('/projects_report/export', [ReportController::class, 'projectsExport'])->middleware('matrix_permission:view_reports')->name('projects_report_export_old');
    Route::get('/tasks_report', [ReportController::class, 'tasksReport'])->middleware('matrix_permission:view_reports')->name('tasks_report_old');
    Route::get('/tasks_report/export', [ReportController::class, 'tasksExport'])->middleware('matrix_permission:view_reports')->name('tasks_report_export_old');
    Route::get('/followups_report', [ReportController::class, 'followupsReport'])->middleware('matrix_permission:view_reports')->name('followups_report_old');
    Route::get('/followups_report/export', [ReportController::class, 'followupsExport'])->middleware('matrix_permission:view_reports')->name('followups_report_export_old');

    // Route aliases for cached views (fix for stale compiled views)
    Route::get('/reports/leads', [ReportController::class, 'leadsReport'])->middleware('matrix_permission:view_reports')->name('reports.leads');
    Route::get('/reports/deals', [ReportController::class, 'dealsReport'])->middleware('matrix_permission:view_reports')->name('reports.deals');
    Route::get('/reports/customers', [ReportController::class, 'customersReport'])->middleware('matrix_permission:view_reports')->name('reports.customers_alias');
    Route::get('/reports/tasks', [ReportController::class, 'tasksReport'])->middleware('matrix_permission:view_reports')->name('reports.tasks');
    Route::get('/reports/followups', [ReportController::class, 'followupsReport'])->middleware('matrix_permission:view_reports')->name('reports.followups');

    // Operations
    Route::group(['prefix' => 'operations', 'as' => 'operations.'], function () {
        Route::get('/rooming-list', [OperationsController::class, 'roomingList'])->name('rooming_list');
        Route::get('/driver-sheet', [OperationsController::class, 'driverSheet'])->name('driver_sheet');
    });

    // Marketing Automation
    Route::group(['prefix' => 'marketing', 'as' => 'marketing.'], function () {
        Route::get('/dashboard', [MarketingController::class, 'dashboard'])->middleware('matrix_permission:view_email')->name('dashboard');
        Route::get('/templates', [MarketingController::class, 'templatesIndex'])->middleware('matrix_permission:view_email')->name('templates.index');
        Route::get('/templates/create', [MarketingController::class, 'templatesCreate'])->middleware('matrix_permission:create_email')->name('templates.create');
        Route::post('/templates', [MarketingController::class, 'templatesStore'])->middleware('matrix_permission:create_email')->name('templates.store');
        Route::get('/templates/{template}', [MarketingController::class, 'templatesShow'])->middleware('matrix_permission:view_email')->name('templates.show');
        Route::get('/templates/{template}/edit', [MarketingController::class, 'templatesEdit'])->middleware('matrix_permission:edit_email')->name('templates.edit');
        Route::put('/templates/{template}', [MarketingController::class, 'templatesUpdate'])->middleware('matrix_permission:edit_email')->name('templates.update');
        Route::delete('/templates/{template}', [MarketingController::class, 'templatesDestroy'])->middleware('matrix_permission:delete_email')->name('templates.destroy');

        // Bulk mail send
        Route::post('/templates/bulk-send', [MarketingController::class, 'bulkSendMail'])->name('templates.bulk_send');

        Route::get('/campaigns', [MarketingController::class, 'campaignsIndex'])->name('campaigns.index');
        Route::get('/campaigns/create', [MarketingController::class, 'campaignsCreate'])->name('campaigns.create');
        Route::post('/campaigns', [MarketingController::class, 'campaignsStore'])->name('campaigns.store');
        Route::post('/campaigns/{campaign}/send', [MarketingController::class, 'sendCampaign'])->name('campaigns.send');
        Route::delete('/campaigns/{campaign}', [MarketingController::class, 'campaignsDestroy'])->name('campaigns.destroy');

        Route::resource('email-marketing-templates', EmailMarketingTemplateController::class)
            ->names('email_marketing_templates');

        // SMS Marketing
        Route::get('/sms-marketing', [SmsMarketingController::class, 'index'])->name('sms_marketing.index');
        Route::get('/sms-marketing/logs', [SmsMarketingController::class, 'logs'])->name('sms_marketing.logs');
        Route::get('/sms-marketing/templates/create', [SmsMarketingController::class, 'createTemplate'])->name('sms_marketing.templates.create');
        Route::post('/sms-marketing/templates', [SmsMarketingController::class, 'storeTemplate'])->name('sms_marketing.templates.store');
        Route::get('/sms-marketing/templates/{sms_template}/edit', [SmsMarketingController::class, 'editTemplate'])->name('sms_marketing.templates.edit');
        Route::put('/sms-marketing/templates/{sms_template}', [SmsMarketingController::class, 'updateTemplate'])->name('sms_marketing.templates.update');
        Route::delete('/sms-marketing/templates/{sms_template}', [SmsMarketingController::class, 'destroyTemplate'])->name('sms_marketing.templates.destroy');
        Route::post('/sms-marketing/save-credentials', [SmsMarketingController::class, 'saveCredentials'])->name('sms_marketing.save_credentials');
        Route::post('/sms-marketing/send-sms', [SmsMarketingController::class, 'sendSms'])->name('sms_marketing.send_sms');
        Route::delete('/sms-marketing/logs/{sms_log}', [SmsMarketingController::class, 'destroy'])->name('sms_marketing.logs.destroy');
    });

    // WhatsApp configuration API (used by settings page JS)
    Route::get('/whatsapp-config', [WhatsappConfigController::class, 'show'])->name('whatsapp.config.show');
    Route::post('/whatsapp-config', [WhatsappConfigController::class, 'store'])->name('whatsapp.config.store');
    Route::post('/whatsapp-templates/refresh', [WhatsappConfigController::class, 'refreshTemplates'])->name('whatsapp.templates.refresh');
    Route::post('/whatsapp-templates/{template}/module', [WhatsappConfigController::class, 'updateTemplateModule'])->name('whatsapp.templates.update_module');
    Route::post('/whatsapp-templates/{template}/status', [WhatsappConfigController::class, 'updateTemplateStatus'])->name('whatsapp.templates.update_status');
    Route::post('/whatsapp-send', [WhatsappConfigController::class, 'send'])->name('whatsapp.send');
    Route::get('/whatsapp-logs', [WhatsappConfigController::class, 'logs'])->name('whatsapp.logs');

    // AI Chatbot route
    Route::get('/global-search', \App\Http\Controllers\GlobalSearchController::class);
    Route::post('/crm-assistant', [ChatbotController::class, 'assistant']);

    // PDF Builder routes
    Route::prefix('pdfbuilder')->name('pdfbuilder.')->group(function () {
        Route::get('/', [PdfbuilderController::class, 'index'])->middleware('matrix_permission:view_templates')->name('index');
        Route::get('create', [PdfbuilderController::class, 'create'])->middleware('matrix_permission:create_templates')->name('create');
        Route::post('generate', [PdfbuilderController::class, 'generate'])->middleware('matrix_permission:create_templates')->name('generate');
        Route::get('list', [PdfbuilderController::class, 'index'])->middleware('matrix_permission:view_templates')->name('list');
        Route::get('edit/{id}', [PdfbuilderController::class, 'edit'])->middleware('matrix_permission:edit_templates')->name('edit');
        Route::post('update/{id}', [PdfbuilderController::class, 'update'])->middleware('matrix_permission:edit_templates')->name('update');
        Route::get('view/{id}', [PdfbuilderController::class, 'view'])->middleware('matrix_permission:view_templates')->name('view');
        Route::get('stream/{id}', [PdfbuilderController::class, 'stream'])->middleware('matrix_permission:view_templates')->name('stream');
        Route::get('view-qt-000150', [PdfbuilderController::class, 'viewQt000150'])->middleware('matrix_permission:view_templates')->name('view.qt000150');
        Route::post('delete/{id}', [PdfbuilderController::class, 'delete'])->middleware('matrix_permission:delete_templates')->name('delete');
    });

    // Background Email Processing Route
    Route::get('/process-queued-emails', function () {
        if (\Illuminate\Support\Facades\DB::table('jobs')->count() > 0) {
            $command = 'php ' . base_path('artisan') . ' queue:work --stop-when-empty';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                pclose(popen('start /B ' . $command . ' > NUL 2>&1', 'r'));
            } else {
                exec($command . ' > /dev/null 2>&1 &');
            }
        }
        return response()->json(['status' => 'processed']);
    })->name('process.queued.emails');

    // Workflow System
    Route::group(['prefix' => 'workflow', 'as' => 'workflow.'], function () {
        Route::get('/{stage}', [App\Http\Controllers\WorkflowController::class, 'index'])->name('index');
    });

});

// Cache clearing route - accessible to clear compiled views
Route::get('/admin/clear-all-cache', function () {
    try {
        // Clear all caches
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        
        // Also clear compiled views manually
        $viewPath = storage_path('framework/views');
        if (is_dir($viewPath)) {
            $files = glob("$viewPath/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'All caches and compiled views cleared successfully! Refresh the page now.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// Storage link creation route - accessible to create storage symlink
Route::get('/admin/create-storage-link', function () {
    try {
        // Create storage link
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        
        return response()->json([
            'success' => true,
            'message' => 'Storage link created successfully! Images should now load properly.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});

// Fix make images route - update frontend to use storage URLs
Route::get('/admin/fix-make-images', function () {
    try {
        // Create storage link first
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        
        // Clear caches
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        
        return response()->json([
            'success' => true,
            'message' => 'Make images fixed! Storage link created and caches cleared. Images should now load properly via /storage/makes/ URLs.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
});
// TEMPORARY FIX ROUTES - For storage symlink issue
Route::get('/admin/fix-storage-link', function() {
    try {
        // Method 1: Try artisan command
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        
        // Method 2: Create symlink manually if artisan fails
        $target = storage_path('app/public');
        $link = public_path('storage');
        
        // Remove existing if it exists
        if (file_exists($link)) {
            if (is_link($link)) {
                unlink($link);
            } else {
                rmdir($link);
            }
        }
        
        // Create symlink
        if (function_exists('symlink')) {
            symlink($target, $link);
            $method = 'symlink';
        } else {
            // Fallback for Windows or restricted servers
            $result = shell_exec('mklink /D "' . $link . '" "' . $target . '"');
            $method = 'mklink';
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Storage link created successfully using ' . $method,
            'target' => $target,
            'link' => $link,
            'test_url' => asset('storage/makes/gQsOhAAKUu5EA9XOnLj89b3srdlXPV2TafS3vZUF.webp')
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'suggestion' => 'Try manual file copy method'
        ]);
    }
})->middleware(['auth', 'main_admin']);

// MANUAL COPY ROUTE - For servers that don't support symlinks
Route::get('/admin/copy-storage-files', function() {
    try {
        $sourceDir = storage_path('app/public');
        $targetDir = public_path('storage');
        
        // Create target directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Copy all files recursively
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        $copiedFiles = 0;
        foreach ($iterator as $item) {
            $target = $targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!file_exists($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item, $target);
                $copiedFiles++;
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Files copied successfully',
            'files_copied' => $copiedFiles,
            'test_url' => asset('storage/makes/gQsOhAAKUu5EA9XOnLj89b3srdlXPV2TafS3vZUF.webp')
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
})->middleware(['auth', 'main_admin']);

// TEST ROUTE - No authentication needed for debugging
Route::get('test-make-image/{id}', function($id) {
    $category = App\Models\Category::findOrFail($id);
    
    if (!$category->image || !Storage::disk('public')->exists($category->image)) {
        return response()->json(['error' => 'Image not found'], 404);
    }
    
    return response()->file(Storage::disk('public')->path($category->image));
})->name('test.make.image');
