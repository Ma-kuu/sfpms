<?php
// pages/login.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';

// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (!$email || !$password) {
        $error = 'Please enter both email and password.';
    } elseif (!Auth::login($email, $password)) {
        $error = 'Invalid email or password. Please try again.';
    } else {
        header('Location: dashboard.php');
        exit;
    }
}