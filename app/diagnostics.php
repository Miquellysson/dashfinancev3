<?php
// Diagnóstico principal
$baseDir = __DIR__;
$results = [];

function section(string $title): void {
    global $results;
    $results[] = "\n=== {$title} ===";
}
function item(string $label, $value): void {
    global $results;
    if (is_bool($value)) {
        $value = $value ? 'OK' : 'NÃO';
    }
    $results[] = sprintf('%-30s %s', $label . ':', $value);
}
function pathInfo(string $path): string {
    if (!file_exists($path)) {
        return 'NÃO encontrado';
    }
    $perm = substr(sprintf('%o', fileperms($path)), -4);
    return sprintf('existe | leitura:%s | escrita:%s | perm:%s', is_readable($path)?'SIM':'NÃO', is_writable($path)?'SIM':'NÃO', $perm);
}

section('Ambiente PHP');
item('Versão PHP', PHP_VERSION);
item('SAPI', php_sapi_name());
item('Timezone', date_default_timezone_get());
item('memory_limit', ini_get('memory_limit'));

section('Arquivos cruciais');
$important = [
    'index.php'              => $baseDir . '/index.php',
    'AuthController.php'     => $baseDir . '/app/Controllers/AuthController.php',
    'Auth.php'               => $baseDir . '/app/Helpers/Auth.php',
    'Utils.php'              => $baseDir . '/app/Helpers/Utils.php',
    'login.php'              => $baseDir . '/app/Views/login.php',
    'config/database.php'    => $baseDir . '/config/database.php',
    '.htaccess'              => $baseDir . '/.htaccess',
];
foreach ($important as $label => $path) {
    item($label, pathInfo($path));
}

section('Banco de dados');
$dbStatus = 'Não conectado';
$dbError = null;
$tableUsers = false;
$tableClients = false;
$tableProjects = false;
$tablePayments = false;
$tableGoals = false;
$tableStatus = false;
$userCount = null;
$userSample = null;
$passwordTest = null;
$statusCatalog = [];
try {
    require $baseDir . '/config/database.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbStatus = 'Conectado';

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $tableUsers    = in_array('users', $tables, true);
        $tableClients  = in_array('clients', $tables, true);
        $tableProjects = in_array('projects', $tables, true);
        $tablePayments = in_array('payments', $tables, true);
        $tableGoals    = in_array('goals', $tables, true);
        $tableStatus   = in_array('status_catalog', $tables, true);

        if ($tableUsers) {
            $userCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $stmt = $pdo->query('SELECT id, nome_completo, email, tipo_usuario FROM users LIMIT 3');
            $userSample = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($tableStatus) {
            $statusCatalog = $pdo->query('SELECT name FROM status_catalog ORDER BY sort_order, name')->fetchAll(PDO::FETCH_COLUMN);
        }
    }
} catch (Throwable $e) {
    $dbStatus = 'Falhou';
    $dbError = $e->getMessage();
}
item('Status conexão', $dbStatus);
if ($dbError) {
    item('Erro', $dbError);
}
item('Tabela users', $tableUsers);
item('Tabela clients', $tableClients);
item('Tabela projects', $tableProjects);
item('Tabela payments', $tablePayments);
item('Tabela goals', $tableGoals);
item('Tabela status_catalog', $tableStatus);
if ($userCount !== null) {
    item('users.total', $userCount);
}
if ($userSample) {
    item('users.amostra', json_encode($userSample, JSON_UNESCAPED_UNICODE));
}
if ($statusCatalog) {
    item('status_catalog', implode(', ', $statusCatalog));
}

section('Estrutura payments');
$expectedColumns = ['currency','transaction_type','description','category'];
$missingColumns = [];
if ($tablePayments && isset($pdo)) {
    try {
        $cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments'")->fetchAll(PDO::FETCH_COLUMN);
        $missingColumns = array_diff($expectedColumns, $cols);
    } catch (Throwable $e) {
        item('Erro listando colunas', $e->getMessage());
    }
}
item('Colunas faltantes', $missingColumns ? implode(', ', $missingColumns) : 'Nenhuma');

$report = implode(PHP_EOL, $results) . PHP_EOL;
$logDir = $baseDir . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
$logFile = $logDir . '/diagnostics-' . date('Ymd-His') . '.log';
@file_put_contents($logFile, $report);

echo '<pre>' . htmlspecialchars($report, ENT_QUOTES, 'UTF-8') . '</pre>';
echo '<p>Relatório salvo em ' . htmlspecialchars($logFile, ENT_QUOTES, 'UTF-8') . '</p>';
