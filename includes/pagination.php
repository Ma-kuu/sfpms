<?php
// ============================================================
// includes/pagination.php  — reusable pagination helper
// ============================================================

/**
 * Returns ['page', 'perPage', 'offset', 'totalPages', 'total']
 */
function paginate(int $totalRows, int $perPage = 15): array {
    $page       = max(1, (int)($_GET['page'] ?? 1));
    $totalPages = (int) ceil($totalRows / $perPage);
    $page       = min($page, max(1, $totalPages));
    return [
        'page'       => $page,
        'perPage'    => $perPage,
        'offset'     => ($page - 1) * $perPage,
        'totalPages' => $totalPages,
        'total'      => $totalRows,
    ];
}

/**
 * Renders Bootstrap-style prev/next pagination links
 */
function renderPagination(array $p, string $baseUrl = ''): string {
    if ($p['totalPages'] <= 1) return '';

    if (!$baseUrl) {
        $q = $_GET;
        unset($q['page']);
        $baseUrl = '?' . http_build_query($q);
        $sep     = '&';
    } else {
        $sep = str_contains($baseUrl, '?') ? '&' : '?';
    }

    $prev = $p['page'] - 1;
    $next = $p['page'] + 1;
    $last = $p['totalPages'];

    $out  = '<nav class="d-flex align-items-center justify-content-between mt-3 px-1" aria-label="Pagination">';
    $out .= '<small class="text-muted" style="font-size:.75rem;">Showing page '
          . $p['page'] . ' of ' . $last
          . ' &nbsp;·&nbsp; ' . $p['total'] . ' total records</small>';
    $out .= '<ul class="pagination pagination-sm mb-0">';

    // Prev
    $out .= '<li class="page-item' . ($p['page'] <= 1 ? ' disabled' : '') . '">';
    $out .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . $prev . '">&laquo; Prev</a></li>';

    // Page numbers (show up to 5 around current)
    $start = max(1, $p['page'] - 2);
    $end   = min($last, $p['page'] + 2);
    if ($start > 1) $out .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $p['page'] ? ' active' : '';
        $out .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $baseUrl . $sep . 'page=' . $i . '">' . $i . '</a></li>';
    }
    if ($end < $last) $out .= '<li class="page-item disabled"><span class="page-link">…</span></li>';

    // Next
    $out .= '<li class="page-item' . ($p['page'] >= $last ? ' disabled' : '') . '">';
    $out .= '<a class="page-link" href="' . $baseUrl . $sep . 'page=' . $next . '">Next &raquo;</a></li>';

    $out .= '</ul></nav>';
    return $out;
}
