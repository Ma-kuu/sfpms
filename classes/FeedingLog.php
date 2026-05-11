<?php
// classes/FeedingLog.php

require_once __DIR__ . '/../config/db.php';

class FeedingLog {

    // Sessions
    public static function getSessions(
        ?int $schoolId = null,
        int $page = 1,
        int $perPage = 15,
        string $sortBy = 'session_date',
        string $sortDir = 'desc'
    ): array {
        $pdo    = getPDO();
        $where  = $schoolId ? 'WHERE fs.school_id = ?' : '';
        $params = $schoolId ? [$schoolId] : [];
        $offset = ($page - 1) * $perPage;

        $allowedSorts = ['session_date', 'school_name', 'meal_type', 'present_count'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'session_date';
        $sortDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';
        
        $orderBy = $sortBy === 'school_name' ? "s.name {$sortDir}, fs.session_date DESC" : "{$sortBy} {$sortDir}, fs.session_date DESC";

        $sql    = "
            SELECT fs.*, s.name AS school_name,
                   COUNT(fa.id) AS total_enrolled,
                   SUM(fa.present) AS present_count
            FROM feeding_sessions fs
            JOIN schools s ON s.id = fs.school_id
            LEFT JOIN feeding_attendance fa ON fa.session_id = fs.id
            {$where}
            GROUP BY fs.id
            ORDER BY {$orderBy}
            LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getSessionById(int $id): array|false {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'SELECT fs.*, s.name AS school_name
             FROM feeding_sessions fs
             JOIN schools s ON s.id = fs.school_id
             WHERE fs.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function createSession(array $data): int {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO feeding_sessions (school_id, session_date, meal_type, remarks, created_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['school_id'], $data['session_date'],
            $data['meal_type'], $data['remarks'] ?? null,
            $data['created_by'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateSession(int $id, array $data): void {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'UPDATE feeding_sessions
             SET school_id=?, session_date=?, meal_type=?, remarks=?
             WHERE id=?'
        );
        $stmt->execute([
            $data['school_id'], $data['session_date'],
            $data['meal_type'], $data['remarks'] ?? null,
            $id,
        ]);
    }

    public static function deleteSession(int $id): void {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('DELETE FROM feeding_sessions WHERE id = ?');
        $stmt->execute([$id]);
    }

    // Attendance
    public static function getAttendanceForSession(int $sessionId, ?string $grade = null, ?string $section = null): array {
        $pdo    = getPDO();
        $where  = 'b.school_id = fs.school_id AND b.status = "Active"';
        $params = [$sessionId, $sessionId];
        
        if ($grade) {
            $where .= ' AND b.grade_level = ?';
            $params[] = $grade;
        }
        if ($section) {
            $where .= ' AND b.section = ?';
            $params[] = $section;
        }

        $stmt = $pdo->prepare(
            "SELECT b.id AS beneficiary_id,
                    CONCAT(b.first_name,' ',b.last_name) AS full_name,
                    b.grade_level, b.lrn,
                    COALESCE(fa.present, 0) AS present
             FROM beneficiaries b
             JOIN feeding_sessions fs ON fs.id = ?
             LEFT JOIN feeding_attendance fa
                    ON fa.session_id = ? AND fa.beneficiary_id = b.id
             WHERE {$where}
             ORDER BY b.last_name, b.first_name"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function saveAttendance(int $sessionId, array $presentIds, array $targetIds): void {
        $pdo = getPDO();
        if (empty($targetIds)) return;

        // Ensure no duplicates in targetIds
        $targetIds = array_unique($targetIds);

        // Delete existing records ONLY for the target IDs for this session
        $inMarks = str_repeat('?,', count($targetIds) - 1) . '?';
        $params = array_merge([$sessionId], $targetIds);
        $pdo->prepare("DELETE FROM feeding_attendance WHERE session_id = ? AND beneficiary_id IN ($inMarks)")->execute($params);

        $ins = $pdo->prepare(
            'INSERT INTO feeding_attendance (session_id, beneficiary_id, present)
             VALUES (?, ?, ?)'
        );
        foreach ($targetIds as $bid) {
            $ins->execute([$sessionId, $bid, in_array($bid, $presentIds) ? 1 : 0]);
        }
    }

    public static function countSessions(?int $schoolId = null): int {
        $pdo    = getPDO();
        $where  = $schoolId ? 'WHERE school_id = ?' : '';
        $params = $schoolId ? [$schoolId] : [];
        $stmt   = $pdo->prepare("SELECT COUNT(*) FROM feeding_sessions {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // Beneficiaries per school (for Chart.js)
    public static function getBeneficiaryCountPerSchool(): array {
        $pdo  = getPDO();
        $stmt = $pdo->query(
            'SELECT s.name AS school_name, COUNT(b.id) AS total
             FROM schools s
             LEFT JOIN beneficiaries b ON b.school_id = s.id
             GROUP BY s.id ORDER BY total DESC'
        );
        return $stmt->fetchAll();
    }
}
