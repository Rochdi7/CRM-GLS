<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root URL redirects to the Backoffice login (admin-first phase);
     * the public Frontoffice home lives at /home.
     */
    public function test_the_root_url_redirects_to_backoffice_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/backoffice/login');
    }

    public function test_the_frontoffice_home_returns_a_successful_response(): void
    {
        $response = $this->get('/home');

        $response->assertStatus(200);
    }
}
