<?php
// pages/router.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/Beneficiary.php';
require_once __DIR__ . '/../classes/Inventory.php';
require_once __DIR__ . '/../classes/FeedingLog.php';
require_once __DIR__ . '/../classes/NutritionalRecord.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/School.php';
require_once __DIR__ . '/../config/db.php';
Auth::check();

$module = $_POST['module'] ?? '';
$action = $_POST['action'] ?? '';
$user = Auth::user();
$isSA = Auth::isSuperAdmin();
$isTeacher = Auth::isTeacher();
$schoolId = $isSA ? null : (int)$user['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/helpers.php';
    csrf_validate();
    
    if ($module === 'notification') {
        $id = (int)($_POST['id'] ?? 0);
        if ($action === 'read') {
            Notification::markAsRead($id, $user['id']);
        } elseif ($action === 'delete') {
            Notification::delete($id, $user['id']);
        }
        exit; // AJAX call, no redirect
    }

    if ($module === 'beneficiary') {
        if ($action === 'add' || $action === 'edit') {
            $data = [
                'lrn'         => trim($_POST['lrn'] ?? ''),
                'first_name'  => trim($_POST['first_name'] ?? ''),
                'last_name'   => trim($_POST['last_name'] ?? ''),
                'birthdate'   => $_POST['birthdate'] ?? '',
                'sex'         => $_POST['sex'] ?? 'Male',
                'grade_level' => $isTeacher ? $user['grade_level'] : trim($_POST['grade_level'] ?? ''),
                'section'     => $isTeacher ? $user['section'] : trim($_POST['section'] ?? ''),
                'school_id'   => $isSA ? (int)$_POST['school_id'] : $schoolId,
                'status'      => $_POST['status'] ?? 'Active',
            ];
            if ($action === 'add') {
                Beneficiary::create($data);
            } else {
                Beneficiary::update((int)$_POST['id'], $data);
            }
        } elseif ($action === 'delete') {
            Beneficiary::delete((int)$_POST['id']);
        }
    }

    if ($module === 'inventory') {
        Auth::checkRole(['super_admin', 'school_admin']);
        if ($action === 'add') {
            Inventory::create([
                'school_id'           => $isSA ? (int)$_POST['school_id'] : $schoolId,
                'item_name'           => trim($_POST['item_name']),
                'unit'                => trim($_POST['unit']),
                'quantity'            => (float)$_POST['quantity'],
                'low_stock_threshold' => (float)($_POST['low_stock_threshold'] ?? 10),
            ]);
        } elseif ($action === 'edit') {
            Inventory::update((int)$_POST['id'], [
                'school_id'           => $isSA ? (int)$_POST['school_id'] : $schoolId,
                'item_name'           => trim($_POST['item_name']),
                'unit'                => trim($_POST['unit']),
                'quantity'            => (float)$_POST['quantity'],
                'low_stock_threshold' => (float)($_POST['low_stock_threshold'] ?? 10),
            ]);
        } elseif ($action === 'delete') {
            Inventory::delete((int)$_POST['id']);
        }
    }

    if ($module === 'feeding_session') {
        if ($action === 'add') {
            FeedingLog::createSession([
                'school_id'    => $isSA ? (int)$_POST['school_id'] : $schoolId,
                'session_date' => $_POST['session_date'],
                'meal_type'    => $_POST['meal_type'],
                'remarks'      => trim($_POST['remarks'] ?? ''),
                'created_by'   => $user['id'],
            ]);
        } elseif ($action === 'edit') {
            FeedingLog::updateSession((int)$_POST['id'], [
                'school_id'    => $isSA ? (int)$_POST['school_id'] : $schoolId,
                'session_date' => $_POST['session_date'],
                'meal_type'    => $_POST['meal_type'],
                'remarks'      => trim($_POST['remarks'] ?? ''),
            ]);
        } elseif ($action === 'delete') {
            FeedingLog::deleteSession((int)$_POST['id']);
        }
    }

    if ($module === 'attendance') {
        if ($action === 'save_attendance') {
            $sessionId  = (int)$_POST['session_id'];
            $presentIds = array_map('intval', $_POST['present'] ?? []);
            $targetIds  = array_map('intval', $_POST['target_ids'] ?? []);
            FeedingLog::saveAttendance($sessionId, $presentIds, $targetIds);
            header('Location: feeding_log.php?msg=saved');
            exit;
        }
    }

    if ($module === 'nutritional') {
        if ($action === 'add') {
            NutritionalRecord::create([
                'beneficiary_id' => (int)$_POST['beneficiary_id'],
                'record_date'    => $_POST['record_date'],
                'weight_kg'      => (float)$_POST['weight_kg'],
                'height_cm'      => (float)$_POST['height_cm'],
                'recorded_by'    => $user['id'],
            ]);
        } elseif ($action === 'edit') {
            NutritionalRecord::update((int)$_POST['id'], [
                'beneficiary_id' => (int)$_POST['beneficiary_id'],
                'record_date'    => $_POST['record_date'],
                'weight_kg'      => (float)$_POST['weight_kg'],
                'height_cm'      => (float)$_POST['height_cm'],
            ]);
        } elseif ($action === 'delete') {
            NutritionalRecord::delete((int)$_POST['id']);
        }
    }

    if ($module === 'user') {
        Auth::checkRole(['super_admin']);
        if ($action === 'add' || $action === 'edit') {
            $data = [
                'name'        => trim($_POST['name'] ?? ''),
                'email'       => trim($_POST['email'] ?? ''),
                'password'    => $_POST['password'] ?? '',
                'role'        => $_POST['role'] ?? 'teacher',
                'school_id'   => $_POST['school_id'] ?: null,
                'grade_level' => trim($_POST['grade_level'] ?? ''),
                'section'     => trim($_POST['section'] ?? '')
            ];
            if ($action === 'add') {
                if (!$data['password']) $data['password'] = 'password';
                User::create($data);
                $msg = 'User added successfully.';
            } else {
                User::update((int)$_POST['id'], $data);
                $msg = 'User updated.';
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id && $id !== $user['id']) {
                User::delete($id);
                $msg = 'User deleted.';
            } else {
                $err = 'Cannot delete yourself.';
            }
        }
    }

    if ($module === 'school') {
        Auth::checkRole(['super_admin']);
        if ($action === 'add') {
            School::create(['name' => trim($_POST['name']), 'address' => trim($_POST['address'])]);
            $msg = 'School added successfully.';
        } elseif ($action === 'edit') {
            School::update((int)$_POST['id'], ['name' => trim($_POST['name']), 'address' => trim($_POST['address'])]);
            $msg = 'School updated.';
        } elseif ($action === 'delete') {
            School::delete((int)$_POST['id']);
            $msg = 'School deleted.';
        } elseif ($action === 'toggle_status') {
            School::updateStatus((int)$_POST['id'], $_POST['status'] ?? 'Active');
            $msg = 'School status updated.';
        }
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? 'dashboard.php';
    // Append messages if any
    if (isset($msg)) {
        $referer .= (strpos($referer, '?') !== false ? '&' : '?') . 'msg=' . urlencode($msg);
    } elseif (isset($err)) {
        $referer .= (strpos($referer, '?') !== false ? '&' : '?') . 'err=' . urlencode($err);
    }
    header('Location: ' . $referer);
    exit;
}
