<?php

class SecmAuthMiddleware
{
    private static $authApiUrl = 'http://secmusuarios.test:8083/api';
    private static $loginUrl = 'http://secmusuarios.test:8083';
    private static $systemName;
    private static $currentUser = null;

    public static function init(string $systemName)
    {
        self::$systemName = $systemName;
    }

    public static function checkAuth(bool $redirect = true): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $headers = getallheaders();
        $token = $headers['Authorization'] ?? ($_SESSION['secm_token'] ?? null);

        if (!$token) {
            if ($redirect) {
                self::redirectToLogin();
            }
            return null;
        }

        $token = str_replace('Bearer ', '', $token);

        $userData = self::validateToken($token);

        if (!$userData) {
            if ($redirect) {
                self::redirectToLogin();
            }
            return null;
        }

        $apps = $userData['apps'] ?? [];
        if (!in_array(self::$systemName, $apps)) {
            if ($redirect) {
                http_response_code(403);
                die(json_encode(['error' => 'No tienes acceso a este sistema']));
            }
            return null;
        }

        self::$currentUser = $userData;
        return $userData;
    }

    public static function requireAuth(): array
    {
        $user = self::checkAuth();
        if (!$user) {
            exit;
        }
        return $user;
    }

    private static function validateToken(string $token): ?array
    {
        try {
            $ch = curl_init(self::$authApiUrl . '/validate');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ],
                CURLOPT_TIMEOUT => 5
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return $data['success'] ? $data : null;
            }

            return null;
        } catch (\Exception $e) {
            error_log('SecmAuth: Error validando token - ' . $e->getMessage());
            return null;
        }
    }

    private static function redirectToLogin()
    {
        $currentUrl = self::getCurrentUrl();
        $loginUrl = self::$loginUrl . '?redirect=' . urlencode($currentUrl);
        header('Location: ' . $loginUrl);
        exit;
    }

    private static function getCurrentUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        return $protocol . '://' . $host . $uri;
    }

    public static function logout()
    {
        session_start();
        unset($_SESSION['secm_token']);
        session_destroy();
        header('Location: ' . self::$loginUrl);
        exit;
    }
}