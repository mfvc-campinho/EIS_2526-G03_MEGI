<?php
// public_html/PHP/update_profile.php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../HTML/user_page.php');
}

// Verificar se o utilizador está autenticado
if (empty($_SESSION['user_id'])) {
    $_SESSION['error'] = 'You must be logged in to update your profile.';
    redirect('../HTML/user_page.php');
}

$userId = (int) $_SESSION['user_id'];

// Ler dados do formulário
$username  = trim($_POST['user-form-name']  ?? '');
$email     = trim($_POST['user-form-email'] ?? '');
$dob       = $_POST['user-form-dob']        ?? null;
$photoUrl  = trim($_POST['user-form-photo'] ?? '');

if ($username === '' || $email === '') {
    $_SESSION['error'] = 'Username and email are required.';
    redirect('../HTML/user_page.php');
}

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Invalid email address.';
    redirect('../HTML/user_page.php');
}

try {
    // 1) Verificar se o email já está a ser usado por outro utilizador
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
    $stmt->execute([
        ':email' => $email,
        ':id'    => $userId,
    ]);

    if ($stmt->fetch()) {
        $_SESSION['error'] = 'That email is already in use by another account.';
        redirect('../HTML/user_page.php');
    }

    // 2) Atualizar dados do utilizador
    $stmt = $pdo->prepare('
        UPDATE users
           SET username  = :username,
               email     = :email,
               dob       = :dob,
               photo_url = :photo_url
         WHERE id = :id
        LIMIT 1
    ');

    $stmt->execute([
        ':username'  => $username,
        ':email'     => $email,
        ':dob'       => $dob ?: null,
        ':photo_url' => $photoUrl !== '' ? $photoUrl : null,
        ':id'        => $userId,
    ]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = 'Profile updated successfully.';

        // Opcional: atualizar dados na sessão (se os guardares aí)
        $_SESSION['username'] = $username;
        $_SESSION['email']    = $email;
    } else {
        // Nenhuma linha mudou – dados iguais
        $_SESSION['success'] = 'No changes detected in your profile.';
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error while updating profile: ' . $e->getMessage();
}

// Voltar à página de perfil
redirect('../HTML/user_page.php');
