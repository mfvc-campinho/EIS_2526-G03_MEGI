<?php
// public_html/PHP/test_db.php

// Mostrar todos os erros e avisos
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug test_db.php</h2>";

// 1) Confirmar que o ficheiro está a ser executado
echo "<p>Passo 1: PHP está a correr.</p>";

$scriptDir = __DIR__;
echo "<p>__DIR__ = " . htmlspecialchars($scriptDir) . "</p>";

// 2) Tentar incluir o db.php
$dbPath = __DIR__ . '/db.php';
echo "<p>A tentar incluir: " . htmlspecialchars($dbPath) . "</p>";

if (!file_exists($dbPath)) {
    echo "<p style='color:red;'>ERRO: db.php não encontrado nesse caminho.</p>";
    exit;
}

require_once $dbPath;
echo "<p>Passo 2: db.php incluído com sucesso.</p>";

// 3) Ver se a variável $pdo existe
if (!isset($pdo)) {
    echo "<p style='color:red;'>ERRO: \$pdo não está definido depois de incluir db.php.</p>";
    exit;
}

echo "<p>Passo 3: \$pdo existe. A testar ligação com SHOW TABLES...</p>";

// 4) Tentar listar tabelas
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();

    if (!$tables) {
        echo "<p>Sem tabelas na base de dados <strong>sie_db</strong> ou não foi retornado nada.</p>";
    } else {
        echo "<h3>Tabelas em sie_db:</h3>";
        echo "<ul>";
        foreach ($tables as $row) {
            // cada linha é um array associativo ou numérico conforme a config
            echo "<li>" . htmlspecialchars(implode(' | ', $row)) . "</li>";
        }
        echo "</ul>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Erro ao executar SHOW TABLES: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p>Fim do test_db.php.</p>";
