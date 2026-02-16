<?php

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTService
{
    private string $secret;
    private int $expiration;

    public function __construct(string $jwtSecret, int $jwtExpiration = 86400)
    {
        $this->secret = $jwtSecret;
        $this->expiration = $jwtExpiration;
    }

    /**
     * Generate a JWT token
     */
    public function generateToken(array $payload): string
    {
        $now = time();

        $data = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $this->expiration
        ]);

        return JWT::encode($data, $this->secret, 'HS256');
    }

    /**
     * Decode and validate a JWT token
     */
    public function decodeToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verify if a token is valid
     */
    public function verifyToken(string $token): bool
    {
        $decoded = $this->decodeToken($token);
        return $decoded !== null;
    }

    /**
     * Extract user ID from token
     */
    public function getUserIdFromToken(string $token): ?string
    {
        $decoded = $this->decodeToken($token);
        return $decoded['userId'] ?? null;
    }

    /**
     * Extract user role from token
     */
    public function getUserRoleFromToken(string $token): ?string
    {
        $decoded = $this->decodeToken($token);
        return $decoded['role'] ?? null;
    }
}
