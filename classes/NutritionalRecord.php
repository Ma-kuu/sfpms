<?php
// classes/NutritionalRecord.php

require_once __DIR__ . '/../config/db.php';

class NutritionalRecord {

    // WHO BMI-for-age classification (simplified, school-age children)
    public static function classifyBMI(float $bmi): string {
        if ($bmi < 14.0)  return 'Severely Wasted';
        if ($bmi < 16.0)  return 'Wasted';
        if ($bmi < 18.5)  return 'Normal';
        if ($bmi < 25.0)  return 'Normal';
        if ($bmi < 30.0)  return 'Overweight';
        return 'Obese';
    }

    public static function badgeClass(string $classification): string {
        return match($classification) {
            'Severely Wasted' => 'badge-danger',
            'Wasted'          => 'badge-warning',
            'Overweight'      => 'badge-warning',
            'Obese'           => 'badge-danger',
            default           => 'badge-success',
        };
    }

    public static function getAll(
        ?int $schoolId = null,
        ?string $search = null,
        ?string $grade = null,
        ?string $section = null,
        string $classFilter = '',
        int $page = 1,
        int $perPage = 15,
        string $sortBy = 'full_name',
        string $sortDir = 'asc'
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
        if ($section) {
            $where[]  = 'b.section = ?';
            $params[] = $section;
        }
        if ($search) {
            $where[]  = '(b.first_name LIKE ? OR b.last_name LIKE ? OR b.lrn LIKE ?)';
            $like     = "%{$search}%";
            $params   = array_merge($params, [$like, $like, $like]);
        }

        if ($classFilter === 'Severely Wasted') {
            $where[] = 'nr.bmi < 14.0';
        } elseif ($classFilter === 'Wasted') {
            $where[] = 'nr.bmi >= 14.0 AND nr.bmi < 16.0';
        } elseif ($classFilter === 'Normal') {
            $where[] = 'nr.bmi >= 16.0 AND nr.bmi < 25.0';
        } elseif ($classFilter === 'Overweight') {
            $where[] = 'nr.bmi >= 25.0 AND nr.bmi < 30.0';
        } elseif ($classFilter === 'Obese') {
            $where[] = 'nr.bmi >= 30.0';
        }

        $allowedSorts = ['full_name', 'record_date', 'weight_kg', 'height_cm', 'bmi'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'full_name';
        $sortDir = strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC';

        if ($sortBy === 'full_name') {
            $orderBy = "b.last_name {$sortDir}, b.first_name ASC";
        } else {
            $orderBy = "nr.{$sortBy} {$sortDir}, b.last_name ASC";
        }

        $offset = ($page - 1) * $perPage;

        $sql  = "
            SELECT nr.*,
                   CONCAT(b.first_name,' ',b.last_name) AS full_name,
                   b.lrn, b.grade_level,
                   s.name AS school_name
            FROM nutritional_records nr
            JOIN beneficiaries b ON b.id = nr.beneficiary_id
            JOIN schools s ON s.id = b.school_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$orderBy}
            LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countAll(
        ?int $schoolId = null,
        ?string $search = null,
        ?string $grade = null,
        ?string $section = null,
        string $classFilter = ''
    ): int {
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
        if ($section) {
            $where[]  = 'b.section = ?';
            $params[] = $section;
        }
        if ($search) {
            $where[]  = '(b.first_name LIKE ? OR b.last_name LIKE ? OR b.lrn LIKE ?)';
            $like     = "%{$search}%";
            $params   = array_merge($params, [$like, $like, $like]);
        }

        if ($classFilter === 'Severely Wasted') {
            $where[] = 'nr.bmi < 14.0';
        } elseif ($classFilter === 'Wasted') {
            $where[] = 'nr.bmi >= 14.0 AND nr.bmi < 16.0';
        } elseif ($classFilter === 'Normal') {
            $where[] = 'nr.bmi >= 16.0 AND nr.bmi < 25.0';
        } elseif ($classFilter === 'Overweight') {
            $where[] = 'nr.bmi >= 25.0 AND nr.bmi < 30.0';
        } elseif ($classFilter === 'Obese') {
            $where[] = 'nr.bmi >= 30.0';
        }

        $sql  = "
            SELECT COUNT(*)
            FROM nutritional_records nr
            JOIN beneficiaries b ON b.id = nr.beneficiary_id
            WHERE " . implode(' AND ', $where) . "
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function getById(int $id): array|false {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            "SELECT nr.*,
                    CONCAT(b.first_name,' ',b.last_name) AS full_name,
                    b.lrn, b.school_id
             FROM nutritional_records nr
             JOIN beneficiaries b ON b.id = nr.beneficiary_id
             WHERE nr.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function create(array $data): int {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO nutritional_records
             (beneficiary_id, record_date, weight_kg, height_cm, recorded_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['beneficiary_id'], $data['record_date'],
            $data['weight_kg'],      $data['height_cm'],
            $data['recorded_by'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'UPDATE nutritional_records
             SET beneficiary_id=?, record_date=?, weight_kg=?, height_cm=?
             WHERE id=?'
        );
        $stmt->execute([
            $data['beneficiary_id'], $data['record_date'],
            $data['weight_kg'],      $data['height_cm'],
            $id,
        ]);
    }

    public static function delete(int $id): void {
        $pdo  = getPDO();
        $pdo->prepare('DELETE FROM nutritional_records WHERE id = ?')->execute([$id]);
    }
}
