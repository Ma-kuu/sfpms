<?php
// ============================================================
// classes/Beneficiary.php
// ============================================================

require_once __DIR__ . '/../config/db.php';

class Beneficiary {

    // Fetch list with optional filters
    public static function getAll(
        ?int    $schoolId = null,
        ?string $grade    = null,
        ?string $search   = null
    ): array {
        $pdo    = getPDO();
        $where  = ['1=1'];
        $params = [];

        if ($schoolId) {
            $where[]  = 'b.school_id = ?';
            $params[] = $schoolId;
        }
        if ($grade) {
            $where[]  = 'b.grade_level = ?';
            $params[] = $grade;
        }
        if ($search) {
            $where[]  = '(b.lrn LIKE ? OR b.first_name LIKE ? OR b.last_name LIKE ?)';
            $like     = "%{$search}%";
            $params   = array_merge($params, [$like, $like, $like]);
        }

        $sql  = 'SELECT b.*, s.name AS school_name
                 FROM beneficiaries b
                 JOIN schools s ON s.id = b.school_id
                 WHERE ' . implode(' AND ', $where) . '
                 ORDER BY b.last_name, b.first_name';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getById(int $id): array|false {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'SELECT b.*, s.name AS school_name
             FROM beneficiaries b
             JOIN schools s ON s.id = b.school_id
             WHERE b.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create(array $data): int {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO beneficiaries
             (lrn, first_name, last_name, birthdate, sex, grade_level, section, school_id, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['lrn'], $data['first_name'], $data['last_name'],
            $data['birthdate'], $data['sex'], $data['grade_level'],
            $data['section'], $data['school_id'], $data['status'] ?? 'Active',
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'UPDATE beneficiaries
             SET lrn=?, first_name=?, last_name=?, birthdate=?, sex=?,
                 grade_level=?, section=?, school_id=?, status=?
             WHERE id=?'
        );
        $stmt->execute([
            $data['lrn'], $data['first_name'], $data['last_name'],
            $data['birthdate'], $data['sex'], $data['grade_level'],
            $data['section'], $data['school_id'], $data['status'],
            $id,
        ]);
    }

    public static function delete(int $id): void {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('DELETE FROM beneficiaries WHERE id = ?');
        $stmt->execute([$id]);
    }

    // Return beneficiaries absent 3+ consecutive sessions (any school)
    public static function getFlaggedAbsent(?int $schoolId = null): array {
        $pdo    = getPDO();
        $where  = $schoolId ? 'WHERE b.school_id = ?' : '';
        $params = $schoolId ? [$schoolId] : [];

        // We fetch the last 3 attendance records per beneficiary and check all absent
        $sql = "
            SELECT b.id, b.first_name, b.last_name, b.lrn, s.name AS school_name,
                   COUNT(*) AS absent_streak
            FROM beneficiaries b
            JOIN schools s ON s.id = b.school_id
            JOIN feeding_attendance fa ON fa.beneficiary_id = b.id AND fa.present = 0
            JOIN feeding_sessions fs ON fs.id = fa.session_id
            {$where}
            GROUP BY b.id
            HAVING absent_streak >= 3
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countAll(?int $schoolId = null): int {
        $pdo    = getPDO();
        $where  = $schoolId ? 'WHERE school_id = ?' : '';
        $params = $schoolId ? [$schoolId] : [];
        $stmt   = $pdo->prepare("SELECT COUNT(*) FROM beneficiaries {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function getGrades(): array {
        $pdo  = getPDO();
        $stmt = $pdo->query('SELECT DISTINCT grade_level FROM beneficiaries ORDER BY grade_level');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
