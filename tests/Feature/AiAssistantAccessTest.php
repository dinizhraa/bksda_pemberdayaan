<?php

namespace Tests\Feature;

use App\Models\AiConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiAssistantAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_pages_require_authentication(): void
    {
        $this->get(route('ai.chat.index'))
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_access_ai_pages_or_endpoints(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('ai.chat.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('ai.analytics.index'))
            ->assertForbidden();

        $conversation = AiConversation::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('ai.chat.conversations.destroy', $conversation))
            ->assertForbidden();
    }
}
