<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'saas';

    public function up(): void
    {
        Schema::connection('saas')->create('module_catalogs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->default('basic');
            $table->string('billing_type')->default('fixed');
            $table->decimal('base_monthly_price', 10, 2)->default(0);
            $table->string('unit_of_measure')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('required_roles')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('saas')->dropIfExists('module_catalogs');
    }
};
