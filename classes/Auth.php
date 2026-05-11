<?php
// classes/Auth.php
require_once __DIR__ . '/../config/db.php';

class Auth {

    public static function login(string $email, string $password): bool {
        $pdo  = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            session_regenerate_id(true);
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_name']   = $user['name'];
            $_SESSION['user_role']   = $user['role'];
            $_SESSION['school_id']   = $user['school_id'];
            $_SESSION['grade_level'] = $user['grade_level'];
            $_SESSION['section']     = $user['section'];
            return true;
        }
        return false;
    }

    public static function logout(): void {
        session_unset();
        session_destroy();
        header('Location: ../pages/login.php');
        exit;
    }

    public static function check(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: ../pages/login.php');
            exit;
        }
    }

    public static function checkRole(array $roles): void {
        self::check();
        if (!in_array($_SESSION['user_role'], $roles, true)) {
            http_response_code(403);
            die('<p style="font-family:sans-serif;padding:2rem;">403 — Access denied.</p>');
        }
    }

    public static function user(): array {
        return [
            'id'          => $_SESSION['user_id']     ?? null,
            'name'        => $_SESSION['user_name']   ?? '',
            'role'        => $_SESSION['user_role']    ?? '',
            'school_id'   => $_SESSION['school_id']   ?? null,
            'grade_level' => $_SESSION['grade_level']  ?? null,
            'section'     => $_SESSION['section']      ?? null,
        ];
    }

    public static function isSuperAdmin(): bool {
        return ($_SESSION['user_role'] ?? '') === 'super_admin';
    }

    public static function isTeacher(): bool {
        return ($_SESSION['user_role'] ?? '') === 'teacher';
    }

    public static function isSchoolAdmin(): bool {
        return ($_SESSION['user_role'] ?? '') === 'school_admin';
    }
}
