<?php

use App\Models\User;
use Database\Seeders\AdminCatalogSeeder;

beforeEach(function () {
    $this->seed(AdminCatalogSeeder::class);
});

test('the public shell exposes authentication entry points', function () {
    $this->get('/')->assertOk()->assertSee('Business Logic Lab');
    $this->get('/login')->assertOk()->assertSee('Tiếp tục vào lab');
    $this->get('/register')->assertOk()->assertSee('Tạo tài khoản học tập');
});

test('the public shell changes its navigation for an authenticated user', function () {
    $this->get('/')
        ->assertSee('Đăng nhập')
        ->assertSee('Tạo tài khoản');

    $user = User::factory()->create(['name' => 'Ada Lovelace']);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('Xin chào, Ada Lovelace')
        ->assertSee('Vào learning shell')
        ->assertDontSee('Đăng nhập')
        ->assertDontSee('Tạo tài khoản');
});

test('a learner can register and reach the protected learning shell', function () {
    $response = $this->post('/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'correct horse battery staple',
        'password_confirmation' => 'correct horse battery staple',
    ]);

    $response->assertRedirect('/home');
    $this->assertAuthenticatedAs(User::where('email', 'ada@example.com')->first());
    $this->get('/home')->assertOk()->assertSee('Ada Lovelace');
});

test('the learning shell requires authentication', function () {
    $this->get('/home')->assertRedirect('/login');
});

test('a learner can log in and log out', function () {
    $user = User::factory()->create([
        'email' => 'learner@example.com',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/home');

    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});
