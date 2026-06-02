<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Bnomei\KirbyMcp\Docs\ExtensionReferenceIndex;
use Bnomei\KirbyMcp\Docs\HookReferenceIndex;
use Bnomei\KirbyMcp\Docs\PanelReferenceIndex;
use Bnomei\KirbyMcp\Mcp\Handlers\RequireInitForToolsHandler;
use Bnomei\KirbyMcp\Mcp\Handlers\SetLogLevelHandler;
use Bnomei\KirbyMcp\Mcp\Resources\CliResources;
use Bnomei\KirbyMcp\Mcp\Resources\ExtensionReferenceResources;
use Bnomei\KirbyMcp\Mcp\Resources\GlossaryResources;
use Bnomei\KirbyMcp\Mcp\Resources\HookReferenceResources;
use Bnomei\KirbyMcp\Mcp\Resources\KbResources;
use Bnomei\KirbyMcp\Mcp\Resources\PanelReferenceResources;
use Bnomei\KirbyMcp\Mcp\Resources\UpdateSchemaResources;
use Bnomei\KirbyMcp\Mcp\Support\KbDocuments;
use Composer\InstalledVersions;
use Mcp\Capability\Registry;
use Mcp\Capability\Registry\Container;
use Mcp\Capability\Registry\ReferenceHandler;
use Mcp\Schema\Annotations;
use Mcp\Schema\Enum\Role;
use Mcp\Schema\ResourceDefinition;
use Mcp\Schema\ServerCapabilities;
use Mcp\Server;
use Mcp\Server\Handler\Request\CallToolHandler;
use Mcp\Server\Session\SessionStoreInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

final class ServerFactory
{
    public const HTTP_SESSION_TTL_SECONDS = 3600;
    public const SESSION_GC_PROBABILITY = 1;
    public const SESSION_GC_DIVISOR = 20;

    public function create(?SessionStoreInterface $sessionStore = null): Server
    {
        $container = new Container();
        $registry = new Registry();
        $referenceHandler = new ReferenceHandler($container);
        $callToolHandler = new CallToolHandler($registry, $referenceHandler);

        $builder = Server::builder()
            ->setContainer($container)
            ->setRegistry($registry)
            ->setServerInfo('Kirby MCP', $this->resolveVersion())
            ->setInstructions('Call kirby_init once per session before calling any other Kirby tools. Use kirby_tool_suggest if unsure which tool/resource to use.');

        if ($sessionStore !== null) {
            $builder->setSession(
                sessionStore: $sessionStore,
                gcProbability: self::SESSION_GC_PROBABILITY,
                gcDivisor: self::SESSION_GC_DIVISOR,
            );
        }

        $server = $builder
            ->setDiscovery(dirname(__DIR__, 2), ['src/Mcp/Tools', 'src/Mcp/Resources'])
            ->addRequestHandler(new RequireInitForToolsHandler($callToolHandler))
            ->addRequestHandler(new SetLogLevelHandler())
            ->setCapabilities(new ServerCapabilities(
                tools: true,
                resources: true,
                resourcesSubscribe: true,
                prompts: false,
                logging: true,
                completions: true,
            ))
            ->build();

        $this->registerSizedMarkdownResources($registry);

        return $server;
    }

    private function registerSizedMarkdownResources(Registry $registry): void
    {
        $defaultAnnotations = new Annotations(
            audience: [Role::Assistant],
            priority: 0.4,
        );
        $importantAnnotations = new Annotations(
            audience: [Role::Assistant],
            priority: 0.5,
        );

        try {
            $contents = (new ExtensionReferenceResources())->extensionsList();
            $registry->registerResource(
                new ResourceDefinition(
                    uri: 'kirby://extensions',
                    name: 'extensions',
                    title: 'Kirby Extensions',
                    description: 'List Kirby plugin extensions (links to kirby://extension/{name}).',
                    mimeType: 'text/markdown',
                    annotations: $defaultAnnotations,
                    size: strlen($contents),
                    meta: $this->resourceMetaFromMtime($this->resolveClassMtime(ExtensionReferenceIndex::class)),
                ),
                [ExtensionReferenceResources::class, 'extensionsList'],
            );
        } catch (Throwable) {
            // Keep resource available via attribute discovery if sizing fails.
        }

        try {
            $contents = (new HookReferenceResources())->hooksList();
            $registry->registerResource(
                new ResourceDefinition(
                    uri: 'kirby://hooks',
                    name: 'hooks',
                    title: 'Kirby Hooks',
                    description: 'List Kirby plugin hook names (links to kirby://hook/{name}).',
                    mimeType: 'text/markdown',
                    annotations: $defaultAnnotations,
                    size: strlen($contents),
                    meta: $this->resourceMetaFromMtime($this->resolveClassMtime(HookReferenceIndex::class)),
                ),
                [HookReferenceResources::class, 'hooksList'],
            );
        } catch (Throwable) {
            // Keep resource available via attribute discovery if sizing fails.
        }

        try {
            $panel = new PanelReferenceResources();

            $fields = $panel->fieldsList();
            $registry->registerResource(
                new ResourceDefinition(
                    uri: 'kirby://fields',
                    name: 'panel_fields',
                    title: 'Panel Field Types',
                    description: 'List Kirby Panel field types (links to kirby://field/{type}).',
                    mimeType: 'text/markdown',
                    annotations: $defaultAnnotations,
                    size: strlen($fields),
                    meta: $this->resourceMetaFromMtime($this->resolveClassMtime(PanelReferenceIndex::class)),
                ),
                [PanelReferenceResources::class, 'fieldsList'],
            );

            $sections = $panel->sectionsList();
            $registry->registerResource(
                new ResourceDefinition(
                    uri: 'kirby://sections',
                    name: 'panel_sections',
                    title: 'Panel Section Types',
                    description: 'List Kirby Panel section types (links to kirby://section/{type}).',
                    mimeType: 'text/markdown',
                    annotations: $defaultAnnotations,
                    size: strlen($sections),
                    meta: $this->resourceMetaFromMtime($this->resolveClassMtime(PanelReferenceIndex::class)),
                ),
                [PanelReferenceResources::class, 'sectionsList'],
            );
        } catch (Throwable) {
            // Keep resources available via attribute discovery if sizing fails.
        }

        try {
            $contents = (new GlossaryResources())->glossaryList();
            $registry->registerResource(
                new ResourceDefinition(
                    uri: 'kirby://glossary',
                    name: 'glossary',
                    title: 'Kirby Glossary',
                    description: 'List bundled Kirby glossary terms (links to kirby://glossary/{term}).',
                    mimeType: 'text/markdown',
                    annotations: $defaultAnnotations,
                    size: strlen($contents),
                    meta: $this->resourceMetaFromMtime($this->resolveKbPrefixMtime('kb/glossary/')),
                ),
                [GlossaryResources::class, 'glossaryList'],
            );
        } catch (Throwable) {
            // Keep resource available via attribute discovery if sizing fails.
        }

        try {
            $contents = (new KbResources())->kbList();
            $registry->registerResource(
                new ResourceDefinition(
                    uri: 'kirby://kb',
                    name: 'kb',
                    title: 'Kirby Knowledge Base',
                    description: 'List bundled KB documents (links to kirby://kb/{path}).',
                    mimeType: 'text/markdown',
                    annotations: $defaultAnnotations,
                    size: strlen($contents),
                    meta: $this->resourceMetaFromMtime($this->resolveKbPrefixMtime('kb/')),
                ),
                [KbResources::class, 'kbList'],
            );
        } catch (Throwable) {
            // Keep resource available via attribute discovery if sizing fails.
        }

        try {
            $contents = (new UpdateSchemaResources())->contentFieldsList();
            $registry->registerResource(
                new ResourceDefinition(
                    uri: 'kirby://fields/update-schema',
                    name: 'update_schema_fields',
                    title: 'Content Field Update Schemas',
                    description: 'List bundled content field update schemas (links to kirby://field/{type}/update-schema).',
                    mimeType: 'text/markdown',
                    annotations: $importantAnnotations,
                    size: strlen($contents),
                    meta: $this->resourceMetaFromMtime($this->resolveKbPrefixMtime('kb/update-schema/')),
                ),
                [UpdateSchemaResources::class, 'contentFieldsList'],
            );
        } catch (Throwable) {
            // Keep resource available via attribute discovery if sizing fails.
        }

        try {
            $context = new ProjectContext();
            $projectRoot = $context->projectRoot();
            $lastModified = $this->resolveProjectMtime($projectRoot);
            $size = null;

            try {
                $payload = (new CliResources($context))->commands();
                if (($payload['ok'] ?? false) === true) {
                    $size = $this->resolveJsonSize($payload);
                }
            } catch (Throwable) {
                // Ignore size calculation failures; still register metadata.
            }

            $registry->registerResource(
                new ResourceDefinition(
                    uri: 'kirby://commands',
                    name: 'commands',
                    title: 'Kirby CLI Commands',
                    description: 'Kirby CLI command list for this project (parsed from `kirby help`).',
                    mimeType: 'application/json',
                    annotations: $importantAnnotations,
                    size: $size,
                    meta: $this->resourceMetaFromMtime($lastModified),
                ),
                [CliResources::class, 'commands'],
            );
        } catch (Throwable) {
            // Keep resource available via attribute discovery if registration fails.
        }
    }

    /**
     * @param array<int, string> $paths
     */
    private function resolveMaxMtime(array $paths): ?int
    {
        $latest = null;

        foreach ($paths as $path) {
            if ($path === '' || !is_file($path)) {
                continue;
            }

            $mtime = @filemtime($path);
            if (is_int($mtime)) {
                $latest = $latest === null ? $mtime : max($latest, $mtime);
            }
        }

        return $latest;
    }

    /**
     * @param class-string $className
     */
    private function resolveClassMtime(string $className): ?int
    {
        try {
            $reflection = new \ReflectionClass($className);
            $path = $reflection->getFileName();
            if (is_string($path) && $path !== '') {
                $mtime = @filemtime($path);
                if (is_int($mtime)) {
                    return $mtime;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function resolveKbPrefixMtime(string $prefix): ?int
    {
        $kbRoot = KbDocuments::kbRoot();
        if (!is_dir($kbRoot)) {
            return null;
        }

        $projectRoot = KbDocuments::projectRoot();
        $prefix = ltrim($prefix, '/');

        $latest = null;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($kbRoot, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'md') {
                continue;
            }

            $name = strtolower($file->getFilename());
            if ($name === 'plan.md' || $name === 'agents.md') {
                continue;
            }

            $path = $file->getPathname();
            $relative = ltrim(substr($path, strlen($projectRoot)), \DIRECTORY_SEPARATOR);
            $relative = str_replace(\DIRECTORY_SEPARATOR, '/', $relative);

            if (!str_starts_with($relative, $prefix)) {
                continue;
            }

            $mtime = $file->getMTime();
            $latest = $latest === null ? $mtime : max($latest, $mtime);
        }

        return $latest;
    }

    private function resolveProjectMtime(string $projectRoot): ?int
    {
        $projectRoot = rtrim($projectRoot, \DIRECTORY_SEPARATOR);

        return $this->resolveMaxMtime([
            $projectRoot . \DIRECTORY_SEPARATOR . 'composer.json',
            $projectRoot . \DIRECTORY_SEPARATOR . 'composer.lock',
            $projectRoot . \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR . 'bin' . \DIRECTORY_SEPARATOR . 'kirby',
        ]);
    }

    /**
     * @param array<string, mixed>|array<int, mixed> $payload
     */
    private function resolveJsonSize(array $payload): ?int
    {
        try {
            $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (Throwable) {
            return null;
        }

        return is_string($encoded) ? strlen($encoded) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resourceMetaFromMtime(?int $mtime): ?array
    {
        if (!is_int($mtime)) {
            return null;
        }

        $formatted = gmdate(DATE_ATOM, $mtime);
        if (!is_string($formatted) || $formatted === '') {
            return null;
        }

        return ['lastModified' => $formatted];
    }

    private function resolveVersion(): string
    {
        $composerJson = dirname(__DIR__, 2) . \DIRECTORY_SEPARATOR . 'composer.json';
        $composerVersion = null;

        if (is_file($composerJson)) {
            $contents = file_get_contents($composerJson);
            if (is_string($contents) && trim($contents) !== '') {
                try {
                    $decoded = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
                    if (is_array($decoded)) {
                        $version = $decoded['version'] ?? null;
                        if (is_string($version) && trim($version) !== '') {
                            $composerVersion = trim($version);
                        }
                    }
                } catch (Throwable) {
                    // Ignore invalid JSON and fall back to other sources.
                }
            }
        }

        if (class_exists(InstalledVersions::class)) {
            try {
                if (InstalledVersions::isInstalled('bnomei/kirby-mcp')) {
                    $pretty = InstalledVersions::getPrettyVersion('bnomei/kirby-mcp');
                    $reference = InstalledVersions::getReference('bnomei/kirby-mcp');

                    if (is_string($pretty) && $pretty !== '') {
                        $isDev = str_starts_with($pretty, 'dev-') || $pretty === 'dev-main' || $pretty === 'dev-master';
                        if ($isDev && is_string($composerVersion) && $composerVersion !== '') {
                            if (is_string($reference) && $reference !== '') {
                                return $composerVersion . '+' . substr($reference, 0, 7);
                            }

                            return $composerVersion;
                        }

                        if (is_string($reference) && $reference !== '') {
                            return $pretty . '+' . substr($reference, 0, 7);
                        }

                        return $pretty;
                    }
                }
            } catch (Throwable) {
                // Fall through to file-based/versionless fallback.
            }
        }

        if (is_string($composerVersion) && $composerVersion !== '') {
            return $composerVersion;
        }

        return '0.0.0';
    }
}
