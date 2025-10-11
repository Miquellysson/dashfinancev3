<?php
require_once __DIR__ . '/../Models/BillingCaseModel.php';
require_once __DIR__ . '/../Models/BillingTaskModel.php';
require_once __DIR__ . '/../Models/ClientModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/NotificationModel.php';
require_once __DIR__ . '/../Models/PaymentModel.php';

class CobrancaController {
    private PDO $pdo;
    private BillingCaseModel $cases;
    private BillingTaskModel $tasks;
    private ClientModel $clients;
    private UserModel $users;
    private NotificationModel $notifications;
    private PaymentModel $payments;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['user_id'])) { Utils::redirect('/auth/login'); }
        $this->cases = new BillingCaseModel($pdo);
        $this->tasks = new BillingTaskModel($pdo);
        $this->clients = new ClientModel($pdo);
        $this->users = new UserModel($pdo);
        $this->notifications = new NotificationModel($pdo);
        $this->payments = new PaymentModel($pdo);
    }

    public function index() {
        $filters = [
            'status' => $_GET['status'] ?? null,
            'responsavel_id' => $_GET['responsavel_id'] ?? null,
            'cliente' => $_GET['cliente'] ?? null,
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $cases = $this->cases->list($filters, $limit, $offset);
        $clients = $this->clients->getAll(200, 0);
        $responsaveis = $this->users->listActive();

        $title = 'Cobranças';
        ob_start();
        include __DIR__ . '/../Views/cobranca/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function criar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = $this->sanitizeCase($_POST);
            $id = $this->cases->create($payload);
            $this->maybeNotifyCase($id, $payload);
            Utils::redirect('/cobranca/ver/'.$id, 'Caso de cobrança criado.');
        }

        $clients = $this->clients->getAll(200, 0);
        $responsaveis = $this->users->listActive();
        $case = $this->prefillFromPayment($_GET['payment_id'] ?? null);

        $title = 'Nova Cobrança';
        ob_start();
        include __DIR__ . '/../Views/cobranca/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function ver($id) {
        $id = (int)$id;
        $case = $this->cases->find($id);
        if (!$case) {
            Utils::redirect('/cobranca', 'Cobrança não encontrada.');
        }
        $tasks = $this->tasks->listByCase($id);
        $responsaveis = $this->users->listActive();

        $title = 'Cobrança: ' . $case['titulo'];
        ob_start();
        include __DIR__ . '/../Views/cobranca/show.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function editar($id) {
        $id = (int)$id;
        $case = $this->cases->find($id);
        if (!$case) {
            Utils::redirect('/cobranca', 'Cobrança não encontrada.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = $this->sanitizeCase($_POST);
            $this->cases->update($id, $payload);
            $this->maybeNotifyCase($id, $payload);
            Utils::redirect('/cobranca/ver/'.$id, 'Cobrança atualizada.');
        }

        $clients = $this->clients->getAll(200, 0);
        $responsaveis = $this->users->listActive();

        $title = 'Editar Cobrança';
        ob_start();
        include __DIR__ . '/../Views/cobranca/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function excluir($id) {
        Auth::requireAdmin();
        $this->cases->delete((int)$id);
        Utils::redirect('/cobranca', 'Cobrança removida.');
    }

    public function tarefaCriar($caseId) {
        $payload = $this->sanitizeTask($_POST);
        $payload['case_id'] = (int)$caseId;
        $taskId = $this->tasks->create($payload);
        $this->maybeNotifyTask((int)$caseId, $taskId, $payload);
        Utils::redirect('/cobranca/ver/'.$caseId, 'Tarefa criada.');
    }

    public function tarefaExcluir($caseId, $taskId) {
        $this->tasks->delete((int)$taskId);
        Utils::redirect('/cobranca/ver/'.$caseId, 'Tarefa excluída.');
    }

    private function sanitizeCase(array $input): array {
        $valorTotal = Utils::decimalFromInput($input['valor_total'] ?? 0);
        $valorPendente = array_key_exists('valor_pendente', $input)
            ? Utils::decimalFromInput($input['valor_pendente'])
            : $valorTotal;
        $valorPendente = max(0, min($valorTotal, $valorPendente));

        return [
            'client_id' => (int)($input['client_id'] ?? 0),
            'responsavel_id' => !empty($input['responsavel_id']) ? (int)$input['responsavel_id'] : null,
            'origem' => $input['origem'] ?? 'manual',
            'origem_id' => !empty($input['origem_id']) ? (int)$input['origem_id'] : null,
            'titulo' => Utils::sanitize($input['titulo'] ?? ''),
            'valor_total' => $valorTotal,
            'valor_pendente' => $valorPendente,
            'status' => $input['status'] ?? 'aberto',
            'prioridade' => $input['prioridade'] ?? 'media',
            'proxima_acao_em' => $input['proxima_acao_em'] ?? null,
            'encerrado_em' => $input['encerrado_em'] ?? null,
            'observacoes' => $input['observacoes'] ?? null,
        ];
    }

    private function sanitizeTask(array $input): array {
        return [
            'responsavel_id' => !empty($input['responsavel_id']) ? (int)$input['responsavel_id'] : null,
            'titulo' => Utils::sanitize($input['titulo'] ?? ''),
            'descricao' => $input['descricao'] ?? null,
            'tipo' => $input['tipo'] ?? 'outro',
            'status' => $input['status'] ?? 'pendente',
            'due_at' => $input['due_at'] ?? null,
            'completed_at' => $input['completed_at'] ?? null,
            'lembrete_minutos' => !empty($input['lembrete_minutos']) ? (int)$input['lembrete_minutos'] : null,
        ];
    }

    private function maybeNotifyCase(int $caseId, array $payload): void {
        if (empty($payload['proxima_acao_em']) || empty($payload['responsavel_id'])) {
            return;
        }
        $trigger = new DateTime($payload['proxima_acao_em']);
        if ($trigger < new DateTime()) {
            return;
        }
        $this->notifications->schedule([
            'user_id' => (int)$payload['responsavel_id'],
            'resource_type' => 'billing_case',
            'resource_id' => $caseId,
            'title' => 'Follow-up de cobrança',
            'message' => 'Ação programada para "' . $payload['titulo'] . '".',
            'trigger_at' => $trigger->format('Y-m-d H:i:s'),
        ]);
    }

    private function maybeNotifyTask(int $caseId, int $taskId, array $payload): void {
        if (empty($payload['due_at']) || empty($payload['responsavel_id'])) {
            return;
        }
        $trigger = new DateTime($payload['due_at']);
        if (!empty($payload['lembrete_minutos'])) {
            $trigger = $trigger->modify('-' . (int)$payload['lembrete_minutos'] . ' minutes');
        }
        if ($trigger < new DateTime()) {
            return;
        }
        $case = $this->cases->find($caseId);
        $this->notifications->schedule([
            'user_id' => (int)$payload['responsavel_id'],
            'resource_type' => 'billing_task',
            'resource_id' => $taskId,
            'title' => 'Tarefa de cobrança: ' . $payload['titulo'],
            'message' => 'Lembrete para o caso "' . ($case['titulo'] ?? '#'.$caseId) . '".',
            'trigger_at' => $trigger->format('Y-m-d H:i:s'),
        ]);
    }

    private function prefillFromPayment($paymentId): ?array {
        $paymentId = (int)$paymentId;
        if ($paymentId <= 0) {
            return null;
        }
        $payment = $this->payments->getById($paymentId);
        if (!$payment) {
            return null;
        }

        $clienteRelacionado = $payment['project_client_id'] ?? null;

        return [
            'client_id' => $clienteRelacionado ? (int)$clienteRelacionado : 0,
            'origem' => 'payment',
            'origem_id' => $paymentId,
            'titulo' => $payment['description'] ?? 'Cobrança #' . $paymentId,
            'valor_total' => $payment['amount'] ?? 0,
            'valor_pendente' => $payment['amount'] ?? 0,
            'status' => 'aberto',
            'prioridade' => 'media',
            'observacoes' => $payment['project_name'] ?? null,
        ];
    }
}
