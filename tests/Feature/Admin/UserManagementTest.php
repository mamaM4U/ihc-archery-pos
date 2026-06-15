<?php

namespace Tests\Feature\Admin;

use App\Models\CoachMember;
use App\Models\User;
use App\Models\UserRelationship;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders to set up roles and permissions
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        // Create an admin user
        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@test.com',
        ]);
    }

    public function test_admin_can_view_users_list(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee($this->admin->name);
    }

    public function test_admin_can_filter_users_by_role_and_status(): void
    {
        // Create a coach
        $coach = User::factory()->coach()->create([
            'name' => 'Coach Budi',
            'is_active' => true,
        ]);

        // Create an inactive member
        $inactiveMember = User::factory()->member()->inactive()->create([
            'name' => 'Atlet Susi',
        ]);

        // Filter by coach role
        $response = $this->actingAs($this->admin)
            ->get(route('users.index', ['role' => 'coach']));

        $response->assertStatus(200);
        $response->assertSee('Coach Budi');
        $response->assertDontSee('Atlet Susi');

        // Filter by inactive status
        $response = $this->actingAs($this->admin)
            ->get(route('users.index', ['status' => 'inactive']));

        $response->assertStatus(200);
        $response->assertSee('Atlet Susi');
        $response->assertDontSee('Coach Budi');
    }

    public function test_admin_can_create_user(): void
    {
        Storage::fake('public');
        $avatar = UploadedFile::fake()->image('avatar.jpg');

        $payload = [
            'name' => 'Coach Joko',
            'email' => 'joko@coach.com',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'coach',
            'is_active' => true,
            'avatar' => $avatar,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('users.store'), $payload);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'joko@coach.com',
            'role' => 'coach',
            'is_active' => true,
        ]);

        $user = User::where('email', 'joko@coach.com')->first();
        $this->assertTrue($user->hasRole('coach'));
        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->getRawOriginal('avatar'));
    }

    public function test_admin_can_update_user(): void
    {
        $user = User::factory()->member()->create([
            'name' => 'Old Name',
            'email' => 'old@member.com',
            'phone' => '08000000',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'New Name',
            'email' => 'new@member.com',
            'phone' => '08111111',
            'role' => 'coach', // Changing role
            'is_active' => false,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('users.update', $user->id), $payload);

        $response->assertRedirect(route('users.index'));

        $updatedUser = $user->fresh();
        $this->assertEquals('New Name', $updatedUser->name);
        $this->assertEquals('new@member.com', $updatedUser->email);
        $this->assertEquals('08111111', $updatedUser->phone);
        $this->assertEquals('coach', $updatedUser->role);
        $this->assertFalse($updatedUser->is_active);
        $this->assertTrue($updatedUser->hasRole('coach'));
    }

    public function test_admin_can_delete_user(): void
    {
        $user = User::factory()->member()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('users.destroy', $user->id));

        $response->assertRedirect();
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_can_view_assignments_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('users.assignments'));

        $response->assertStatus(200);
    }

    public function test_admin_can_assign_coach_to_member(): void
    {
        $coach = User::factory()->coach()->create();
        $member = User::factory()->member()->create();

        $payload = [
            'coach_id' => $coach->id,
            'member_id' => $member->id,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('users.assign-coach'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('coach_members', [
            'coach_id' => $coach->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_admin_cannot_assign_invalid_roles_to_coach_member(): void
    {
        $notACoach = User::factory()->member()->create();
        $member = User::factory()->member()->create();

        $payload = [
            'coach_id' => $notACoach->id,
            'member_id' => $member->id,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('users.assign-coach'), $payload);

        $response->assertSessionHasErrors(['coach_id']);
        $this->assertDatabaseMissing('coach_members', [
            'member_id' => $member->id,
        ]);
    }

    public function test_admin_can_assign_guardian_to_member(): void
    {
        $guardian = User::factory()->guardian()->create();
        $member = User::factory()->member()->create();

        $payload = [
            'guardian_id' => $guardian->id,
            'member_id' => $member->id,
            'can_approve_booking' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('users.assign-guardian'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_relationships', [
            'guardian_id' => $guardian->id,
            'member_id' => $member->id,
            'can_approve_booking' => true,
        ]);
    }

    public function test_admin_can_remove_coach_assignment(): void
    {
        $coach = User::factory()->coach()->create();
        $member = User::factory()->member()->create();

        CoachMember::create([
            'coach_id' => $coach->id,
            'member_id' => $member->id,
        ]);

        $payload = [
            'coach_id' => $coach->id,
            'member_id' => $member->id,
        ];

        $response = $this->actingAs($this->admin)
            ->delete(route('users.remove-coach'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseMissing('coach_members', [
            'coach_id' => $coach->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_admin_can_remove_guardian_assignment(): void
    {
        $guardian = User::factory()->guardian()->create();
        $member = User::factory()->member()->create();

        UserRelationship::create([
            'guardian_id' => $guardian->id,
            'member_id' => $member->id,
            'can_approve_booking' => true,
        ]);

        $payload = [
            'guardian_id' => $guardian->id,
            'member_id' => $member->id,
        ];

        $response = $this->actingAs($this->admin)
            ->delete(route('users.remove-guardian'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseMissing('user_relationships', [
            'guardian_id' => $guardian->id,
            'member_id' => $member->id,
        ]);
    }
}
