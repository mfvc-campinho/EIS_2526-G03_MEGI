<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? '';
$currentUser = $_SESSION['user']['id'] ?? null;

if ($action !== 'create' && !$currentUser) {
  flash_set('error', 'You need to be logged in.');
  header('Location: user_page.php');
  exit;
}

function redirect_success($message, $location)
{
  flash_set('success', $message);
  header('Location: ' . $location);
  exit;
}

function redirect_error($message, $location)
{
  flash_set('error', $message);
  header('Location: ' . $location);
  exit;
}

function handle_upload($field, $folder, $fallback = '')
{
  if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    return $fallback;
  }

  $file = $_FILES[$field];
  if ($file['error'] !== UPLOAD_ERR_OK) {
    redirect_error('Image upload failed.', 'users_form.php');
  }

  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
    redirect_error('Invalid image format.', 'users_form.php');
  }

  $dir = __DIR__ . '/../uploads/' . $folder;
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }

  $filename = uniqid('img_', true) . '.' . $ext;
  $target = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
  if (!move_uploaded_file($file['tmp_name'], $target)) {
    redirect_error('Could not save the image.', 'users_form.php');
  }

  return 'uploads/' . $folder . '/' . $filename;
}

function compute_age($dob)
{
  $dob = trim((string)$dob);
  if ($dob === '') return null;
  $ts = strtotime($dob);
  if ($ts === false) return null;
  $birth = new DateTime(date('Y-m-d', $ts));
  $today = new DateTime('today');
  $diff = $birth->diff($today);
  return (int)$diff->y;
}

if ($action === 'create') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $dob = $_POST['dob'] ?? null;
  $password = $_POST['password'] ?? '';
  $photoPath = handle_upload('photoFile', 'users');

  if ($name === '' || $email === '' || $password === '') {
    $mysqli->close();
    redirect_error('Please fill in all required fields.', 'user_create.php');
  }

  // Require valid DOB and age between 14 and 90
  if ($dob === null || trim((string)$dob) === '') {
    $mysqli->close();
    redirect_error('Date of birth is required.', 'user_create.php');
  }
  $age = compute_age($dob);
  if ($age === null || $age < 14 || $age > 90) {
    $mysqli->close();
    redirect_error('Age must be between 14 and 90 years.', 'user_create.php');
  }

  $stmt = $mysqli->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows > 0) {
    $stmt->close();
    $mysqli->close();
    redirect_error('This email is already registered.', 'user_create.php');
  }
  $stmt->close();

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $newId = uniqid('u_', true);
  $memberSince = date('Y-m-d H:i:s');

  $stmt = $mysqli->prepare('INSERT INTO users (user_id, user_name, email, user_photo, date_of_birth, member_since, password) VALUES (?, ?, ?, ?, ?, ?, ?)');
  $stmt->bind_param('sssssss', $newId, $name, $email, $photoPath, $dob, $memberSince, $hash);
  $ok = $stmt->execute();
  $stmt->close();

  if ($ok) {
    $_SESSION['user'] = [
      'id' => $newId,
      'name' => $name,
      'user_name' => $name,
      'email' => $email,
      'user_photo' => $photoPath,
    ];
    $mysqli->close();
    redirect_success('Account created successfully.', 'user_page.php?id=' . urlencode($newId));
  }

  $mysqli->close();
  redirect_error('Failed to create account.', 'user_create.php');
}

if ($action === 'update') {
  $stmt = $mysqli->prepare('SELECT user_photo FROM users WHERE user_id = ? LIMIT 1');
  $stmt->bind_param('s', $currentUser);
  $stmt->execute();
  $result = $stmt->get_result();
  $existing = $result->fetch_assoc();
  $stmt->close();

  if (!$existing) {
    $mysqli->close();
    redirect_error('User not found.', 'users_form.php');
  }

  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $dob = $_POST['dob'] ?? null;
  $password = $_POST['password'] ?? '';
  $oldPhoto = $existing['user_photo'] ?? '';
  $photoPath = handle_upload('photoFile', 'users', $oldPhoto);
  // If a new photo was uploaded, remove the previous file (only local paths).
  if ($photoPath && $oldPhoto && $photoPath !== $oldPhoto && !preg_match('#^https?://#i', $oldPhoto)) {
    $oldFile = __DIR__ . '/../' . ltrim($oldPhoto, '/');
    if (is_file($oldFile)) {
      @unlink($oldFile);
    }
  }

  if ($name === '' || $email === '') {
    $mysqli->close();
    redirect_error('Name and email are required.', 'users_form.php');
  }

  // Require valid DOB and age between 14 and 90 on update as well
  if ($dob === null || trim((string)$dob) === '') {
    $mysqli->close();
    redirect_error('Date of birth is required.', 'users_form.php');
  }
  $age = compute_age($dob);
  if ($age === null || $age < 14 || $age > 90) {
    $mysqli->close();
    redirect_error('Age must be between 14 and 90 years.', 'users_form.php');
  }

  if ($password !== '') {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare('UPDATE users SET user_name = ?, email = ?, user_photo = ?, date_of_birth = ?, password = ? WHERE user_id = ?');
    $stmt->bind_param('ssssss', $name, $email, $photoPath, $dob, $hash, $currentUser);
  } else {
    $stmt = $mysqli->prepare('UPDATE users SET user_name = ?, email = ?, user_photo = ?, date_of_birth = ? WHERE user_id = ?');
    $stmt->bind_param('sssss', $name, $email, $photoPath, $dob, $currentUser);
  }

  $ok = $stmt->execute();
  $stmt->close();

  $_SESSION['user']['user_name'] = $name;
  $_SESSION['user']['name'] = $name;
  $_SESSION['user']['email'] = $email;
  $_SESSION['user']['user_photo'] = $photoPath;
  $_SESSION['user']['photo'] = $photoPath;

  $mysqli->close();
  if ($ok) {
    redirect_success('Profile updated.', 'user_page.php?id=' . urlencode($currentUser));
  }

  redirect_error('Failed to update profile.', 'users_form.php');
}

$mysqli->close();
redirect_error('Invalid action.', 'users_form.php');
