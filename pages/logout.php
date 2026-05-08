<?php
// ============================================================
// pages/logout.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../classes/Auth.php';
Auth::logout();
