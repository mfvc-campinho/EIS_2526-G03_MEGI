<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
// Use sessions for authentication when updating profiles
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $mysqli->prepare('SELECT user_id,user_name,user_photo,date_of_birth,email,member_since FROM users WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if ($row) {
      echo json_encode(['id' => $row['user_id'], 'user_name' => $row['user_name'], 'user_photo' => $row['user_photo'], 'date_of_birth' => $row['date_of_birth'], 'email' => $row['email'], 'member_since' => $row['member_since']]);
      exit;
    }
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
  }

  $res = $mysqli->query('SELECT user_id,user_name,user_photo,date_of_birth,email,member_since FROM users');
  $out = [];
  while ($row = $res->fetch_assoc()) {
    $out[] = ['id' => $row['user_id'], 'user_name' => $row['user_name'], 'user_photo' => $row['user_photo'], 'date_of_birth' => $row['date_of_birth'], 'email' => $row['email'], 'member_since' => $row['member_since']];
  }
  echo json_encode($out);
  exit;
}

if ($method === 'POST') {
  $action = $_POST['action'] ?? null;
  if ($action === 'create') {
    $id = trim($_POST['id'] ?? '') ?: uniqid('user-');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $photo = trim($_POST['photo'] ?? '') ?: null;
    $dob = trim($_POST['dob'] ?? '') ?: null;
    $member = trim($_POST['member_since'] ?? '') ?: null;

    // Basic validation
    if ($id === '' || $name === '' || $email === '' || $password === '') {
      http_response_code(400);
      echo json_encode(['error' => 'missing required fields']);
      exit;
    }

    // Prevent duplicate email or user_id
    $chk = $mysqli->prepare('SELECT user_id FROM users WHERE email = ? OR user_id = ? LIMIT 1');
    if ($chk) {
      $chk->bind_param('ss', $email, $id);
      $chk->execute();
      $res = $chk->get_result();
      if ($res && $res->fetch_assoc()) {
        http_response_code(409);
        echo json_encode(['error' => 'user exists']);
        $chk->close();
        exit;
      }
      $chk->close();
    }

    $stmt = $mysqli->prepare('INSERT INTO users (user_id,user_name,user_photo,date_of_birth,email,password,member_since) VALUES (?,?,?,?,?,?,?)');
    $stmt->bind_param('sssssss', $id, $name, $photo, $dob, $email, $password, $member);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
      echo json_encode(['success' => true, 'id' => $id]);
    } else {
      http_response_code(500);
      echo json_encode(['error' => 'db error']);
    }
    exit;
  } elseif ($action === 'update') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
      http_response_code(400);
      echo json_encode(['error' => 'missing id']);
      exit;
    }
    // Require logged in user and ownership
    if (empty($_SESSION['user']) || empty($_SESSION['user']['id']) || $_SESSION['user']['id'] !== $id) {
      http_response_code(401);
      echo json_encode(['error' => 'not authorized']);
      exit;
    }
    $name = $_POST['name'] ?? null;
    $photo = $_POST['photo'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $member = $_POST['member_since'] ?? null;
    $stmt = $mysqli->prepare('UPDATE users SET user_name=?, user_photo=?, date_of_birth=?, member_since=? WHERE user_id=?');
    if (!$stmt) {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => 'prepare failed', 'mysqli_error' => $mysqli->error]);
      exit;
    }
    $stmt->bind_param('sssss', $name, $photo, $dob, $member, $id);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmtErr = $stmt->error;
    $stmt->close();
    if ($ok) {
      // Refresh session user info so auth.php reflects changes
      $_SESSION['user']['name'] = $name;
      $_SESSION['user']['photo'] = $photo;
      echo json_encode(['success' => true, 'user' => $_SESSION['user'], 'affected_rows' => $affected, 'stmt_error' => $stmtErr]);
    } else {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => 'db error', 'mysqli_error' => $mysqli->error, 'stmt_error' => $stmtErr]);
    }
    exit;
  } elseif ($action === 'delete') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
      http_response_code(400);
      echo json_encode(['error' => 'missing id']);
      exit;
    }
    $stmt = $mysqli->prepare('DELETE FROM users WHERE user_id = ?');
    $stmt->bind_param('s', $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
    exit;
  }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
