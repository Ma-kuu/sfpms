<?php
// classes/Notification.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/Beneficiary.php';
require_once __DIR__ . '/Inventory.php';

class Notification {
    public static function syncDynamicNotifs($userId, $schoolId, $role, $gradeLevel = null, $section = null) {
        $pdo = getPDO();
        $today = date('Y-m-d');
        
        $isTeacher = ($role === 'teacher');
        $isSA = ($role === 'super_admin');
        
        // Helper to insert if not exists today
        $insertIfNotExists = function($type, $icon, $message, $link) use ($pdo, $userId, $schoolId, $role, $today) {
            $stmt = $pdo->prepare("SELECT id FROM notifications WHERE user_id = ? AND message = ? AND DATE(created_at) = ?");
            $stmt->execute([$userId, $message, $today]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, school_id, role, type, icon, message, link) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $schoolId, $role, $type, $icon, $message, $link]);
            }
        };

        // 1. Missing sessions
        $flaggedAbsent = Beneficiary::getFlaggedAbsent($schoolId);
        if (count($flaggedAbsent) > 0) {
            $msg = count($flaggedAbsent) . ' beneficiar' . (count($flaggedAbsent) > 1 ? 'ies have' : 'y has') . ' missed 3+ sessions.';
            $insertIfNotExists('danger', 'person-x-fill', $msg, 'beneficiaries.php');
        }

        // 2. Needs recheck
        $recheckWhere = $schoolId ? 'AND b.school_id = ' . (int)$schoolId : '';
        if ($isTeacher) {
            $recheckWhere .= " AND b.grade_level = " . $pdo->quote($gradeLevel)
                          .  " AND b.section = " . $pdo->quote($section);
        }
        $needsRecheck = $pdo->query("
            SELECT COUNT(*) FROM beneficiaries b
            WHERE b.status = 'Active' {$recheckWhere}
            AND b.id NOT IN (
                SELECT beneficiary_id FROM nutritional_records
                WHERE record_date >= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
            )
        ")->fetchColumn();
        
        if ($needsRecheck > 0) {
            $msg = $needsRecheck . ' student' . ($needsRecheck > 1 ? 's need' : ' needs') . ' nutritional re-check.';
            $insertIfNotExists('warning', 'bell-fill', $msg, 'nutritional.php');
        }

        // 3. Low stock (admins only)
        if (!$isTeacher) {
            $lowStockCount = Inventory::getLowStockCount($schoolId);
            if ($lowStockCount > 0) {
                $msg = $lowStockCount . ' item' . ($lowStockCount > 1 ? 's are' : ' is') . ' low on stock.';
                $insertIfNotExists('warning', 'exclamation-triangle-fill', $msg, 'inventory.php');
            }
        }
    }

    public static function getUnread($userId) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function markAsRead($id, $userId) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }

    public static function delete($id, $userId) {
        $pdo = getPDO();
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    }
}
