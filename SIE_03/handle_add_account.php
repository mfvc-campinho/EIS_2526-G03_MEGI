<?php
// Handler para o formulário "Create New Account"
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: home_page.php');
    exit;
}

$username       = trim($_POST['acc-name'] ?? '');
$photoUrl       = trim($_POST['acc-owner-photo'] ?? '');
$dob            = $_POST['acc-dob'] ?? null;
$memberSince    = $_POST['acc-member-since'] ?? null; // vem readonly do form
$email          = trim($_POST['acc-email'] ?? '');
$password       = $_POST['acc-password'] ?? '';
$passwordConfirm= $_POST['acc-password-confirm'] ?? '';

// TODO: validação (password == passwordConfirm, email válido, etc.)
// TODO: hash da password e gravação na base de dados

$_SESSION['message'] = 'Account form received successfully. (Implementar criação de conta.)';

header('Location: home_page.php');
exit;
