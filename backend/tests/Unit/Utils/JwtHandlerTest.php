<?php

namespace Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use App\Utils\JwtHandler;

class JwtHandlerTest extends TestCase
{
    private JwtHandler $jwt;

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['JWT_SECRET'] = 'test_secret_key_for_testing';
        $_ENV['JWT_ALGORITHM'] = 'HS256';
        $_ENV['JWT_EXPIRES_IN'] = '3600';
        $this->jwt = new JwtHandler();
    }

    public function testEncodeReturnsValidToken(): void
    {
        $payload = ['user_id' => 1, 'username' => 'testuser'];

        $token = $this->jwt->encode($payload);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        // JWT tiene 3 partes separadas por punto
        $this->assertCount(3, explode('.', $token));
    }

    public function testDecodeReturnsPayloadForValidToken(): void
    {
        $payload = ['user_id' => 123, 'username' => 'testuser', 'rol' => 'admin'];
        $token = $this->jwt->encode($payload);

        $decoded = $this->jwt->decode($token);

        $this->assertIsArray($decoded);
        $this->assertEquals(123, $decoded['user_id']);
        $this->assertEquals('testuser', $decoded['username']);
        $this->assertEquals('admin', $decoded['rol']);
    }

    public function testDecodeReturnsNullForInvalidToken(): void
    {
        $result = $this->jwt->decode('invalid.token.here');

        $this->assertNull($result);
    }

    public function testDecodeReturnsNullForTamperedToken(): void
    {
        $token = $this->jwt->encode(['user_id' => 1]);

        // Modificar el payload del token
        $parts = explode('.', $token);
        $parts[1] = base64_encode('{"user_id":999}');
        $tamperedToken = implode('.', $parts);

        $result = $this->jwt->decode($tamperedToken);

        $this->assertNull($result);
    }

    public function testGetExpiresInReturnsConfiguredValue(): void
    {
        $expiresIn = $this->jwt->getExpiresIn();

        $this->assertEquals(3600, $expiresIn);
    }

    public function testEncodeWithDifferentPayloads(): void
    {
        $payload1 = ['user_id' => 1, 'username' => 'user1'];
        $payload2 = ['user_id' => 2, 'username' => 'user2'];

        $token1 = $this->jwt->encode($payload1);
        $token2 = $this->jwt->encode($payload2);

        $this->assertNotEquals($token1, $token2);

        $decoded1 = $this->jwt->decode($token1);
        $decoded2 = $this->jwt->decode($token2);

        $this->assertEquals(1, $decoded1['user_id']);
        $this->assertEquals(2, $decoded2['user_id']);
    }

    public function testStaticGenerarMethod(): void
    {
        $secret = 'another_secret';
        $payload = ['user_id' => 42, 'rol' => 'user'];

        $token = JwtHandler::generar($secret, $payload);

        $this->assertIsString($token);
        $this->assertCount(3, explode('.', $token));
    }

    public function testStaticValidarMethod(): void
    {
        $secret = 'static_test_secret';
        $payload = ['user_id' => 99, 'username' => 'static_user'];

        $token = JwtHandler::generar($secret, $payload);
        $decoded = JwtHandler::validar($secret, $token);

        $this->assertNotNull($decoded);
        $this->assertEquals(99, $decoded->user_id);
    }

    public function testStaticValidarReturnsNullForWrongSecret(): void
    {
        $token = JwtHandler::generar('correct_secret', ['user_id' => 1]);
        $decoded = JwtHandler::validar('wrong_secret', $token);

        $this->assertNull($decoded);
    }
}
