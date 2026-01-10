<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp\Commands;

use Bnomei\KirbyMcp\Dumps\DumpValueNormalizer;
use Bnomei\KirbyMcp\Mcp\Support\RuntimeCommand;
use Bnomei\KirbyMcp\Project\KirbyMcpConfig;
use Kirby\CLI\CLI;
use Kirby\Cms\App;
use Kirby\Cms\Collection as CmsCollection;
use Kirby\Cms\File;
use Kirby\Cms\Files;
use Kirby\Cms\ModelWithContent;
use Kirby\Cms\Page;
use Kirby\Cms\Pages;
use Kirby\Cms\Site;
use Kirby\Cms\User;
use Kirby\Cms\Users;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\Str;
use Kirby\Uuid\Uuid;
use Throwable;

final class QueryDot extends RuntimeCommand
{
    public const ENV_ENABLE_QUERY = 'KIRBY_MCP_ENABLE_QUERY';

    /**
     * @return array{
     *   description: string,
     *   args: array<string, mixed>,
     *   command: callable(CLI): void
     * }
     */
    public static function definition(): array
    {
        return [
            'description' => 'Evaluates Kirby query language (dot-notation) strings in Kirby context and returns structured JSON for MCP.',
            'args' => [
                'query' => [
                    'description' => 'Query string to evaluate (dot notation).',
                ],
                'model' => [
                    'longPrefix' => 'model',
                    'description' => 'Optional context model (page/file/user/site). Pass a UUID like page://..., file://..., or user://..., a user email, or a file path with extension; otherwise it is treated as a page id. Use "site" for the site. Defaults to the home page.',
                ],
                'confirm' => [
                    'longPrefix' => 'confirm',
                    'description' => 'Actually execute the query. Without this flag, the command only returns a dry-run response.',
                    'noValue' => true,
                ],
            ],
            'command' => [self::class, 'run'],
        ];
    }

    public static function run(CLI $cli): void
    {
        $kirby = self::kirbyOrEmitError($cli);
        if ($kirby === null) {
            return;
        }

        $projectRoot = $cli->dir();

        if (self::isQueryEnabled($projectRoot) !== true) {
            self::emit($cli, [
                'ok' => false,
                'enabled' => false,
                'needsEnable' => true,
                'message' => 'Query evaluation is disabled. Enable via env ' . self::ENV_ENABLE_QUERY . '=1 or via .kirby-mcp/mcp.json: {"query":{"enabled":true}}.',
            ]);
            return;
        }

        $confirm = $cli->arg('confirm') === true;
        if ($confirm !== true) {
            self::emit($cli, [
                'ok' => false,
                'enabled' => true,
                'needsConfirm' => true,
                'message' => 'Dry run: pass --confirm to execute the query.',
                'available' => [
                    'variables' => ['$kirby', '$site', '$user', '$currentUser', '$model', '$page', '$file'],
                    'modelArg' => 'blog/post, page://AbC123XyZ9LmN0, file://QwE4RtY6UiOp, user://GhJ7KlM8NoPq, blog/post/cover.jpg, editor@example.com, site',
                ],
            ]);
            return;
        }

        $query = $cli->arg('query');
        if (!is_string($query) || trim($query) === '') {
            self::emit($cli, [
                'ok' => false,
                'enabled' => true,
                'error' => [
                    'class' => 'InvalidArgumentException',
                    'message' => 'Missing query string.',
                    'code' => 0,
                ],
            ]);
            return;
        }

        $modelArg = $cli->arg('model');
        $model = null;

        if (is_string($modelArg) && trim($modelArg) !== '') {
            $model = self::resolveModel($kirby, $modelArg);
            if ($model === null) {
                self::emit($cli, [
                    'ok' => false,
                    'enabled' => true,
                    'error' => [
                        'class' => 'InvalidArgumentException',
                        'message' => 'Model not found for: ' . trim($modelArg),
                        'code' => 0,
                    ],
                ]);
                return;
            }
        } else {
            $model = $kirby->site()->homePage() ?? $kirby->site();
        }

        $currentUser = $kirby->user();
        $context = self::buildContext($kirby, $model, $currentUser);

        $start = microtime(true);
        $memStart = memory_get_usage(true);

        $exceptionPayload = null;
        $resultValue = null;

        try {
            $resultValue = Str::query(trim($query), $context);
        } catch (Throwable $exception) {
            $exceptionPayload = self::errorArray($exception, self::traceForCli($cli, $exception));
        }

        $seconds = microtime(true) - $start;
        $memBytes = memory_get_usage(true) - $memStart;

        $payload = [
            'ok' => $exceptionPayload === null,
            'enabled' => true,
            'query' => trim($query),
            'context' => self::summarizeContext($kirby, $model, $currentUser),
            'result' => [
                'type' => get_debug_type($resultValue),
                'value' => self::summarizeValue($resultValue),
            ],
            'timing' => [
                'seconds' => $seconds,
                'memoryBytes' => $memBytes,
            ],
        ];

        if (is_array($exceptionPayload)) {
            $payload['error'] = $exceptionPayload;
        }

        if ($cli->arg('debug') === true) {
            $payload['modelArg'] = is_string($modelArg) ? trim($modelArg) : null;
            $payload['contextKeys'] = array_keys($context);
        }

        self::emit($cli, $payload);
    }

    private static function isQueryEnabled(string $projectRoot): bool
    {
        $raw = getenv(self::ENV_ENABLE_QUERY);
        if (is_string($raw) && $raw !== '') {
            $normalized = strtolower(trim($raw));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
        }

        return KirbyMcpConfig::load($projectRoot)->queryEnabled();
    }

    private static function resolveModel(App $kirby, string $modelArg): ?ModelWithContent
    {
        $modelArg = trim($modelArg);
        if ($modelArg === '') {
            return null;
        }

        if (Uuid::is($modelArg, ['page', 'file', 'user', 'site']) === true) {
            return Uuid::for($modelArg)?->model();
        }

        if ($modelArg === 'site') {
            return $kirby->site();
        }

        if (self::looksLikeEmail($modelArg)) {
            return $kirby->user($modelArg);
        }

        if (self::looksLikeFile($modelArg)) {
            return $kirby->file($modelArg);
        }

        return $kirby->page($modelArg);
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildContext(App $kirby, ?ModelWithContent $model, ?User $currentUser): array
    {
        $site = $kirby->site();
        $context = [
            'kirby' => $kirby,
            'site' => $site,
        ];

        if ($currentUser instanceof User) {
            $context['currentUser'] = $currentUser;
        }

        if ($model instanceof ModelWithContent) {
            $context['model'] = $model;

            if (is_string($model::CLASS_ALIAS) && $model::CLASS_ALIAS !== '') {
                $context[$model::CLASS_ALIAS] = $model;
            }
        }

        if ($model instanceof Site) {
            $context['site'] = $model;
        }

        if ($model instanceof Page) {
            $context['page'] = $model;
        }

        if ($model instanceof File) {
            $context['file'] = $model;
        }

        if ($model instanceof User) {
            $context['user'] = $model;
        } elseif ($currentUser instanceof User) {
            $context['user'] = $currentUser;
        }

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    private static function summarizeContext(App $kirby, ?ModelWithContent $model, ?User $currentUser): array
    {
        $site = $kirby->site();

        $summary = [
            'site' => [
                'title' => $site->title()->value(),
                'url' => $site->url(),
            ],
            'model' => $model instanceof ModelWithContent ? self::summarizeModel($model) : null,
        ];

        $contextUser = $model instanceof User ? $model : $currentUser;
        if ($contextUser instanceof User) {
            $summary['user'] = self::summarizeUser($contextUser);
        }

        if ($currentUser instanceof User) {
            $summary['currentUser'] = self::summarizeUser($currentUser);
            if ($contextUser instanceof User && $contextUser->id() === $currentUser->id()) {
                unset($summary['currentUser']);
            }
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>
     */
    private static function summarizeModel(ModelWithContent $model): array
    {
        if ($model instanceof Site) {
            return self::summarizeSite($model);
        }

        if ($model instanceof Page) {
            return self::summarizePage($model);
        }

        if ($model instanceof File) {
            return self::summarizeFile($model);
        }

        if ($model instanceof User) {
            return self::summarizeUser($model);
        }

        return [
            'type' => is_string($model::CLASS_ALIAS) ? $model::CLASS_ALIAS : 'model',
            'class' => $model::class,
            'id' => $model->id(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function summarizeSite(Site $site): array
    {
        return [
            'type' => 'site',
            'class' => $site::class,
            'title' => $site->title()->value(),
            'url' => $site->url(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function summarizePage(Page $page): array
    {
        return [
            'type' => 'page',
            'class' => $page::class,
            'id' => $page->id(),
            'uuid' => self::safeUuid($page),
            'template' => $page->template()->name(),
            'url' => $page->url(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function summarizeFile(File $file): array
    {
        return [
            'type' => 'file',
            'class' => $file::class,
            'id' => $file->id(),
            'uuid' => self::safeUuid($file),
            'filename' => $file->filename(),
            'url' => $file->url(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function summarizeUser(User $user): array
    {
        return [
            'type' => 'user',
            'class' => $user::class,
            'id' => $user->id(),
            'email' => $user->email(),
            'role' => $user->role()->id(),
        ];
    }

    private static function safeUuid(ModelWithContent $model): ?string
    {
        if (method_exists($model, 'uuid') !== true) {
            return null;
        }

        try {
            return $model->uuid()->toString();
        } catch (Throwable) {
            return null;
        }
    }

    private static function summarizeValue(mixed $value, int $maxItems = 50): mixed
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return $value;
        }

        if ($value instanceof Site) {
            return self::summarizeSite($value);
        }

        if ($value instanceof Page) {
            return self::summarizePage($value);
        }

        if ($value instanceof File) {
            return self::summarizeFile($value);
        }

        if ($value instanceof User) {
            return self::summarizeUser($value);
        }

        if ($value instanceof Pages) {
            return self::summarizePages($value, $maxItems);
        }

        if ($value instanceof Files) {
            return self::summarizeFiles($value, $maxItems);
        }

        if ($value instanceof Users) {
            return self::summarizeUsers($value, $maxItems);
        }

        if ($value instanceof CmsCollection || $value instanceof Collection) {
            return self::summarizeCollection($value, $maxItems);
        }

        return DumpValueNormalizer::normalize($value, maxItems: $maxItems);
    }

    /**
     * @return array<string, mixed>
     */
    private static function summarizePages(Pages $pages, int $maxItems): array
    {
        $ids = [];
        $count = 0;
        foreach ($pages as $page) {
            if ($count >= $maxItems) {
                break;
            }
            $ids[] = $page->id();
            $count++;
        }

        $total = $pages->count();

        return [
            'type' => 'pages',
            'class' => $pages::class,
            'count' => $total,
            'truncated' => $total > $count,
            'ids' => $ids,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function summarizeFiles(Files $files, int $maxItems): array
    {
        $ids = [];
        $count = 0;
        foreach ($files as $file) {
            if ($count >= $maxItems) {
                break;
            }
            $ids[] = $file->id();
            $count++;
        }

        $total = $files->count();

        return [
            'type' => 'files',
            'class' => $files::class,
            'count' => $total,
            'truncated' => $total > $count,
            'ids' => $ids,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function summarizeUsers(Users $users, int $maxItems): array
    {
        $ids = [];
        $count = 0;
        foreach ($users as $user) {
            if ($count >= $maxItems) {
                break;
            }
            $ids[] = $user->id();
            $count++;
        }

        $total = $users->count();

        return [
            'type' => 'users',
            'class' => $users::class,
            'count' => $total,
            'truncated' => $total > $count,
            'ids' => $ids,
        ];
    }

    /**
     * @param CmsCollection|Collection $collection
     * @return array<string, mixed>
     */
    private static function summarizeCollection($collection, int $maxItems): array
    {
        $items = [];
        $count = 0;
        foreach ($collection as $item) {
            if ($count >= $maxItems) {
                break;
            }
            $items[] = self::summarizeValue($item, $maxItems);
            $count++;
        }

        $total = $collection->count();

        return [
            'type' => 'collection',
            'class' => $collection::class,
            'count' => $total,
            'truncated' => $total > $count,
            'items' => $items,
        ];
    }

    private static function looksLikeEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private static function looksLikeFile(string $value): bool
    {
        $basename = basename($value);
        $extension = pathinfo($basename, PATHINFO_EXTENSION);

        return $extension !== '';
    }
}
