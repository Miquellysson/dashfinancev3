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
        if (empty($_SESSION['user_id'])) {
            Utils::redirect('/auth/login');
        }
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
            Utils::redirect('/cobranca/ver/'.$id, 'Caso de cobrança criado com sucesso!');
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
            Utils::redirect('/cobranca', 'Registro não encontrado.');
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
            Utils::redirect('/cobranca', 'Registro não encontrado.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = $this->sanitizeCase($_POST);
            $this->cases->update($id, $payload);
            $this->maybeNotifyCase($id, $payload);
            Utils::redirect('/cobranca/ver/'.$id, 'Cobrança atualizada com sucesso!');
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
        Utils::redirect('/cobranca', 'Registro removido.');
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
        Utils::redirect('/cobranca/ver/'.$caseId, 'Tarefa removida.');
    }

    private function sanitizeCase(array $data): array {
        $valorTotal = Utils::decimalFromInput($data['valor_total'] ?? 0);
        $valorPendente = array_key_exists('valor_pendente', $data)
            ? Utils::decimalFromInput($data['valor_pendente'])
            : $valorTotal;
        $valorPendente = max(0, min($valorTotal, $valorPendente));

        return [
            'client_id' => (int)($data['client_id'] ?? 0),
            'responsavel_id' => !empty($data['responsavel_id']) ? (int)$data['responsavel_id'] : null,
            'origem' => $data['origem'] ?? 'manual',
            'origem_id' => !empty($data['origem_id']) ? (int)$data['origem_id'] : null,
            'titulo' => Utils::sanitize($data['titulo'] ?? ''),
            'valor_total' => $valorTotal,
            'valor_pendente' => $valorPendente,
            'status' => $data['status'] ?? 'aberto',
            'prioridade' => $data['prioridade'] ?? 'media',
            'proxima_acao_em' => $data['proxima_acao_em'] ?? null,
            'encerrado_em' => $data['encerrado_em'] ?? null,
            'observacoes' => $data['observacoes'] ?? null,
        ];
    }

    private function sanitizeTask(array $data): array {
        return [
            'responsavel_id' => !empty($data['responsavel_id']) ? (int)$data['responsavel_id'] : null,
            'titulo' => Utils::sanitize($data['titulo'] ?? ''),
            'descricao' => $data['descricao'] ?? null,
            'tipo' => $data['tipo'] ?? 'outro',
            'status' => $data['status'] ?? 'pendente',
            'due_at' => $data['due_at'] ?? null,
            'completed_at' => $data['completed_at'] ?? null,
            'lembrete_minutos' => !empty($data['lembrete_minutos']) ? (int)$data['lembrete_minutos'] : null,
        ];
    }

    private function maybeNotifyCase(int $caseId, array $payload): void {
        if (empty($payload['proxima_acao_em']) || empty($payload['responsavel_id'])) {
            return;
        }
        $titulo = 'Follow-up de cobrança agendado';
        $mensagem = 'Há uma ação programada para o caso "' . $payload['titulo'] . '".';
        $trigger = new DateTime($payload['proxima_acao_em']);
        if ($trigger < new DateTime()) {
            return;
        }
        $this->notifications->schedule([
            'user_id' => (int)$payload['responsavel_id'],
            'resource_type' => 'billing_case',
            'resource_id' => $caseId,
            'title' => $titulo,
            'message' => $mensagem,
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
        $titulo = 'Tarefa de cobrança: ' . $payload['titulo'];
        $mensagem = 'Lembrete para o caso "' . ($case['titulo'] ?? '#'.$caseId) . '".';
        $this->notifications->schedule([
            'user_id' => (int)$payload['responsavel_id'],
            'resource_type' => 'billing_task',
            'resource_id' => $taskId,
            'title' => $titulo,
            'message' => $mensagem,
            'trigger_at' => $trigger->format('Y-m-d H:i:s'),
        ]);
    }
}
