<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_developer_docs(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/developer/docs')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Developer/Docs/Index'));
    }

    public function test_guest_cannot_view_developer_docs(): void
    {
        $this->get('/developer/docs')
            ->assertRedirect('/login');
    }
}

