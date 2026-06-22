<?php

namespace App\Support;

/**
 * Lecture ciblée d'un dump MySQL (INSERT) pour import de données.
 */
class SqlDumpReader
{
    public function __construct(
        private string $path
    ) {}

    public function path(): string
    {
        return $this->path;
    }

    /** @return list<string> lignes CSV sans parenthèses extérieures */
    public function rows(string $table): array
    {
        $sql = file_get_contents($this->path);
        if ($sql === false) {
            return [];
        }

        if (! preg_match_all('/INSERT INTO `'.preg_quote($table, '/').'`[^;]+;/s', $sql, $matches)) {
            return [];
        }

        $rows = [];
        foreach ($matches[0] as $block) {
            if (! preg_match('/VALUES\s*(.*);/s', $block, $vm)) {
                continue;
            }
            $rows = array_merge($rows, $this->splitTuples($vm[1]));
        }

        return $rows;
    }

    /** @return list<string> */
    private function splitTuples(string $body): array
    {
        $rows = [];
        $depth = 0;
        $current = '';
        $inStr = false;
        $esc = false;
        $len = strlen($body);

        for ($i = 0; $i < $len; $i++) {
            $c = $body[$i];
            if ($esc) {
                $current .= $c;
                $esc = false;

                continue;
            }
            if ($c === '\\' && $inStr) {
                $esc = true;
                $current .= $c;

                continue;
            }
            if ($c === "'") {
                $inStr = ! $inStr;
                $current .= $c;

                continue;
            }
            if (! $inStr && $c === '(') {
                if ($depth === 0) {
                    $current = '';
                } else {
                    $current .= $c;
                }
                $depth++;

                continue;
            }
            if (! $inStr && $c === ')') {
                $depth--;
                if ($depth === 0) {
                    $rows[] = $current;

                    continue;
                }
            }
            if ($depth > 0) {
                $current .= $c;
            }
        }

        return $rows;
    }

    /** @return list<string> */
    public function fields(string $row): array
    {
        $fields = [];
        $cur = '';
        $inStr = false;
        $esc = false;
        $len = strlen($row);

        for ($i = 0; $i < $len; $i++) {
            $c = $row[$i];
            if ($esc) {
                $cur .= $c;
                $esc = false;

                continue;
            }
            if ($c === '\\' && $inStr) {
                $esc = true;

                continue;
            }
            if ($c === "'") {
                $inStr = ! $inStr;

                continue;
            }
            if (! $inStr && $c === ',') {
                $fields[] = $this->normalizeField($cur);
                $cur = '';

                continue;
            }
            $cur .= $c;
        }
        $fields[] = $this->normalizeField($cur);

        return $fields;
    }

    private function normalizeField(string $v): string
    {
        $v = trim($v);
        if ($v === 'NULL') {
            return '';
        }

        return $v;
    }

    public function str(array $fields, int $i): string
    {
        return $fields[$i] ?? '';
    }

    public function int(array $fields, int $i): ?int
    {
        $v = $fields[$i] ?? '';
        if ($v === '') {
            return null;
        }

        return (int) $v;
    }

    public function float(array $fields, int $i): float
    {
        $v = $fields[$i] ?? '0';

        return (float) $v;
    }
}
