<?php
// Handler para o formulário "Forgot Password"
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home_page.php');
    exit;
}

$email = trim($_POST['forgot-email'] ?? '');

// TODO: verificar se o email existe, gerar token, enviar email de reset, etc.

$_SESSION['message'] = 'If this email exists, a reset link will be sent. (Implementar envio de email.)';

header('Location: home_page.php');
exit;
