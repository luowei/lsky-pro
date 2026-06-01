<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ApiTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::share('_is_notice', false);
    }

    public function test_authenticated_user_can_create_and_delete_api_tokens_from_api_pages()
    {
        $user = User::factory()->create();
        View::share('_group', $user->group);

        $this->actingAs($user)
            ->get(route('asset-router.api'))
            ->assertOk()
            ->assertSee('Asset Router / PicGo Token')
            ->assertSee('生成 Token');

        $response = $this->actingAs($user)->post(route('user.api-tokens.store'), [
            'name' => 'PicGo Test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('plain_api_token');
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'PicGo Test',
        ]);

        $token = $user->tokens()->firstOrFail();

        $this->actingAs($user)
            ->delete(route('user.api-tokens.destroy', $token))
            ->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->id,
        ]);
    }

    public function test_authenticated_user_can_clear_only_own_api_tokens()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->createToken('PicGo A');
        $user->createToken('PicGo B');
        $otherToken = $other->createToken('Other')->accessToken;

        $this->actingAs($user)
            ->delete(route('user.api-tokens.clear'))
            ->assertRedirect();

        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $otherToken->id,
        ]);
    }
}
