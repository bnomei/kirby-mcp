<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\FileResolver;
use Bnomei\KirbyMcp\Mcp\Support\PageResolver;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Kirby\CLI\CLI;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Cms\Site;
use Kirby\Filesystem\F;
use Kirby\Form\Fields;
use Kirby\Form\Field\FileListField;
use Kirby\Form\Field\FilePickerField;

/** Native model operations; all resolution and guards run as the request's user. */
final class NativeContent extends RuntimeCommand
{
    public static function definition(): array
    {
        return [
            'description' => 'Native content operations. Use the dedicated MCP tools.',
            'args' => ['payload' => ['description' => 'JSON operation payload.']],
            'command' => [self::class, 'run'],
        ];
    }

    public static function run(CLI $cli): void
    {
        $kirby = self::kirbyOrEmitError($cli);
        if ($kirby === null) {
            return;
        }
        try {
            $input = json_decode((string)$cli->arg('payload'), true, 64, JSON_THROW_ON_ERROR);
            $payload = $kirby->impersonate(self::mutationUser($kirby), fn () => self::execute($kirby, $input));
        } catch (\Throwable $e) {
            $payload = ['ok' => false, 'error' => self::errorArray($e)];
        }
        self::emit($cli, $payload);
    }

    public static function execute(App $kirby, array $input): array
    {
        return match ($input['operation'] ?? '') {
            'children', 'files' => self::listing($kirby, $input),
            'read', 'image' => self::read($kirby, $input),
            'upload' => self::upload($kirby, $input),
            'move' => self::move($kirby, $input),
            default => throw new \InvalidArgumentException('Unknown native operation.'),
        };
    }

    private static function parent(App $kirby, array $parent): Page|Site
    {
        $model = match ($parent['type'] ?? null) {
            'site' => $kirby->site(),
            'page' => PageResolver::resolve($kirby, $parent['id'] ?? null),
            default => null,
        };
        if (!$model instanceof Page && !$model instanceof Site) {
            throw new \InvalidArgumentException('Expected a site or existing page parent.');
        }
        $model->guards()->ensureAvailable('read');
        $model->guards()->ensureAvailable('access');
        return $model;
    }

    public static function identity(Page|Site $model): array
    {
        return $model instanceof Site ? ['type' => 'site'] : ['type' => 'page', 'id' => (string)($model->uuid() ?? $model->id())];
    }

    public static function summary(File|Page $model): array
    {
        $uuid = $model->uuid();
        $id = (string)($uuid ?? $model->id());
        $kind = $model instanceof File ? 'file' : 'page';
        $parent = $model instanceof File ? $model->parent() : $model->parentModel();
        if (!$parent instanceof Page && !$parent instanceof Site) {
            throw new \RuntimeException('Only site/page models are exposed.');
        }
        $base = ['id' => $id, 'pathId' => $model->id(), 'uuid' => $uuid === null ? null : (string)$uuid,
            'parent' => self::identity($parent), 'url' => $model->url(),
            'template' => (string)($model instanceof File ? $model->template() : $model->intendedTemplate()),
            'contentUri' => 'kirby://' . $kind . '/content/' . rawurlencode($id)];
        if ($model instanceof File) {
            return $base + ['filename' => $model->filename(), 'type' => $model->type(), 'mime' => $model->mime(),
                'size' => $model->size(), 'readUri' => 'kirby://file/read/' . rawurlencode($id)];
        }
        return $base + ['title' => $model->title()->value(), 'status' => $model->status(),
            'childrenUri' => 'kirby://page/children/' . rawurlencode($id),
            'filesUri' => 'kirby://page/files/' . rawurlencode($id)];
    }

    private static function listing(App $kirby, array $input): array
    {
        $parent = self::parent($kirby, $input['parent']);
        $limit = $input['limit'] ?? 50;
        if (!is_int($limit) || $limit < 1 || $limit > 100) {
            throw new \InvalidArgumentException('limit must be between 1 and 100.');
        }
        $status = $input['status'] ?? 'all';
        if (!in_array($status, ['all', 'draft', 'listed', 'unlisted', 'published'], true)) {
            throw new \InvalidArgumentException('Invalid page status.');
        }
        $binding = hash('sha256', json_encode([$input['operation'], self::identity($parent), $status, $input['template'] ?? null, $input['type'] ?? null], JSON_THROW_ON_ERROR));
        $after = null;
        if (($input['cursor'] ?? null) !== null) {
            $cursor = json_decode(base64_decode($input['cursor'], true) ?: '', true);
            if (!is_array($cursor) || ($cursor['binding'] ?? '') !== $binding || !is_string($cursor['after'] ?? null)) {
                throw new \InvalidArgumentException('Cursor does not match this listing.');
            }
            $after = $cursor['after'];
        }
        $models = $input['operation'] === 'files' ? $parent->files() : $parent->childrenAndDrafts();
        $items = [];
        foreach ($models->sortBy('id', 'asc', SORT_STRING) as $model) {
            if (!$model->isListable() || ($after !== null && strcmp($model->id(), $after) <= 0)) {
                continue;
            }
            if ($model instanceof Page && $status !== 'all' && !($status === 'published' ? !$model->isDraft() : $model->status() === $status)) {
                continue;
            }
            if ($model instanceof File && (($input['template'] ?? null) !== null && $model->template() !== $input['template'] || ($input['type'] ?? null) !== null && $model->type() !== $input['type'])) {
                continue;
            }
            $items[] = self::summary($model);
            if (count($items) > $limit) {
                break;
            }
        }
        $more = count($items) > $limit;
        if ($more) {
            array_pop($items);
        }
        $nextCursor = $more ? base64_encode(json_encode(['binding' => $binding, 'after' => end($items)['pathId']], JSON_THROW_ON_ERROR)) : null;
        $filters = array_intersect_key($input, array_flip(['status', 'template', 'type']));
        $uri = 'kirby://browse/' . $input['operation'] . '/' . rawurlencode(json_encode(self::identity($parent), JSON_THROW_ON_ERROR)) . '/' . $limit . '/';
        return ['ok' => true, 'parent' => self::identity($parent), 'items' => $items,
            'nextCursor' => $nextCursor,
            'nextUri' => $nextCursor === null ? null : $uri . rawurlencode($nextCursor) . '/' . rawurlencode(json_encode($filters, JSON_THROW_ON_ERROR))];
    }

    private static function read(App $kirby, array $input): array
    {
        $file = FileResolver::resolve($kirby, $input['id']);
        if ($file === null || !($file->parent() instanceof Page || $file->parent() instanceof Site)) {
            throw new \RuntimeException('Site/page file not found.');
        }
        $file->parent()->guards()->ensureAvailable('read');
        $file->parent()->guards()->ensureAvailable('access');
        $file->guards()->ensureAvailable('read');
        $file->guards()->ensureAvailable('access');
        $root = $file->root();
        $contentRoot = $kirby->root('content');
        if ($contentRoot === null) {
            throw new \RuntimeException('No content root configured.');
        }
        $contentRoot = rtrim($contentRoot, '/');
        $physicalContentRoot = realpath($contentRoot);
        if ($root === null || $physicalContentRoot === false || !str_starts_with($root, $contentRoot . '/') ||
            realpath($root) !== $physicalContentRoot . substr($root, strlen($contentRoot))) {
            throw new \RuntimeException('File must be inside the content root without symlink aliases.');
        }
        $summary = self::summary($file);
        $mime = $file->mime();
        $max = max(1, (int)$kirby->option('bnomei.kirby-mcp.files.readMaxBytes', 4194304));
        $details = [];
        if ($input['operation'] === 'image') {
            $width = $input['maxWidth'] ?? 1280;
            $height = $input['maxHeight'] ?? 1280;
            if (!is_int($width) || !is_int($height) || min($width, $height) < 1 || max($width, $height) > 2048) {
                throw new \InvalidArgumentException('Preview bounds must be between 1 and 2048.');
            }
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true) || $file->width() * $file->height() > 40000000) {
                throw new \RuntimeException('Preview requires a raster image of at most 40 megapixels.');
            }
            $preview = $file->thumb(['width' => $width, 'height' => $height, 'format' => 'jpg', 'quality' => 80]);
            if (!$preview instanceof \Kirby\Cms\FileVersion) {
                throw new \RuntimeException('Unable to create image preview.');
            }
            $preview->save();
            $root = $preview->root();
            $mime = 'image/jpeg';
            $max = 2097152;
            $details = ['alt' => $file->content($input['language'] ?? null)->get('alt')->value(),
                'original' => ['width' => $file->width(), 'height' => $file->height()],
                'preview' => ['width' => $preview->width(), 'height' => $preview->height()]];
        }
        if ($root === null) {
            throw new \RuntimeException('No readable file root.');
        }
        $stream = fopen($root, 'rb');
        if ($stream === false) {
            throw new \RuntimeException('Unable to open file.');
        }
        try {
            $bytes = stream_get_contents($stream, $max + 1);
        } finally {
            fclose($stream);
        }
        if ($bytes === false) {
            throw new \RuntimeException('Unable to read file.');
        }
        if (strlen($bytes) > $max) {
            return ['ok' => false, 'file' => $summary, 'error' => ['code' => 'size_limit', 'message' => 'File exceeds response byte limit; no partial contents returned.', 'maxBytes' => $max]];
        }
        $text = $input['operation'] === 'read' && (str_starts_with($mime, 'text/') || in_array($mime, ['application/json', 'application/xml', 'application/javascript', 'application/yaml'], true)) && mb_check_encoding($bytes, 'UTF-8');
        return ['ok' => true, 'file' => $summary, 'mimeType' => $mime, $text ? 'text' : 'blob' => $text ? $bytes : base64_encode($bytes)] + $details;
    }

    private static function upload(App $kirby, array $input): array
    {
        $content = $input['content'] ?? [];
        if (!is_array($content) || (array_is_list($content) && $content !== [])) {
            throw new \InvalidArgumentException('content must be a metadata object.');
        }
        foreach (array_keys($content) as $key) {
            if (in_array(strtolower((string)$key), ['template', 'uuid'], true)) {
                throw new \InvalidArgumentException('Upload metadata cannot set reserved template or uuid fields.');
            }
        }
        // Serialize MCP mutations so capacity/collision checks and creation are one operation.
        $lock = fopen($kirby->root('content') . '/.kirby-mcp-mutations.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new \RuntimeException('Unable to lock content mutations.');
        }
        try {
            $owner = self::parent($kirby, $input['parent']);
            $parent = $owner;
            $template = 'default';
            $context = $input['uploadContext'] ?? null;
            if ($context === null) {
                if ($kirby->option('bnomei.kirby-mcp.files.directUpload', false) !== true) {
                    throw new \RuntimeException('An uploadContext blueprint field name is required; direct uploads are disabled.');
                }
            } else {
                $field = Fields::for($owner, $input['language'] ?? null)->field($context);
                if ($field instanceof FileListField) {
                    if ($field->upload() === false) {
                        throw new \RuntimeException('Uploads disabled or list capacity reached.');
                    }
                    $parent = $field->parentModel();
                    $template = $field->template() ?? 'default';
                } elseif ($field instanceof FilePickerField) {
                    $settings = $field->uploads();
                    $field->fill($owner->content($input['language'] ?? null)->get($context)->value());
                    $max = $field->multiple() ? $field->max() : 1;
                    if ($settings === false || ($max !== null && count($field->toFormValue()) >= $max)) {
                        throw new \RuntimeException('Uploads disabled or picker capacity reached.');
                    }
                    $parent = isset($settings['parent']) ? $owner->query($settings['parent']) : $owner;
                    $template = $settings['template'] ?? 'default';
                } else {
                    throw new \InvalidArgumentException('uploadContext must name a file picker or file list.');
                }
            }
            if (!$parent instanceof Page && !$parent instanceof Site) {
                throw new \RuntimeException('Upload destination must be a page or site.');
            }
            $parent->guards()->ensureAvailable('read');
            $parent->guards()->ensureAvailable('access');
            $filename = F::safeName($input['filename']);
            if ($filename === '' || $filename !== $input['filename'] || basename($filename) !== $filename) {
                throw new \InvalidArgumentException('Use a safe filename without paths.');
            }
            if ($parent->files()->find($filename) !== null || file_exists($parent->root() . '/' . $filename)) {
                throw new \RuntimeException('Filename already exists; replacement is not allowed.');
            }
            $prospective = new File(['parent' => $parent, 'filename' => $filename, 'template' => $template]);
            $prospective->guards()->ensureAvailable('create');
            $format = $prospective->blueprint()->create()['format'] ?? null;
            $maxBytes = max(1, (int)$kirby->option('bnomei.kirby-mcp.files.uploadMaxBytes', 20971520));
            $preview = ['ok' => true, 'needsConfirm' => true, 'parent' => self::identity($parent), 'filename' => $filename, 'template' => $template, 'maxBytes' => $maxBytes];
            if (isset($input['expectedUpload']) && $input['expectedUpload'] !== $preview) {
                throw new \RuntimeException('Upload policy changed; request a new ticket.');
            }
            if (($input['confirm'] ?? false) !== true || !isset($input['source'])) {
                return $preview;
            }
            $source = $input['source'];
            if (!is_file($source) || !is_readable($source) || filesize($source) > $maxBytes) {
                throw new \RuntimeException('Source is unreadable or exceeds upload byte limit.');
            }
            $language = $input['language'] ?? null;
            if ($language !== null && (!$kirby->multilang() || $kirby->language($language) === null)) {
                throw new \InvalidArgumentException('Unknown metadata language.');
            }
            $metadata = $language !== null && $kirby->defaultLanguage()?->code() !== $language
                ? ['translations' => [['code' => $language, 'content' => $content]]]
                : ['content' => $content];
            // Core validates the upload's extension: stage under the requested name,
            // not the local source name, so renaming cannot bypass accept rules.
            $stage = sys_get_temp_dir() . '/kirby-mcp-' . bin2hex(random_bytes(16));
            if (!mkdir($stage, 0700)) {
                throw new \RuntimeException('Unable to stage upload.');
            }
            try {
                if (!copy($source, $stage . '/' . $filename) || filesize($stage . '/' . $filename) > $maxBytes) {
                    throw new \RuntimeException('Unable to stage upload within byte limit.');
                }
                if ($format !== null && $prospective->type() === 'image' && (new \Kirby\Image\Image(['root' => $stage . '/' . $filename]))->isResizable()) {
                    $finalFilename = $prospective->name() . '.' . $format;
                    if (file_exists($parent->root() . '/' . $finalFilename)) {
                        throw new \RuntimeException('Transformed filename already exists; replacement is not allowed.');
                    }
                }
                $file = $parent->createFile(['source' => $stage . '/' . $filename, 'filename' => $filename, 'template' => $template] + $metadata, move: false);
            } finally {
                if (is_file($stage . '/' . $filename)) {
                    unlink($stage . '/' . $filename);
                }
                rmdir($stage);
            }
            return ['ok' => true, 'file' => self::summary($file), 'needsConfirm' => false,
                'updatedUris' => self::listingUris($parent, 'files')];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function move(App $kirby, array $input): array
    {
        $page = PageResolver::resolve($kirby, $input['id']);
        if ($page === null) {
            throw new \RuntimeException('Page not found.');
        }
        $page->guards()->ensureAvailable('read');
        $page->guards()->ensureAvailable('access');
        $parent = self::parent($kirby, $input['parent']);
        $page->guards()->ensureAvailable('move');
        if (!$page->parentModel()->is($parent)) {
            $page->guards()->ensureExecutable('move', $parent);
        }
        $before = self::summary($page);
        $updatedUris = [...self::listingUris($page->parentModel(), 'children'), ...self::listingUris($parent, 'children'), $before['contentUri'], 'kirby://page/content/' . rawurlencode($page->id())];
        if (($input['confirm'] ?? false) !== true) {
            return ['ok' => true, 'needsConfirm' => true, 'before' => $before, 'destination' => self::identity($parent)];
        }
        $page = $page->move($parent);
        return ['ok' => true, 'needsConfirm' => false, 'before' => $before, 'page' => self::summary($page),
            'updatedUris' => array_values(array_unique([...$updatedUris, 'kirby://page/content/' . rawurlencode($page->id())]))];
    }

    private static function listingUris(Page|Site $parent, string $kind): array
    {
        if ($parent instanceof Site) {
            return ['kirby://site/' . $kind];
        }
        return array_values(array_unique(['kirby://page/' . $kind . '/' . rawurlencode($parent->id()),
            'kirby://page/' . $kind . '/' . rawurlencode((string)($parent->uuid() ?? $parent->id()))]));
    }
}
