<?php
// Handler para o formulário "Add / Edit Item"
session_start();

error_reporting(E_ALL);
ini_set('display_errors', '1');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Este script só aceita pedidos POST.\n";
    echo "REQUEST_METHOD = " . $_SERVER['REQUEST_METHOD'] . "\n";
    exit;
}

// Ler campos do formulário
$itemId        = $_POST['item-id'] ?? null;
$name          = trim($_POST['item-name'] ?? '');
$priceRaw      = $_POST['item-price'] ?? null;
$collections   = $_POST['item-collections'] ?? [];
$importance    = trim($_POST['item-importance'] ?? '');
$weightRaw     = $_POST['item-weight'] ?? null;
$acqDate       = trim($_POST['item-date'] ?? '');
$image         = trim($_POST['item-image'] ?? '');

// Garantir que collections é sempre array
if (!is_array($collections)) {
    $collections = [$collections];
}

// Sanitizar/converter alguns campos
$price  = is_numeric($priceRaw)  ? (float) $priceRaw  : null;
$weight = is_numeric($weightRaw) ? (float) $weightRaw : null;

// Validação muito básica
$errors = [];

if ($name === '') {
    $errors[] = 'O campo "Name" é obrigatório.';
}
if ($price === null) {
    $errors[] = 'O campo "Price" tem de ser um número válido.';
}
if (empty($collections)) {
    $errors[] = 'Tens de selecionar pelo menos uma coleção em "Collections".';
}

// 👉 SE HOUVER ERROS, MOSTRAS HTML NORMAL:
if ($errors) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Item handler result</title>
    </head>
    <body>
        <h1>❌ Erros ao submeter o item</h1>
        <ul>
            <?php foreach ($errors as $msg): ?>
                <li><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
        <p><a href="javascript:history.back()">⬅ Voltar atrás e corrigir</a></p>
    </body>
    </html>
    <?php
    exit;
}

// 👉 SE NÃO HÁ ERROS: REDIRECIONAR PARA A COLEÇÃO ESPECÍFICA

// aqui assumes que a primeira coleção escolhida é a "principal"
$primaryCollectionId = $collections[0] ?? null;

if ($primaryCollectionId) {
    // se a tua página espera ?id=ALGUMA_COISA
    header('Location: specific_collection.php?id=' . urlencode($primaryCollectionId));
    exit;
}

// fallback: se por algum motivo não tens collection id, mostra algo simples
header('Content-Type: text/plain; charset=utf-8');
echo "Item recebido, mas não foi possível determinar a coleção para redirecionar.\n";
print_r($_POST);
exit;
