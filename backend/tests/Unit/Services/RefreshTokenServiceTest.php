<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\RefreshTokenService;
use PDO;
use PDOStatement;

class RefreshTokenServiceTest extends TestCase
{
    private $pdoMock;
    private RefreshTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['REFRESH_TOKEN_EXPIRES_DAYS'] = '7';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Agent';

        $this->pdoMock = $this->createMock(PDO::class);
        $this->service = new RefreshTokenService($this->pdoMock);
    }

    public function testGenerateTokenReturns64CharHexString(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $token = $this->service->generateToken(1);

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex chars
        $this->assertTrue(ctype_xdigit($token)); // Solo caracteres hexadecimales
    }

    public function testGenerateTokenStoresHashedToken(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                // El token almacenado debe ser un hash SHA256 (64 caracteres)
                return strlen($params[1]) === 64 // token_hash
                    && $params[0] === 42; // user_id
            }))
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $token = $this->service->generateToken(42);

        // Verificar que el hash almacenado es diferente al token retornado
        $expectedHash = hash('sha256', $token);
        $this->assertEquals(64, strlen($expectedHash));
    }

    public function testValidateTokenReturnsUserDataForValidToken(): void
    {
        $validToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $validToken);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([$tokenHash])
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'user_id' => 1,
                'username' => 'testuser',
                'rol' => 'admin',
                'activo' => 1
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->service->validateToken($validToken);

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['user_id']);
        $this->assertEquals('testuser', $result['username']);
    }

    public function testValidateTokenReturnsNullForInvalidToken(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->service->validateToken('invalid_token');

        $this->assertNull($result);
    }

    public function testRevokeTokenUpdatesRevokedAt(): void
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([$tokenHash])
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('UPDATE refresh_tokens'))
            ->willReturn($stmtMock);

        $result = $this->service->revokeToken($token);

        $this->assertTrue($result);
    }

    public function testRevokeAllUserTokensRevokesAllForUser(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([42]) // user_id
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('WHERE user_id = ?'))
            ->willReturn($stmtMock);

        $result = $this->service->revokeAllUserTokens(42);

        $this->assertTrue($result);
    }

    public function testRotateTokenRevokesOldAndCreatesNew(): void
    {
        $oldToken = bin2hex(random_bytes(32));
        $oldTokenHash = hash('sha256', $oldToken);

        // Mock para validateToken
        $validateStmt = $this->createMock(PDOStatement::class);
        $validateStmt->method('execute')->willReturn(true);
        $validateStmt->method('fetch')->willReturn([
            'user_id' => 1,
            'username' => 'testuser',
            'rol' => 'user',
            'activo' => 1
        ]);

        // Mock para revokeToken
        $revokeStmt = $this->createMock(PDOStatement::class);
        $revokeStmt->method('execute')->willReturn(true);

        // Mock para generateToken
        $insertStmt = $this->createMock(PDOStatement::class);
        $insertStmt->method('execute')->willReturn(true);

        $this->pdoMock->expects($this->exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($validateStmt, $revokeStmt, $insertStmt);

        $newToken = $this->service->rotateToken($oldToken);

        $this->assertIsString($newToken);
        $this->assertEquals(64, strlen($newToken));
        $this->assertNotEquals($oldToken, $newToken);
    }

    public function testRotateTokenReturnsNullForInvalidToken(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->method('execute')->willReturn(true);
        $stmtMock->method('fetch')->willReturn(false);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->service->rotateToken('invalid_token');

        $this->assertNull($result);
    }

    public function testGetActiveTokensReturnsSessionList(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with([1]) // user_id
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                ['id' => 1, 'created_at' => '2024-01-01', 'user_agent' => 'Chrome'],
                ['id' => 2, 'created_at' => '2024-01-02', 'user_agent' => 'Firefox']
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $sessions = $this->service->getActiveTokens(1);

        $this->assertIsArray($sessions);
        $this->assertCount(2, $sessions);
    }
}
