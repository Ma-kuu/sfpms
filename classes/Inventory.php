<?php
// classes/Inventory.php

require_once __DIR__ . '/../config/db.php';

class Inventory {

    public static function getAll(?int $schoolId = null): array {
        $pdo    = getPDO();
        $where  = $schoolId ? 'WHERE i.school_id = ?' : '';
        $params = $schoolId ? [$schoolId] : [];
        $sql    = "
            SELECT i.*, s.name AS school_name,
                   IF(i.quantity <= i.low_stock_threshold, 1, 0) AS is_low
            FROM inventory i
            JOIN schools s ON s.id = i.school_id
            {$where}
            ORDER BY s.name, i.item_name
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
