<?php

namespace Tests\Unit;

use Tests\TestCase;

class PaginationTranslationTest extends TestCase
{
    public function test_pagination_labels_are_translated_in_portuguese(): void
    {
        app()->setLocale('pt');

        $this->assertSame('&laquo; Anterior', __('pagination.previous'));
        $this->assertSame('Próximo &raquo;', __('pagination.next'));
    }
}
