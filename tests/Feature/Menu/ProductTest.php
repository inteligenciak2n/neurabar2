<?php

namespace Tests\Feature\Menu;

use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Settings\KitchenStation;
use App\Models\Tenant\Venue;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_owner_can_create_product(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Burger', 'category_id' => $category->id]);
    }

    public function test_product_creation_with_kitchen_station_from_same_tenant(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $station = KitchenStation::factory()->create(['venue_id' => $venue->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'kitchen_station_id' => $station->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Burger', 'kitchen_station_id' => $station->id]);
    }

    public function test_kitchen_station_from_other_tenant_is_rejected(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $otherVenue = Venue::factory()->create();
        $otherStation = KitchenStation::factory()->create(['venue_id' => $otherVenue->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'kitchen_station_id' => $otherStation->id,
        ])->assertSessionHasErrors('kitchen_station_id');
    }

    public function test_toggle_active_alternates_correctly(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $product = Product::factory()->create(['category_id' => $category->id, 'active' => true]);

        $this->post(route('menu.products.toggle', $product->id))->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'active' => false]);

        $this->post(route('menu.products.toggle', $product->id))->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'active' => true]);
    }

    public function test_products_index_redirects_to_the_menu_products_section(): void
    {
        $this->loginAs(UserRole::Owner);

        $this->get(route('menu.products.index'))
            ->assertRedirect(route('menu.index').'#menu-products');
    }

    public function test_owner_can_upload_a_product_photo(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $photo = UploadedFile::fake()->image('burger.jpg');

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
            'photo' => $photo,
        ])->assertRedirect();

        $product = Product::query()->where('name', 'Burger')->first();

        $this->assertNotNull($product);
        $this->assertNotNull($product->storedImagePath());
        Storage::disk('public')->assertExists($product->storedImagePath());
    }

    public function test_owner_can_replace_a_product_photo(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
            'photo' => UploadedFile::fake()->image('old.jpg'),
        ])->assertRedirect();

        $product = Product::query()->where('name', 'Burger')->first();
        $oldPath = $product->storedImagePath();

        $this->put(route('menu.products.update', $product), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
            'photo' => UploadedFile::fake()->image('new.jpg'),
        ])->assertRedirect();

        $product->refresh();
        $this->assertNotSame($oldPath, $product->storedImagePath());
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($product->storedImagePath());
    }

    public function test_updating_a_product_without_a_photo_keeps_the_existing_image(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
            'photo' => UploadedFile::fake()->image('burger.jpg'),
        ])->assertRedirect();

        $product = Product::query()->where('name', 'Burger')->first();
        $path = $product->storedImagePath();

        $this->put(route('menu.products.update', $product), [
            'name' => 'Cheeseburger',
            'price' => 32.90,
            'category_id' => $category->id,
            'active' => true,
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame('Cheeseburger', $product->name);
        $this->assertSame($path, $product->storedImagePath());
        Storage::disk('public')->assertExists($path);
    }

    public function test_owner_can_remove_a_product_photo(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
            'photo' => UploadedFile::fake()->image('burger.jpg'),
        ])->assertRedirect();

        $product = Product::query()->where('name', 'Burger')->first();
        $path = $product->storedImagePath();

        $this->put(route('menu.products.update', $product), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
            'remove_photo' => true,
        ])->assertRedirect();

        $product->refresh();
        $this->assertNull($product->storedImagePath());
        Storage::disk('public')->assertMissing($path);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
            'photo' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('photo');
    }

    public function test_deleting_a_product_removes_its_photo(): void
    {
        Storage::fake('public');

        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
            'photo' => UploadedFile::fake()->image('burger.jpg'),
        ])->assertRedirect();

        $product = Product::query()->where('name', 'Burger')->first();
        $path = $product->storedImagePath();

        $this->delete(route('menu.products.destroy', $product))->assertRedirect();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_owner_can_reorder_products(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        $first = Product::factory()->create(['category_id' => $category->id, 'name' => 'A', 'sort_order' => 1]);
        $second = Product::factory()->create(['category_id' => $category->id, 'name' => 'B', 'sort_order' => 2]);
        $third = Product::factory()->create(['category_id' => $category->id, 'name' => 'C', 'sort_order' => 3]);

        $this->post(route('menu.products.reorder'), ['ids' => [$third->id, $first->id, $second->id]])
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $third->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('products', ['id' => $first->id, 'sort_order' => 2]);
        $this->assertDatabaseHas('products', ['id' => $second->id, 'sort_order' => 3]);
    }

    public function test_new_product_is_appended_to_the_category_order(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);
        Product::factory()->create(['category_id' => $category->id, 'sort_order' => 2]);

        $this->post(route('menu.products.store'), [
            'name' => 'Last drink',
            'price' => 9.90,
            'category_id' => $category->id,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Last drink', 'sort_order' => 3]);
    }

    public function test_owner_can_set_how_many_people_a_product_serves(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Family burger',
            'price' => 49.90,
            'category_id' => $category->id,
            'servings' => 2,
            'active' => true,
        ])->assertRedirect();

        $product = Product::query()->where('name', 'Family burger')->first();

        $this->assertNotNull($product);
        $this->assertSame(2, $product->servings);

        $this->put(route('menu.products.update', $product), [
            'name' => 'Family burger',
            'price' => 49.90,
            'category_id' => $category->id,
            'servings' => 4,
            'active' => true,
        ])->assertRedirect();

        $this->assertSame(4, $product->fresh()->servings);
    }

    public function test_servings_must_be_at_least_one(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'servings' => 0,
            'active' => true,
        ])->assertSessionHasErrors('servings');
    }

    public function test_new_product_defaults_to_serving_one_person(): void
    {
        $venue = Venue::factory()->create();
        $this->loginAs(UserRole::Owner, $venue);

        $menu = Menu::factory()->create(['venue_id' => $venue->id]);
        $category = Category::factory()->create(['menu_id' => $menu->id]);

        $this->post(route('menu.products.store'), [
            'name' => 'Burger',
            'price' => 29.90,
            'category_id' => $category->id,
            'active' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['name' => 'Burger', 'servings' => 1]);
    }
}
