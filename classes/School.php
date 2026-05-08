<?php
// ============================================================
// classes/School.php
// ============================================================
require_once __DIR__ . '/../config/db.php';

class School {
    public static function getAll(int $page = 1, int $perPage = 20, string $sortBy = 'name', string $sortDir = 'asc'): array {
        $allowed  = ['name', 'address', 'beneficiary_count'];
        $sortBy   = in_array($sortBy, $allowed, true) ? $sortBy : 'name';
        $sortDir  = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';
        $offset   = ($page - 1) * $perPage;
        $stmt     = getPDO()->prepare(
            "SELECT s.*, COUNT(b.id) AS beneficiary_count
             FROM schools s
             LEFT JOIN beneficiaries b ON b.school_id = s.id
             GROUP BY s.id
             ORDER BY {$sortBy} {$sortDir}
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$perPage, $offset]);
        return $stmt->fetchAll();
    }

    public static function countAll(): int {
        return (int) getPDO()->query('SELECT COUNT(*) FROM schools')->fetchColumn();
    }

    public static function getById(int $id): array|false {
        $stmt = getPDO()->prepare('SELECT * FROM schools WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create(array $d): int {
        $pdo  = getPDO();
        $pdo->prepare('INSERT INTO schools (name, address) VALUES (?, ?)')->execute([$d['name'], $d['address'] ?? '']);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $d): void {
        getPDO()->prepare('UPDATE schools SET name = ?, address = ? WHERE id = ?')
            ->execute([$d['name'], $d['address'] ?? '', $id]);
    }

    public static function delete(int $id): void {
        getPDO()->prepare('DELETE FROM schools WHERE id = ?')->execute([$id]);
    }
}
