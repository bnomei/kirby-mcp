<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Support;

final class IndexList
{
    /**
     * @param array<int, string> $ids
     * @return array{
     *   ids: array<int, string>,
     *   pagination: array{cursor:int, limit:int, nextCursor:int|null, hasMore:bool, returned:int, total:int}
     * }
     */
    public static function paginateIds(array $ids, int $cursor, int $limit): array
    {
        if ($cursor < 0) {
            $cursor = 0;
        }

        if ($limit < 0) {
            $limit = 0;
        }

        $total = count($ids);

        $paged = $ids;
        if ($cursor > 0 || $limit > 0) {
            if ($cursor >= $total) {
                $paged = [];
            } elseif ($limit > 0) {
                $paged = array_slice($ids, $cursor, $limit);
            } else {
                $paged = array_slice($ids, $cursor);
            }
        }

        $returned = count($paged);
        $nextCursor = null;
        $hasMore = false;
        if ($limit > 0 && $cursor + $returned < $total) {
            $nextCursor = $cursor + $returned;
            $hasMore = true;
        }

        return [
            'ids' => $paged,
            'pagination' => [
                'cursor' => $cursor,
                'limit' => $limit,
                'nextCursor' => $nextCursor,
                'hasMore' => $hasMore,
                'returned' => $returned,
                'total' => $total,
            ],
        ];
    }

    /**
     * @param array<int, string>|null $fields
     * @param array<string, mixed> $entry
     * @return array<string, mixed>
     */
    public static function selectFields(array $entry, ?array $fields, string $id): array
    {
        if (!is_array($fields)) {
            return $entry;
        }

        $wanted = [];
        foreach ($fields as $field) {
            if (!is_string($field)) {
                continue;
            }

            $field = trim($field);
            if ($field === '') {
                continue;
            }

            $wanted[] = $field;
        }

        $wanted = array_values(array_unique($wanted));
        if ($wanted === []) {
            return $entry;
        }

        if (!in_array('id', $wanted, true)) {
            $wanted[] = 'id';
        }

        $selected = [];
        foreach ($wanted as $field) {
            if ($field === 'id') {
                $selected['id'] = $id;
                continue;
            }

            if (array_key_exists($field, $entry)) {
                $selected[$field] = $entry[$field];
            }
        }

        return $selected;
    }
}
