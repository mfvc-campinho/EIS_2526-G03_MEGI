<?php
session_start();
require_once __DIR__ . '/../includes/flash.php';
require_once __DIR__ . '/../config/db.php';

$action      = $_POST['action'] ?? null;
$currentUser = $_SESSION['user']['id'] ?? null;

if (!$currentUser) {
  flash_set('error', 'Precisa de iniciar sessão.');
  header('Location: home_page.php');
  exit;
}

// helper: confirmar se o user é dono de uma coleção
function user_owns_collection($mysqli, $collectionId, $userId) {
  $chk = $mysqli->prepare('SELECT user_id FROM collections WHERE collection_id = ? LIMIT 1');
  $chk->bind_param('s', $collectionId);
  $chk->execute();
  $res = $chk->get_result();
  $row = $res->fetch_assoc();
  $chk->close();

  return $row && ($row['user_id'] ?? null) === $userId;
}

// helper: guardar links item–coleções (apaga antigos e grava novos)
function replace_item_links($mysqli, $itemId, array $collectionIds) {
  // apagar links antigos
  $del = $mysqli->prepare('DELETE FROM collection_items WHERE item_id = ?');
  $del->bind_param('s', $itemId);
  $del->execute();
  $del->close();

  if (!$collectionIds) {
    return;
  }

  // inserir novos links
  $ins = $mysqli->prepare('INSERT INTO collection_items (collection_id,item_id) VALUES (?,?)');
  foreach ($collectionIds as $cid) {
    $cid = (string) $cid;
    $ins->bind_param('ss', $cid, $itemId);
    $ins->execute();
  }
  $ins->close();
}

// normalizar collectionIds vindos do formulário
$collectionIds = $_POST['collectionIds'] ?? [];
$collectionIds = is_array($collectionIds) ? array_values(array_unique(array_filter($collectionIds))) : [];

if ($action === 'create') {
  if (!$collectionIds) {
    $mysqli->close();
    redirect_error('Selecione pelo menos uma coleção.');
  }

  // todas as coleções escolhidas têm de pertencer ao utilizador
  foreach ($collectionIds as $cid) {
    if (!user_owns_collection($mysqli, $cid, $currentUser)) {
      $mysqli->close();
      redirect_error('Só pode criar itens nas suas coleções.');
    }
  }

  $primaryCol  = $collectionIds[0];
  $id          = uniqid('item-');
  $name        = $_POST['name']            ?? '';
  $importance  = $_POST['importance']      ?? '';
  $weight      = $_POST['weight']          ?? '';
  $price       = $_POST['price']           ?? '';
  $acq         = $_POST['acquisitionDate'] ?? null;
  $image       = handle_upload('imageFile', 'items', '');

  // INSERT no items
  $stmt = $mysqli->prepare(
    'INSERT INTO items (item_id,name,importance,weight,price,acquisition_date,created_at,collection_id,image) 
     VALUES (?,?,?,?,?,?,NOW(),?,?)'
  );
  $stmt->bind_param('ssssssss', $id, $name, $importance, $weight, $price, $acq, $primaryCol, $image);
  $ok = $stmt->execute();
  $stmt->close();

  // links item–coleções
  replace_item_links($mysqli, $id, $collectionIds);

  $mysqli->close();
  if ($ok) {
    redirect_success('Item criado com sucesso.');
  }
  redirect_error('Falha ao criar item.');
}

if ($action === 'update') {
  $id = $_POST['id'] ?? null;
  if (!$id) {
    $mysqli->close();
    redirect_error('ID em falta.');
  }
  if (!$collectionIds) {
    $mysqli->close();
    redirect_error('Selecione pelo menos uma coleção.');
  }

  // buscar item atual (para verificar owner e imagem)
  $chkItem = $mysqli->prepare('SELECT collection_id,image FROM items WHERE item_id = ? LIMIT 1');
  $chkItem->bind_param('s', $id);
  $chkItem->execute();
  $resItem  = $chkItem->get_result();
  $existing = $resItem->fetch_assoc();
  $chkItem->close();

  if (!$existing) {
    $mysqli->close();
    redirect_error('Item não encontrado.');
  }

  // todas as coleções selecionadas têm de ser do utilizador
  foreach ($collectionIds as $cid) {
    if (!user_owns_collection($mysqli, $cid, $currentUser)) {
      $mysqli->close();
      redirect_error('Só pode associar o item às suas coleções.');
    }
  }

  $name       = $_POST['name']            ?? '';
  $importance = $_POST['importance']      ?? '';
  $weight     = $_POST['weight']          ?? '';
  $price      = $_POST['price']           ?? '';
  $acq        = $_POST['acquisitionDate'] ?? null;
  $image      = handle_upload('imageFile', 'items', $existing['image'] ?? '');
  $primaryCol = $collectionIds[0];

  // UPDATE do item
  $stmt = $mysqli->prepare(
    'UPDATE items 
        SET name = ?, 
            importance = ?, 
            weight = ?, 
            price = ?, 
            acquisition_date = ?, 
            collection_id = ?, 
            image = ? 
      WHERE item_id = ?'
  );
  $stmt->bind_param('ssssssss', $name, $importance, $weight, $price, $acq, $primaryCol, $image, $id);
  $ok = $stmt->execute();
  $stmt->close();

  // atualizar links item–coleções
  replace_item_links($mysqli, $id, $collectionIds);

  $mysqli->close();
  if ($ok) {
    redirect_success('Item atualizado.');
  }
  redirect_error('Falha ao atualizar item.');
}

if ($action === 'delete') {
  $id = $_POST['id'] ?? null;
  if (!$id) {
    $mysqli->close();
    redirect_error('ID em falta.');
  }

  // verificar se o utilizador é dono de pelo menos uma coleção onde o item está
  $owns = false;

  // tentar pelas collectionIds enviadas (se vierem)
  if ($collectionIds) {
    foreach ($collectionIds as $cid) {
      if (user_owns_collection($mysqli, $cid, $currentUser)) {
        $owns = true;
        break;
      }
    }
  }

  // caso não venham collectionIds, ver na BD qual a coleção "primária"
  if (!$owns) {
    $chk = $mysqli->prepare('SELECT collection_id FROM items WHERE item_id = ? LIMIT 1');
    $chk->bind_param('s', $id);
    $chk->execute();
    $res = $chk->get_result();
    $row = $res->fetch_assoc();
    $chk->close();

    if (!$row || !user_owns_collection($mysqli, $row['collection_id'], $currentUser)) {
      $mysqli->close();
      redirect_error('Sem permissão para apagar este item.');
    }
  }

  // apagar o item
  $stmt = $mysqli->prepare('DELETE FROM items WHERE item_id = ?');
  $stmt->bind_param('s', $id);
  $ok = $stmt->execute();
  $stmt->close();

  // apagar links item–coleções
  replace_item_links($mysqli, $id, []);

  $mysqli->close();
  if ($ok) {
    redirect_success('Item apagado.');
  }
  redirect_error('Falha ao apagar item.');
}

// se chegou aqui, ação inválida
$mysqli->close();
redirect_error('Ação inválida.');

// -----------------------------------------------------------------
// helpers adicionais
// -----------------------------------------------------------------

function redirect_success($msg) {
  flash_set('success', $msg);
  header('Location: home_page.php');
  exit;
}

function redirect_error($msg) {
  flash_set('error', $msg);
  header('Location: home_page.php');
  exit;
}

function handle_upload($field, $folder, $keep = '') {
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
    return $keep;
  }

  if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
    return $keep;
  }

  $tmpName = $_FILES[$field]['tmp_name'];
  $name    = basename($_FILES[$field]['name']);

  $safeName = uniqid($folder . '-') . '-' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name);
  $destRel  = 'uploads/' . $folder . '/' . $safeName;
  $destAbs  = __DIR__ . '/../../' . $destRel;

  @mkdir(dirname($destAbs), 0777, true);

  if (!move_uploaded_file($tmpName, $destAbs)) {
    return $keep;
  }

  return $destRel;
}

// helper para buscar coleções de um item (se precisares noutro lado)
function fetch_item_collections($mysqli, $itemId) {
  $out  = [];
  $stmt = $mysqli->prepare('SELECT collection_id FROM collection_items WHERE item_id = ?');
  $stmt->bind_param('s', $itemId);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $out[] = $row['collection_id'];
  }
  $stmt->close();
  return $out;
}
