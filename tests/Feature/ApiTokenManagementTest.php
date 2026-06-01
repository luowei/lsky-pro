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
        $response->assertSessionMissing('plain_api_token');
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'PicGo Test',
        ]);

        $token = $user->tokens()->firstOrFail();
        $this->assertNotEmpty($token->encrypted_plain_text_token);

        $this->actingAs($user)
            ->get(route('asset-router.api'))
            ->assertOk()
            ->assertSee('Token 默认脱敏展示')
            ->assertSee('复制')
            ->assertSee('toggle-api-token');

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

    public function test_legacy_tokens_without_saved_plain_text_are_marked_unavailable()
    {
        $user = User::factory()->create();
        View::share('_group', $user->group);
        $user->createToken('Legacy Token');

        $this->actingAs($user)
            ->get(route('asset-router.api'))
            ->assertOk()
            ->assertSee('旧 Token 不可查看，请重新生成');
    }
}
