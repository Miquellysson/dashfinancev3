<?php
// ajuste os dados abaixo conforme seu ambiente
$host = 'localhost';
$db   = 'u100060033_financa';
$user = 'u100060033_financa';
$pass = 'Arkaleads2025!@#';
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $email = 'marketing@arkaleads.com';
    $senha = 'Arkaleads!@#'; // altere para a senha desejada
    $hash = password_hash($senha, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Administrador', $email, $hash, 'admin']);

    echo "Usuário admin criado: $email com senha: $senha\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}