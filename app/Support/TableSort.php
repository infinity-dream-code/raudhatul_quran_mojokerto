<?php

namespace App\Support;

final class TableSort
{
  /**
   * @param  array<string, mixed>  $query
   * @return array{sort_by: string, sort_dir: string}
   */
  public static function resolve(array $query, string $defaultBy, string $defaultDir = 'asc'): array
  {
    $sortBy = strtolower(trim((string) ($query['sort_by'] ?? $defaultBy)));
    $sortDir = strtolower(trim((string) ($query['sort_dir'] ?? $defaultDir)));

    if (!in_array($sortDir, ['asc', 'desc'], true)) {
      $sortDir = $defaultDir === 'desc' ? 'desc' : 'asc';
    }

    return [
      'sort_by' => $sortBy,
      'sort_dir' => $sortDir,
    ];
  }

  /**
   * @param  array<string, mixed>  $query
   * @return array<string, mixed>
   */
  public static function toggleQuery(array $query, string $column, string $defaultDir = 'asc'): array
  {
    $currentBy = strtolower(trim((string) ($query['sort_by'] ?? $column)));
    $currentDir = strtolower(trim((string) ($query['sort_dir'] ?? $defaultDir)));
    if (!in_array($currentDir, ['asc', 'desc'], true)) {
      $currentDir = $defaultDir === 'desc' ? 'desc' : 'asc';
    }

    $nextDir = ($currentBy === $column && $currentDir === 'asc') ? 'desc' : 'asc';

    $next = $query;
    $next['sort_by'] = $column;
    $next['sort_dir'] = $nextDir;
    $next['page'] = 1;

    return $next;
  }

    public static function resolveTagihan(array $query): array
    {
        if (array_key_exists('sort_by', $query) && trim((string) $query['sort_by']) !== '') {
            return self::resolve($query, 'furutan', 'asc');
        }

        $dir = strtolower(trim((string) ($query['sort_urutan'] ?? 'asc')));
        if (!in_array($dir, ['asc', 'desc'], true)) {
            $dir = 'asc';
        }

        return [
            'sort_by' => 'furutan',
            'sort_dir' => $dir,
        ];
    }

    public static function sortRows(array $rows, string $sortBy, string $sortDir, array $columnMap, string $defaultBy = 'id'): array
    {
        $key = $columnMap[$sortBy] ?? $columnMap[$defaultBy] ?? $defaultBy;
        $dir = strtolower($sortDir) === 'desc' ? -1 : 1;

        usort($rows, static function ($a, $b) use ($key, $dir) {
            $left = is_array($a) ? ($a[$key] ?? '') : '';
            $right = is_array($b) ? ($b[$key] ?? '') : '';
            if (is_numeric($left) && is_numeric($right)) {
                return $dir * ((float) $left <=> (float) $right);
            }

            return $dir * strnatcasecmp((string) $left, (string) $right);
        });

        return $rows;
    }

    public static function iconClass(string $column, string $currentBy, string $currentDir): string
    {
        if ($column !== $currentBy) {
            return 'fa-solid fa-sort';
        }

        return $currentDir === 'desc' ? 'fa-solid fa-sort-down' : 'fa-solid fa-sort-up';
    }
}
