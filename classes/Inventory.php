<?php
// classes/Inventory.php

require_once __DIR__ . '/../config/db.php';

class Inventory {

    public static function getAll(
        ?int $schoolId = null,
        string $statusFilter = '',
        int $page = 1,
        int $perPage = 15,
        string $sortBy = 'item_name',
        string $sortDir = 'asc'
    ): array {
        $pdo    = getPDO();
        $where  = ['1=1'];
        $params = [];

        if ($schoolId) {
            $where[]  = 'i.school_id = ?';
            $params[] = $schoolId;
        }

        if ($statusFilter === 'low') {
            $where[] = 'i.quantity <= i.low_stock_threshold';
        } elseif ($statusFilter === 'adequate') {
            $where[] = 'i.quantity > i.low_stock_threshold';
        }

        $allowedSorts = ['item_name', 'school_name', 'quantity', 'unit'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'item_name';
        $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';

        if ($sortBy === 'school_name') {
            $orderBy = "s.name {$sortDir}, i.item_name ASC";
        } else {
            $orderBy = "i.{$sortBy} {$sortDir}, i.item_name ASC";
        }

        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT i.*, s.name AS school_name,
                   IF(i.quantity <= i.low_stock_threshold, 1, 0) AS is_low
            FROM inventory i
            JOIN schools s ON s.id = i.school_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$orderBy}
            LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countAll(?int $schoolId = null, string $statusFilter = ''): int {
        $pdo    = getPDO();
        $where  = ['1=1'];
        $params = [];

        if ($schoolId) {
            $where[]  = 'school_id = ?';
            $params[] = $schoolId;
        }

        if ($statusFilter === 'low') {
            $where[] = 'quantity <= low_stock_threshold';
        } elseif ($statusFilter === 'adequate') {
            $where[] = 'quantity > low_stock_threshold';
        }

        $sql = "SELECT COUNT(*) FROM inventory WHERE " . implode(' AND ', $where);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function getSchoolSummaries(?int $schoolId = null): array {
        $pdo    = getPDO();
        $where  = $schoolId ? 'WHERE s.id = ?' : '';
        $params = $schoolId ? [$schoolId] : [];
        $sql    = "
            SELECT s.id AS school_id, s.name AS school_name,
                   COUNT(i.id) AS total_items,
                   SUM(IF(i.quantity <= i.low_stock_threshold, 1, 0)) AS low_stock_items
            FROM schools s
            LEFT JOIN inventory i ON s.id = i.school_id
            {$where}
            GROUP BY s.id, s.name
            ORDER BY s.name
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): array|false {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'SELECT i.*, s.name AS school_name,
                    IF(i.quantity <= i.low_stock_threshold, 1, 0) AS is_low
             FROM inventory i
             JOIN schools s ON s.id = i.school_id
             WHERE i.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create(array $data): int {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO inventory (school_id, item_name, unit, quantity, low_stock_threshold)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['school_id'], $data['item_name'],
            $data['unit'],      $data['quantity'],
            $data['low_stock_threshold'] ?? 10,
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'UPDATE inventory
             SET school_id=?, item_name=?, unit=?, quantity=?, low_stock_threshold=?
             WHERE id=?'
        );
        $stmt->execute([
            $data['school_id'], $data['item_name'],
            $data['unit'],      $data['quantity'],
            $data['low_stock_threshold'] ?? 10,
            $id,
        ]);
    }

    public static function delete(int $id): void {
        $pdo  = getPDO();
        $pdo->prepare('DELETE FROM inventory WHERE id = ?')->execute([$id]);
    }

    public static function getLowStockCount(?int $schoolId = null): int {
        $pdo    = getPDO();
        $where  = $schoolId ? 'WHERE school_id = ? AND quantity <= low_stock_threshold'
                            : 'WHERE quantity <= low_stock_threshold';
        $params = $schoolId ? [$schoolId] : [];
        $stmt   = $pdo->prepare("SELECT COUNT(*) FROM inventory {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
