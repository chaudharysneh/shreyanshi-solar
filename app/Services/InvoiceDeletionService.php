<?php
namespace App\Services;

use App\Models\{Deal, Invoice, Task};
use Illuminate\Support\Facades\DB;

class InvoiceDeletionService
{
    public function delete(Invoice $invoice, int $userId): void
    {
        DB::transaction(function () use ($invoice, $userId) {
            // Finance writes lock the same invoice, preventing a payment racing deletion.
            $locked = Invoice::withoutGlobalScope('owned_by_user')->whereKey($invoice->getKey())->lockForUpdate()->firstOrFail();
            $id = $locked->getKey();
            $taskIds = Task::withoutGlobalScope('owned_by_user')->where('invoice_id', $id)->pluck('id');
            $dealIds = Deal::withoutGlobalScope('owned_by_user')->where('invoice_id', $id)->pluck('id');

            DB::table('task_workflow_events')->whereIn('task_id', $taskIds)->delete();
            DB::table('documents')->where('documentable_type', (new Task)->getMorphClass())->whereIn('documentable_id', $taskIds)->delete();
            DB::table('module_status_histories')->where(function ($query) use ($taskIds, $dealIds) {
                $query->where(fn ($q) => $q->where('historable_type', (new Task)->getMorphClass())->whereIn('historable_id', $taskIds))
                    ->orWhere(fn ($q) => $q->where('historable_type', (new Deal)->getMorphClass())->whereIn('historable_id', $dealIds));
            })->delete();
            Task::withoutGlobalScope('owned_by_user')->whereIn('id', $taskIds)->delete();
            Deal::withoutGlobalScope('owned_by_user')->whereIn('id', $dealIds)->delete();
            DB::table('payments')->where('invoice_id', $id)->delete();
            DB::table('invoice_finance_events')->where('invoice_id', $id)->delete();
            DB::table('invoice_finance_plans')->where('invoice_id', $id)->delete();
            $locked->deleted_by = $userId;
            $locked->save();
            $locked->delete();
        });
    }
}
