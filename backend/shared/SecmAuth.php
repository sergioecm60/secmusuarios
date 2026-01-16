<?php

class SecmAuth {
    private static $apiUrl = 'http://secmusuarios.test:8083/api';
    private static $secret = 'c4mb14r_53cr3t_k3y_f0r_pr0d_us3_str0ng_p4ssw0rd';

    public static function validarPara(string $sistema): ?array
    {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';
        $token = str_replace('Bearer ', '', $token);

        if (empty($token)) {
            return null;
        }

        try {
            $response = self::callApi('/validate', [], $token);
            $data = json_decode($response, true);

            if (!$data || !isset($data['success']) || !$data['success']) {
                return null;
            }

            $apps = $data['apps'] ?? [];
            if (!in_array($sistema, $apps)) {
                return null;
            }

            return [
                'user_id' => $data['user_id'],
                'username' => $data['username'],
                'rol' => $data['rol'] ?? 'user',
                'apps' => $apps
            ];
        } catch (\Exception $e) {
            error_log('SECM Auth Error: ' . $e->getMessage());
            return null;
        }
    }

    public static function login(string $username, string $password): ?array
    {
        try {
            $response = self::callApi('/login', [
                'username' => $username,
                'password' => $password
            ]);

            $data = json_decode($response, true);

            if (!$data || !isset($data['success']) || !$data['success']) {
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            error_log('SECM Login Error: ' . $e->getMessage());
            return null;
        }
    }

    private static function callApi(string $endpoint, array $data = [], string $token = ''): string
    {
        $ch = curl_init(self::$apiUrl . $endpoint);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3
        ]);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception('Curl error: ' . $error);
        }

        return $response;
    }

    public static function requireAuth(string $sistema)
    {
        $usuario = self::validarPara($sistema);
        if (!$usuario) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Acceso denegado']);
            exit;
        }
        return $usuario;
    }
}