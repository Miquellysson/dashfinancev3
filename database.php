
<?php
$host = 'localhost';
$db   = 'u100060033_financa';
$user = 'u100060033_financa';
$pass = 'Arkaleads2025!@#';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];
try { 
    $pdo = new PDO($dsn, $user, $pass, $options); 
} catch (PDOException $e) { 
    die('Erro de conexão: ' . $e->getMessage()); 
}
