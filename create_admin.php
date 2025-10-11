<?php
/**
 * Script para criar usuário admin inicial
 * Salve como create_admin.php, acesse pelo navegador (http://seudominio.com/create_admin.php)
 * Depois APAGUE por segurança
 */

// CONFIGURAÇÕES DO BANCO
$db_host = "localhost";
$db_name = "u100060033_financa";   // altere para o nome do seu banco
$db_user = "u100060033_financa";   // altere para o usuário do MySQL
$db_pass = "Arkaleads2025!@#";     // altere para a senha do MySQL

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // DADOS DO ADMIN INICIAL
    $nome = "Administrador";
    $email = "marketing@arkaleads.com";
    $senha = "Arkaleads2025!@#e"; // senha inicial (troque depois do login)
    $perfil = "admin";

    // Gera hash seguro da senha
    $senha_hash = password_hash($senha, PASSWORD_BCRYPT);

    // Insere no banco
    $sql = "INSERT INTO usuarios (nome, email, senha_hash, perfil, ativo, criado_em, atualizado_em)
            VALUES (:nome, :email, :senha_hash, :perfil, 1, NOW(), NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha_hash' => $senha_hash,
        ':perfil' => $perfil
    ]);

    echo "✅ Usuário admin criado com sucesso!<br>";
    echo "Login: <b>$email</b><br>";
    echo "Senha inicial: <b>$senha</b><br>";
    echo "<hr>⚠️ Por segurança, apague este arquivo (create_admin.php) após usar.";
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
