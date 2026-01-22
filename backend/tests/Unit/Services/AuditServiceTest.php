<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\AuditService;
use PDO;
use PDOStatement;

class AuditServiceTest extends TestCase
{
    private $pdoMock;
    private AuditService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear mock de PDO
        $this->pdoMock = $this->createMock(PDO::class);
        $this->service = new AuditService($this->pdoMock);

        // Configurar $_SERVER para tests
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Agent';
    }

    public function testLogCreatesAuditEntry(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return $params[0] === 1 // user_id
                    && $params[1] === 'testuser' // username
                    && $params[2] === 'test_action'; // action
            }))
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->service->log('test_action', 1, 'testuser');

        $this->assertTrue($result);
    }

    public function testLogLoginSuccessCreatesCorrectEntry(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return $params[0] === 42 // user_id
                    && $params[1] === 'loginuser' // username
                    && $params[2] === 'login_success'; // action
            }))
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->service->logLoginSuccess(42, 'loginuser');

        $this->assertTrue($result);
    }

    public function testLogLoginFailedWorksWithoutUserId(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return $params[0] === null // user_id es null
                    && $params[1] === 'nonexistent' // username
                    && $params[2] === 'login_failed'; // action
            }))
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->service->logLoginFailed('nonexistent', 'user_not_found');

        $this->assertTrue($result);
    }

    public function testLogUserCreateExcludesPassword(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                // new_values es el parametro 7 (indice 6)
                $newValues = json_decode($params[6], true);
                return !isset($newValues['password'])
                    && !isset($newValues['password_hash'])
                    && $newValues['username'] === 'newuser';
            }))
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->service->logUserCreate(1, 'admin', 99, [
            'username' => 'newuser',
            'email' => 'new@test.com',
            'password' => 'secret_password',
            'password_hash' => 'hashed_value'
        ]);

        $this->assertTrue($result);
    }

    public function testLogAccountLockedIncludesAttemptCount(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                // additional_data es el parametro 10 (indice 9)
                $additionalData = json_decode($params[9], true);
                return $params[2] === 'account_locked'
                    && $additionalData['attempts'] === 5;
            }))
            ->willReturn(true);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturn($stmtMock);

        $result = $this->service->logAccountLocked('blockeduser', 123, 5);

        $this->assertTrue($result);
    }

    public function testLogReturnsFalseOnException(): void
    {
        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willThrowException(new \Exception('Database error'));

        $result = $this->service->log('test_action', 1, 'testuser');

        $this->assertFalse($result);
    }

    public function testGetAuditLogsWithFilters(): void
    {
        $stmtMock = $this->createMock(PDOStatement::class);
        $stmtMock->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $stmtMock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                ['id' => 1, 'action' => 'login_success', 'username' => 'testuser']
            ]);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('action = ?'))
            ->willReturn($stmtMock);

        $logs = $this->service->getAuditLogs(50, 0, ['action' => 'login_success']);

        $this->assertIsArray($logs);
        $this->assertCount(1, $logs);
        $this->assertEquals('login_success', $logs[0]['action']);
    }
}
