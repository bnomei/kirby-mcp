<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Resources;

use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\Tools\NativeContentTools;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Schema\Content\BlobResourceContents;
use Mcp\Schema\Content\TextResourceContents;

final class NativeContentResources
{
    private readonly NativeContentTools $tools;

    public function __construct(ProjectContext $context = new ProjectContext())
    {
        $this->tools = new NativeContentTools($context);
    }

    #[McpResourceTemplate(uriTemplate: 'kirby://file/read/{encodedIdOrUuid}', name: 'file_read', title: 'Original File', description: 'Original file bytes, not metadata. UTF-8 text or binary blob; explicit size errors.')]
    #[McpToolIndex(whenToUse: 'Read original file bytes by UUID or path ID.', keywords: ['file' => 100, 'bytes' => 100])]
    public function read(string $encodedIdOrUuid): TextResourceContents|BlobResourceContents
    {
        $payload = $this->tools->run(['operation' => 'read', 'id' => rawurldecode($encodedIdOrUuid)]);
        if (($payload['ok'] ?? false) !== true) {
            throw new \RuntimeException(json_encode($payload, JSON_THROW_ON_ERROR));
        }
        return NativeContentTools::resource($payload);
    }

    #[McpResource(uri: 'kirby://site/children', name: 'site_children', title: 'Site Children', mimeType: 'application/json')]
    #[McpToolIndex(whenToUse: 'Browse top-level pages.', keywords: ['site' => 80, 'children' => 100])]
    public function siteChildren(): array
    {
        return $this->tools->children(['type' => 'site']);
    }

    #[McpResource(uri: 'kirby://site/files', name: 'site_files', title: 'Site Files', mimeType: 'application/json')]
    #[McpToolIndex(whenToUse: 'Browse site attachments.', keywords: ['site' => 80, 'files' => 100])]
    public function siteFiles(): array
    {
        return $this->tools->files(['type' => 'site']);
    }

    #[McpResourceTemplate(uriTemplate: 'kirby://page/children/{encodedIdOrUuid}', name: 'page_children', title: 'Page Children', mimeType: 'application/json')]
    #[McpToolIndex(whenToUse: 'Browse child pages.', keywords: ['page' => 80, 'children' => 100])]
    public function children(string $encodedIdOrUuid): array
    {
        return $this->tools->children(['type' => 'page', 'id' => rawurldecode($encodedIdOrUuid)]);
    }

    #[McpResourceTemplate(uriTemplate: 'kirby://page/files/{encodedIdOrUuid}', name: 'page_files', title: 'Page Files', mimeType: 'application/json')]
    #[McpToolIndex(whenToUse: 'Browse page attachments.', keywords: ['page' => 80, 'files' => 100])]
    public function files(string $encodedIdOrUuid): array
    {
        return $this->tools->files(['type' => 'page', 'id' => rawurldecode($encodedIdOrUuid)]);
    }

    #[McpResourceTemplate(uriTemplate: 'kirby://browse/children/{encodedParent}/{limit}/{encodedCursor}/{encodedFilters}', name: 'browse_children', title: 'Paginated Children', description: 'Follow nextUri from a listing. Parent and filters are URL-encoded JSON. Cursor is URL-encoded or start.', mimeType: 'application/json')]
    #[McpToolIndex(whenToUse: 'Follow nextUri for the next page of children.', keywords: ['pagination' => 100, 'children' => 100])]
    public function browseChildren(string $encodedParent, string $limit, string $encodedCursor, string $encodedFilters): array
    {
        return $this->browse('children', $encodedParent, $limit, $encodedCursor, $encodedFilters);
    }

    #[McpResourceTemplate(uriTemplate: 'kirby://browse/files/{encodedParent}/{limit}/{encodedCursor}/{encodedFilters}', name: 'browse_files', title: 'Paginated Files', description: 'Follow nextUri from a listing. Parent and filters are URL-encoded JSON. Cursor is URL-encoded or start.', mimeType: 'application/json')]
    #[McpToolIndex(whenToUse: 'Follow nextUri for the next page of files.', keywords: ['pagination' => 100, 'files' => 100])]
    public function browseFiles(string $encodedParent, string $limit, string $encodedCursor, string $encodedFilters): array
    {
        return $this->browse('files', $encodedParent, $limit, $encodedCursor, $encodedFilters);
    }

    private function browse(string $kind, string $encodedParent, string $limit, string $encodedCursor, string $encodedFilters): array
    {
        if (!in_array($kind, ['children', 'files'], true) || !ctype_digit($limit)) {
            throw new \InvalidArgumentException('Invalid listing parameters.');
        }
        $parent = json_decode(rawurldecode($encodedParent), true, 32, JSON_THROW_ON_ERROR);
        $filters = json_decode(rawurldecode($encodedFilters), true, 32, JSON_THROW_ON_ERROR);
        return $this->tools->run(['operation' => $kind, 'parent' => $parent, 'limit' => (int)$limit, 'cursor' => $encodedCursor === 'start' ? null : rawurldecode($encodedCursor)] + array_intersect_key($filters, array_flip(['status', 'template', 'type'])));
    }
}
