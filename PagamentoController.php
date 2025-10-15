<?php
require_once __DIR__ . '/../Models/PaymentModel.php';
require_once __DIR__ . '/../Models/ProjectModel.php';
require_once __DIR__ . '/../Models/ClientModel.php';

class PagamentoController {
    private $pdo;
    private $paymentModel;
    private $projectModel;
    private $clientModel;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['user_id'])) { Utils::redirect('/auth/login'); }
        $this->paymentModel = new PaymentModel($pdo);
        $this->projectModel = new ProjectModel($pdo);
        $this->clientModel = new ClientModel($pdo);
    }

    private function statusOptions(): array {
        return [
            'receita' => [
                ['value' => 'Recebido',   'label' => 'Recebido'],
                ['value' => 'A Receber',  'label' => 'A Receber'],
                ['value' => 'Em Atraso',  'label' => 'Em Atraso'],
                ['value' => 'Cancelado',  'label' => 'Cancelado'],
            ],
            'despesa' => [
                ['value' => 'Pago',       'label' => 'Pago'],
                ['value' => 'Pendente',   'label' => 'Pendente'],
                ['value' => 'Vencido',    'label' => 'Vencido'],
                ['value' => 'Parcelado',  'label' => 'Parcelado'],
                ['value' => 'Cancelado',  'label' => 'Cancelado'],
            ],
        ];
    }

    private function normalizeStatusLabel(?string $status, string $type): string {
        if (!$status) {
            return '';
        }
        $key = mb_strtolower(trim($status), 'UTF-8');
        return [
            'paid'      => $type === 'despesa' ? 'Pago' : 'Recebido',
            'recebido'  => 'Recebido',
            'pago'      => 'Pago',
            'pending'   => $type === 'despesa' ? 'Pendente' : 'A Receber',
            'pendente'  => 'Pendente',
            'overdue'   => $type === 'despesa' ? 'Vencido' : 'Em Atraso',
            'em atraso' => 'Em Atraso',
            'dropped'   => 'Cancelado',
            'cancelado' => 'Cancelado',
            'a receber' => 'A Receber',
            'vencido'   => 'Vencido',
            'parcelado' => 'Parcelado',
        ][$key] ?? $status;
    }

    public function index() {
        $payments = $this->paymentModel->getAll();
        include __DIR__ . '/../Views/pagamentos/list.php';
    }

    public function create() {
        $payment = null;
        $projects = $this->projectModel->getAll(1000,0);
        $clients = $this->clientModel->getAll(1000,0);
        $statusOptions = $this->statusOptions();
        include __DIR__ . '/../Views/pagamentos/form.php';
    }

    public function edit($id) {
        $payment = $this->paymentModel->getById($id);
        $projects = $this->projectModel->getAll(1000,0);
        $clients = $this->clientModel->getAll(1000,0);
        $statusOptions = $this->statusOptions();
        if ($payment) {
            $payment['transaction_type'] = $payment['transaction_type'] ?? 'receita';
            $payment['status_name'] = $this->normalizeStatusLabel($payment['status_name'] ?? '', $payment['transaction_type']);
        }
        include __DIR__ . '/../Views/pagamentos/form.php';
    }

    public function save() {
        $data = [
            'project_id' => $_POST['project_id'] ?? null,
            'client_id' => $_POST['client_id'] ?? null,
            'amount'     => $_POST['amount'] ?? null,
            'kind'       => $_POST['kind'] ?? 'one_time',
            'currency'   => $_POST['currency'] ?? 'BRL',
            'transaction_type' => $_POST['transaction_type'] ?? 'receita',
            'description' => $_POST['description'] ?? null,
            'category'   => $_POST['category'] ?? null,
            'notes'      => $_POST['notes'] ?? null,
            'due_date'   => $_POST['due_date'] ?? null,
            'paid_at'    => $_POST['paid_at'] ?? null,
            // pode vir status_id (numérico) ou status (texto)
            'status_id'  => null,
            'status'     => $_POST['status'] ?? null,
        ];

        if (isset($data['status']) && $data['status'] === '') {
            $data['status'] = null;
        }

        if (!empty($_POST['id'])) {
            $ok = $this->paymentModel->update((int)$_POST['id'], $data);
        } else {
            $ok = $this->paymentModel->create($data);
        }
        Utils::redirect('/pagamento' . ($ok ? '' : '?error=1'));
    }

    public function delete($id) {
        $this->paymentModel->delete((int)$id);
        Utils::redirect('/pagamento');
    }

    // /pagamento/export -> CSV
    public function export() {
        $rows = $this->paymentModel->getAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=payments_export.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Projeto','Cliente','Tipo','Descrição','Categoria','Valor','Moeda','Vencimento','Pago em','Status']);
        $statusAliases = [
            'paid'      => 'Recebido',
            'recebido'  => 'Recebido',
            'pago'      => 'Pago',
            'pending'   => 'Pendente',
            'pendente'  => 'Pendente',
            'overdue'   => 'Em Atraso',
            'em atraso' => 'Em Atraso',
            'dropped'   => 'Cancelado',
            'cancelado' => 'Cancelado',
            'a receber' => 'A Receber',
            'vencido'   => 'Vencido',
            'parcelado' => 'Parcelado',
        ];

        foreach ($rows as $r) {
            $amount = (float)($r['amount'] ?? 0);
            $currency = strtoupper($r['currency'] ?? 'BRL');
            $amountFormatted = $currency === 'USD'
                ? number_format($amount, 2, '.', ',')
                : number_format($amount, 2, ',', '.');
            $typeLabel = ($r['transaction_type'] ?? 'receita') === 'despesa' ? 'Despesa' : 'Receita';
            $statusName = trim((string)($r['status_name'] ?? ''));
            $statusKey = mb_strtolower($statusName, 'UTF-8');
            $statusLabel = $statusAliases[$statusKey] ?? $statusName;
            fputcsv($out, [
                $r['id'],
                $r['project_name'] ?? '',
                $r['client_name'] ?? '',
                $typeLabel,
                $r['description'] ?? '',
                $r['category'] ?? '',
                $amountFormatted,
                $currency,
                $r['due_date'] ?? '',
                $r['paid_at'] ?? '',
                $statusLabel,
            ]);
        }
        fclose($out);
        exit;
    }
}
