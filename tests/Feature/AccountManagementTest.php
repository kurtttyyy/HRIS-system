<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('first_name'); $table->string('last_name');
            $table->string('email'); $table->string('role'); $table->text('admin_permissions')->nullable();
            $table->string('password'); $table->string('remember_token')->nullable();
            $table->text('temporary_pin')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('employees', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->string('employee_id');
        });
    }

    private function account(string $role = 'Admin'): User
    {
        $id = DB::table('users')->insertGetId(['first_name' => 'Test', 'last_name' => 'Staff', 'email' => uniqid().'@example.com', 'role' => $role, 'password' => Hash::make('admin-secret')]);
        return User::findOrFail($id);
    }

    public function test_accounts_restores_admin_from_tab_session_before_authentication(): void
    {
        $admin = $this->account();
        $this->withSession(['tab_auth_users_admin' => ['admin-tab' => $admin->id]])
            ->get('/system/accounts?tab_session=admin-tab')->assertOk()->assertSee('Staff accounts');
        $this->postJson('/system/accounts/'.$admin->id.'/verify?tab_session=admin-tab', ['admin_password' => 'admin-secret'])
            ->assertOk()->assertJsonStructure(['token']);
    }

    public function test_employee_cannot_access_accounts_or_verify_password(): void
    {
        $employee = $this->account('Employee');
        $this->actingAs($employee)->get('/system/accounts')->assertForbidden();
        $this->postJson('/system/accounts/'.$employee->id.'/verify', ['admin_password' => 'admin-secret'])->assertForbidden();
    }

    public function test_password_change_requires_verification_and_token_cannot_be_reused(): void
    {
        $admin = $this->account(); $target = $this->account();
        $this->actingAs($admin);
        $url = '/system/accounts/'.$target->id;
        $payload = ['password' => 'new-password', 'password_confirmation' => 'new-password'];
        $this->patchJson($url, $payload)->assertForbidden();
        $this->postJson($url.'/verify', ['admin_password' => 'wrong'])->assertUnprocessable();
        $token = $this->postJson($url.'/verify', ['admin_password' => 'admin-secret'])->assertOk()->json('token');
        $payload['edit_token'] = $token;
        $this->patchJson($url, $payload)->assertOk();
        $this->assertTrue(Hash::check('new-password', $target->fresh()->password));
        $this->patchJson($url, $payload)->assertForbidden();
    }

    public function test_deletion_retains_record_and_revokes_existing_session(): void
    {
        $admin = $this->account(); $target = $this->account();
        $this->actingAs($admin)->delete('/system/accounts/'.$target->id)->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertNull(User::find($target->id));
        $this->actingAs($target)->get('/system/accounts')->assertRedirect(route('login_display'));
    }

    public function test_admin_cannot_delete_self_and_limited_admin_cannot_manage_accounts(): void
    {
        $admin = $this->account();
        $this->actingAs($admin)->deleteJson('/system/accounts/'.$admin->id)->assertUnprocessable();
        $admin->admin_permissions = ['employees']; $admin->save();
        $this->get('/system/accounts')->assertForbidden();
    }

    public function test_search_finds_employee_id_and_displays_stored_password_hash_to_admin(): void
    {
        $admin = $this->account(); $target = $this->account();
        DB::table('employees')->insert(['user_id' => $target->id, 'employee_id' => 'EMP-123']);
        $this->actingAs($admin)->get('/system/accounts?search=EMP-123')->assertOk()
            ->assertSee('EMP-123')->assertSee($target->getRawOriginal('password'));
    }

    public function test_deleted_section_search_and_restore_preserve_password_and_employee_record(): void
    {
        $admin = $this->account(); $target = $this->account();
        $originalHash = $target->password;
        DB::table('employees')->insert(['user_id' => $target->id, 'employee_id' => 'DELETED-123']);
        $target->delete();
        $this->actingAs($admin)->get('/system/accounts?search=DELETED-123')->assertOk()->assertViewHas('accounts', fn ($accounts) => $accounts->total() === 0);
        $this->get('/system/accounts?section=deleted&search=DELETED-123')->assertOk()
            ->assertSee('DELETED-123')->assertSee('Restore account')->assertDontSee($originalHash);
        $this->post('/system/accounts/'.$target->id.'/restore', ['search' => 'DELETED-123'])
            ->assertRedirect(route('admin.accountManagement.index', ['section' => 'deleted', 'search' => 'DELETED-123']));
        $this->assertSame($originalHash, User::findOrFail($target->id)->password);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('employees', ['user_id' => $target->id, 'employee_id' => 'DELETED-123']);
        $this->get('/system/accounts?search=DELETED-123')->assertOk()->assertSee('DELETED-123');
        $this->post('/system/accounts/'.$target->id.'/restore')->assertNotFound();
    }

    public function test_restore_is_for_authorized_admins_only(): void
    {
        $target = $this->account(); $target->delete();
        $employee = $this->account('Employee');
        $this->actingAs($employee)->post('/system/accounts/'.$target->id.'/restore')->assertForbidden();
        $admin = $this->account(); $admin->admin_permissions = ['employees']; $admin->save();
        $this->actingAs($admin)->post('/system/accounts/'.$target->id.'/restore')->assertForbidden();
        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }
}
