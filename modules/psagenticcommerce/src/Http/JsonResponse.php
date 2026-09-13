<?php

namespace PrestaShopAgenticCommerce\Http;

final class JsonResponse
{
    public static function etag(string $body): string
    {
        return '"' . hash('sha256', $body) . '"';
    }

    public static function isNotModified(string $etag, ?string $ifNoneMatch): bool
    {
        if ($ifNoneMatch === null || trim($ifNoneMatch) === '') {
            return false;
        }

        foreach (explode(',', $ifNoneMatch) as $candidate) {
            if (trim($candidate) === $etag || trim($candidate) === '*') {
                return true;
            }
        }

        return false;
    }

    public static function send(string $body, int $maxAge = 300): void
    {
        $etag = self::etag($body);
        header('Content-Type: application/json; charset=utf-8');
        header('ETag: ' . $etag);
        header('Cache-Control: public, max-age=' . max(0, $maxAge) . ', must-revalidate');
        header('Vary: Host');

        $ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH'])
            ? (string) $_SERVER['HTTP_IF_NONE_MATCH']
            : null;

        if (self::isNotModified($etag, $ifNoneMatch)) {
            http_response_code(304);
            return;
        }

        http_response_code(200);
        echo $body;
    }
}
