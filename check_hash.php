<?php
// check_hash.php — verifica se a senha confere com o hash salvo

$db_host = "localhost";
$db_name = "u100060033_financa";
$db_user = "u100060033_financa";
$db_pass = "Arkaleads2025!@#";

$email = "admin@empresa.com";
$senha = "Arkaleads!@#2025"; // senha que você quer testar

try {
  $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",$db_user,$db_pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION
  ]);
  $stmt = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE email=:e AND ativo=1 LIMIT 1");
  $stmt->execute([':e'=>$email]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) { exit("Usuário não encontrado ou inativo.\n"); }

  if (password_verify($senha, $row['senha_hash'])) {
    echo "✅ password_verify OK para {$email}\n";
  } else {
    echo "❌ password_verify FALHOU para {$email}\n";
    echo "Hash salvo: {$row['senha_hash']}\n";
  }
} catch (Throwable $e) {
  echo "Erro: ".$e->getMessage();
}
