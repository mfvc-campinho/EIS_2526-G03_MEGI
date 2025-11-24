<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: all_collections.php');
    exit;
}

$errors  = [];
$success = null;

$email = trim($_POST['forgot-email'] ?? '');

if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format.';
}

if (empty($errors)) {
    // TODO: aqui entraria a lógica real de reset de password:
    // - gerar token
    // - guardar token na BD
    // - enviar email com link de reset

    $success = 'If the email exists in our system, a reset link has been sent.';
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

