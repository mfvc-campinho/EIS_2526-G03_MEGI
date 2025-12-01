<?php
// public_html/PHP/login.php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../HTML/home_page.php');
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    $_SESSION['error'] = 'Email and password are required.';
    redirect('../HTML/home_page.php');
}

$stmt = $pdo->prepare('SELECT id, username, email, password_hash FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['error'] = 'Invalid credentials.';
    redirect('../HTML/home_page.php');
}

$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];

redirect('../HTML/home_page.php');
