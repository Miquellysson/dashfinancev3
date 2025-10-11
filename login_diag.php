<?php
// login_diag.php — diagnostica login e password_verify para um email/senha informados via POST
$db_host = "localhost";
$db_name = "u100060033_financa";
$db_user = "u100060033_financa";
$db_pass = "Arkaleads2025!@#";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';

  try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",$db_user,$db_pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare("SELECT id,nome,email,senha_hash,perfil,ativo FROM usuarios WHERE email=:e LIMIT 1");
    $stmt->execute([':e'=>$email]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: text/plain; charset=utf-8');

    if (!$u) { exit("❌ Usuário não encontrado para: {$email}\n"); }

    echo "Usuário encontrado:\n";
    print_r($u);

    if (intval($u['ativo']) !== 1) {
      echo "\n⚠️ Usuario está inativo (ativo=".$u['ativo']."). Ative-o no banco.\n";
    }

    $ok = password_verify($senha, $u['senha_hash']);
    echo "\npassword_verify: ".($ok ? "✅ OK" : "❌ FALHOU")."\n";
    if (!$ok) {
      echo "Senha digitada bytes: ";
      for ($i=0;$i<strlen($senha);$i++) echo ord($senha[$i])." ";
      echo "\n";
    }
  } catch (Throwable $e) {
    echo "Erro: ".$e->getMessage();
  }
  exit;
}
?>
<!doctype html><meta charset="utf-8"><title>Diag Login</title>
<form method="post" style="font-family:system-ui;max-width:420px;margin:40px auto">
  <h3>Diagnóstico de Login</h3>
  <label>Email</label><br>
  <input name="email" value="marketing@arkaleads.com" style="width:100%;padding:8px"><br><br>
  <label>Senha</label><br>
  <input name="senha" type="password" style="width:100%;padding:8px"><br><br>
  <button>Testar</button>
  <p style="color:#555">Use o mesmo e-mail/senha que você usa na tela de login.</p>
</form>
