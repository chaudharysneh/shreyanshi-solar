<?php

namespace Tests\Feature;

use App\Support\DocumentNumber;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class DocumentNumberTest extends TestCase
{
    public function test_configured_start_increments_both_modules_and_skips_reserved_numbers(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('settings', function (Blueprint $t) { $t->id(); $t->string('key'); $t->string('value'); $t->softDeletes(); });
        foreach (['estimate', 'invoice'] as $kind) {
            Schema::create($kind . 's', function (Blueprint $t) use ($kind) {
                $t->id($kind === 'estimate' ? 'estimate_id' : 'id'); $t->string($kind . '_no'); $t->softDeletes();
            });
            DB::table('settings')->insert(['key' => $kind . '_number_start', 'value' => '200']);
            foreach ([200, 201, 202] as $sequence) {
                $expected = $kind === 'invoice' ? 'INV-' . str_pad($sequence, 5, '0', STR_PAD_LEFT) : str_pad($sequence, 6, '0', STR_PAD_LEFT);
                $this->assertSame($expected, DocumentNumber::next($kind));
                DB::table($kind . 's')->insert([$kind . '_no' => $expected, 'deleted_at' => $sequence === 201 ? now() : null]);
            }
            DB::table('settings')->where('key', $kind . '_number_start')->delete();
            $this->assertSame($kind === 'invoice' ? 'INV-00203' : '000203', DocumentNumber::next($kind));
        }
    }
}
