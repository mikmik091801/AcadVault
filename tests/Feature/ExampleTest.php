<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guests_are_sent_to_the_login_screen(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_the_login_screen_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('AcadVault')
            ->assertSee('Welcome back');
    }
}
