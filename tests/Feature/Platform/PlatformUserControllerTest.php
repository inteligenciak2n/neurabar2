<?php

namespace Tests\Feature\Platform;

use App\Enums\ProfileEnum;
use App\Models\AuditLog;
use App\Models\User;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class PlatformUserControllerTest extends TestCase
{
    use RefreshAllDatabases;

    public function test_tenant_user_cannot_be_updated_from_the_backoffice(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $tenantUser = User::factory()->create(['profile' => ProfileEnum::Client]);

        $this->put(route('platform.users.update', $tenantUser->id), [
            'name' => 'Hijacked',
        ])->assertNotFound();

        $this->assertNotSame('Hijacked', $tenantUser->fresh()->name);
    }

    public function test_tenant_user_cannot_be_deleted_from_the_backoffice(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $tenantUser = User::factory()->create(['profile' => ProfileEnum::Client]);

        $this->delete(route('platform.users.destroy', $tenantUser->id))->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $tenantUser->id]);
    }

    public function test_platform_user_cannot_delete_themselves(): void
    {
        $admin = $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $this->delete(route('platform.users.destroy', $admin->id))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_platform_user_can_delete_another_platform_user(): void
    {
        $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $target = User::factory()->create(['profile' => ProfileEnum::Finance]);

        $this->delete(route('platform.users.destroy', $target->id))->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_deleting_a_platform_user_is_audited(): void
    {
        $actor = $this->loginAsPlatformUser(ProfileEnum::SuperAdmin);

        $target = User::factory()->create(['profile' => ProfileEnum::Finance]);

        $this->delete(route('platform.users.destroy', $target->id))->assertRedirect();

        $log = AuditLog::query()->where('action', 'platform_user.deleted')->firstOrFail();

        $this->assertSame($target->id, $log->auditable_id);
        $this->assertSame($actor->id, $log->actor_id);
        $this->assertSame($target->email, $log->before['email']);
    }
}
