<?php
// includes/helpers.php

if (!function_exists('sortUrl')) {
    function sortUrl(string $col, string $currentSort, string $currentDir): string {
        $newDir = ($currentSort === $col && $currentDir === 'asc') ? 'desc' : 'asc';
        $q = array_merge($_GET, ['sort' => $col, 'dir' => $newDir, 'page' => 1]);
        return '?' . http_build_query($q);
    }
}

if (!function_exists('sortIcon')) {
    function sortIcon(string $col, string $currentSort, string $currentDir): string {
        if ($currentSort !== $col) return '<i class="bi bi-arrow-down-up ms-1" style="color:#d1d5db;"></i>';
        return $currentDir === 'asc'
            ? '<i class="bi bi-sort-alpha-down ms-1" style="color:var(--primary);"></i>'
            : '<i class="bi bi-sort-alpha-up ms-1" style="color:var(--primary);"></i>';
    }
}

if (!function_exists('sortArray')) {
    function sortArray(array &$array, string $key, string $dir = 'asc'): void {
        usort($array, function($a, $b) use ($key, $dir) {
            $valA = $a[$key] ?? '';
            $valB = $b[$key] ?? '';
            if (is_numeric($valA) && is_numeric($valB)) {
                $res = $valA <=> $valB;
            } else {
                $res = strcasecmp((string)$valA, (string)$valB);
            }
            return $dir === 'asc' ? $res : -$res;
        });
    }
}

// --- CSRF Protection ---

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function csrf_validate(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token mismatch.');
    }
}
