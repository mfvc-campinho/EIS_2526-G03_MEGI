<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
  // Login
  $email = isset($_POST['email']) ? trim($_POST['email']) : null;
  $password = isset($_POST['password']) ? $_POST['password'] : null;
  if (!$email || !$password) {
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
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
  }

  // Note: passwords in the provided database are plain text. In production, use password_hash.
  if ($password !== $user['password']) {
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

  echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
  exit;
}

if ($method === 'GET') {
  // GET /auth.php?action=logout
  $action = isset($_GET['action']) ? $_GET['action'] : null;
  if ($action === 'logout') {
    session_unset();
    session_destroy();
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
