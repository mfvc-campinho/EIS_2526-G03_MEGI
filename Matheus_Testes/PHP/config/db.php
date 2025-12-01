<?php
// public_html/PHP/db.php

// ⚙️ CONFIGURAÇÃO DA LIGAÇÃO
$dbHost = '127.0.0.1';   // ou 'localhost'
$dbName = 'sie_db';      // <-- o nome que disseste
$dbUser = 'root';        // XAMPP default
$dbPass = '';            // normalmente vazio no XAMPP

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Modo simples de ver o erro em desenvolvimento
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}
