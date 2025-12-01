<?php
session_start();

// Simple login/logout handler using PDO from config/db.php
// POST to this script to log in (fields: email, password)
// GET with ?action=logout to log out

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // logout
    unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_photo']);
    session_regenerate_id(true);
    header('Location: ../SIE_03/all_collections.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../SIE_03/all_collections.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$errors = [];

if ($email === '' || $password === '') {
    $errors[] = 'Email and password are required.';
}

if (empty($errors)) {
    require_once __DIR__ . '/../config/db.php';
    $pdo = get_db();

    try {
        $stmt = $pdo->prepare('SELECT user_id, user_name, user_photo, password FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = 'Invalid email or password.';
        } else {
            // Verify password (stored as hash)
            if (password_verify($password, $user['password'])) {
                // Successful login
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['user_name'];
                $_SESSION['user_photo'] = $user['user_photo'];
                session_regenerate_id(true);
                header('Location: ../SIE_03/all_collections.php');
                exit;
            } else {
                $errors[] = 'Invalid email or password.';
            }
        }
    } catch (Exception $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
}

if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
}

header('Location: ../SIE_03/all_collections.php');
exit;

?>
