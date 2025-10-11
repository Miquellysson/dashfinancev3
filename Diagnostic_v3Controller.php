<?php

class Diagnostic_v3Controller {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function index(): string {
        $baseDir = dirname(__DIR__, 1);
        $report = [];
        $json = [];

        $add = function (string $label, $value) use (&$report) {
            if (is_bool($value)) {
                $value = $value ? 'SIM' : 'NÃO';
            } elseif (is_array($value)) {
                $value = implode(', ', $value);
            }
            $report[] = sprintf('%-28s %s', $label . ':', $value);
        };

        $section = function (string $title) use (&$report) {
            $report[] = "\n=== {$title} ===";
        };

        $describe = static function (string $path): string {
            if (!file_exists($path)) return 'NÃO encontrado';
            $perm = substr(sprintf('%o', fileperms($path)), -4);
            return sprintf('existe | leitura:%s | escrita:%s | perm:%s',
                is_readable($path) ? 'SIM' : 'NÃO',
                is_writable($path) ? 'SIM' : 'NÃO',
                $perm
            );
        };

        $section('Ambiente PHP (v3)');
        $add('Versão PHP', PHP_VERSION);
        $add('SAPI', php_sapi_name());
        $add('Timezone', date_default_timezone_get());
        $add('memory_limit', ini_get('memory_limit'));

        $section('Arquivos principais');
        $base = $baseDir . '/..';
        $files = [
            'index.php'            => $baseDir . '/../index.php',
            'AuthController.php'   => $baseDir . '/Controllers/AuthController.php',
            'Auth.php'             => $baseDir . '/Helpers/Auth.php',
            'Utils.php'            => $baseDir . '/Helpers/Utils.php',
            'login.php'            => $baseDir . '/Views/login.php',
            'config/database.php'  => $baseDir . '/../config/database.php',
            '.htaccess'            => $baseDir . '/../.htaccess',
        ];
        foreach ($files as $label => $path) {
            $add($label, $describe($path));
        }

        $section('Banco de dados');
        $status = 'Desconhecido';
        $tables = [];
        $statusCatalog = [];
        $paymentsColumns = [];
        $userSample = [];
        $userCount = null;
        try {
            $status = 'Conectado';
            $tables = $this->pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
            if (in_array('status_catalog', $tables, true)) {
                $statusCatalog = $this->pdo->query('SELECT name FROM status_catalog ORDER BY sort_order, name')->fetchAll(PDO::FETCH_COLUMN) ?: [];
            }
            if (in_array('payments', $tables, true)) {
                $paymentsColumns = $this->pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments'")->fetchAll(PDO::FETCH_COLUMN) ?: [];
            }
            if (in_array('users', $tables, true)) {
                $userCount = (int)$this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
                $userSample = $this->pdo->query('SELECT id, nome_completo, email, tipo_usuario FROM users LIMIT 3')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (Throwable $e) {
            $status = 'Falhou: ' . $e->getMessage();
        }
        $add('Status conexão', $status);
        $add('Tabelas', $tables ?: 'n/d');
        $add('users.total', $userCount ?? 'n/d');
        if ($userSample) {
            $add('users.amostra', json_encode($userSample, JSON_UNESCAPED_UNICODE));
        }
        if ($statusCatalog) {
            $add('status_catalog', implode(', ', $statusCatalog));
        }

        $section('Pagamentos - colunas');
        $expected = ['currency','transaction_type','description','category'];
        $missing  = $paymentsColumns ? array_diff($expected, $paymentsColumns) : $expected;
        $add('Colunas encontradas', $paymentsColumns ?: 'n/d');
        $add('Faltantes', $missing ? implode(', ', $missing) : 'Nenhuma');

        $content = implode(PHP_EOL, $report) . PHP_EOL;
        $logDir = $baseDir . '/../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/diagnostic-v3-' . date('Ymd-His') . '.log';
        @file_put_contents($logFile, $content);

        return '<pre>' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '</pre>'
             . '<p>Relatório salvo em ' . htmlspecialchars($logFile, ENT_QUOTES, 'UTF-8') . '</p>';
    }
}
