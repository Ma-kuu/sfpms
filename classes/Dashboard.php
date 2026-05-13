<?php
// classes/Dashboard.php

require_once __DIR__ . '/../config/db.php';

class Dashboard {
    public static function getNeedsRecheckCount(?int $schoolId, bool $isTeacher, ?string $teacherGrade, ?string $teacherSection): int {
        $pdo = getPDO();
        $recheckWhere = '';
        $recheckParams = [];
        if ($schoolId) {
            $recheckWhere .= ' AND b.school_id = ?';
            $recheckParams[] = $schoolId;
        }
        if ($isTeacher) {
            $recheckWhere .= ' AND b.grade_level = ? AND b.section = ?';
            $recheckParams[] = $teacherGrade;
            $recheckParams[] = $teacherSection;
        }
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM beneficiaries b
            WHERE b.status = 'Active' {$recheckWhere}
            AND b.id NOT IN (
                SELECT beneficiary_id FROM nutritional_records
                WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
            )
        ");
        $stmt->execute($recheckParams);
        return (int) $stmt->fetchColumn();
    }

    public static function getBeneficiariesPerGrade(?int $schoolId): array {
        $pdo = getPDO();
        $gradeData = $pdo->prepare("
            SELECT grade_level, COUNT(*) AS total
            FROM beneficiaries WHERE school_id = ? AND status='Active'
            GROUP BY grade_level ORDER BY grade_level
        ");
        $gradeData->execute([$schoolId]);
        return $gradeData->fetchAll();
    }

    public static function getAttendanceToday(?int $schoolId, bool $isTeacher, ?string $teacherGrade, ?string $teacherSection): array {
        $pdo = getPDO();
        
        $latestSql = 'SELECT id, session_date FROM feeding_sessions WHERE 1=1';
        $latestParams = [];
        if ($schoolId) {
            $latestSql .= ' AND school_id = ?';
            $latestParams[] = $schoolId;
        }
        $latestSql .= ' ORDER BY session_date DESC LIMIT 1';
        $stmt = $pdo->prepare($latestSql);
        $stmt->execute($latestParams);
        $latestSession = $stmt->fetch();

        if (!$latestSession) {
            return ['date' => 'No Sessions Yet', 'present' => 0, 'absent' => 0];
        }

        $sid = $latestSession['id'];
        $attSql = 'SELECT SUM(fa.present) AS p, COUNT(*) AS t
                   FROM feeding_attendance fa
                   JOIN beneficiaries b ON b.id = fa.beneficiary_id
                   WHERE fa.session_id = ?';
        $attParams = [$sid];
        if ($isTeacher) {
            $attSql .= ' AND b.grade_level = ? AND b.section = ?';
            $attParams[] = $teacherGrade;
            $attParams[] = $teacherSection;
        }
        $stmt = $pdo->prepare($attSql);
        $stmt->execute($attParams);
        $row = $stmt->fetch();
        
        $present = (int)($row['p'] ?? 0);
        $absent  = (int)($row['t'] ?? 0) - $present;
        
        return [
            'date' => date('M j, Y', strtotime($latestSession['session_date'])),
            'present' => $present,
            'absent' => $absent
        ];
    }

    public static function getAttendanceTrend(?int $schoolId): array {
        $pdo = getPDO();
        $trendSql = "
            SELECT fs.session_date, SUM(fa.present) AS present, COUNT(fa.id) AS total
            FROM feeding_sessions fs
            JOIN feeding_attendance fa ON fa.session_id = fs.id
            WHERE 1=1
        ";
        $trendParams = [];
        if ($schoolId) {
            $trendSql .= ' AND fs.school_id = ?';
            $trendParams[] = $schoolId;
        }
        $trendSql .= ' GROUP BY fs.id ORDER BY fs.session_date ASC LIMIT 10';
        $stmt = $pdo->prepare($trendSql);
        $stmt->execute($trendParams);
        return $stmt->fetchAll();
    }
}
