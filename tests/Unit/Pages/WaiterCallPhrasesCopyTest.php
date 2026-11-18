<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class WaiterCallPhrasesCopyTest extends TestCase
{
    public function test_waiter_call_phrases_settings_page_lets_the_venue_register_custom_chips(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/WaiterCallPhrases.vue');
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/WaiterCallPhrases.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertNotFalse($page);
        $this->assertStringContainsString("route('settings.waiter-call-phrases.update')", $page);
        $this->assertStringContainsString("__('Waiter call phrases')", $page);
        $this->assertStringNotContainsString("__('Default phrases')", $page);
        $this->assertStringContainsString("__('Add phrase')", $page);
        $this->assertStringContainsString("__('Delete')", $page);
        $this->assertStringContainsString('removePhrase', $page);
        $this->assertStringContainsString('MAX_PHRASES = 8', $page);
        $this->assertStringContainsString("__('You can register up to 8 phrases.')", $page);
        $this->assertStringContainsString("__('These are suggestions. You can delete any of them and create your own.')", $page);
        $this->assertSame('Frases do chama garçom', $translations['Waiter call phrases']);
        $this->assertSame(
            'Estas são sugestões. Você pode excluir qualquer uma e criar as suas.',
            $translations['These are suggestions. You can delete any of them and create your own.'],
        );
        $this->assertSame('Adicionar frase', $translations['Add phrase']);
        $this->assertSame('Você pode cadastrar até 8 frases.', $translations['You can register up to 8 phrases.']);
        $this->assertSame('Você chegou no limite de 8 frases.', $translations['You reached the limit of 8 phrases.']);
    }
}
