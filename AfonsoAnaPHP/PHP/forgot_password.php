<?php
// public_html/PHP/forgot_password.php
session_start();
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../HTML/home_page.php');
}

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    $_SESSION['error'] = 'Email is required.';
    redirect('../HTML/home_page.php');
}

// Aqui normalmente gerarias um token e enviavas email.
// Por agora vamos só simular a ação.
$_SESSION['success'] = 'If that email exists, a reset link has been sent.';
redirect('../HTML/home_page.php');
