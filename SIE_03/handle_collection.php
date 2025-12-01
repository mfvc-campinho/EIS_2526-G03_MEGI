<?php
// Handler para o formulário "New Collection"
session_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "This endpoint accepts POST only.";
    exit;
}

// Read POST data
$collectionId   = trim($_POST['collection-id'] ?? '');
$name           = trim($_POST['col-name'] ?? '');
$summary        = trim($_POST['col-summary'] ?? '');
$description    = trim($_POST['col-description'] ?? '');
$image          = trim($_POST['col-image'] ?? '');
$type           = trim($_POST['col-type'] ?? '');

$errors = [];
if ($name === '') {
    $errors[] = 'Collection name is required.';
}

require_once __DIR__ . '/../config/db.php';
$pdo = get_db();

try {
    $userId = $_SESSION['user_id'] ?? null;

    if ($collectionId === '') {
        // CREATE: generate unique collection_id (slug)
        $base = preg_replace('/[^a-z0-9_\-]/', '', strtolower(str_replace(' ', '_', $name)));
        if ($base === '') {
            $base = 'col_' . time();
        }
        $candidate = $base;
        $i = 1;
        $check = $pdo->prepare('SELECT 1 FROM collections WHERE collection_id = ? LIMIT 1');
        while (true) {
            $check->execute([$candidate]);
            if (!$check->fetch()) {
                break;
            }
            $candidate = $base . '_' . $i;
            $i++;
        }

        $collectionId = $candidate;

        $insert = $pdo->prepare('INSERT INTO collections (collection_id, name, type, cover_image, summary, description, created_at, user_id) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)');
        $insert->execute([$collectionId, $name, $type ?: null, $image ?: null, $summary ?: null, $description ?: null, $userId]);

        $_SESSION['form_success'] = 'Collection created successfully.';
    } else {
        // UPDATE
        $update = $pdo->prepare('UPDATE collections SET name = ?, type = ?, cover_image = ?, summary = ?, description = ? WHERE collection_id = ?');
        $update->execute([$name, $type ?: null, $image ?: null, $summary ?: null, $description ?: null, $collectionId]);

        $_SESSION['form_success'] = 'Collection updated successfully.';
    }
} catch (Exception $e) {
    $_SESSION['form_errors'] = ['Database error: ' . $e->getMessage()];
}

// Redirect back to collections page
header('Location: all_collections.php');
exit;
