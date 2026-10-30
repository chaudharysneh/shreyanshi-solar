<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class CustomerDocumentName
{
    public function next(int $customerId, string $type, bool $reserve = false): string
    {
        return DB::transaction(function () use ($customerId, $type, $reserve) {
            $customer = Customer::findOrFail($customerId);
            $model = $type === 'EST' ? Estimate::class : Invoice::class;
            $field = $type === 'EST' ? 'estimate_name' : 'invoice_name';
            if ($reserve) {
                DB::table('customer_document_sequences')->insertOrIgnore(['customer_id' => $customerId, 'type' => $type, 'value' => 0]);
            }
            $query = DB::table('customer_document_sequences')->where('customer_id', $customerId)->where('type', $type);
            $sequence = ($reserve ? (clone $query)->lockForUpdate() : clone $query)->first();
            $documents = $model::withTrashed()->where('customer_id', $customerId)->pluck($field);
            $highest = $documents->reduce(function ($max, $name) use ($type) {
                return preg_match('/^#(\d+)'. $type .'-/', (string) $name, $matches) ? max($max, (int) $matches[1]) : $max;
            }, 0);
            $next = max((int) ($sequence->value ?? 0), $highest, $documents->count()) + 1;
            if ($reserve) $query->update(['value' => $next]);
            return '#'.str_pad((string) $next, 2, '0', STR_PAD_LEFT).$type.'-'.$customer->name;
        });
    }

    public function assign($document, string $type): void
    {
        $field = $type === 'EST' ? 'estimate_name' : 'invoice_name';
        $name = trim((string) $document->$field);
        $customerName = Customer::find($document->customer_id)?->name;
        if (!$customerName) return;
        if ($name === '' || in_array($name, ['EST-'.$customerName, 'INV-'.$customerName]) || preg_match('/^#\d+(EST|INV)-'.preg_quote($customerName, '/').'$/u', $name)) {
            $document->$field = $this->next($document->customer_id, $type, true);
        }
    }
}
