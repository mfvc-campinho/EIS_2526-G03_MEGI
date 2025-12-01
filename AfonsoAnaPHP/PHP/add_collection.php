<?php
// public_html/PHP/register.php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../HTML/home_page.php');
}

// Aceita dois formatos:
// 1) acc-name, acc-email, acc-password, acc-password-confirm, acc-owner-photo, acc-dob, acc-member-since
// 2) username, email, password, password_confirm, photo_url, dob, member_since

$username   = trim($_POST['acc-name']            ?? $_POST['username']         ?? '');
$email      = trim($_POST['acc-email']           ?? $_POST['email']            ?? '');
$password   =        $_POST['acc-password']      ?? $_POST['password']        ?? '';
$confirm    =        $_POST['acc-password-confirm'] ?? $_POST['password_confirm'] ?? '';
$photo_url  = trim($_POST['acc-owner-photo']     ?? $_POST['photo_url']       ?? '');
$dob        =        $_POST['acc-dob']           ?? $_POST['dob']             ?? null;
$memberYear =        $_POST['acc-member-since']  ?? $_POST['member_since']    ?? date('Y');

if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    $_SESSION['error'] = 'All required fields must be filled.';
    redirect('../HTML/home_page.php');
}

if ($password !== $confirm) {
    $_SESSION['error'] = 'Passwords do not match.';
    redirect('../HTML/home_page.php');
}

// Verificar se email já existe
$check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->execute([$email]);
if ($check->fetch()) {
    $_SESSION['error'] = 'Email already registered.';
    redirect('../HTML/home_page.php');
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('
    INSERT INTO users (username, email, password_hash, photo_url, dob, member_since, created_at)
    VALUES (:username, :email, :hash, :photo_url, :dob, :member_since, NOW())
');

$stmt->execute([
    ':username'     => $username,
    ':email'        => $email,
    ':hash'         => $hash,
    ':photo_url'    => $photo_url !== '' ? $photo_url : null,
    ':dob'          => $dob ?: null,
    ':member_since' => $memberYear,
]);

$_SESSION['success'] = 'Account created. You can now log in.';
redirect('../HTML/home_page.php');
