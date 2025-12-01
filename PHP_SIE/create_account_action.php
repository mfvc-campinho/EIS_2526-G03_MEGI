<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../SIE_03/all_collections.php');
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

// Basic validation
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
    require_once __DIR__ . '/../config/db.php';
    $pdo = get_db();

    try {
        // Check if email already exists
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }

        // Generate a simple user_id from username (slug)
        $baseId = preg_replace('/[^a-z0-9_]/', '', strtolower(str_replace(' ', '_', $username)));
        if ($baseId === '') {
            $baseId = 'user_' . time();
        }
        $userId = $baseId;
        $i = 1;
        $check = $pdo->prepare('SELECT 1 FROM users WHERE user_id = ? LIMIT 1');
        while (true) {
            $check->execute([$userId]);
            if (!$check->fetch()) {
                break;
            }
            $userId = $baseId . '_' . $i;
            $i++;
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare('INSERT INTO users (user_id, user_name, user_photo, date_of_birth, email, password, member_since) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$userId, $username, $photo, $dob, $email, $hash, $memberSince]);

            $success = 'Account created successfully! You can now log in.';
        }
    } catch (Exception $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
}
if ($success) {
    $_SESSION['form_success'] = $success;
}

header('Location: ../SIE_03/all_collections.php');
exit;
