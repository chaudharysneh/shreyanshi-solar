<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Tax;
use App\Models\Subsidy;
use App\Models\WhatsappMessageTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        $whatsappTemplates = WhatsappMessageTemplate::visibleForSettings()->orderBy('name')->get();
        $whatsappModuleOptions = $this->whatsappModuleOptions();
        $taxes = Tax::orderBy('name')->orderBy('rate')->get();
        $subsidies = Subsidy::orderBy('category')->get();

        // Fetch tables for truncate utility
        $tables = DB::select('SHOW TABLES');
        $allTables = array_map('current', $tables);
        $whitelist = \App\Http\Controllers\TableTruncateController::$allowedModulesTables;
        $allowedTables = array_intersect($allTables, $whitelist);
        $truncateTables = [];
        foreach ($allowedTables as $table) {
            try {
                $count = DB::table($table)->count();
                if ($table === 'users') {
                    // Get only non-admin count for display? Actually total records is fine, but let's show total for non-admins if we want, or just total. The UI says "users (Excludes Admins)"
                    $count = \App\Models\User::nonAdmin()->count();
                }
                $truncateTables[] = [
                    'name' => $table,
                    'count' => $count
                ];
            } catch (\Exception $e) {
                // Ignore tables that can't be queried
            }
        }
        // Sort tables by their defined order in the whitelist
        usort($truncateTables, function($a, $b) use ($whitelist) {
            $posA = array_search($a['name'], $whitelist);
            $posB = array_search($b['name'], $whitelist);
            return $posA <=> $posB;
        });

        $integrationSettings = \App\Models\IntegrationSetting::first() ?? \App\Models\IntegrationSetting::create([
            'whatsapp_integration' => true,
            'email_smtp' => true,
            'google_connection' => true,
        ]);

        return view('settings.index', compact('settings', 'whatsappTemplates', 'whatsappModuleOptions', 'taxes', 'subsidies', 'truncateTables', 'integrationSettings'));
    }

    public function update(Request $request)
    {
        $settingsTab = in_array($request->input('_settings_tab'), [
            'integrations-main',
            'financial-information',
            'table-truncate',
            'estimate-invoice-setting',
        ], true) ? $request->input('_settings_tab') : null;
        $settingsUrl = route('settings.index') . ($settingsTab ? '#' . $settingsTab : '');

        try {
            $this->persistSettings($request);
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
            return redirect($settingsUrl)->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            Log::error('Settings update failed', [
                'route' => 'settings.update',
                'inputs' => array_keys($request->except(['_token', '_method'])),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => config('app.debug')
                        ? $e->getMessage()
                        : 'Settings save failed. Check logs for details.',
                ], 500);
            }

            return redirect($settingsUrl)->with('error', 'Settings save failed. Check logs for details.');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Settings saved successfully!',
            ]);
        }

        return redirect($settingsUrl)->with('success', 'Settings saved successfully!');
    }

    public function apiIndex(): JsonResponse
    {
        $settings = Setting::query()
            ->whereIn('key', [
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_name',
                'google_client_id',
                'google_client_secret',
                'google_redirect_uri',
                'firebase_key_file',
            ])
            ->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'data' => [
                'settings' => $settings,
                'whatsapp_module_options' => $this->whatsappModuleOptions(),
            ],
        ]);
    }

    public function apiUpdate(Request $request): JsonResponse
    {
        try {
            $this->persistSettings($request);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Settings API update failed', [
                'route' => 'api.settings.update',
                'inputs' => array_keys($request->except(['_token', '_method'])),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'Settings save failed on server.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully.',
        ]);
    }

    private function persistSettings(Request $request): void
    {
        $validated = $request->validate([
            'mail_host' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mail_port' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mail_password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mail_encryption' => ['sometimes', 'nullable', 'in:tls,ssl,'],
            'mail_from_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'google_client_id' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'google_client_secret' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'google_redirect_uri' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'firebase_key_file' => ['sometimes', 'nullable', 'file', 'mimes:json', 'max:2048'],
            // Bank Details
            'bank_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'account_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:255'],
            'ifsc_code' => ['sometimes', 'nullable', 'string', 'max:255'],
            'branch_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'company_qr_code_path' => ['sometimes', 'nullable', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp,avif', 'max:51200'],
            'company_signature_path' => ['sometimes', 'nullable', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp,avif', 'max:51200'],
            'show_bank_details' => ['sometimes', 'boolean'],
            'show_company_qr_code' => ['sometimes', 'boolean'],
            'show_company_signature' => ['sometimes', 'boolean'],
            'estimate_price_mode' => ['sometimes', 'required', 'in:base,bom'],
        ]);

        foreach ($validated as $key => $value) {
            if ($request->hasFile($key)) {
                $oldSetting = Setting::where('key', $key)->first();
                if ($oldSetting && $oldSetting->value && Storage::disk('public')->exists($oldSetting->value)) {
                    Storage::disk('public')->delete($oldSetting->value);
                }

                $path = $request->file($key)->store('company', 'public');
                $value = $path;
            } elseif ($request->exists($key) && $request->file($key) === null && str_contains($key, 'logo')) {
                continue;
            }

            $attributes = ['value' => $this->normalizeSettingValue($value)];

            // Some live databases may still be on an older schema; avoid writing
            // columns that do not exist there.
            if (Schema::hasColumn('settings', 'group')) {
                $attributes['group'] = 'general';
            }

            if (Schema::hasColumn('settings', 'type')) {
                $attributes['type'] = $request->hasFile($key) ? 'file' : 'string';
            }

            Setting::updateOrCreate(['key' => $key], $attributes);
        }
    }

    private function normalizeSettingValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function whatsappModuleOptions(): array
    {
        return [
            'hello_world' => 'Hello World',
            'meeting_scheduled_customer' => 'Meeting Scheduled Customer',
            'meeting_scheduled_staff' => 'Meeting Scheduled Staff',
            'meeting_updated' => 'Meeting Updated',
            'staff_account_created' => 'Staff Account Created',
            'staff_account_updated' => 'Staff Account Updated',
            'task_assigned_staff' => 'Task Assigned Staff',
            'task_created_customer' => 'Task Created Customer',
            'task_updated_customer' => 'Task Updated Customer',
            'task_updated_staff' => 'Task Updated Staff',
            'customer_welcome_message' => 'Customer Welcome Message',
            'customer_profile_updated' => 'Customer Profile Updated',
        ];
    }

    /**
     * Store a new tax
     */
    public function storeTax(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            ]);

            $tax = Tax::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tax added successfully.',
                'tax' => $tax
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Tax creation failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create tax.',
            ], 500);
        }
    }

    /**
     * Update a tax
     */
    public function updateTax(Request $request, Tax $tax): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            ]);

            $tax->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Tax updated successfully.',
                'tax' => $tax->fresh()
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Tax update failed', [
                'tax_id' => $tax->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update tax.',
            ], 500);
        }
    }

    /**
     * Delete a tax
     */
    public function destroyTax(Tax $tax): JsonResponse
    {
        try {
            $tax->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tax deleted successfully.'
            ]);
        } catch (Throwable $e) {
            Log::error('Tax deletion failed', [
                'tax_id' => $tax->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tax.',
            ], 500);
        }
    }

    /**
     * Get taxes for API
     */
    public function getTaxes(): JsonResponse
    {
        try {
            $taxes = Tax::active()->orderBy('name')->orderBy('rate')->get();

            return response()->json([
                'success' => true,
                'data' => $taxes->groupBy('name')
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch taxes', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch taxes.',
            ], 500);
        }
    }

    /**
     * Update a subsidy
     */
    public function updateSubsidy(Request $request, Subsidy $subsidy): JsonResponse
    {
        dd($subsidy->get());
        try {
            $validated = $request->validate([
                'amount' => ['required', 'numeric', 'min:0'],
            ]);

            $subsidy->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Subsidy updated successfully.',
                'subsidy' => $subsidy->fresh()
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            Log::error('Subsidy update failed', [
                'subsidy_id' => $subsidy->id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update subsidy.',
            ], 500);
        }
    }

    /**
     * Get subsidies for API
     */
    public function getSubsidies(): JsonResponse
    {
        try {
            $subsidies = Subsidy::active()->orderBy('category')->get();

            return response()->json([
                'success' => true,
                'data' => $subsidies->keyBy('category')
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to fetch subsidies', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subsidies.',
            ], 500);
        }
    }

    /**
     * Toggle integration status
     */
    public function toggleIntegration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'integration' => ['required', 'string', 'in:social_media_integration,whatsapp_integration,email_smtp,google_connection'],
            'enabled' => ['required', 'boolean'],
        ]);

        $settings = \App\Models\IntegrationSetting::first() ?? \App\Models\IntegrationSetting::create([
            'social_media_integration' => true,
            'whatsapp_integration' => true,
            'email_smtp' => true,
            'google_connection' => true,
        ]);

        $settings->update([
            $validated['integration'] => $validated['enabled'],
        ]);

        \App\Helpers\IntegrationHelper::clearCache();

        return response()->json([
            'success' => true,
            'message' => 'Integration status updated successfully.',
            'data' => [
                'integration' => $validated['integration'],
                'enabled' => (bool) $settings->{$validated['integration']},
            ]
        ]);
    }
}
