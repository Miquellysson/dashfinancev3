<?php
// set_admin_password.php — define/atualiza a senha do admin gerando o hash bcrypt automaticamente
// Use uma vez e APAGUE depois por segurança.

$db_host = "localhost";
$db_name = "u100060033_financa";
$db_user = "u100060033_financa";
$db_pass = "Arkaleads2025!@#";

$email_admin = "marketing@arkaleads.com";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    if ($senha === '') {
        exit('❌ Informe uma senha.');
    }

    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",$db_user,$db_pass,[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // garante tamanho suficiente para o hash
        $pdo->exec("ALTER TABLE usuarios MODIFY senha_hash VARCHAR(255) NOT NULL");

        // gera hash seguro
        $hash = password_hash($senha, PASSWORD_BCRYPT);

        // tenta atualizar; se não existir, cria
        $stmt = $pdo->prepare("UPDATE usuarios SET senha_hash = :h, perfil='admin', ativo=1, atualizado_em=NOW() WHERE email = :e LIMIT 1");
        $stmt->execute([':h'=>$hash, ':e'=>$email_admin]);

        if ($stmt->rowCount() === 0) {
            $ins = $pdo->prepare("INSERT INTO usuarios (nome,email,senha_hash,perfil,ativo,criado_em,atualizado_em)
                                  VALUES ('Administrador', :e, :h, 'admin', 1, NOW(), NOW())");
            $ins->execute([':e'=>$email_admin, ':h'=>$hash]);
        }

        echo "✅ Admin atualizado/criado com sucesso.<br>";
        echo "Login: <b>{$email_admin}</b><br>";
        echo "Senha definida agora (guarde com segurança).<br><br>";
        echo "⚠️ Apague este arquivo (set_admin_password.php) após usar.";
        exit;
    } catch (Throwable $e) {
        exit("❌ Erro: ".$e->getMessage());
    }
}
?>
<!doctype html>
<html lang="pt-br"><meta charset="utf-8"><title>Definir senha admin</title>
<body style="font-family:system-ui;max-width:520px;margin:40px auto">
  <h2>Definir/Atualizar senha do admin</h2>
  <p><b>Usuário:</b> marketing@arkaleads.com</p>
  <form method="post" autocomplete="off">
    <label>Nova senha:</label><br>
    <input type="password" name="senha" required autofocus style="width:100%;padding:10px;margin:8px 0">
    <button type="submit" style="padding:10px 16px">Salvar</button>
  </form>
  <p style="margin-top:12px;color:#555">Dica: use uma senha forte. Apague este arquivo após uso.</p>
</body>
</html>
