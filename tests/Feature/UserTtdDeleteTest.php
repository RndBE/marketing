<?php

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function userTtdDeleteManager(Company $company): User
{
    $role = Role::create([
        'name' => 'User Manager',
        'slug' => 'user-manager-' . uniqid(),
    ]);
    $permission = Permission::firstOrCreate(
        ['slug' => 'manage-users'],
        ['name' => 'Kelola Users', 'group' => 'User Management']
    );
    $role->permissions()->syncWithoutDetaching([$permission->id]);

    $user = User::factory()->create(['company_id' => $company->id]);
    $user->roles()->attach($role->id);

    return $user;
}

test('user ttd upload can be deleted after upload', function () {
    Storage::fake('public');

    $company = Company::create([
        'code' => 'TTD',
        'name' => 'TTD Test Company',
    ]);
    $manager = userTtdDeleteManager($company);
    $target = User::factory()->create([
        'company_id' => $company->id,
        'ttd' => 'signatures/current-ttd.png',
    ]);

    Storage::disk('public')->put($target->ttd, 'fake image content');

    $response = $this->actingAs($manager)
        ->delete(route('users.ttd.destroy', $target));

    $response->assertRedirect();
    Storage::disk('public')->assertMissing('signatures/current-ttd.png');
    expect($target->fresh()->ttd)->toBeNull();
});
