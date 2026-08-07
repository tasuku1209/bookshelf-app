<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ap_i版ログインで正しいメールアドレスとパスワードでログインできる(): void
    {
        // Arrange
        $password = 'password';

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => $password,
        ]);

        $data = [
            'email' => 'test@example.com',
            'password' => $password,
        ];

        // Act
        $response = $this->postJson('/api/v1/login', $data);

        // Assert
        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'ログインしました',
        ]);

        $response->assertJsonPath('data.user.id', $user->id);

        $response->assertJsonStructure([
            'message',
            'data' => [
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ],
        ]);
    }

    public function test_ap_i版ログインで誤ったメールアドレスとパスワードではログインできない(): void
    {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $data = [
            'email' => 'wrong@example.com',
            'password' => 'wrong-password',
        ];

        // Act
        $response = $this->postJson('/api/v1/login', $data);

        // Assert
        $response->assertStatus(401);

        $response->assertJson([
            'message' => 'メールアドレスまたはパスワードが正しくありません',
        ]);

        $response->assertJsonMissingPath('data.token');
    }

    public function test_ap_i版ログインでメールアドレスとパスワード未入力ではログインできない(): void
    {
        // Act
        $response = $this->postJson('/api/v1/login', []);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'email',
            'password',
        ]);
    }

    public function test_ap_i版ログインで不正なメールアドレス形式ではログインできない(): void
    {
        // Arrange
        $data = [
            'email' => 'invalid-email',
            'password' => 'password',
        ];

        // Act
        $response = $this->postJson('/api/v1/login', $data);

        // Assert
        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'email',
        ]);
    }

    public function test_ap_i版ログアウトで認証済みユーザーはログアウトできる(): void
    {
        // Arrange
        $user = User::factory()->create();

        $token = $user->createToken('api-token');

        // Act
        $response = $this
            ->withToken($token->plainTextToken)
            ->postJson('/api/v1/logout');

        // Assert
        $response->assertStatus(200);

        $response->assertJson([
            'message' => 'ログアウトしました',
        ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $token->accessToken->id,
        ]);
    }

    public function test_ap_i版ログアウトで未認証ユーザーはログアウトできない(): void
    {
        // Act
        $response = $this->postJson('/api/v1/logout');

        // Assert
        $response->assertStatus(401);
    }
}
