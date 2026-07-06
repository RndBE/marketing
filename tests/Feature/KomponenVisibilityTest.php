<?php

use App\Models\Company;
use App\Models\Komponen;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

function komponenTestCompany(string $code): Company
{
    return Company::firstOrCreate(
        ['code' => $code],
        ['name' => 'Komponen Company ' . $code]
    );
}

function komponenUserWithPricelistPermission(Company $company): User
{
    $user = User::factory()->create(['company_id' => $company->id]);
    $role = Role::create([
        'name' => 'Komponen Tester ' . uniqid(),
        'slug' => 'komponen-tester-' . uniqid(),
    ]);
    $permission = Permission::firstOrCreate(
        ['slug' => 'manage-pricelist'],
        ['name' => 'Manage Pricelist', 'group' => 'Price List']
    );

    $role->permissions()->attach($permission->id);
    $user->roles()->attach($role->id);

    return $user;
}

test('komponen api returns active components from every company', function () {
    $companyOne = komponenTestCompany('KOMP-ONE');
    $companyTwo = komponenTestCompany('KOMP-TWO');
    $user = komponenUserWithPricelistPermission($companyTwo);

    Komponen::create([
        'company_id' => $companyOne->id,
        'kode' => 'ACTIVE-ONE',
        'nama' => 'Active Company One',
        'satuan' => 'Unit',
        'harga' => 1000,
        'is_active' => true,
    ]);
    Komponen::create([
        'company_id' => $companyTwo->id,
        'kode' => 'ACTIVE-TWO',
        'nama' => 'Active Company Two',
        'satuan' => 'Unit',
        'harga' => 2000,
        'is_active' => true,
    ]);
    Komponen::create([
        'company_id' => $companyOne->id,
        'kode' => 'INACTIVE-ONE',
        'nama' => 'Inactive Company One',
        'satuan' => 'Unit',
        'harga' => 3000,
        'is_active' => false,
    ]);

    $response = $this->actingAs($user)->getJson(route('api.komponen.list'));

    $response->assertOk();
    $names = collect($response->json())->pluck('nama');

    expect($names)->toContain('Active Company One')
        ->and($names)->toContain('Active Company Two')
        ->and($names)->not->toContain('Inactive Company One');
});

test('komponen index shows active components from every company but hides actions for foreign company rows', function () {
    $companyOne = komponenTestCompany('KOMP-IDX-ONE');
    $companyTwo = komponenTestCompany('KOMP-IDX-TWO');
    $user = komponenUserWithPricelistPermission($companyTwo);

    $foreignActive = Komponen::create([
        'company_id' => $companyOne->id,
        'kode' => 'IDX-ACTIVE-ONE',
        'nama' => 'Index Active Company One',
        'satuan' => 'Unit',
        'harga' => 1000,
        'is_active' => true,
    ]);
    $ownedActive = Komponen::create([
        'company_id' => $companyTwo->id,
        'kode' => 'IDX-ACTIVE-TWO',
        'nama' => 'Index Active Company Two',
        'satuan' => 'Unit',
        'harga' => 2000,
        'is_active' => true,
    ]);
    Komponen::create([
        'company_id' => $companyOne->id,
        'kode' => 'IDX-INACTIVE-ONE',
        'nama' => 'Index Inactive Company One',
        'satuan' => 'Unit',
        'harga' => 3000,
        'is_active' => false,
    ]);

    $response = $this->actingAs($user)->get(route('komponen.index'));

    $response->assertOk()
        ->assertSee('Index Active Company One')
        ->assertSee('Index Active Company Two')
        ->assertDontSee('Index Inactive Company One')
        ->assertDontSee("data-id=\"{$foreignActive->id}\"", false)
        ->assertSee("data-id=\"{$ownedActive->id}\"", false);
});
