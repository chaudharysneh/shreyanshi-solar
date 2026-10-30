<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Observers\CustomerObserver;
use App\Observers\LeadObserver;
use App\Observers\StaffObserver;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('Helpers/assetHelper.php');
        $this->app->scoped(\App\Support\FrontendAsset::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Create storage directories if they don't exist
        $this->ensureStorageDirectoriesExist();
        
        // Create storage symlink if it doesn't exist
        $this->createStorageSymlink();

        // Load dynamic mail configuration for all requests and queued jobs
        if (function_exists('setMailConfig')) {
            setMailConfig();
        }

        \App\Models\Deal::observe(\App\Observers\DealObserver::class);
        Customer::observe(CustomerObserver::class);
        Lead::observe(LeadObserver::class);
        User::observe(StaffObserver::class);

        // New Automated Notification Observers
        \App\Models\FollowUp::observe(\App\Observers\FollowUpObserver::class);
        \App\Models\Meeting::observe(\App\Observers\MeetingObserver::class);
        \App\Models\Project::observe(\App\Observers\ProjectObserver::class);
        \App\Models\Task::observe(\App\Observers\TaskObserver::class);
        \App\Models\Stage::observe(\App\Observers\StageObserver::class);
        \App\Models\Pipeline::observe(\App\Observers\PipelineObserver::class);
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
        \App\Models\SupportTicket::observe(\App\Observers\SupportTicketObserver::class);
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        \App\Models\ProductCategory::observe(\App\Observers\ProductCategoryObserver::class);
        \App\Models\Service::observe(\App\Observers\ServiceObserver::class);

        View::composer(['layouts.app', 'profile.show'], function ($view): void {
            $googleCalendarConnected = false;

            try {
                $googleCalendarConnected = app(GoogleCalendarService::class)->isAuthenticated();
            } catch (\Throwable $e) {
                $googleCalendarConnected = false;
            }

            $viewData = [
                'googleCalendarConnected' => $googleCalendarConnected,
                'showHeaderQuickEstimate' => false,
                'headerQuickEstimateCustomers' => collect(),
                'headerQuickEstimateTemplates' => collect(),
                'headerQuickEstimateBomProducts' => collect(),
                'headerQuickEstimateCategories' => collect(),
                'headerQuickEstimateGstTaxes' => collect(),
                'headerQuickEstimateSubsidies' => collect(),
                'headerQuickBomCategories' => collect(),
                'headerQuickBomTechnologies' => collect(),
                'headerQuickBomWarranties' => collect(),
            ];

            $user = auth()->user();
            $skipHeaderQuickEstimateModal = request()->routeIs([
                'estimates.index',
                'deals.create',
                'deals.edit',
                'tasks.create',
                'tasks.edit',
            ]);

            if ($user?->hasMatrixPermission('create_estimates') && !$skipHeaderQuickEstimateModal) {
                $viewData['showHeaderQuickEstimate'] = true;
                $viewData['headerQuickEstimateCustomers'] = \App\Models\Customer::visibleTo($user)->orderBy('name')->get();
                $viewData['headerQuickEstimateTemplates'] = \App\Models\PdfBuilderForm::orderBy('template_name')->get();
                $viewData['headerQuickEstimateBomProducts'] = \App\Models\BomProduct::with('categories')->orderBy('product_name')->get();
                $viewData['headerQuickEstimateCategories'] = \App\Models\Category::orderBy('name')->get();
                $viewData['headerQuickEstimateGstTaxes'] = \App\Models\Tax::active()->orderBy('name')->orderBy('rate')->get();
                $viewData['headerQuickEstimateSubsidies'] = \App\Models\Subsidy::active()->get();
            }

            if ($user?->hasMatrixPermission('create_bom') && !request()->routeIs('bom-products.index')) {
                $viewData['headerQuickBomCategories'] = \App\Models\Category::orderBy('name')->get();
                $viewData['headerQuickBomTechnologies'] = \App\Models\Technology::orderBy('title')->get();
                $viewData['headerQuickBomWarranties'] = \App\Models\Warranty::orderBy('title')->get();
            }

            $view->with($viewData);
        });
    }

    /**
     * Ensure all required storage directories exist
     */
    private function ensureStorageDirectoriesExist(): void
    {
        $directories = [
            'app/public/make',
            'app/public/categories',
            'app/public/products',
            'app/public/bom-products',
            'app/public/leads',
            'app/public/customers',
            'app/public/vendors',
            'app/public/users',
            'app/public/avatars',
            'app/public/company',
            'app/public/documents',
            'app/public/estimates',
            'logs',
            'framework/cache',
            'framework/sessions',
            'framework/views',
        ];

        foreach ($directories as $dir) {
            $path = storage_path($dir);
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
        }
    }

    /**
     * Create storage symlink if it doesn't exist
     */
    private function createStorageSymlink(): void
    {
        try {
            $link = public_path('storage');
            $target = storage_path('app/public');

            // Check if symlink already exists and is valid
            if (is_link($link) && readlink($link) === $target) {
                return;
            }

            // Remove existing link/directory if it exists
            if (file_exists($link)) {
                if (is_link($link)) {
                    unlink($link);
                } elseif (is_dir($link)) {
                    // Only remove if empty
                    $files = array_diff(scandir($link), ['.', '..']);
                    if (empty($files)) {
                        rmdir($link);
                    } else {
                        // Directory has files, skip symlink creation
                        return;
                    }
                }
            }

            // Create the symlink
            if (!file_exists($link)) {
                symlink($target, $link);
            }
        } catch (\Throwable $e) {
            // Log error but don't break the application
            \Log::warning('Could not create storage symlink: ' . $e->getMessage());
        }
    }
}
