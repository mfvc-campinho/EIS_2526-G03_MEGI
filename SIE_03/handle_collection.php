<?php
// Handler para o formulário "New Collection"
session_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Este script só aceita POST.\n";
    echo "REQUEST_METHOD = " . $_SERVER['REQUEST_METHOD'];
    exit;
}

// Aqui podes ler os dados do POST
$collectionId   = $_POST['collection-id'] ?? null;
$name           = trim($_POST['col-name'] ?? '');
$summary        = trim($_POST['col-summary'] ?? '');
$description    = trim($_POST['col-description'] ?? '');
$image          = trim($_POST['col-image'] ?? '');
$type           = trim($_POST['col-type'] ?? '');


echo "<h1>HELLO FROM handle_collection.php</h1>";
echo "<pre>";
print_r($_POST);
echo "</pre>";


exit; // 👈 IMPORTANTE: para não continuar para mais nada
