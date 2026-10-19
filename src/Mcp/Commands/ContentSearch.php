<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Mcp\Support\PageResolver;
use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Files;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Site;

/** Fixed-filter native searches, called inside NativeContent's impersonation boundary. */
final class ContentSearch
{
    public static function execute(App $kirby, array $input): array
    {
        $operation = $input['operation'] ?? null;
        if (!in_array($operation, ['find_pages', 'find_files'], true)) {
            throw new \InvalidArgumentException('Unknown content search operation.');
        }
        $files = $operation === 'find_files';
        $allowed = ['operation', 'parent', 'recursive', 'template', 'language', 'query', 'limit', 'cursor', $files ? 'type' : 'status'];
        if ($files) {
            $allowed[] = 'missingField';
        }
        if (array_diff(array_keys($input), $allowed) !== []) {
            throw new \InvalidArgumentException('Unsupported search filter.');
        }
        $limit = $input['limit'] ?? 20;
        if (!is_int($limit) || $limit < 1 || $limit > 100) {
            throw new \InvalidArgumentException('limit must be between 1 and 100.');
        }
        foreach (['template', 'type', 'language', 'query', 'missingField', 'cursor'] as $key) {
            if (isset($input[$key]) && !is_string($input[$key])) {
                throw new \InvalidArgumentException($key . ' must be a string.');
            }
        }
        if (isset($input['recursive']) && !is_bool($input['recursive'])) {
            throw new \InvalidArgumentException('recursive must be boolean.');
        }
        $status = $input['status'] ?? 'all';
        if (!in_array($status, ['all', 'draft', 'listed', 'unlisted', 'published'], true)) {
            throw new \InvalidArgumentException('Invalid page status.');
        }
        $language = $input['language'] ?? $kirby->defaultLanguage()?->code();
        if ($language !== null && (!$kirby->multilang() || $kirby->language($language) === null)) {
            throw new \InvalidArgumentException('Unknown search language.');
        }
        $parent = $kirby->site();
        if (isset($input['parent'])) {
            $spec = $input['parent'];
            if (!is_array($spec) || !in_array($spec['type'] ?? null, ['site', 'page'], true) ||
                array_diff(array_keys($spec), ($spec['type'] ?? null) === 'page' ? ['type', 'id'] : ['type']) !== []) {
                throw new \InvalidArgumentException('Expected a site or page parent.');
            }
            if ($spec['type'] === 'page' && (!is_string($spec['id'] ?? null) || trim($spec['id']) === '')) {
                throw new \InvalidArgumentException('Page parent requires an ID.');
            }
            $parent = $spec['type'] === 'site' ? $parent : PageResolver::resolve($kirby, $spec['id'] ?? null);
            if (!$parent instanceof Page && !$parent instanceof Site) {
                throw new \InvalidArgumentException('Parent page not found.');
            }
        }
        $parent->guards()->ensureAvailable('read');
        $parent->guards()->ensureAvailable('access');
        $recursive = !isset($input['parent']) || ($input['recursive'] ?? false);
        $query = trim($input['query'] ?? '');
        $missing = isset($input['missingField']) ? strtolower($input['missingField']) : null;
        if ($missing !== null && !preg_match('/^[a-z][a-z0-9_]*$/', $missing)) {
            throw new \InvalidArgumentException('missingField must name a blueprint metadata field.');
        }
        $binding = hash('sha256', json_encode([$operation, isset($input['parent']) ? NativeContent::identity($parent) : null,
            $recursive, $status, $input['template'] ?? null, $input['type'] ?? null, $language, $query, $missing], JSON_THROW_ON_ERROR));
        $offset = 0;
        if (isset($input['cursor'])) {
            $cursor = json_decode(base64_decode($input['cursor'], true) ?: '', true);
            if (!is_array($cursor) || ($cursor['binding'] ?? null) !== $binding || !is_int($cursor['offset'] ?? null) || $cursor['offset'] < 0) {
                throw new \InvalidArgumentException('Cursor does not match this search.');
            }
            $offset = $cursor['offset'];
        }
        $previous = $kirby->language()?->code();
        $kirby->setCurrentLanguage($language);
        try {
            $foundFiles = [];
            $foundPages = [];
            $visit = function (Page|Site $owner) use (&$visit, &$foundFiles, &$foundPages, $files, $recursive): void {
                if ($files) {
                    foreach ($owner->files() as $file) {
                        if (self::visible($file)) {
                            $foundFiles[] = $file;
                        }
                    }
                    if (!$recursive) {
                        return;
                    }
                }
                foreach ($owner->childrenAndDrafts() as $page) {
                    if (!self::visible($page)) {
                        continue;
                    }
                    if (!$files) {
                        $foundPages[] = $page;
                    }
                    if ($recursive) {
                        $visit($page);
                    }
                }
            };
            $visit($parent);
            $models = $files ? new Files($foundFiles) : new Pages($foundPages);
            $models = $models->filter(function (File|Page $model) use ($input, $status, $missing, $language): bool {
                $template = (string)($model instanceof File ? $model->template() : $model->intendedTemplate());
                if (isset($input['template']) && $template !== $input['template']) {
                    return false;
                }
                if ($model instanceof Page) {
                    return $status === 'all' || ($status === 'published' ? !$model->isDraft() : $model->status() === $status);
                }
                if (isset($input['type']) && $model->type() !== $input['type']) {
                    return false;
                }
                return $missing === null || (array_key_exists($missing, $model->blueprint()->fields()) && $model->content($language)->get($missing)->isEmpty());
            })->sortBy('id', 'asc', SORT_STRING);
            if ($query !== '') {
                $models = $models->search($query);
            }
            $total = $models->count();
            $items = [];
            foreach ($models->slice($offset, $limit) as $model) {
                $items[] = NativeContent::summary($model);
            }
            $next = $offset + count($items);
            return ['ok' => true, 'parent' => isset($input['parent']) ? NativeContent::identity($parent) : null,
                'language' => $language, 'total' => $total, 'items' => $items,
                'nextCursor' => $next < $total ? base64_encode(json_encode(['binding' => $binding, 'offset' => $next], JSON_THROW_ON_ERROR)) : null];
        } finally {
            $kirby->setCurrentLanguage($previous);
        }
    }

    private static function visible(File|Page $model): bool
    {
        return $model->guards()->isAvailable('read') && $model->guards()->isAvailable('access') && $model->isListable();
    }
}
