<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$action = $_POST['action'] ?? null;
$currentUser = $_SESSION['user']['id'] ?? null;

// Só obriga a estar autenticado para ações que não sejam "create"
if ($action !== 'create' && !$currentUser) {
  flash_set('error', 'Precisa de iniciar sessão.');
  header('Location: user_page.php');
  exit;
}



function redirect_success($msg)
{
  flash_set('success', $msg);
  header('Location: user_page.php');
  exit;
}

function redirect_error($msg)
{
  flash_set('error', $msg);
  header('Location: user_page.php');
  exit;
}

function handle_upload($field, $folder, $keep = '')
{
  if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    return $keep;
  }
  $file = $_FILES[$field];
  if ($file['error'] !== UPLOAD_ERR_OK) {
    redirect_error('Falha no upload da imagem.');
  }
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
    redirect_error('Formato de imagem inválido.');
  }
  $dir = __DIR__ . '/../uploads/' . $folder;
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }
  $filename = uniqid('img_') . '.' . $ext;
  $target = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
  if (!move_uploaded_file($file['tmp_name'], $target)) {
    redirect_error('Could not save the image.');
  }
  return 'uploads/' . $folder . '/' . $filename;
}

if ($action === 'create') {

  $name     = trim($_POST['name']  ?? '');
  $email    = trim($_POST['email'] ?? '');
  $dob      = $_POST['dob']        ?? null;
  $password = $_POST['password']   ?? '';
  $photoPath = handle_upload('photoFile', 'users', '');

  if ($name === '' || $email === '' || $password === '') {
    $mysqli->close();
    redirect_error('Preencha todos os campos obrigatórios.');
  }

  // Verificar se já existe email
  $stmt = $mysqli->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows > 0) {
    $stmt->close();
    $mysqli->close();
    redirect_error('Já existe uma conta com este email.');
  }
  $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);

  // ID para a primary key
  $newId = uniqid('u_', true);

  // Data/hora em que a conta é criada
  $memberSince = date('Y-m-d H:i:s');

  // Agora incluímos também a coluna member_since
  $stmt = $mysqli->prepare(
    'INSERT INTO users (user_id, user_name, email, user_photo, date_of_birth, member_since, password)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
  );
  $stmt->bind_param('sssssss', $newId, $name, $email, $photoPath, $dob, $memberSince, $hash);
  $ok = $stmt->execute();
  $stmt->close();

  if ($ok) {
    $_SESSION['user'] = [
      'id'         => $newId,
      'name'       => $name,
      'user_name'  => $name,
      'email'      => $email,
      'user_photo' => $photoPath,
    ];

    $mysqli->close();
    redirect_success('Conta criada com sucesso.');
  }





if ($action === 'update') {
  // fetch existing photo
  $chk = $mysqli->prepare('SELECT user_photo FROM users WHERE user_id = ? LIMIT 1');
  $chk->bind_param('s', $currentUser);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();
  if (!$row) {
    $mysqli->close();
    redirect_error('Utilizador não encontrado.');
  }
  $existingPhoto = $row['user_photo'] ?? '';

  $name = $_POST['name'] ?? '';
  $email = $_POST['email'] ?? '';
  $dob = $_POST['dob'] ?? null;
  $password = $_POST['password'] ?? null;
  $photoPath = handle_upload('photoFile', 'users', $existingPhoto);

  if ($password) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare('UPDATE users SET user_name=?, email=?, user_photo=?, date_of_birth=?, password=? WHERE user_id=?');
    $stmt->bind_param('ssssss', $name, $email, $photoPath, $dob, $hash, $currentUser);
  } else {
    $stmt = $mysqli->prepare('UPDATE users SET user_name=?, email=?, user_photo=?, date_of_birth=? WHERE user_id=?');
    $stmt->bind_param('sssss', $name, $email, $photoPath, $dob, $currentUser);
  }
  $ok = $stmt->execute();
  $stmt->close();
  // refresh session user photo/name
  $_SESSION['user']['user_name'] = $name;
  $_SESSION['user']['email'] = $email;
  $_SESSION['user']['user_photo'] = $photoPath;
  $mysqli->close();
  if ($ok) redirect_success('Perfil atualizado.');
  redirect_error('Falha ao atualizar perfil.');
}



$mysqli->close();
redirect_error('Ação inválida.');

}
