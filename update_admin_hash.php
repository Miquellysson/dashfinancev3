<?php
// update_admin_hash.php — atualiza/cria o admin com novo hash

$db_host = "localhost";
$db_name = "u100060033_financa";
$db_user = "u100060033_financa";
$db_pass = "Arkaleads2025!@#";

$email_admin = "admin@empresa.com";

// COLE AQUI O HASH GERADO NO generate_hash.php
$novo_hash   = '$2y$10$COLOQUE_AQUI_SEU_HASH_BCRYPT';

try {
  $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",$db_user,$db_pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION
  ]);

  // garante coluna com tamanho suficiente
  $pdo->exec("ALTER TABLE usuarios MODIFY senha_hash VARCHAR(255) NOT NULL");

  // tenta atualizar
  $stmt = $pdo->prepare("UPDATE usuarios 
                          SET senha_hash=:h, atualizado_em=NOW(), ativo=1 
                          WHERE email=:e LIMIT 1");
  $stmt->execute([':h'=>$novo_hash, ':e'=>$email_admin]);

  if ($stmt->rowCount() === 0) {
    // se não existia, cria
    $ins = $pdo->prepare("INSERT INTO usuarios 
      (nome,email,senha_hash,perfil,ativo,criado_em,atualizado_em)
      VALUES ('Administrador',:e,:h,'admin',1,NOW(),NOW())");
    $ins->execute([':e'=>$email_admin, ':h'=>$novo_hash]);
  }

  echo "✅ Hash atualizado/criado para {$email_admin}. Agora você pode logar.";
} catch (Throwable $e) {
  echo "❌ Erro: ".$e->getMessage();
}
