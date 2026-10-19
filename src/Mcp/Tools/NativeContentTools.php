<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Tools;

use Bnomei\KirbyMcp\Mcp\Attributes\McpToolIndex;
use Bnomei\KirbyMcp\Mcp\ProjectContext;
use Bnomei\KirbyMcp\Mcp\Support\KirbyRuntimeContext;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommandRunner;
use Bnomei\KirbyMcp\Mcp\Http\UploadTickets;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\Content\BlobResourceContents;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Notification\ResourceUpdatedNotification;
use Mcp\Server\RequestContext;
use Mcp\Server\Subscription\NotificationBusInterface;

final class NativeContentTools
{
    use Concerns\StructuredToolResult;

    public const OUTPUT = ['type' => 'object', 'properties' => ['ok' => ['type' => 'boolean']], 'required' => ['ok']];
    public const PARENT = ['oneOf' => [
        ['type' => 'object', 'properties' => ['type' => ['const' => 'site']], 'required' => ['type'], 'additionalProperties' => false],
        ['type' => 'object', 'properties' => ['type' => ['const' => 'page'], 'id' => ['type' => 'string', 'minLength' => 1]], 'required' => ['type', 'id'], 'additionalProperties' => false],
    ]];

    public function __construct(private readonly ProjectContext $context = new ProjectContext(), private readonly ?NotificationBusInterface $notificationBus = null)
    {
    }

    public function run(array $input, ?RequestContext $requestContext = null): array
    {
        $result = (new RuntimeCommandRunner(new KirbyRuntimeContext($this->context)))->runMarkedJson(
            'mcp/native.php',
            ['mcp:native', json_encode($input, JSON_THROW_ON_ERROR)],
        );
        $payload = $result->payload ?? ($result->installed ? $result->parseErrorResponse() : $result->needsRuntimeInstallResponse());
        if (($payload['ok'] ?? false) === true) {
            foreach ($payload['updatedUris'] ?? [] as $uri) {
                try {
                    $this->notificationBus?->publish(new ResourceUpdatedNotification($uri));
                    if (isset(($requestContext?->getSession()->get('resource_subscriptions', []) ?? [])[$uri])) {
                        $requestContext?->getClientGateway()->notify(new ResourceUpdatedNotification($uri));
                    }
                } catch (\Throwable $e) {
                    error_log('Kirby MCP resource notification failed: ' . $e->getMessage());
                }
            }
        }
        return $payload;
    }

    /** @param array{type: string, id?: string} $parent */
    #[McpTool(name: 'kirby_list_children', title: 'List Child Pages', description: 'List permitted child pages, including drafts. parent is {type: site} or {type: page, id: UUID or path ID}. Follow nextCursor with the same filters. limit is 1–100.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Browse child pages.', keywords: ['pages' => 100, 'children' => 100, 'browse' => 100])]
    #[Schema(properties: ['parent' => self::PARENT, 'status' => ['type' => 'string', 'enum' => ['all', 'draft', 'listed', 'unlisted', 'published']], 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]])]
    public function children(array $parent, string $status = 'all', int $limit = 50, ?string $cursor = null, ?RequestContext $requestContext = null): array|CallToolResult
    {
        return $this->maybeStructuredResult($requestContext, $this->run(['operation' => 'children'] + compact('parent', 'status', 'limit', 'cursor')));
    }

    /** @param array{type: string, id?: string} $parent */
    #[McpTool(name: 'kirby_list_files', title: 'List Attached Files', description: 'List permitted site/page attachments, not files referenced by content fields. parent is {type: site} or {type: page, id: UUID or path ID}. limit is 1–100.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Browse attached files.', keywords: ['files' => 100, 'browse' => 100, 'attachments' => 100])]
    #[Schema(properties: ['parent' => self::PARENT, 'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100]])]
    public function files(array $parent, ?string $template = null, ?string $type = null, int $limit = 50, ?string $cursor = null, ?RequestContext $requestContext = null): array|CallToolResult
    {
        return $this->maybeStructuredResult($requestContext, $this->run(['operation' => 'files'] + compact('parent', 'template', 'type', 'limit', 'cursor')));
    }

    #[McpTool(name: 'kirby_read_file', title: 'Read Original File', description: 'Read original bytes by Kirby file UUID or path ID (not a filesystem path). UTF-8 text is text; other files are MCP blobs. No silent truncation.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Read original file bytes.', keywords: ['file' => 100, 'read' => 100, 'csv' => 80, 'bytes' => 100])]
    public function read(string $id): CallToolResult
    {
        $payload = $this->run(['operation' => 'read', 'id' => $id]);
        $contents = [];
        if (($payload['ok'] ?? false) === true) {
            $contents[] = new EmbeddedResource(self::resource($payload));
            unset($payload['text'], $payload['blob']);
        }
        return new CallToolResult([new TextContent(json_encode($payload, JSON_THROW_ON_ERROR)), ...$contents], ($payload['ok'] ?? false) !== true, $payload);
    }

    public static function resource(array $payload): TextResourceContents|BlobResourceContents
    {
        return isset($payload['text'])
            ? new TextResourceContents($payload['file']['readUri'], $payload['mimeType'], $payload['text'])
            : new BlobResourceContents($payload['file']['readUri'], $payload['mimeType'], $payload['blob']);
    }

    #[McpTool(name: 'kirby_view_image', title: 'View Image', description: 'Return actual MCP image content: a bounded JPEG preview of a permitted Kirby raster file. Bounds 1–2048, default 1280; original remains unchanged.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'View an image preview.', keywords: ['image' => 100, 'preview' => 100, 'view' => 100])]
    #[Schema(properties: ['maxWidth' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 2048], 'maxHeight' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 2048]])]
    public function image(string $id, int $maxWidth = 1280, int $maxHeight = 1280, ?string $language = null): CallToolResult
    {
        $payload = $this->run(['operation' => 'image'] + compact('id', 'maxWidth', 'maxHeight', 'language'));
        $contents = [];
        if (($payload['ok'] ?? false) === true) {
            $contents[] = new ImageContent($payload['blob'], $payload['mimeType']);
            unset($payload['blob']);
        }
        return new CallToolResult([new TextContent(json_encode($payload, JSON_THROW_ON_ERROR)), ...$contents], ($payload['ok'] ?? false) !== true, $payload);
    }

    /**
     * @param array{type: string, id?: string} $parent
     * @param array<string, mixed> $content
     */
    #[McpTool(name: 'kirby_upload_file', title: 'Upload File', description: 'Preview then confirm an upload. uploadContext names a file picker/list on parent; its blueprint chooses destination/template. source is a local file for stdio only. HTTP returns a single-use upload ticket; POST original bytes to uploadUrl with Bearer authorization and X-Kirby-Upload-Ticket. content is initial metadata; no field references are changed.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Upload a file with initial metadata.', keywords: ['file' => 100, 'upload' => 100, 'create' => 80])]
    #[Schema(properties: ['parent' => self::PARENT, 'content' => ['type' => 'object', 'additionalProperties' => true]])]
    public function upload(array $parent, string $filename, ?string $uploadContext = null, array $content = [], ?string $language = null, ?string $source = null, bool $confirm = false, ?RequestContext $requestContext = null): array
    {
        if ($this->context->uploadUrl !== null && $source !== null) {
            return ['ok' => false, 'message' => 'HTTP uploads cannot reference server filesystem paths.'];
        }
        $input = ['operation' => 'upload'] + compact('parent', 'filename', 'uploadContext', 'content', 'language', 'confirm');
        if ($this->context->uploadUrl === null) {
            if ($source === null) {
                return ['ok' => false, 'message' => 'Local uploads require source.'];
            }
            return $this->run($input + ['source' => $source], $requestContext);
        }
        $preview = $this->run($input);
        if (($preview['ok'] ?? false) !== true || !$confirm) {
            return $preview;
        }
        $ticket = (new UploadTickets($this->context->projectRoot()))->issue($this->context->oauthUserId, $input, $preview);
        return ['ok' => true, 'uploadUrl' => $this->context->uploadUrl, 'method' => 'POST', 'ticketHeader' => 'X-Kirby-Upload-Ticket'] + $ticket;
    }

    /** @param array{type: string, id?: string} $parent */
    #[McpTool(name: 'kirby_move_page', title: 'Move Page', description: 'Preview or confirm a native Kirby page move. parent is {type: site} or {type: page, id: UUID or path ID}. Requires native move permission and valid blueprint destination; rejects cycles/collisions.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Move a page to another parent.', keywords: ['page' => 100, 'move' => 100, 'parent' => 80])]
    #[Schema(properties: ['parent' => self::PARENT])]
    public function move(string $id, array $parent, bool $confirm = false, ?RequestContext $requestContext = null): array
    {
        return $this->run(['operation' => 'move'] + compact('id', 'parent', 'confirm'), $requestContext);
    }

    /** @param array{type: string, id?: string} $parent
     * @param array<string, mixed> $content
     */
    #[McpTool(name: 'kirby_create_page', title: 'Create Draft Page', description: 'Preview or confirm native model-level draft creation. Checks native create permission, not Panel page-list field template/capacity restrictions. Content cannot set reserved model fields. Field handlers and hooks run on confirmation.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Create a draft page.', keywords: ['page' => 100, 'create' => 100, 'draft' => 100])]
    #[Schema(properties: ['parent' => self::PARENT, 'content' => ['type' => 'object', 'additionalProperties' => true]])]
    public function createPage(array $parent, string $slug, string $template, array $content = [], bool $confirm = false, ?RequestContext $requestContext = null): array
    {
        return $this->run(['operation' => 'createPage'] + compact('parent', 'slug', 'template', 'content', 'confirm'), $requestContext);
    }

    #[McpTool(name: 'kirby_delete_page', title: 'Delete Page', description: 'Preview all affected pages and attached files before native deletion. Descendants require recursive=true. References elsewhere are not repaired.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Delete a page and its attachments.', keywords: ['page' => 100, 'delete' => 100])]
    public function deletePage(string $id, bool $recursive = false, bool $confirm = false, ?RequestContext $requestContext = null): array
    {
        return $this->run(['operation' => 'deletePage'] + compact('id', 'recursive', 'confirm'), $requestContext);
    }

    #[McpTool(name: 'kirby_change_page_slug', title: 'Change Page Slug', description: 'Preview or confirm a native slug change, optionally in a configured language. References elsewhere are not repaired.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Change a page URL slug.', keywords: ['page' => 100, 'slug' => 100, 'rename' => 100])]
    public function changePageSlug(string $id, string $slug, ?string $language = null, bool $confirm = false, ?RequestContext $requestContext = null): array
    {
        return $this->run(['operation' => 'changePageSlug'] + compact('id', 'slug', 'language', 'confirm'), $requestContext);
    }

    #[McpTool(name: 'kirby_change_page_status', title: 'Change Page Status', description: 'Preview or confirm draft/unlisted/listed status. position is only valid for listed pages.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Publish, unpublish or order a page.', keywords: ['page' => 100, 'status' => 100, 'publish' => 100])]
    #[Schema(properties: ['status' => ['type' => 'string', 'enum' => ['draft', 'unlisted', 'listed']], 'position' => ['type' => ['integer', 'null'], 'minimum' => 1]])]
    public function changePageStatus(string $id, string $status, ?int $position = null, bool $confirm = false, ?RequestContext $requestContext = null): array
    {
        return $this->run(['operation' => 'changePageStatus'] + compact('id', 'status', 'position', 'confirm'), $requestContext);
    }

    #[McpTool(name: 'kirby_delete_file', title: 'Delete File', description: 'Preview or confirm native attachment deletion. Content references elsewhere are not repaired.', outputSchema: self::OUTPUT)]
    #[McpToolIndex(whenToUse: 'Delete an attached file.', keywords: ['file' => 100, 'delete' => 100])]
    public function deleteFile(string $id, bool $confirm = false, ?RequestContext $requestContext = null): array
    {
        return $this->run(['operation' => 'deleteFile'] + compact('id', 'confirm'), $requestContext);
    }
}
