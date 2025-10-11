<?php

require_once __DIR__ . '/../Models/ProjectModel.php';
require_once __DIR__ . '/../Models/ProjectActivityModel.php';
require_once __DIR__ . '/../Models/ClientModel.php';
require_once __DIR__ . '/../Models/UserModel.php';

class ProjetoController {
    private PDO $pdo;
    private ProjectModel $projects;
    private ProjectActivityModel $activities;
    private ClientModel $clients;
    private UserModel $users;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->projects = new ProjectModel($pdo);
        $this->activities = new ProjectActivityModel($pdo);
        $this->clients = new ClientModel($pdo);
        $this->users = new UserModel($pdo);
    }

    public function index() {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $filters = [
            'status_pagamento' => $_GET['status_pagamento'] ?? null,
            'tipo_servico' => $_GET['tipo_servico'] ?? null,
            'status_satisfacao' => $_GET['status_satisfacao'] ?? null,
            'data_inicio' => $_GET['data_inicio'] ?? null,
            'data_fim' => $_GET['data_fim'] ?? null,
            'busca' => $_GET['busca'] ?? null,
            'responsavel_id' => $_GET['responsavel_id'] ?? null,
        ];

        $orderBy = $_GET['order_by'] ?? 'default';
        $orderDir = $_GET['order_dir'] ?? 'DESC';

        $projects = $this->projects->paginate($filters, $limit, $offset, $orderBy, $orderDir);
        $total = $this->projects->countWithFilters($filters);
        $totalPages = (int)ceil($total / $limit);

        $responsaveis = $this->users->listActive();

        $summary = $this->projects->getFinancialSummary($filters);
        $paymentBreakdown = $this->projects->getPaymentBreakdown($filters);
        $revenueByService = $this->projects->getRevenueByService($filters);
        $evolution = $this->projects->getMonthlyEvolution(12);

        $title = 'Projetos';
        ob_start();
        include __DIR__ . '/../Views/projetos/list.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function dashboard() {
        $filters = [];
        $summary = $this->projects->getFinancialSummary([]);
        $paymentBreakdown = $this->projects->getPaymentBreakdown([]);
        $revenueByService = $this->projects->getRevenueByService([]);
        $evolution = $this->projects->getMonthlyEvolution(12);

        $title = 'Dashboard Financeiro';
        ob_start();
        include __DIR__ . '/../Views/projetos/dashboard.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function create() {
        $clients = $this->clients->getAll();
        $responsaveis = $this->users->listActive();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->sanitizeProjectPayload($_POST);
            $errors = $this->validateProject($data);

            if (!empty($errors)) {
                $this->renderForm($data, $clients, $responsaveis, $errors);
                return;
            }

            try {
                $this->pdo->beginTransaction();
                $this->projects->create($data);
                $this->pdo->commit();
                Utils::redirect('/projeto', 'Projeto criado com sucesso!');
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                $errors['general'] = 'Erro ao criar projeto: ' . $e->getMessage();
                $this->renderForm($data, $clients, $responsaveis, $errors);
            }
            return;
        }

        $this->renderForm(null, $clients, $responsaveis, []);
    }

    public function edit($id) {
        $id = (int)$id;
        $project = $this->projects->find($id);
        if (!$project) {
            Utils::redirect('/projeto', 'Projeto não encontrado.');
        }

        $clients = $this->clients->getAll();
        $responsaveis = $this->users->listActive();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->sanitizeProjectPayload($_POST);
            $errors = $this->validateProject($data, $id);

            if (!empty($errors)) {
                $project = array_merge($project, $data);
                $this->renderForm($project, $clients, $responsaveis, $errors);
                return;
            }

            try {
                $this->pdo->beginTransaction();
                $this->projects->update($id, $data);
                $this->pdo->commit();
                Utils::redirect('/projeto', 'Projeto atualizado com sucesso!');
            } catch (Throwable $e) {
                $this->pdo->rollBack();
                $project = array_merge($project, $data);
                $errors['general'] = 'Erro ao atualizar projeto: ' . $e->getMessage();
                $this->renderForm($project, $clients, $responsaveis, $errors);
            }
            return;
        }

        $this->renderForm($project, $clients, $responsaveis, []);
    }

    public function show($id) {
        $id = (int)$id;
        $project = $this->projects->find($id);
        if (!$project) {
            Utils::redirect('/projeto', 'Projeto não encontrado.');
        }

        $metrics = $this->activities->getMetrics($id);
        $activities = $this->activities->listByProject($id, []);
        $responsaveis = $this->users->listActive();

        $title = 'Detalhes do Projeto';
        ob_start();
        include __DIR__ . '/../Views/projetos/show.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function delete($id) {
        Auth::requireAdmin();
        $id = (int)$id;
        if ($this->projects->softDelete($id)) {
            Utils::redirect('/projeto', 'Projeto enviado para a lixeira.');
        }
        Utils::redirect('/projeto', 'Não foi possível remover o projeto.');
    }

    public function atividades($id) {
        $id = (int)$id;
        $project = $this->projects->find($id);
        if (!$project) {
            Utils::redirect('/projeto', 'Projeto não encontrado.');
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = $this->sanitizeActivityPayload($_POST);
            $payload['projeto_id'] = $id;
            $errors = $this->validateActivity($payload);
            if (!empty($errors)) {
                $activities = $this->activities->listByProject($id, []);
                $metrics = $this->activities->getMetrics($id);
                $responsaveis = $this->users->listActive();
                include __DIR__ . '/../Views/projetos/atividades.php';
                return;
            }

            $this->activities->create($payload);
            Utils::redirect("/projeto/atividades/{$id}", 'Atividade criada com sucesso!');
        }

        $filters = [
            'status' => $_GET['status'] ?? null,
            'responsavel_id' => $_GET['responsavel_id'] ?? null,
        ];
        $activities = $this->activities->listByProject($id, $filters);
        $metrics = $this->activities->getMetrics($id);
        $responsaveis = $this->users->listActive();

        $title = 'Atividades do Projeto';
        ob_start();
        include __DIR__ . '/../Views/projetos/atividades.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    public function atividadeEditar($atividadeId) {
        $atividadeId = (int)$atividadeId;
        $activity = $this->activities->find($atividadeId);
        if (!$activity) {
            Utils::redirect('/projeto', 'Atividade não encontrada.');
        }

        $project = $this->projects->find((int)$activity['projeto_id']);
        if (!$project) {
            Utils::redirect('/projeto', 'Projeto não encontrado.');
        }

        $responsaveis = $this->users->listActive();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payload = $this->sanitizeActivityPayload($_POST);
            $payload['projeto_id'] = (int)$activity['projeto_id'];
            $errors = $this->validateActivity($payload);
            if (!empty($errors)) {
                include __DIR__ . '/../Views/projetos/atividade_form.php';
                return;
            }

            $this->activities->update($atividadeId, $payload);
            Utils::redirect("/projeto/atividades/{$project['id']}", 'Atividade atualizada com sucesso!');
        }

        include __DIR__ . '/../Views/projetos/atividade_form.php';
    }

    public function atividadeExcluir($atividadeId) {
        Auth::requireAdmin();
        $activity = $this->activities->find((int)$atividadeId);
        if (!$activity) {
            Utils::redirect('/projeto', 'Atividade não encontrada.');
        }
        $this->activities->softDelete((int)$atividadeId);
        Utils::redirect("/projeto/atividades/{$activity['projeto_id']}", 'Atividade removida.');
    }

    private function sanitizeProjectPayload(array $input): array {
        $nomeCliente = Utils::sanitize($input['nome_cliente'] ?? '');
        $titulo = Utils::sanitize($input['name'] ?? ($input['titulo'] ?? ''));
        $status = $input['status'] ?? 'ativo';
        if (!in_array($status, ['ativo','pausado','concluido','cancelado'], true)) {
            $status = 'ativo';
        }

        $tipoServico = $input['tipo_servico'] ?? 'Desenvolvimento Web';
        if (!in_array($tipoServico, ProjectModel::SERVICE_TYPES, true)) {
            $tipoServico = 'Desenvolvimento Web';
        }

        $statusSatisfacao = $input['status_satisfacao'] ?? 'Aguardando Feedback';
        if (!in_array($statusSatisfacao, ProjectModel::SATISFACTION_STATUS, true)) {
            $statusSatisfacao = 'Aguardando Feedback';
        }

        $statusPagamento = $input['status_pagamento'] ?? 'Pendente';
        if (!in_array($statusPagamento, ProjectModel::PAYMENT_STATUS, true)) {
            $statusPagamento = 'Pendente';
        }

        return [
            'client_id' => isset($input['client_id']) && $input['client_id'] !== '' ? (int)$input['client_id'] : null,
            'nome_cliente' => $nomeCliente,
            'name' => $titulo,
            'data_entrada' => $input['data_entrada'] ?? null,
            'tipo_servico' => $tipoServico,
            'status_satisfacao' => $statusSatisfacao,
            'status' => $status,
            'valor_projeto' => Utils::decimalFromInput($input['valor_projeto'] ?? 0),
            'status_pagamento' => $statusPagamento,
            'valor_pago' => Utils::decimalFromInput($input['valor_pago'] ?? 0),
            'observacoes' => isset($input['observacoes']) ? Utils::sanitize($input['observacoes']) : null,
            'usuario_responsavel_id' => isset($input['usuario_responsavel_id']) && $input['usuario_responsavel_id'] !== '' ? (int)$input['usuario_responsavel_id'] : null,
        ];
    }

    private function validateProject(array $data, ?int $projectId = null): array {
        $errors = [];
        if (mb_strlen(trim((string)$data['nome_cliente'])) < 3) {
            $errors['nome_cliente'] = 'Informe o nome do cliente (mínimo 3 caracteres).';
        }
        if (empty($data['data_entrada'])) {
            $errors['data_entrada'] = 'Data de entrada é obrigatória.';
        }
        $valorProjeto = Utils::decimalFromInput($data['valor_projeto'] ?? 0);
        $data['valor_projeto'] = $valorProjeto;
        if ($valorProjeto <= 0) {
            $errors['valor_projeto'] = 'Valor do projeto deve ser maior que zero.';
        }
        $valorPago = Utils::decimalFromInput($data['valor_pago'] ?? 0);
        $data['valor_pago'] = $valorPago;
        if ($valorPago < 0) {
            $errors['valor_pago'] = 'Valor pago não pode ser negativo.';
        }
        return $errors;
    }

    private function renderForm(?array $project, array $clients, array $responsaveis, array $errors): void {
        $project = $project ?? [];
        $title = empty($project['id']) ? 'Novo Projeto' : 'Editar Projeto';
        ob_start();
        include __DIR__ . '/../Views/projetos/form.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

    private function sanitizeActivityPayload(array $input): array {
        return [
            'titulo_atividade' => $input['titulo_atividade'] ?? '',
            'descricao' => $input['descricao'] ?? '',
            'data_inicio' => $input['data_inicio'] ?? null,
            'data_conclusao' => $input['data_conclusao'] ?? null,
            'status_atividade' => $input['status_atividade'] ?? null,
            'prioridade' => $input['prioridade'] ?? null,
            'responsavel_id' => $input['responsavel_id'] ?? null,
            'horas_estimadas' => $input['horas_estimadas'] ?? null,
            'horas_reais' => $input['horas_reais'] ?? null,
        ];
    }

    private function validateActivity(array $data): array {
        $errors = [];
        if (mb_strlen(trim($data['titulo_atividade'])) === 0) {
            $errors['titulo_atividade'] = 'Título é obrigatório.';
        }
        if (mb_strlen(trim($data['descricao'])) === 0) {
            $errors['descricao'] = 'Descrição é obrigatória.';
        }
        if (empty($data['data_inicio'])) {
            $errors['data_inicio'] = 'Data de início é obrigatória.';
        }
        if (!empty($data['data_conclusao']) && strtotime($data['data_conclusao']) < strtotime($data['data_inicio'])) {
            $errors['data_conclusao'] = 'Data de conclusão não pode ser anterior à data de início.';
        }
        return $errors;
    }
}
