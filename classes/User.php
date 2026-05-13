<?php
// classes/User.php
require_once __DIR__ . '/../config/db.php';

class User {
    public static function getAll(int $page = 1, int $perPage = 20, string $sortBy = 'name', string $sortDir = 'asc'): array {
        $allowed  = ['name', 'email', 'role', 'school_name'];
        $sortBy   = in_array($sortBy, $allowed, true) ? $sortBy : 'name';
        if ($sortBy === 'school_name') {
            $sortBy = 's.name';
        } else {
            $sortBy = "u.{$sortBy}";
        }
        $sortDir  = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';
        $offset   = ($page - 1) * $perPage;
        
        $stmt = getPDO()->prepare(
            "SELECT u.*, s.name AS school_name
             FROM users u
             LEFT JOIN schools s ON u.school_id = s.id
             ORDER BY {$sortBy} {$sortDir}
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$perPage, $offset]);
        return $stmt->fetchAll();
    }

    public static function countAll(): int {
        return (int) getPDO()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public static function getById(int $id): array|false {
        $stmt = getPDO()->prepare('
            SELECT u.*, s.name AS school_name 
            FROM users u
            LEFT JOIN schools s ON u.school_id = s.id
            WHERE u.id = ?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create(array $d): int {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('
            INSERT INTO users (name, email, password, role, school_id, grade_level, section) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $d['name'], 
            $d['email'], 
            password_hash($d['password'], PASSWORD_DEFAULT),
            $d['role'], 
            $d['school_id'] ?: null, 
            $d['grade_level'] ?: null, 
            $d['section'] ?: null
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $d): void {
        $pdo  = getPDO();
        if (!empty($d['password'])) {
            $stmt = $pdo->prepare('
                UPDATE users 
                SET name = ?, email = ?, password = ?, role = ?, school_id = ?, grade_level = ?, section = ? 
                WHERE id = ?
            ');
            $stmt->execute([
                $d['name'], $d['email'], password_hash($d['password'], PASSWORD_DEFAULT), $d['role'], 
                $d['school_id'] ?: null, $d['grade_level'] ?: null, $d['section'] ?: null, $id
            ]);
        } else {
            $stmt = $pdo->prepare('
                UPDATE users 
                SET name = ?, email = ?, role = ?, school_id = ?, grade_level = ?, section = ? 
                WHERE id = ?
            ');
            $stmt->execute([
                $d['name'], $d['email'], $d['role'], 
                $d['school_id'] ?: null, $d['grade_level'] ?: null, $d['section'] ?: null, $id
            ]);
        }
    }

    public static function delete(int $id): void {
        getPDO()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
}
