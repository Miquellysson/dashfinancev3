<?php
declare(strict_types=1);

$baseDir = __DIR__;
$report = [];

function section(string $title): void { global $report; $report[] = "\n=== {$title} ==="; }
function item(string $label, $value): void {
    global $report;
    if (is_bool($value)) { $value = $value ? 'SIM' : 'NÃO'; }
    elseif (is_array($value)) { $value = implode(', ', $value); }
    $report[] = sprintf('%-28s %s', $label . ':', $value);
}
function describe(string $path): string {
    if (!file_exists($path)) return 'NÃO encontrado';
    $perm = substr(sprintf('%o', fileperms($path)), -4);
    return sprintf('existe | leitura:%s | escrita:%s | perm:%s', is_readable($path)?'SIM':'NÃO', is_writable($path)?'SIM':'NÃO', $perm);
}

section('Ambiente PHP (v3)');
item('Versão PHP', PHP_VERSION);
item('SAPI', php_sapi_name());
item('Timezone', date_default_timezone_get());
item('memory_limit', ini_get('memory_limit'));

section('Arquivos principais');
$files = [
    'index.php'            => $baseDir . '/index.php',
    'AuthController.php'   => $baseDir . '/app/Controllers/AuthController.php',
    'Auth.php'             => $baseDir . '/app/Helpers/Auth.php',
    'Utils.php'            => $baseDir . '/app/Helpers/Utils.php',
    'login.php'            => $baseDir . '/app/Views/login.php',
    'config/database.php'  => $baseDir . '/config/database.php',
    '.htaccess'            => $baseDir . '/.htaccess',
];
foreach ($files as $label => $path) { item($label, describe($path)); }

section('Banco de dados');
$dbStatus = 'Não conectado';
$dbError = null;
$tableUsers = $tablePayments = $tableStatus = false;
$userCount = null;
$userSample = [];
$statusCatalog = [];
$paymentsColumns = [];
try {
    require $baseDir . '/config/database.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbStatus = 'Conectado';
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $tableUsers    = in_array('users', $tables, true);
        $tablePayments = in_array('payments', $tables, true);
        $tableStatus   = in_array('status_catalog', $tables, true);
        if ($tableUsers) {
            $userCount  = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $userSample = $pdo->query('SELECT id, nome_completo, email, tipo_usuario FROM users LIMIT 3')->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($tableStatus) {
            $statusCatalog = $pdo->query('SELECT name FROM status_catalog ORDER BY sort_order, name')->fetchAll(PDO::FETCH_COLUMN);
        }
        if ($tablePayments) {
            $paymentsColumns = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments'")->fetchAll(PDO::FETCH_COLUMN);
        }
    }
} catch (Throwable $e) {
    $dbStatus = 'Falhou';
    $dbError  = $e->getMessage();
}
item('Status conexão', $dbStatus);
if ($dbError) item('Erro', $dbError);
item('users.total', $userCount ?? 'n/d');
if ($userSample) item('users.amostra', json_encode($userSample, JSON_UNESCAPED_UNICODE));
if ($statusCatalog) item('status_catalog', implode(', ', $statusCatalog));
$expected = ['currency','transaction_type','description','category'];
$missing  = array_diff($expected, $paymentsColumns);
item('payments.colunas', $paymentsColumns ?: 'n/d');
item('payments.faltantes', $missing ? implode(', ', $missing) : 'Nenhuma');

$reportText = implode(PHP_EOL, $report) . PHP_EOL;
$logDir = $baseDir . '/storage/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
$logFile = $logDir . '/diagnostics-v3-' . date('Ymd-His') . '.log';
@file_put_contents($logFile, $reportText);

echo '<pre>' . htmlspecialchars($reportText, ENT_QUOTES, 'UTF-8') . '</pre>';
echo '<p>Relatório salvo em ' . htmlspecialchars($logFile, ENT_QUOTES, 'UTF-8') . '</p>';
