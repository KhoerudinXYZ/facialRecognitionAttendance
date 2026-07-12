<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guests hitting the root URL land on the student portal login, since
     * students are the largest and most frequent user group (see routes/web.php).
     */
    public function test_guest_root_redirects_to_siswa_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('siswa.login'));
    }

    /**
     * A logged-in admin/staff hitting root still lands on their own dashboard.
     */
    public function test_authenticated_staff_root_redirects_to_dashboard(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard'));
    }
}
