<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: all_collections.php');
    exit;
}

$errors  = [];
$success = null;

$username        = trim($_POST['acc-name'] ?? '');
$photo           = trim($_POST['acc-owner-photo'] ?? '');
$dob             = $_POST['acc-dob'] ?? null;
$memberSince     = $_POST['acc-member-since'] ?? null;
$email           = trim($_POST['acc-email'] ?? '');
$password        = $_POST['acc-password'] ?? '';
$passwordConfirm = $_POST['acc-password-confirm'] ?? '';

// validações básicas
if ($username === '' || $email === '' || $password === '') {
    $errors[] = 'Username, email and password are required.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}
if ($password !== $passwordConfirm) {
    $errors[] = 'Passwords do not match.';
}

if (empty($errors)) {
    // TODO: lógica real de criação de conta:
    // - verificar se email/username já existem
    // - hash da password
    // - inserir na BD

    /*
    require_once '../config/db.php';

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, photo, dob, member_since) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$username, $email, $hash, $photo, $dob, $memberSince]);
    */

    $success = 'Account created successfully! (dummy)';
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
}
if ($success) {
    $_SESSION['form_success'] = $success;
}

header('Location: all_collections.php');
exit;

?>
/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

