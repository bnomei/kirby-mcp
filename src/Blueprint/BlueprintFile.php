<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Blueprint;

final readonly class BlueprintFile
{
    /**
     * @param array<mixed>|null $data Parsed YAML (null if parse failed)
     */
    public function __construct(
        public string $id,
        public BlueprintType $type,
        public string $absolutePath,
        public string $relativePath,
        public ?array $data,
    ) {
    }

    /**
     * @return array{
     *   id:string,
     *   type:string,
     *   absolutePath:string,
     *   relativePath:string,
     *   displayName:string,
     *   displayNameSource: 'title'|'name'|'label'|'id',
     *   data: array<mixed>|null
     * }
     */
    public function toArray(): array
    {
        [$displayName, $displayNameSource] = $this->deriveDisplayName();

        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'absolutePath' => $this->absolutePath,
            'relativePath' => $this->relativePath,
            'displayName' => $displayName,
            'displayNameSource' => $displayNameSource,
            'data' => $this->data,
        ];
    }

    public function displayName(): string
    {
        return $this->deriveDisplayName()[0];
    }

    /**
     * @return 'title'|'name'|'label'|'id'
     */
    public function displayNameSource(): string
    {
        return $this->deriveDisplayName()[1];
    }

    /**
     * @return array{0: string, 1: 'title'|'name'|'label'|'id'}
     */
    private function deriveDisplayName(): array
    {
        $fallback = $this->id;
        $lastSlash = strrpos($fallback, '/');
        if ($lastSlash !== false) {
            $fallback = substr($fallback, $lastSlash + 1);
        }

        $fallback = trim($fallback);
        if ($fallback === '') {
            $fallback = $this->id;
        }

        if (!is_array($this->data)) {
            return [$fallback, 'id'];
        }

        foreach (['title', 'name', 'label'] as $key) {
            if (!array_key_exists($key, $this->data)) {
                continue;
            }

            $value = $this->data[$key];
            $string = $this->stringFromNameValue($value);
            if ($string !== null) {
                /** @var 'title'|'name'|'label' $key */
                return [$string, $key];
            }
        }

        return [$fallback, 'id'];
    }

    private function stringFromNameValue(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);
            return $value !== '' ? $value : null;
        }

        if (!is_array($value)) {
            return null;
        }

        $en = $value['en'] ?? null;
        if (is_string($en) && trim($en) !== '') {
            return trim($en);
        }

        foreach ($value as $v) {
            if (!is_string($v)) {
                continue;
            }

            $v = trim($v);
            if ($v !== '') {
                return $v;
            }
        }

        return null;
    }
}
