<?php
// generate_hash.php — gera um hash bcrypt da senha digitada e mostra debug de bytes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $senha = $_POST['senha'] ?? '';
  $hash  = password_hash($senha, PASSWORD_BCRYPT);

  echo "<p><b>Hash bcrypt:</b><br><code>{$hash}</code></p>";
  echo "<p><b>Tamanho da senha:</b> ".strlen($senha)." bytes</p>";

  echo "<p><b>Bytes (ord):</b><br><code>";
  for ($i=0;$i<strlen($senha);$i++) {
    echo ord($senha[$i]).($i < strlen($senha)-1 ? ' ' : '');
  }
  echo "</code></p>";

  echo '<p><a href="generate_hash.php">Voltar</a></p>';
  exit;
}
?>
<form method="post">
  <label>Senha (digite manualmente):</label><br>
  <input type="password" name="senha" autocomplete="off" autofocus>
  <button type="submit">Gerar hash</button>
</form>
