<?php

namespace Tests\Unit\Layouts;

use PHPUnit\Framework\TestCase;

class AppLayoutTriggerLabelTest extends TestCase
{
    public function test_user_dropdown_trigger_shows_settings_instead_of_the_user_name(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3).'/resources/js/Layouts/AppLayout.vue');

        $this->assertNotFalse($layout);
        $this->assertStringContainsString(
            '<span class="hidden sm:block font-medium">{{ __(\'Configure the System\') }}</span>',
            $layout,
        );
        $this->assertStringNotContainsString(
            '<span class="hidden sm:block font-medium">{{ $page.props.auth.user.name }}</span>',
            $layout,
        );
    }

    public function test_venue_switcher_stacks_establishment_name_above_user_name(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3).'/resources/js/Layouts/AppLayout.vue');

        $this->assertNotFalse($layout);
        $this->assertStringContainsString('flex min-w-0 flex-col items-start leading-tight', $layout);
        $this->assertStringContainsString('font-heading text-sm font-bold', $layout);
        $this->assertStringContainsString('{{ $page.props.defs.venue.name }}', $layout);
        $this->assertStringContainsString('{{ $page.props.auth.user.name }}', $layout);
        $this->assertStringNotContainsString("{{ __('Venue:') }}", $layout);
    }

    public function test_user_dropdown_items_include_muted_descriptions(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3).'/resources/js/Layouts/AppLayout.vue');

        $this->assertNotFalse($layout);
        $this->assertStringContainsString(":description=\"__('Edit your access profile information')\"", $layout);
        $this->assertStringContainsString(":description=\"__('Manage the venue, users and preferences')\"", $layout);
        $this->assertStringContainsString(":description=\"__('Open tickets and browse tutorials')\"", $layout);
        $this->assertStringContainsString(":description=\"__('End the current session')\"", $layout);
    }

    public function test_portuguese_settings_label_is_configuracoes(): void
    {
        $translations = json_decode(
            file_get_contents(dirname(__DIR__, 3).'/resources/translations/pt/AppLayout.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('Configurações', $translations['Settings']);
        $this->assertSame(
            'Edite as informações do perfil de acesso',
            $translations['Edit your access profile information'],
        );
    }

    public function test_dropdown_links_use_dark_blue_highlight_and_muted_descriptions(): void
    {
        $component = file_get_contents(dirname(__DIR__, 3).'/resources/js/Components/DropdownLink.vue');

        $this->assertNotFalse($component);
        $this->assertMatchesRegularExpression("/labelClass = '[^']*bg-primary/", $component);
        $this->assertMatchesRegularExpression("/labelClass = '[^']*py-2\\.5/", $component);
        $this->assertDoesNotMatchRegularExpression("/rowClass = '[^']*bg-primary/", $component);
        $this->assertStringContainsString('text-gray-400', $component);
        $this->assertStringContainsString('description', $component);
    }
}
