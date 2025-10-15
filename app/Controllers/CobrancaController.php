<?php
require_once __DIR__ . '/../Models/BillingCaseModel.php';
require_once __DIR__ . '/../Models/BillingTaskModel.php';
require_once __DIR__ . '/../Models/ClientModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/NotificationModel.php';
require_once __DIR__ . '/../Models/PaymentModel.php';
require_once __DIR__ . '/../Models/ProjectModel.php';
require_once __DIR__ . '/../Models/CollectionBoardModel.php';

class CobrancaController {
    private PDO $pdo;
    private BillingCaseModel $cases;
    private BillingTaskModel $tasks;
    private ClientModel $clients;
    private UserModel $users;
    private NotificationModel $notifications;
    private PaymentModel $payments;
    private ProjectModel $projects;
    private CollectionBoardModel $collectionBoard;

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
        $this->projects = new ProjectModel($pdo);
        $this->collectionBoard = new CollectionBoardModel($pdo);
    }

    public function index() {
        if ($this->isLegacyRequested()) {
            return $this->legacyIndex();
        }
        return $this->boardView();
    }

    private function boardView(): void {
        $filters = $this->parseBoardFilters($_GET);
        $board = $this->collectionBoard->fetchBoard($filters);

        $columns = $board['columns'] ?? [];
        $summary = $board['summary'] ?? ['total_amount' => 0, 'total_cards' => 0, 'due_today' => 0, 'due_today_amount' => 0, 'overdue_amount' => 0];
        $filterSelections = $board['filters_used'] ?? $filters;

        $responsaveis = $this->users->listActive();
        $columnMeta = $this->columnMeta();
        $lostReasons = $this->lostReasonOptions();
        $templates = $this->messageTemplates();
        $statusOptions = $this->statusFilterOptions();
        $orderOptions = $this->orderOptions();
        $projectsList = array_map(static function (array $project): array {
            return [
                'id' => (int)($project['id'] ?? 0),
                'name' => $project['name'] ?? '',
                'client_id' => isset($project['client_id']) ? (int)$project['client_id'] : null,
                'client_name' => $project['client_name'] ?? ($project['nome_cliente'] ?? ''),
            ];
        }, $this->projects->getAll(500, 0));
        $clientsList = array_map(static function (array $client): array {
            return [
                'id' => (int)($client['id'] ?? 0),
                'name' => $client['name'] ?? '',
                'email' => $client['email'] ?? null,
                'phone' => $client['phone'] ?? null,
            ];
        }, $this->clients->getAll(500, 0));

        $title = 'Gestão de Cobranças';
        ob_start();
        include __DIR__ . '/../Views/cobranca/kanban.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    private function isLegacyRequested(): bool {
        return isset($_GET['modo']) && $_GET['modo'] === 'legacy';
    }

    private function legacyIndex(): void {
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

        $title = 'Cobranças (legado)';
        ob_start();
        include __DIR__ . '/../Views/cobranca/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function boardData() {
        $filters = $this->parseBoardFilters($_GET);
        $board = $this->collectionBoard->fetchBoard($filters);
        $this->jsonResponse([
            'columns' => $board['columns'] ?? [],
            'summary' => $board['summary'] ?? [],
            'filters' => $board['filters_used'] ?? $filters,
        ]);
    }

    public function cards() {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'GET') {
            return $this->listCardsEndpoint();
        }
        if ($method === 'POST') {
            return $this->createCardEndpoint();
        }
        return $this->jsonError('Método não permitido.', 405);
    }

    public function card($id) {
        $paymentId = (int)$id;
        if ($paymentId <= 0) {
            return $this->jsonError('Cobrança inválida.', 404);
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        switch ($method) {
            case 'GET':
                return $this->showCardEndpoint($paymentId);
            case 'PUT':
            case 'PATCH':
                return $this->updateCardEndpoint($paymentId);
            case 'DELETE':
                return $this->deleteCardEndpoint($paymentId);
            default:
                return $this->jsonError('Método não permitido.', 405);
        }
    }

    private function listCardsEndpoint(): void {
        $status = $_GET['status'] ?? null;
        $filters = [];
        if ($status && in_array($status, $this->boardStatusOptions(), true)) {
            $filters['column'] = $status;
        }

        $board = $this->collectionBoard->fetchBoard($filters);
        $columns = $board['columns'] ?? [];
        if ($status) {
            $columns = isset($columns[$status]) ? [$status => $columns[$status]] : [];
        }

        $this->jsonResponse([
            'columns' => $columns,
            'summary' => $board['summary'] ?? [],
        ]);
    }

    private function createCardEndpoint(): void {
        try {
            $input = $this->requestData();
            $payload = $this->sanitizeCardPayload($input, false);
            $clientId = $this->ensureClientForPayload($payload, null);

            $paymentData = [
                'project_id' => $payload['payment']['project_id'] ?? null,
                'client_id' => $clientId,
                'kind' => 'one_time',
                'amount' => $payload['payment']['amount'],
                'currency' => $payload['payment']['currency'] ?? 'BRL',
                'transaction_type' => 'receita',
                'description' => $payload['payment']['description'] ?? null,
                'category' => $payload['payment']['category'] ?? null,
                'notes' => $payload['payment']['notes'] ?? null,
                'due_date' => $payload['payment']['due_date'],
                'paid_at' => null,
                'status' => $this->mapBoardStatusToFinance($payload['status'] ?? null),
            ];

            $paymentId = $this->payments->create($paymentData);
            if (!$paymentId) {
                throw new RuntimeException('Falha ao criar a cobrança.');
            }

            $this->collectionBoard->ensureCard($paymentId, $this->currentUserId());
            $options = [
                'reason_code' => 'create_manual',
                'notes' => $payload['movement_notes'] ?? null,
            ];
            if (($payload['status'] ?? '') === 'perdido') {
                $options['lost_reason'] = $payload['lost_reason'] ?? null;
                $options['lost_details'] = $payload['lost_details'] ?? null;
            }
            $this->collectionBoard->updateManualStatus($paymentId, $payload['status'], $options, $this->currentUserId());

            $card = $this->collectionBoard->getCardDetails($paymentId);
            $this->jsonResponse([
                'success' => true,
                'card' => $card,
            ], 201);
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 422);
        } catch (Throwable $e) {
            $this->jsonError('Não foi possível criar a cobrança.', 500);
        }
    }

    private function showCardEndpoint(int $paymentId): void {
        $this->detalhes($paymentId);
    }

    private function updateCardEndpoint(int $paymentId): void {
        $existing = $this->payments->getById($paymentId);
        if (!$existing) {
            $this->jsonError('Cobrança não encontrada.', 404);
        }

        try {
            $input = $this->requestData();
            $payload = $this->sanitizeCardPayload($input, true);
            $existingClientId = $existing['client_id'] ?? ($existing['project_client_id'] ?? null);
            $clientId = $this->ensureClientForPayload($payload, $existingClientId);

            $paymentData = [
                'project_id' => array_key_exists('project_id', $payload['payment'])
                    ? $payload['payment']['project_id']
                    : ($existing['project_id'] ?? null),
                'client_id' => $clientId,
                'kind' => $existing['kind'] ?? 'one_time',
                'amount' => $payload['payment']['amount'] ?? ($existing['amount'] ?? 0),
                'currency' => $payload['payment']['currency'] ?? ($existing['currency'] ?? 'BRL'),
                'transaction_type' => $existing['transaction_type'] ?? 'receita',
                'description' => array_key_exists('description', $payload['payment'])
                    ? $payload['payment']['description']
                    : ($existing['description'] ?? null),
                'category' => array_key_exists('category', $payload['payment'])
                    ? $payload['payment']['category']
                    : ($existing['category'] ?? null),
                'notes' => array_key_exists('notes', $payload['payment'])
                    ? $payload['payment']['notes']
                    : ($existing['notes'] ?? null),
                'due_date' => $payload['payment']['due_date'] ?? ($existing['due_date'] ?? null),
                'paid_at' => $existing['paid_at'] ?? null,
                'status_id' => $existing['status_id'] ?? null,
            ];

            if (!empty($payload['status'])) {
                $paymentData['status'] = $this->mapBoardStatusToFinance($payload['status']);
            }

            $this->payments->update($paymentId, $paymentData);

            if (!empty($payload['status'])) {
                $options = [
                    'reason_code' => $payload['status'] === 'perdido' ? 'lost_manual' : 'manual_update',
                    'notes' => $payload['movement_notes'] ?? null,
                ];
                if ($payload['status'] === 'perdido') {
                    $options['lost_reason'] = $payload['lost_reason'] ?? null;
                    $options['lost_details'] = $payload['lost_details'] ?? null;
                    if (empty($options['lost_reason'])) {
                        throw new InvalidArgumentException('Selecione um motivo para a perda.');
                    }
                }
                $this->collectionBoard->updateManualStatus($paymentId, $payload['status'], $options, $this->currentUserId());
            }

            $card = $this->collectionBoard->getCardDetails($paymentId);
            $contacts = $this->collectionBoard->listContacts($paymentId);
            $movements = $this->collectionBoard->listMovements($paymentId);

            $this->jsonResponse([
                'success' => true,
                'card' => $card,
                'contacts' => $contacts,
                'movements' => $movements,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 422);
        } catch (Throwable $e) {
            $this->jsonError('Não foi possível atualizar a cobrança.', 500);
        }
    }

    private function deleteCardEndpoint(int $paymentId): void {
        if (!$this->payments->getById($paymentId)) {
            $this->jsonError('Cobrança não encontrada.', 404);
        }

        try {
            $this->payments->delete($paymentId);
            $this->jsonResponse(['success' => true]);
        } catch (Throwable $e) {
            $this->jsonError('Não foi possível remover a cobrança.', 500);
        }
    }

    public function move() {
        $this->requirePost();
        $input = $this->requestData();
        $paymentId = (int)($input['payment_id'] ?? 0);
        $targetStatus = $input['to_status'] ?? '';

        if ($paymentId <= 0 || !$targetStatus) {
            return $this->jsonError('Dados inválidos para movimentação.', 422);
        }

        $options = [
            'reason_code' => $input['reason_code'] ?? null,
            'notes' => $input['notes'] ?? null,
        ];

        if ($targetStatus === 'perdido') {
            $options['lost_reason'] = $input['lost_reason'] ?? null;
            $options['lost_details'] = $input['lost_details'] ?? null;
            if (empty($options['lost_reason'])) {
                return $this->jsonError('Selecione um motivo para a perda.', 422);
            }
        }

        try {
            $this->collectionBoard->updateManualStatus($paymentId, $targetStatus, $options, $this->currentUserId());
            $card = $this->collectionBoard->getCardDetails($paymentId);
            $contacts = $this->collectionBoard->listContacts($paymentId);
            $movements = $this->collectionBoard->listMovements($paymentId);
            $this->jsonResponse([
                'success' => true,
                'card' => $card,
                'contacts' => $contacts,
                'movements' => $movements,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->jsonError($e->getMessage(), 422);
        } catch (Throwable $e) {
            $this->jsonError('Não foi possível mover a cobrança.', 500);
        }
    }

    public function registrarContato() {
        $this->requirePost();
        $input = $this->requestData();
        $paymentId = (int)($input['payment_id'] ?? 0);
        $contactType = $input['contact_type'] ?? '';

        if ($paymentId <= 0 || !$contactType) {
            return $this->jsonError('Dados do contato inválidos.', 422);
        }

        $allowedTypes = ['email','whatsapp','sms','ligacao','outro'];
        if (!in_array($contactType, $allowedTypes, true)) {
            return $this->jsonError('Tipo de contato inválido.', 422);
        }

        $payload = [
            'contact_type' => $contactType,
            'contacted_at' => $this->normalizeDateTimeInput($input['contacted_at'] ?? null),
            'client_response' => $input['client_response'] ?? null,
            'expected_payment_at' => $this->normalizeDateInput($input['expected_payment_at'] ?? null),
            'notes' => $input['notes'] ?? null,
            'is_reminder' => 0,
            'auto_move_to' => 'em_cobranca',
        ];

        try {
            $contact = $this->collectionBoard->addContact($paymentId, $payload, $this->currentUserId());
            $card = $this->collectionBoard->getCardDetails($paymentId);
            $contacts = $this->collectionBoard->listContacts($paymentId);
            $movements = $this->collectionBoard->listMovements($paymentId);
            $this->jsonResponse([
                'success' => true,
                'contact' => $contact,
                'card' => $card,
                'contacts' => $contacts,
                'movements' => $movements,
            ]);
        } catch (Throwable $e) {
            $this->jsonError('Não foi possível registrar o contato.', 500);
        }
    }

    public function enviarLembrete() {
        $this->requirePost();
        $input = $this->requestData();
        $paymentId = (int)($input['payment_id'] ?? 0);
        $channel = $input['channel'] ?? '';
        if ($paymentId <= 0 || !$channel) {
            return $this->jsonError('Dados do lembrete inválidos.', 422);
        }

        $allowedChannels = ['email','whatsapp','sms'];
        if (!in_array($channel, $allowedChannels, true)) {
            return $this->jsonError('Canal de lembrete inválido.', 422);
        }

        $payload = [
            'contact_type' => $channel,
            'contacted_at' => $this->normalizeDateTimeInput($input['scheduled_at'] ?? null) ?? (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'client_response' => $input['client_response'] ?? null,
            'expected_payment_at' => $this->normalizeDateInput($input['expected_payment_at'] ?? null),
            'notes' => $input['notes'] ?? null,
            'is_reminder' => 1,
        ];

        try {
            $contact = $this->collectionBoard->addContact($paymentId, $payload, $this->currentUserId());
            $card = $this->collectionBoard->getCardDetails($paymentId);
            $contacts = $this->collectionBoard->listContacts($paymentId);
            $movements = $this->collectionBoard->listMovements($paymentId);
            $this->jsonResponse([
                'success' => true,
                'contact' => $contact,
                'card' => $card,
                'contacts' => $contacts,
                'movements' => $movements,
            ]);
        } catch (Throwable $e) {
            $this->jsonError('Não foi possível registrar o lembrete.', 500);
        }
    }

    public function detalhes($paymentId) {
        $paymentId = (int)$paymentId;
        if ($paymentId <= 0) {
            return $this->jsonError('Cobrança inválida.', 404);
        }

        $card = $this->collectionBoard->getCardDetails($paymentId);
        if (!$card) {
            return $this->jsonError('Cobrança não encontrada ou já concluída.', 404);
        }

        $payment = $this->payments->getById($paymentId);
        $client = null;
        if (!empty($payment['client_id'])) {
            $client = $this->clients->getById((int)$payment['client_id']);
        } elseif (!empty($payment['project_client_id'])) {
            $client = $this->clients->getById((int)$payment['project_client_id']);
        }

        $contacts = $this->collectionBoard->listContacts($paymentId);
        $movements = $this->collectionBoard->listMovements($paymentId);

        $this->jsonResponse([
            'card' => $card,
            'payment' => $payment,
            'client' => $client,
            'contacts' => $contacts,
            'movements' => $movements,
        ]);
    }

    private function sanitizeCardPayload(array $input, bool $isUpdate = false): array {
        $payload = ['payment' => []];
        $allowedStatuses = $this->boardStatusOptions();

        if (!$isUpdate || array_key_exists('status', $input)) {
            $status = trim((string)($input['status'] ?? ''));
            if ($status === '' || !in_array($status, $allowedStatuses, true)) {
                throw new InvalidArgumentException('Status inválido.');
            }
            $payload['status'] = $status;
        }

        if (!$isUpdate || array_key_exists('amount', $input)) {
            $amount = Utils::decimalFromInput($input['amount'] ?? null);
            if ($amount === null || $amount <= 0) {
                throw new InvalidArgumentException('Informe um valor válido.');
            }
            $payload['payment']['amount'] = $amount;
        }

        if (!$isUpdate || array_key_exists('due_date', $input)) {
            $dueDate = $this->normalizeDateInput($input['due_date'] ?? null);
            if (!$dueDate) {
                throw new InvalidArgumentException('Informe uma data de vencimento válida.');
            }
            $payload['payment']['due_date'] = $dueDate;
        }

        if (array_key_exists('currency', $input) || !$isUpdate) {
            $currency = strtoupper(trim((string)($input['currency'] ?? 'BRL')));
            if (!in_array($currency, ['BRL','USD'], true)) {
                $currency = 'BRL';
            }
            $payload['payment']['currency'] = $currency;
        }

        if (array_key_exists('project_id', $input)) {
            $payload['payment']['project_id'] = $input['project_id'] !== '' && $input['project_id'] !== null
                ? (int)$input['project_id']
                : null;
        }

        if (array_key_exists('client_id', $input)) {
            $payload['payment']['client_id'] = $input['client_id'] !== '' && $input['client_id'] !== null
                ? (int)$input['client_id']
                : null;
        }

        foreach (['description', 'category'] as $field) {
            if (array_key_exists($field, $input)) {
                $value = Utils::sanitize($input[$field] ?? '');
                $payload['payment'][$field] = $value !== '' ? $value : null;
            }
        }

        if (array_key_exists('notes', $input)) {
            $notesValue = trim((string)$input['notes']);
            $payload['payment']['notes'] = $notesValue !== '' ? $notesValue : null;
        }

        $clientName = isset($input['client_name']) ? trim((string)$input['client_name']) : '';
        $clientEmail = isset($input['client_email']) ? trim((string)$input['client_email']) : '';
        $clientPhone = isset($input['client_phone']) ? trim((string)$input['client_phone']) : '';
        if ($clientName !== '' || $clientEmail !== '' || $clientPhone !== '') {
            $payload['client'] = [
                'name' => Utils::sanitize($clientName),
                'email' => Utils::sanitize($clientEmail),
                'phone' => Utils::sanitize($clientPhone),
            ];
        }

        if (isset($payload['status']) && $payload['status'] === 'perdido') {
            $lostReason = $input['lost_reason'] ?? null;
            if (!$isUpdate && empty($lostReason)) {
                throw new InvalidArgumentException('Selecione um motivo para a perda.');
            }
            if ($lostReason !== null) {
                $payload['lost_reason'] = $lostReason;
            }
            if (array_key_exists('lost_details', $input)) {
                $details = trim((string)$input['lost_details']);
                $payload['lost_details'] = $details !== '' ? $details : null;
            }
        }

        if (array_key_exists('movement_notes', $input)) {
            $movementNotes = trim((string)$input['movement_notes']);
            $payload['movement_notes'] = $movementNotes !== '' ? $movementNotes : null;
        }

        return $payload;
    }

    private function ensureClientForPayload(array $payload, ?int $fallbackId): int {
        $clientId = $payload['payment']['client_id'] ?? null;
        if ($clientId) {
            return (int)$clientId;
        }
        if ($fallbackId) {
            return (int)$fallbackId;
        }

        $clientData = $payload['client'] ?? null;
        if (!$clientData || empty($clientData['name'])) {
            throw new InvalidArgumentException('Selecione ou informe um cliente.');
        }

        $clientPayload = [
            'name' => $clientData['name'],
            'email' => $clientData['email'] ?? null,
            'phone' => $clientData['phone'] ?? null,
            'entry_date' => date('Y-m-d'),
            'notes' => null,
        ];

        $newId = $this->clients->create($clientPayload);
        if (!$newId) {
            throw new RuntimeException('Não foi possível criar o cliente.');
        }
        return (int)$newId;
    }

    private function boardStatusOptions(): array {
        return array_keys($this->columnMeta());
    }

    private function mapBoardStatusToFinance(?string $status): ?string {
        if ($status === null) {
            return null;
        }
        $map = [
            'a_vencer' => 'A Receber',
            'vencendo' => 'Pendente',
            'vencido' => 'Em Atraso',
            'em_cobranca' => 'Em Cobrança',
            'perdido' => 'Perdido',
        ];
        return $map[$status] ?? null;
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

    private function parseBoardFilters(array $source): array {
        $filters = [];
        $raw = [];
        $columnOptions = array_keys($this->columnMeta());

        $column = trim((string)($source['column'] ?? $source['status'] ?? ''));
        if ($column !== '' && in_array($column, $columnOptions, true)) {
            $filters['column'] = $column;
        }
        $raw['column'] = $column;

        $valueMinInput = trim((string)($source['value_min'] ?? ''));
        if ($valueMinInput !== '') {
            $filters['value_min'] = Utils::decimalFromInput($valueMinInput);
        }
        $raw['value_min'] = $valueMinInput;

        $valueMaxInput = trim((string)($source['value_max'] ?? ''));
        if ($valueMaxInput !== '') {
            $filters['value_max'] = Utils::decimalFromInput($valueMaxInput);
        }
        $raw['value_max'] = $valueMaxInput;

        $clientFilter = trim((string)($source['client'] ?? ''));
        if ($clientFilter !== '') {
            $filters['client'] = $clientFilter;
        }
        $raw['client'] = $clientFilter;

        $projectFilter = trim((string)($source['project'] ?? ''));
        if ($projectFilter !== '') {
            $filters['project'] = $projectFilter;
        }
        $raw['project'] = $projectFilter;

        $responsavel = (int)($source['responsavel_id'] ?? 0);
        if ($responsavel > 0) {
            $filters['responsavel_id'] = $responsavel;
        }
        $raw['responsavel_id'] = $responsavel > 0 ? $responsavel : '';

        $dueFromInput = trim((string)($source['due_from'] ?? ''));
        $dueFrom = $this->normalizeDateInput($dueFromInput);
        if ($dueFrom) {
            $filters['due_from'] = $dueFrom;
        }
        $raw['due_from'] = $dueFromInput;

        $dueToInput = trim((string)($source['due_to'] ?? ''));
        $dueTo = $this->normalizeDateInput($dueToInput);
        if ($dueTo) {
            $filters['due_to'] = $dueTo;
        }
        $raw['due_to'] = $dueToInput;

        $search = trim((string)($source['search'] ?? $source['buscar'] ?? ''));
        if ($search !== '') {
            $filters['search'] = $search;
        }
        $raw['search'] = $search;

        $orderOptions = array_column($this->orderOptions(), 'value');
        $orderBy = strtolower(trim((string)($source['order'] ?? '')));
        if (!in_array($orderBy, $orderOptions, true)) {
            $orderBy = 'due_date';
        }
        $filters['order_by'] = $orderBy;
        $raw['order_by'] = $orderBy;

        $orderDir = strtolower(trim((string)($source['direction'] ?? '')));
        if (!in_array($orderDir, ['asc','desc'], true)) {
            $orderDir = 'asc';
        }
        $filters['order_dir'] = $orderDir;
        $raw['order_dir'] = $orderDir;

        $filters['raw'] = $raw;
        return $filters;
    }

    private function statusFilterOptions(): array {
        return [
            ['value' => '', 'label' => 'Todos'],
            ['value' => 'a_vencer', 'label' => 'A vencer'],
            ['value' => 'vencendo', 'label' => 'Vencendo'],
            ['value' => 'vencido', 'label' => 'Vencido'],
            ['value' => 'em_cobranca', 'label' => 'Em cobrança'],
            ['value' => 'perdido', 'label' => 'Perdido'],
        ];
    }

    private function orderOptions(): array {
        return [
            ['value' => 'due_date', 'label' => 'Vencimento'],
            ['value' => 'amount', 'label' => 'Valor'],
            ['value' => 'days_overdue', 'label' => 'Dias em atraso'],
            ['value' => 'client_name', 'label' => 'Cliente (A-Z)'],
        ];
    }

    private function columnMeta(): array {
        return [
            'a_vencer' => [
                'title' => 'A VENCER',
                'header_bg' => '#D1FAE5',
                'header_text' => '#065F46',
                'accent' => '#10B981',
                'card_bg' => '#FFFFFF',
                'card_text' => '#111827',
            ],
            'vencendo' => [
                'title' => 'VENCENDO',
                'header_bg' => '#FEF3C7',
                'header_text' => '#92400E',
                'accent' => '#F59E0B',
                'card_bg' => '#FFFFFF',
                'card_text' => '#111827',
            ],
            'vencido' => [
                'title' => 'VENCIDO',
                'header_bg' => '#FEE2E2',
                'header_text' => '#991B1B',
                'accent' => '#EF4444',
                'card_bg' => '#FFFFFF',
                'card_text' => '#111827',
            ],
            'em_cobranca' => [
                'title' => 'EM COBRANÇA',
                'header_bg' => '#FFEDD5',
                'header_text' => '#9A3412',
                'accent' => '#F97316',
                'card_bg' => '#FFFFFF',
                'card_text' => '#111827',
            ],
            'perdido' => [
                'title' => 'PERDIDO',
                'header_bg' => '#E5E7EB',
                'header_text' => '#374151',
                'accent' => '#6B7280',
                'card_bg' => '#F9FAFB',
                'card_text' => '#4B5563',
                'card_opacity' => 0.7,
            ],
        ];
    }

    private function lostReasonOptions(): array {
        return [
            ['value' => 'cliente_nao_responde', 'label' => 'Cliente não responde'],
            ['value' => 'cliente_recusa', 'label' => 'Cliente se recusa a pagar'],
            ['value' => 'empresa_fechou', 'label' => 'Empresa fechou'],
            ['value' => 'valor_nao_compensa', 'label' => 'Valor não compensa ação judicial'],
            ['value' => 'outros', 'label' => 'Outros'],
        ];
    }

    private function messageTemplates(): array {
        return [
            [
                'key' => 'gentle_reminder',
                'label' => 'Lembrete educado (3 dias antes)',
                'body' => "Olá [NOME], tudo bem?\n\nPassando para lembrar que o pagamento do projeto [PROJETO] no valor de R$ [VALOR] vence em 3 dias (dia [DATA]).\n\nCaso já tenha realizado, desconsidere esta mensagem.\n\nQualquer dúvida, estou à disposição!",
            ],
            [
                'key' => 'due_today',
                'label' => 'Lembrete no vencimento',
                'body' => "Olá [NOME],\n\nO pagamento do projeto [PROJETO] vence hoje. Valor: R$ [VALOR]\n\nPode confirmar o pagamento?\n\nObrigado!",
            ],
            [
                'key' => 'overdue_charge',
                'label' => 'Cobrança (vencido)',
                'body' => "Olá [NOME],\n\nNotei que o pagamento do projeto [PROJETO] está vencido há [DIAS] dias. Valor: R$ [VALOR]\n\nPoderia me atualizar sobre o pagamento?\n\nAguardo seu retorno.",
            ],
        ];
    }

    private function jsonResponse(array $payload, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }

    private function jsonError(string $message, int $status = 400, array $extra = []): void {
        $this->jsonResponse(array_merge(['success' => false, 'message' => $message], $extra), $status);
    }

    private function requirePost(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->jsonError('Método não permitido.', 405);
        }
    }

    private function currentUserId(): int {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    private function requestData(): array {
        if (!empty($_POST)) {
            return $_POST;
        }
        $raw = file_get_contents('php://input');
        if (!$raw) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeDateInput(?string $value): ?string {
        if (!$value) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $formats = ['Y-m-d', 'd/m/Y'];
        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            if ($dt instanceof DateTimeImmutable) {
                return $dt->format('Y-m-d');
            }
        }
        return null;
    }

    private function normalizeDateTimeInput(?string $value): ?string {
        if (!$value) {
            return null;
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $formats = ['Y-m-d H:i', 'Y-m-d H:i:s', 'd/m/Y H:i', 'd/m/Y H:i:s'];
        foreach ($formats as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            if ($dt instanceof DateTimeImmutable) {
                return $dt->format('Y-m-d H:i:s');
            }
        }
        $dateOnly = DateTimeImmutable::createFromFormat('d/m/Y', $value);
        if ($dateOnly instanceof DateTimeImmutable) {
            return $dateOnly->setTime(9, 0)->format('Y-m-d H:i:s');
        }
        $isoDate = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($isoDate instanceof DateTimeImmutable) {
            return $isoDate->setTime(9, 0)->format('Y-m-d H:i:s');
        }
        return null;
    }
}
