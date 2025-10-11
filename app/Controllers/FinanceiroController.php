<?php
require_once __DIR__ . '/../Models/FinancialReserveModel.php';
require_once __DIR__ . '/../Models/PaymentModel.php';

class FinanceiroController {
    private PDO $pdo;
    private FinancialReserveModel $reserve;
    private PaymentModel $payments;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['user_id'])) {
            Utils::redirect('/auth/login');
        }
        $this->reserve = new FinancialReserveModel($pdo);
        $this->payments = new PaymentModel($pdo);
    }

    public function index() {
        Utils::redirect('/financeiro/dashboard');
    }

    public function dashboard() {
        Utils::redirect('/dashboard');
    }

    public function caixa() {
        $today = new DateTimeImmutable('today');
        $monthStart = $today->modify('first day of this month')->format('Y-m-d');
        $monthEnd = $today->modify('last day of this month')->format('Y-m-d');

        $stmt = $this->pdo->prepare("
            SELECT
              transaction_type,
              COALESCE(SUM(CASE WHEN paid_at BETWEEN :start AND :end THEN amount END), 0) AS paid_total,
              COALESCE(SUM(CASE WHEN due_date BETWEEN :start AND :end AND (paid_at IS NULL OR paid_at = '0000-00-00') THEN amount END), 0) AS scheduled_total
            FROM payments
            WHERE (currency = 'BRL' OR currency IS NULL)
        ");
        $stmt->bindValue(':start', $monthStart);
        $stmt->bindValue(':end', $monthEnd);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totals = [
            'receita' => ['pagos' => 0.0, 'previstos' => 0.0],
            'despesa' => ['pagos' => 0.0, 'previstos' => 0.0],
        ];
        foreach ($rows as $row) {
            $type = $row['transaction_type'] ?? 'receita';
            if (!isset($totals[$type])) {
                $totals[$type] = ['pagos' => 0.0, 'previstos' => 0.0];
            }
            $totals[$type]['pagos'] = (float)$row['paid_total'];
            $totals[$type]['previstos'] = (float)$row['scheduled_total'];
        }

        $upcoming = $this->pdo->prepare("
            SELECT description, transaction_type, amount, due_date, status_id
            FROM payments
            WHERE due_date >= :today
            ORDER BY due_date ASC
            LIMIT 8
        ");
        $upcoming->bindValue(':today', $today->format('Y-m-d'));
        $upcoming->execute();
        $upcomingPayments = $upcoming->fetchAll(PDO::FETCH_ASSOC);

        $reserveBalance = $this->reserve->getBalance();

        include __DIR__ . '/../Views/financeiro/caixa.php';
    }

    public function reserva() {
        $filters = [
            'from' => $_GET['from'] ?? '',
            'to' => $_GET['to'] ?? '',
            'type' => $_GET['type'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $entries = $this->reserve->paginate($filters, $perPage, $offset);
        $totalEntries = $this->reserve->count($filters);
        $totals = $this->reserve->getTotals($filters);
        $overallBalance = $this->reserve->getBalance();

        $usage = 0.0;
        if ($totals['deposits'] > 0) {
            $usage = min(100, max(0, round(($totals['withdrawals'] / $totals['deposits']) * 100)));
        }

        $pagination = [
            'current' => $page,
            'per_page' => $perPage,
            'total' => $totalEntries,
            'total_pages' => max(1, (int)ceil($totalEntries / $perPage)),
        ];

        $query = $_GET;
        unset($query['page']);
        $exportUrl = '/financeiro/reserva-exportar' . ($query ? '?' . http_build_query($query) : '');

        include __DIR__ . '/../Views/financeiro/reserva.php';
    }

    public function reservaCriar() {
        $movement = [
            'reference_date' => date('Y-m-d'),
            'operation_type' => 'deposit',
            'amount' => '',
            'description' => '',
            'category' => '',
            'notes' => '',
        ];
        include __DIR__ . '/../Views/financeiro/reserva_form.php';
    }

    public function reservaSalvar() {
        $payload = $this->sanitizeReserveInput($_POST);
        $payload['created_by'] = $_SESSION['user_id'] ?? null;
        try {
            $this->reserve->create($payload);
            Utils::redirect('/financeiro/reserva', 'Movimentação registrada com sucesso.');
        } catch (PDOException $e) {
            $this->handleReserveException($e);
        }
    }

    public function reservaEditar($id) {
        $movement = $this->reserve->find((int)$id);
        if (!$movement) {
            Utils::redirect('/financeiro/reserva', 'Movimentação não encontrada.');
        }
        include __DIR__ . '/../Views/financeiro/reserva_form.php';
    }

    public function reservaAtualizar($id) {
        $movement = $this->reserve->find((int)$id);
        if (!$movement) {
            Utils::redirect('/financeiro/reserva', 'Movimentação não encontrada.');
        }
        $payload = $this->sanitizeReserveInput($_POST);
        try {
            $this->reserve->update((int)$id, $payload);
            Utils::redirect('/financeiro/reserva', 'Movimentação atualizada com sucesso.');
        } catch (PDOException $e) {
            $this->handleReserveException($e);
        }
    }

    public function reservaExcluir($id) {
        $movement = $this->reserve->find((int)$id);
        if (!$movement) {
            Utils::redirect('/financeiro/reserva', 'Movimentação não encontrada.');
        }
        try {
            $this->reserve->delete((int)$id);
            Utils::redirect('/financeiro/reserva', 'Movimentação removida.');
        } catch (PDOException $e) {
            $this->handleReserveException($e);
        }
    }

    public function reservaExportar() {
        $filters = [
            'from' => $_GET['from'] ?? '',
            'to' => $_GET['to'] ?? '',
            'type' => $_GET['type'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        $rows = $this->reserve->export($filters);
        $filename = 'reserva-financeira-' . date('Ymd-His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID', 'Data', 'Tipo', 'Valor', 'Categoria', 'Descrição', 'Observações'], ';');
        foreach ($rows as $row) {
            $label = $row['operation_type'] === 'withdraw' ? 'Retirada' : 'Depósito';
            fputcsv($out, [
                $row['id'],
                $row['reference_date'],
                $label,
                number_format((float)$row['amount'], 2, ',', '.'),
                $row['category'],
                $row['description'],
                $row['notes'],
            ], ';');
        }
        fclose($out);
        exit;
    }

    public function contasPagar() {
        $query = $_GET;
        $query['transaction_type'] = 'despesa';
        Utils::redirect('/pagamento?' . http_build_query($query));
    }

    public function contasReceber() {
        $query = $_GET;
        $query['transaction_type'] = 'receita';
        Utils::redirect('/pagamento?' . http_build_query($query));
    }

    public function relatorios() {
        include __DIR__ . '/../Views/financeiro/relatorios.php';
    }

    public function configuracoes() {
        include __DIR__ . '/../Views/financeiro/configuracoes.php';
    }

    private function sanitizeReserveInput(array $input): array {
        $type = $input['operation_type'] ?? 'deposit';
        $type = in_array($type, ['deposit', 'withdraw'], true) ? $type : 'deposit';

        $amount = Utils::decimalFromInput($input['amount'] ?? 0);
        if ($amount <= 0) {
            Utils::redirect('/financeiro/reserva', 'Informe um valor maior que zero.');
        }

        $dateRaw = $input['reference_date'] ?? date('Y-m-d');
        $date = DateTime::createFromFormat('Y-m-d', $dateRaw);
        if (!$date) {
            $date = new DateTimeImmutable('today');
        }

        $description = isset($input['description']) ? trim((string)$input['description']) : '';
        $category = isset($input['category']) ? trim((string)$input['category']) : '';
        $notes = isset($input['notes']) ? trim((string)$input['notes']) : '';

        return [
            'operation_type' => $type,
            'amount' => $amount,
            'reference_date' => $date->format('Y-m-d'),
            'description' => $description !== '' ? Utils::sanitize($description) : null,
            'category' => $category !== '' ? Utils::sanitize($category) : null,
            'notes' => $notes !== '' ? Utils::sanitize($notes) : null,
        ];
    }

    private function handleReserveException(PDOException $e): void {
        if (in_array($e->getCode(), ['42S02', '42S22'], true)) {
            Utils::redirect(
                '/financeiro/reserva',
                'Estrutura da reserva financeira não está disponível. Execute a migração 20241019_financial_reserve.sql antes de continuar.'
            );
        }
        throw $e;
    }
}
