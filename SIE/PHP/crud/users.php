<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';
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

    if ($id === '' || $name === '' || $email === '' || $password === '') {
      http_response_code(400);
      echo json_encode(['error' => 'missing required fields']);
      exit;
    }

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

    // Hash the password before storing
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare('INSERT INTO users (user_id,user_name,user_photo,date_of_birth,email,password,member_since) VALUES (?,?,?,?,?,?,?)');
    $stmt->bind_param('sssssss', $id, $name, $photo, $dob, $email, $hash, $member);
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

    // Build dynamic update to avoid overwriting fields with empty values
    $fields = [];
    $types = '';
    $params = [];

    if (array_key_exists('name', $_POST)) {
      $fields[] = 'user_name = ?';
      $types .= 's';
      $params[] = $_POST['name'];
    }
    if (array_key_exists('photo', $_POST)) {
      $fields[] = 'user_photo = ?';
      $types .= 's';
      $params[] = $_POST['photo'];
    }
    if (array_key_exists('dob', $_POST)) {
      $fields[] = 'date_of_birth = ?';
      $types .= 's';
      $params[] = ($_POST['dob'] !== '') ? $_POST['dob'] : null;
    }
    if (array_key_exists('member_since', $_POST) && $_POST['member_since'] !== '') {
      $ms = trim($_POST['member_since']);
      if (preg_match('/^\d{4}$/', $ms) && intval($ms) >= 1900 && intval($ms) <= 2100) {
        $fields[] = 'member_since = ?';
        $types .= 's';
        $params[] = $ms;
      }
    }
    if (array_key_exists('email', $_POST)) {
      $emailVal = trim($_POST['email']);
      if ($emailVal !== '') {
        if (!filter_var($emailVal, FILTER_VALIDATE_EMAIL)) {
          http_response_code(400);
          echo json_encode(['success' => false, 'error' => 'invalid email']);
          exit;
        }
        $chk = $mysqli->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1');
        if ($chk) {
          $chk->bind_param('ss', $emailVal, $id);
          $chk->execute();
          $res = $chk->get_result();
          if ($res && $res->fetch_assoc()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'email exists']);
            $chk->close();
            exit;
          }
          $chk->close();
        }
        $fields[] = 'email = ?';
        $types .= 's';
        $params[] = $emailVal;
      }
    }

    // Allow password updates: hash new password when provided
    if (array_key_exists('password', $_POST) && $_POST['password'] !== '') {
      $fields[] = 'password = ?';
      $types .= 's';
      $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    if (empty($fields)) {
      echo json_encode(['success' => true, 'message' => 'no changes']);
      exit;
    }

    $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE user_id = ?';
    $types .= 's';
    $params[] = $id;
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => 'prepare failed', 'mysqli_error' => $mysqli->error, 'sql' => $sql]);
      exit;
    }
    // bind params
    $bindNames = [];
    $bindNames[] = $types;
    for ($i = 0; $i < count($params); $i++) $bindNames[] = &$params[$i];
    call_user_func_array([$stmt, 'bind_param'], $bindNames);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmtErr = $stmt->error;
    $stmt->close();
    if ($ok) {
      if (isset($_POST['name'])) $_SESSION['user']['name'] = $_POST['name'];
      if (isset($_POST['photo'])) $_SESSION['user']['photo'] = $_POST['photo'];
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
