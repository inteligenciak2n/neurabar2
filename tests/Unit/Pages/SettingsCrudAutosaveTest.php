<?php

namespace Tests\Unit\Pages;

use PHPUnit\Framework\TestCase;

class SettingsCrudAutosaveTest extends TestCase
{
    public function test_edit_buttons_enable_automatic_saving(): void
    {
        $pages = [
            'resources/js/Pages/Settings/AttendanceChannels.vue',
            'resources/js/Pages/Settings/KitchenStations.vue',
            'resources/js/Pages/Settings/PreparationStatuses.vue',
            'resources/js/Pages/Settings/ServiceLocations.vue',
            'resources/js/Pages/Settings/Users.vue',
        ];

        foreach ($pages as $path) {
            $page = file_get_contents(dirname(__DIR__, 3).'/'.$path);

            $this->assertNotFalse($page, $path);
            $this->assertStringContainsString('useSettingsEditing', $page, $path);
            $this->assertStringContainsString('enableEditing()', $page, $path);
            $this->assertStringContainsString('data-enable-editing', $page, $path);
        }
    }

    public function test_list_settings_autosave_existing_records_without_a_save_button(): void
    {
        $pages = [
            'resources/js/Pages/Settings/AttendanceChannels.vue' => 'editingChannel',
            'resources/js/Pages/Settings/KitchenStations.vue' => 'editingStation',
            'resources/js/Pages/Settings/PreparationStatuses.vue' => 'editingStatus',
            'resources/js/Pages/Settings/ServiceLocations.vue' => 'editingLocation',
            'resources/js/Pages/Settings/Users.vue' => 'editingUser',
        ];

        foreach ($pages as $path => $editingRef) {
            $page = file_get_contents(dirname(__DIR__, 3).'/'.$path);

            $this->assertNotFalse($page, $path);
            $this->assertStringContainsString('useAutosaveForm(form, persist)', $page, $path);
            $this->assertStringContainsString('preserveScroll: true', $page, $path);
            $this->assertStringContainsString('form.defaults()', $page, $path);
            $this->assertStringContainsString("v-if=\"!{$editingRef}\"", $page, $path);
        }
    }

    public function test_settings_crud_pages_return_to_the_section_when_backing_out_of_edit(): void
    {
        $pages = [
            'resources/js/Pages/Settings/AttendanceChannels.vue',
            'resources/js/Pages/Settings/KitchenStations.vue',
            'resources/js/Pages/Settings/PreparationStatuses.vue',
            'resources/js/Pages/Settings/ServiceLocations.vue',
            'resources/js/Pages/Settings/Users.vue',
        ];

        foreach ($pages as $path) {
            $page = file_get_contents(dirname(__DIR__, 3).'/'.$path);

            $this->assertNotFalse($page, $path);
            $this->assertStringContainsString(':nested="showForm"', $page, $path);
            $this->assertStringContainsString('@close-nested="closeForm"', $page, $path);
        }
    }

    public function test_billing_address_saves_automatically_without_a_save_button(): void
    {
        $page = file_get_contents(dirname(__DIR__, 3).'/resources/js/Pages/Settings/Subscription/BillingAddress.vue');

        $this->assertNotFalse($page);
        $this->assertStringContainsString('useAutosaveForm(corporationForm, submitCorporation)', $page);
        $this->assertStringContainsString('useAutosaveForm(venueForm, submitVenue)', $page);
        $this->assertStringNotContainsString("__('Save')", $page);
    }

    public function test_subscription_subpages_back_to_the_parent_section(): void
    {
        $pages = [
            'resources/js/Pages/Settings/Subscription/Invoices.vue' => 'settings.subscription.index',
            'resources/js/Pages/Settings/Subscription/PaymentMethods.vue' => 'settings.subscription.index',
            'resources/js/Pages/Settings/Subscription/BillingAddress.vue' => 'settings.subscription.index',
            'resources/js/Pages/Settings/Subscription/Usage.vue' => 'settings.subscription.index',
            'resources/js/Pages/Settings/Subscription/InvoiceShow.vue' => 'settings.subscription.invoices.index',
        ];

        foreach ($pages as $path => $parentRoute) {
            $page = file_get_contents(dirname(__DIR__, 3).'/'.$path);

            $this->assertNotFalse($page, $path);
            $this->assertStringContainsString('parent-route="'.$parentRoute.'"', $page, $path);
        }
    }
}
