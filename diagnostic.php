<?php
/* ============================================================
   Arkaleads App - Diagnostic v2
   Coloque este arquivo na RAIZ do site (public_html) e acesse:
   https://app.arkaleads.com/diagnostic.php
   ============================================================ */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNOSTIC REPORT (v2) ===\n\n";

/* ---------- Config de DB (as que você passou) ---------- */
$db_host = "localhost";
$db_name = "u100060033_financa";
$db_user = "u100060033_financa";
$db_pass = "Arkaleads2025!@#";

/* ---------- Ambiente ---------- */
echo "PHP: ".PHP_VERSION."\n";
echo "Time: ".date('Y-m-d H:i:s')." (".date_default_timezone_get().")\n";
echo "DocRoot: ".$_SERVER['DOCUMENT_ROOT']."\n";
echo "This file: ".__FILE__."\n\n";

/* ---------- 1) DB Connection ---------- */
try {
  $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",$db_user,$db_pass,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  ]);
  echo "✅ DB connection OK\n";
} catch (Throwable $e) {
  echo "❌ DB connection FAILED: ".$e->getMessage()."\n";
  exit("\n=== END ===\n");
}

/* ---------- 2) Tables existence ---------- */
$needTables = ['users','clients','projects','payments','goals','status_catalog'];
foreach ($needTables as $t) {
  $exists = $pdo->query("SHOW TABLES LIKE ".$pdo->quote($t))->fetchColumn();
  echo $exists ? "✅ Table '$t' exists\n" : "❌ Table '$t' missing\n";
}
echo "\n";

/* ---------- 3) Users overview ---------- */
try {
  $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  if ($count === 0) {
    echo "❌ No users found in 'users'\n";
  } else {
    echo "✅ Users count: $count\n";
    $rows = $pdo->query("SELECT id,name,email,role,active,created_at FROM users ORDER BY id LIMIT 10")->fetchAll();
    foreach ($rows as $r) {
      echo "   - [{$r['id']}] {$r['email']} role={$r['role']} active={$r['active']} created_at={$r['created_at']}\n";
    }
  }
} catch (Throwable $e) {
  echo "❌ Could not query users: ".$e->getMessage()."\n";
}
echo "\n";

/* ---------- 4) Password verify test (default: marketing@arkaleads.com) ---------- */
$testEmail = $_POST['test_email'] ?? $_GET['test_email'] ?? 'marketing@arkaleads.com';
$testPass  = $_POST['test_pass']  ?? $_GET['test_pass']  ?? 'Arkaleads!@#2025';

try {
  $st = $pdo->prepare("SELECT id,name,email,password,active FROM users WHERE email = :e LIMIT 1");
  $st->execute([':e'=>$testEmail]);
  $u = $st->fetch();

  if (!$u) {
    echo "❌ Test login: user {$testEmail} not found in 'users'\n";
  } else {
    echo "🔎 Found user: {$u['email']} (id={$u['id']}) active={$u['active']}\n";
    $ok = password_verify($testPass, $u['password'] ?? '');
    echo $ok ? "✅ password_verify OK for {$testEmail}\n" : "❌ password_verify FAILED for {$testEmail}\n";
  }
} catch (Throwable $e) {
  echo "❌ Error testing password_verify: ".$e->getMessage()."\n";
}
echo "\n";

/* ---------- 5) Files & Paths ---------- */
/* Estrutura esperada (ajuste se sua árvore for diferente):
   raiz/
     index.php
     app/
       Controllers/AuthController.php
       Helpers/{Auth.php, Utils.php}
       Views/login.php
     config/
       database.php
     .htaccess
*/
$root = dirname(__FILE__);
$paths = [
  $root."/index.php",
  $root."/app/Controllers/AuthController.php",
  $root."/app/Helpers/Auth.php",
  $root."/app/Helpers/Utils.php",
  $root."/app/Views/login.php",
  $root."/config/database.php",
  $root."/.htaccess",
];
foreach ($paths as $p) {
  echo file_exists($p) ? "✅ File exists: $p\n" : "❌ Missing file: $p\n";
}
echo "\n";

/* ---------- 6) Router expectations ---------- */
/* Seu router usa /{controller}/{method}. Para login, esperamos:
   POST /auth/doLogin  -> AuthController::doLogin()
*/
$expectedLoginPath = "/auth/doLogin";
echo "Router expected login POST path: {$expectedLoginPath}\n";
echo "Current request URI: ".($_SERVER['REQUEST_URI'] ?? '(n/a)')."\n";
echo "Note: Ensure <form action=\"{$expectedLoginPath}\" method=\"post\"> in login view.\n\n";

/* ---------- 7) Quick advice if users table is empty ---------- */
if ($count === 0) {
  echo "👉 SUGESTÃO: criar admin rapidamente (cole no phpMyAdmin):\n";
  echo "INSERT INTO users (name,email,password,role,active,created_at,updated_at)\n";
  echo "VALUES ('Administrador','marketing@arkaleads.com',\n";
  echo "        '\$2b\$12\$jbAWcqe/xqSLtqSerzbh0Ox9Wmj5NYf5d9n0k.JKKHoIQuoJeg8qa',\n";
  echo "        'admin',1,NOW(),NOW())\n";
  echo "ON DUPLICATE KEY UPDATE password=VALUES(password), role='admin', active=1, updated_at=NOW();\n\n";
}

/* ---------- 8) Optional interactive form ---------- */
echo "=== Interactive Test ===\n";
echo "POST this same page with custom credentials to test password_verify.\n";
echo "Example curl:\n";
echo "curl -X POST -d 'test_email=marketing@arkaleads.com&test_pass=Arkaleads!@#2025' https://".$_SERVER['HTTP_HOST']."/diagnostic.php\n";

echo "\n=== END OF REPORT ===\n";
