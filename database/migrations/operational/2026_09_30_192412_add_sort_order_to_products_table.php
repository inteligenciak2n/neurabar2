<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->integer('sort_order')->default(0)->after('active');
            $table->index(['category_id', 'sort_order']);
        });

        $connection = Schema::getConnection();
        $categoryIds = $connection->table('products')->distinct()->pluck('category_id');

        foreach ($categoryIds as $categoryId) {
            $products = $connection->table('products')
                ->where('category_id', $categoryId)
                ->orderBy('name')
                ->get(['id']);

            foreach ($products as $index => $product) {
                $connection->table('products')
                    ->where('id', $product->id)
                    ->update(['sort_order' => $index + 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['category_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
