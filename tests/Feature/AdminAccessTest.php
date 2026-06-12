<?php

use App\Models\User;
use Database\Seeders\RolesSeeder;

beforeEach(function () {
    $this->seed(RolesSeeder::class);
});

it('redirects guests to login', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('forbids customers from the admin area', function () {
    $user = User::factory()->create();
    $user->assignRole('customer');

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('allows admins into the admin area', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('allows staff into the admin area', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('shares staff status with the frontend', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('auth.isStaff', true));
});
