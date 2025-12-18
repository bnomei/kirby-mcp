<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Docs;

final class KirbyDocsUrl
{
    private const BASE_URL = 'https://getkirby.com/';

    /**
     * @return array{
     *   path: string,
     *   htmlUrl: string,
     *   crawlUrl: string,
     *   markdownUrl: string|null
     * }
     */
    public static function fromObjectId(string $objectId): array
    {
        $path = ltrim(trim($objectId), '/');
        $htmlUrl = self::BASE_URL . $path;

        if (str_starts_with($path, 'docs/')) {
            $markdownUrl = rtrim($htmlUrl, '/') . '.md';

            return [
                'path' => $path,
                'htmlUrl' => $htmlUrl,
                'crawlUrl' => $markdownUrl,
                'markdownUrl' => $markdownUrl,
            ];
        }

        return [
            'path' => $path,
            'htmlUrl' => $htmlUrl,
            'crawlUrl' => $htmlUrl,
            'markdownUrl' => null,
        ];
    }
}
