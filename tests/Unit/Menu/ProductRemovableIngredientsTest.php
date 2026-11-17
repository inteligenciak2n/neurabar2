<?php

namespace Tests\Unit\Menu;

use App\Models\Menu\Product;
use PHPUnit\Framework\TestCase;

class ProductRemovableIngredientsTest extends TestCase
{
    public function test_normalize_splits_lines_trims_and_drops_duplicates(): void
    {
        $this->assertSame(
            ['Hamburguer', 'tomate', 'alface', 'pão', 'ketchup', 'mostarda'],
            Product::normalizeRemovableIngredients("Hamburguer\n tomate\nalface\npão\nketchup\nmostarda\ntomate"),
        );
    }

    public function test_normalize_accepts_arrays_and_json(): void
    {
        $this->assertSame(['tomate', 'alface'], Product::normalizeRemovableIngredients([' tomate ', 'alface', 'TOMATE']));
        $this->assertSame(['pão', 'ketchup'], Product::normalizeRemovableIngredients('["pão","ketchup",""]'));
        $this->assertSame([], Product::normalizeRemovableIngredients(''));
        $this->assertSame([], Product::normalizeRemovableIngredients(null));
    }
}
