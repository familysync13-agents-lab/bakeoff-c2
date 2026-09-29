<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_is_served_at_signup()
    {
        $this->assertSame(url('/signup'), route('register'));

        $this->get('/signup')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/register'));
    }

    public function test_new_users_can_sign_up_without_a_password_confirmation()
    {
        $response = $this->post('/signup', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'abcdefgh',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/lists');
        $this->assertSame('Test User', User::query()->sole()->name);
    }

    public function test_passwords_shorter_than_eight_characters_are_rejected()
    {
        $this->from('/signup')->post('/signup', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'abcdefg',
        ])->assertRedirect('/signup')->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_emails_must_be_unique()
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/signup', [
            'name' => 'Test User',
            'email' => 'taken@example.com',
            'password' => 'abcdefgh',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
