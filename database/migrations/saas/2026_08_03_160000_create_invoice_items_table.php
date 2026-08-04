<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    /**
     * A fatura guardava apenas totais agregados: não havia como o cliente (nem
     * o suporte) saber de onde vinha cada real cobrado.
     */
    public function up(): void
    {
        Schema::connection('saas')->create('invoice_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('invoice');
            $table->string('type');
            $table->string('description');
            $table->string('module_code')->nullable();
            $table->string('period')->nullable();
            $table->integer('quantity')->default(1);
            $table->bigInteger('unit_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->timestamps();

            $table->index(['invoice_type', 'invoice_id', 'type'], 'invoice_items_invoice_type_index');
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('invoice_items');
    }
};
