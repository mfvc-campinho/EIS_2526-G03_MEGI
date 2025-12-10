<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/flash.php';

$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? null;
if (!$redirect) {
  header('Content-Type: application/json; charset=utf-8');
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  // Login
  $email = isset($_POST['email']) ? trim($_POST['email']) : null;
  $password = isset($_POST['password']) ? $_POST['password'] : null;
  if (!$email || !$password) {
    if ($redirect) {
      flash_set('error', 'Missing email or password');
      header('Location: ' . $redirect);
      exit;
    }
    http_response_code(400);
    echo json_encode(['error' => 'Missing email or password']);
    exit;
  }

  $stmt = $mysqli->prepare('SELECT user_id,user_name,user_photo,email,password,member_since FROM users WHERE email = ? LIMIT 1');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $res = $stmt->get_result();
  $user = $res->fetch_assoc();
  $stmt->close();

  if (!$user) {
    if ($redirect) {
      flash_set('error', 'Invalid credentials');
      header('Location: ' . $redirect);
      exit;
    }
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
  }

  // Verify password using password_verify().
  $verified = false;
  if (isset($user['password']) && $user['password'] !== '') {
    if (password_verify($password, $user['password'])) {
      $verified = true;
    } else {
      // Fallback for existing plaintext-stored passwords: if direct match, rehash and update DB.
      if ($password === $user['password']) {
        $verified = true;
        // Re-hash and persist the password securely
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $up = $mysqli->prepare('UPDATE users SET password = ? WHERE user_id = ?');
        if ($up) {
          $up->bind_param('ss', $newHash, $user['user_id']);
          $up->execute();
          $up->close();
        }
      }
    }
  }

  if (! $verified) {
    if ($redirect) {
      flash_set('error', 'Invalid credentials');
      header('Location: ' . $redirect);
      exit;
    }
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
  }

  // Set session
  $_SESSION['user'] = [
    'id' => $user['user_id'],
    'name' => $user['user_name'],
    'photo' => $user['user_photo'],
    'email' => $user['email'],
    'member_since' => $user['member_since']
  ];

  if ($redirect) {
    flash_set('success', 'Logged in successfully.');
    header('Location: ' . $redirect);
    exit;
  }

  echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
  exit;
}

if ($method === 'GET') {
  // GET /auth.php?action=logout
  $action = isset($_GET['action']) ? $_GET['action'] : null;
  if ($action === 'logout') {
    $_SESSION = [];
    flash_set('success', 'Signed out successfully.');
    session_regenerate_id(true);
    if ($redirect) {
      header('Location: ' . $redirect);
      exit;
    }
    echo json_encode(['success' => true]);
    exit;
  }

  // Return current session user
  if (isset($_SESSION['user'])) {
    echo json_encode(['user' => $_SESSION['user']]);
  } else {
    echo json_encode(['user' => null]);
  }
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
