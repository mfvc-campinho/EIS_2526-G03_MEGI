<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/db.php';

// Ensure a logged-in user
if (empty($_SESSION['user']) || empty($_SESSION['user']['id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'error' => 'Not authenticated']);
  exit;
}

$currentUser = $_SESSION['user']['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!$action) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Missing action']);
  exit;
}

try {
  if ($action === 'follow') {
    $target = $_POST['targetId'] ?? null;
    if (!$target) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Missing targetId']);
      exit;
    }
    if ($target === $currentUser) {
      echo json_encode(['success' => false, 'error' => 'Cannot follow yourself']);
      exit;
    }
    // Check exists
    $stmt = $mysqli->prepare('SELECT 1 FROM user_followers WHERE follower_id = ? AND following_id = ? LIMIT 1');
    $stmt->bind_param('ss', $currentUser, $target);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
      $stmt->close();
      $ins = $mysqli->prepare('INSERT INTO user_followers (follower_id, following_id) VALUES (?, ?)');
      $ins->bind_param('ss', $currentUser, $target);
      if (!$ins->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Insert failed']);
        exit;
      }
      $ins->close();
    } else {
      $stmt->close();
    }
    // return follower count
    $countStmt = $mysqli->prepare('SELECT COUNT(*) FROM user_followers WHERE following_id = ?');
    $countStmt->bind_param('s', $target);
    $countStmt->execute();
    $countStmt->bind_result($followersCount);
    $countStmt->fetch();
    $countStmt->close();
    echo json_encode(['success' => true, 'following' => true, 'followersCount' => intval($followersCount)]);
    exit;
  }

  if ($action === 'unfollow') {
    $target = $_POST['targetId'] ?? null;
    if (!$target) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Missing targetId']);
      exit;
    }
    $del = $mysqli->prepare('DELETE FROM user_followers WHERE follower_id = ? AND following_id = ?');
    $del->bind_param('ss', $currentUser, $target);
    if (!$del->execute()) {
      http_response_code(500);
      echo json_encode(['success' => false, 'error' => 'Delete failed']);
      exit;
    }
    $del->close();
    $countStmt = $mysqli->prepare('SELECT COUNT(*) FROM user_followers WHERE following_id = ?');
    $countStmt->bind_param('s', $target);
    $countStmt->execute();
    $countStmt->bind_result($followersCount);
    $countStmt->fetch();
    $countStmt->close();
    echo json_encode(['success' => true, 'following' => false, 'followersCount' => intval($followersCount)]);
    exit;
  }

  if ($action === 'getFollowing') {
    $who = $_GET['followerId'] ?? $currentUser;
    $stmt = $mysqli->prepare('SELECT following_id FROM user_followers WHERE follower_id = ?');
    $stmt->bind_param('s', $who);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r['following_id'];
    echo json_encode(['success' => true, 'following' => $rows]);
    exit;
  }

  if ($action === 'getFollowers') {
    $target = $_GET['targetId'] ?? null;
    if (!$target) {
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Missing targetId']);
      exit;
    }
    $stmt = $mysqli->prepare('SELECT follower_id FROM user_followers WHERE following_id = ?');
    $stmt->bind_param('s', $target);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r['follower_id'];
    echo json_encode(['success' => true, 'followers' => $rows, 'count' => count($rows)]);
    exit;
  }

  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Unknown action']);
  exit;
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Server error', 'details' => $e->getMessage()]);
  exit;
}
