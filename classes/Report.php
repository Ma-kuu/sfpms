<?php
// classes/Report.php

require_once __DIR__ . '/../config/db.php';

class Report {
    public static function getAttendance(string $dateFrom, string $dateTo, ?int $schoolId = null): array {
        $pdo = getPDO();
        $w = $schoolId ? 'AND fs.school_id = ?' : '';
        $p = $schoolId ? [$dateFrom, $dateTo, $schoolId] : [$dateFrom, $dateTo];
        $stmt = $pdo->prepare("
            SELECT
                CONCAT(b.first_name,' ',b.last_name) AS full_name,
                b.lrn, b.grade_level,
                s.name AS school_name,
                COUNT(fa.id) AS total_sessions,
                SUM(fa.present) AS total_present,
                (COUNT(fa.id) - SUM(fa.present)) AS total_absent
            FROM beneficiaries b
            JOIN schools s ON s.id = b.school_id
            JOIN feeding_attendance fa ON fa.beneficiary_id = b.id
            JOIN feeding_sessions fs ON fs.id = fa.session_id
                AND fs.session_date BETWEEN ? AND ?
            WHERE b.status = 'Active' {$w}
            GROUP BY b.id
            ORDER BY s.name, b.last_name
        ");
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    public static function getNutritional(string $dateFrom, string $dateTo, ?int $schoolId = null): array {
        $pdo = getPDO();
        $w = $schoolId ? 'AND b.school_id = ?' : '';
        $p = $schoolId ? [$dateFrom, $dateTo, $schoolId] : [$dateFrom, $dateTo];
        $stmt = $pdo->prepare("
            SELECT
                CONCAT(b.first_name,' ',b.last_name) AS full_name,
                b.lrn, b.grade_level,
                s.name AS school_name,
                nr.record_date, nr.weight_kg, nr.height_cm, nr.bmi
            FROM nutritional_records nr
            JOIN beneficiaries b ON b.id = nr.beneficiary_id
            JOIN schools s ON s.id = b.school_id
            WHERE nr.record_date BETWEEN ? AND ? {$w}
            ORDER BY s.name, b.last_name
        ");
        $stmt->execute($p);
        return $stmt->fetchAll();
    }

    public static function getInventory(?int $schoolId = null): array {
        $pdo = getPDO();
        $w = $schoolId ? 'WHERE i.school_id = ?' : '';
        $p = $schoolId ? [$schoolId] : [];
        $stmt = $pdo->prepare("
            SELECT i.*, s.name AS school_name,
                   IF(i.quantity <= i.low_stock_threshold,1,0) AS is_low
            FROM inventory i
            JOIN schools s ON s.id = i.school_id
            {$w}
            ORDER BY s.name, i.item_name
        ");
        $stmt->execute($p);
        return $stmt->fetchAll();
    }
}
