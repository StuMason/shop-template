<?php

use App\Models\User;
use App\Support\ShopSettings;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->staff = User::factory()->create();
    $this->staff->assignRole('staff');
});

it('lets admins update shop settings and reflects them in shared props', function () {
    $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), [
            'name' => 'Mason & Co',
            'tagline' => 'Fine goods, no nonsense.',
            'description' => 'A test shop.',
            'contact_email' => 'shop@mason.co',
            'order_prefix' => 'MAS',
        ])
        ->assertRedirect();

    expect(app(ShopSettings::class)->name())->toBe('Mason & Co')
        ->and(app(ShopSettings::class)->orderPrefix())->toBe('MAS');

    $this->actingAs($this->admin)
        ->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('shop.name', 'Mason & Co'));
});

it('keeps staff out of settings and user management', function () {
    $this->actingAs($this->staff)->get(route('admin.settings.edit'))->assertForbidden();
    $this->actingAs($this->staff)->get(route('admin.users.index'))->assertForbidden();
});

it('lets admins change a user role', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');

    $this->actingAs($this->admin)
        ->patch(route('admin.users.role', $customer), ['role' => 'staff'])
        ->assertRedirect();

    expect($customer->fresh()->hasRole('staff'))->toBeTrue()
        ->and($customer->fresh()->hasRole('customer'))->toBeFalse();
});

it('stops admins demoting themselves', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.users.role', $this->admin), ['role' => 'customer'])
        ->assertSessionHasErrors('role');

    expect($this->admin->fresh()->hasRole('admin'))->toBeTrue();
});

it('searches users by name or email', function () {
    User::factory()->create(['name' => 'Findable Fred', 'email' => 'fred@example.com']);

    $this->actingAs($this->admin)
        ->get(route('admin.users.index', ['q' => 'findable']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->count('users.data', 1));
});
