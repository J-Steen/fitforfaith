<?php
namespace App\Services;

abstract class BasePlatformService {
    /** Map a platform-specific activity type string to Run/Walk/Ride or null (skip). */
    abstract public static function normalizeType(string $rawType): ?string;

    /** Normalize a raw activity from the platform API into a standard array. Returns null to skip. */
    abstract public static function normalizeActivity(array $raw): ?array;

    // ── Shared HTTP helpers ──────────────────────────────────────

    protected static function httpGet(string $url, string $accessToken, array $params = [], array $extraHeaders = []): array {
        if ($params) $url .= '?' . http_build_query($params);
        $headers = array_merge(["Authorization: Bearer $accessToken", 'Accept: application/json'], $extraHeaders);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        @curl_close($ch);

        if ($body === false) throw new \RuntimeException('HTTP GET failed (network error): ' . $url);
        $data = json_decode($body, true);
        if ($code >= 400) throw new \RuntimeException("API error $code from $url: " . ($body ?: 'empty'));
        return $data ?? [];
    }

    protected static function httpPost(string $url, array $data, array $headers = []): array {
        $ch = curl_init($url);
        $defaultHeaders = ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'];
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_HTTPHEADER     => array_merge($defaultHeaders, $headers),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        @curl_close($ch);
        if ($body === false) throw new \RuntimeException('HTTP POST failed (network error): ' . $url);
        return json_decode($body, true) ?? [];
    }

    protected static function httpPostJson(string $url, array $data, string $accessToken): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer $accessToken",
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        @curl_close($ch);
        if ($body === false) throw new \RuntimeException('HTTP POST JSON failed (network error): ' . $url);
        $decoded = json_decode($body, true) ?? [];
        if ($code >= 400) throw new \RuntimeException("API error $code: " . $body);
        return $decoded;
    }
}
