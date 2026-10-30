<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $columns = [
        'user_id' => ['type' => 'integer', 'after' => 'customer_id'],
        'invoice_name' => ['type' => 'string', 'length' => 200, 'nullable' => true, 'after' => 'user_id'],
        'type' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'invoice_name'],
        'attach_file' => ['type' => 'text', 'nullable' => true, 'after' => 'type'],
        'quantity' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'attach_file'],
        'price' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'quantity'],
        'solar_structure_charges' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'price'],
        'solar_meter_charges' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'solar_structure_charges'],
        'template_id' => ['type' => 'integer', 'nullable' => true, 'after' => 'solar_meter_charges'],
        'product_id' => ['type' => 'integer', 'after' => 'template_id'],
        'handover_id' => ['type' => 'integer', 'nullable' => true, 'after' => 'product_id'],
        'invoice_date' => ['type' => 'string', 'length' => 50, 'after' => 'invoice_no'],
        'due_date' => ['type' => 'string', 'length' => 50, 'nullable' => true, 'after' => 'invoice_date'],
        'currency' => ['type' => 'string', 'length' => 50, 'after' => 'due_date'],
        'gst' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'total'],
        'other_charges' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'gst'],
        'subsidy_amount' => ['type' => 'string', 'length' => 100, 'nullable' => true, 'after' => 'other_charges'],
        'amount' => ['type' => 'integer', 'nullable' => true, 'after' => 'subsidy_amount'],
        'product_name' => ['type' => 'string', 'length' => 255, 'nullable' => true, 'after' => 'amount'],
        'status' => ['type' => 'string', 'length' => 50, 'after' => 'product_name'],
        'comment' => ['type' => 'string', 'length' => 255, 'nullable' => true, 'after' => 'status'],
        'customer_docs' => ['type' => 'text', 'nullable' => true, 'after' => 'comment'],
        'isDeleted' => ['type' => 'integer', 'default' => 0, 'after' => 'customer_docs'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->columns as $column => $definition) {
            if (Schema::hasColumn('sales', $column)) {
                continue;
            }

            Schema::table('sales', function (Blueprint $table) use ($column, $definition) {
                $columnDefinition = match ($definition['type']) {
                    'integer' => $table->integer($column),
                    'text' => $table->text($column),
                    'string' => $table->string($column, $definition['length'] ?? 255),
                };

                if (($definition['nullable'] ?? false) === true) {
                    $columnDefinition->nullable();
                }

                if (array_key_exists('default', $definition)) {
                    $columnDefinition->default($definition['default']);
                }

                if (!empty($definition['after'])) {
                    $columnDefinition->after($definition['after']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $existingColumns = array_values(array_filter(array_keys($this->columns), function ($column) {
            return Schema::hasColumn('sales', $column);
        }));

        if ($existingColumns === []) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) use ($existingColumns) {
            $table->dropColumn($existingColumns);
        });
    }
};
