<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_initial' => 'P',
            'email' => 'juan@university.edu',
            'phone' => '0917 555 0123',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ], $overrides);
    }

    public function test_registration_records_the_name_parts_and_contact_number(): void
    {
        $this->post('/register', $this->payload())->assertRedirect();

        $user = User::where('email', 'juan@university.edu')->firstOrFail();

        $this->assertSame('Dela Cruz', $user->last_name);
        $this->assertSame('Juan', $user->first_name);
        $this->assertSame('P', $user->middle_initial);
        $this->assertSame('0917 555 0123', $user->phone);

        // `name` is composed from the parts, and is what the rest of the app
        // and the document fingerprints read.
        $this->assertSame('Juan P. Dela Cruz', $user->name);
        $this->assertSame('Dela Cruz, Juan P.', $user->filedName());
    }

    public function test_the_surname_and_given_name_are_required(): void
    {
        $this->post('/register', $this->payload(['last_name' => '', 'first_name' => '']))
            ->assertSessionHasErrors(['last_name', 'first_name']);

        $this->assertGuest();
    }

    public function test_the_middle_initial_is_optional_but_must_be_one_letter(): void
    {
        $this->post('/register', $this->payload(['middle_initial' => 'PQ']))
            ->assertSessionHasErrors('middle_initial');

        $this->post('/register', $this->payload(['middle_initial' => '']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Juan Dela Cruz', User::where('email', 'juan@university.edu')->firstOrFail()->name);
    }

    public function test_a_contact_number_is_optional_but_validated(): void
    {
        $this->post('/register', $this->payload(['phone' => 'not a number']))
            ->assertSessionHasErrors('phone');

        $this->post('/register', $this->payload(['phone' => '']))
            ->assertSessionHasNoErrors();
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'last_name' => 'Dela Cruz',
            'first_name' => 'Test',
            'middle_initial' => 'U',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
