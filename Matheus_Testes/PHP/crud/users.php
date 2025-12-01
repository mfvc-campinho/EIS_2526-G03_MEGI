<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

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
    $id = $_POST['id'] ?? uniqid('user-');
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $photo = $_POST['photo'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $member = $_POST['member_since'] ?? null;
    $stmt = $mysqli->prepare('INSERT INTO users (user_id,user_name,user_photo,date_of_birth,email,password,member_since) VALUES (?,?,?,?,?,?,?)');
    $stmt->bind_param('sssssss', $id, $name, $photo, $dob, $email, $password, $member);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok, 'id' => $id]);
    exit;
  } elseif ($action === 'update') {
    $id = $_POST['id'] ?? null;
    if (!$id) {
      http_response_code(400);
      echo json_encode(['error' => 'missing id']);
      exit;
    }
    $name = $_POST['name'] ?? null;
    $photo = $_POST['photo'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $member = $_POST['member_since'] ?? null;
    $stmt = $mysqli->prepare('UPDATE users SET user_name=?, user_photo=?, date_of_birth=?, member_since=? WHERE user_id=?');
    $stmt->bind_param('sssss', $name, $photo, $dob, $member, $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
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
