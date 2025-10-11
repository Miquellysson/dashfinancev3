<?php

class ProjectModel {
    private $pdo;

    public const SERVICE_TYPES = [
        'Desenvolvimento Web',
        'Design',
        'Consultoria',
        'Manutenção',
        'SEO',
        'Marketing Digital',
        'Outro',
    ];

    public const PAYMENT_STATUS = ['Pago', 'Pendente', 'Parcial', 'Cancelado'];
    public const SATISFACTION_STATUS = ['Satisfeito', 'Parcialmente Satisfeito', 'Insatisfeito', 'Aguardando Feedback'];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /** Lista paginada com filtros e ordenação */
    public function paginate(array $filters, int $limit, int $offset, string $orderBy, string $orderDir): array {
        [$whereSql, $params] = $this->buildFilters($filters);
        $orderSql = $this->normalizeOrderBy($orderBy, $orderDir);

        $sql = "
            SELECT
                p.*,
                COALESCE(u.nome_completo, '—') AS responsavel_nome,
                COALESCE(c.name, p.nome_cliente) AS cliente_nome
            FROM projects p
            LEFT JOIN users u ON u.id = p.usuario_responsavel_id
            LEFT JOIN clients c ON c.id = p.client_id
            {$whereSql}
            {$orderSql}
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Conta total com filtros aplicados */
    public function countWithFilters(array $filters): int {
        [$whereSql, $params] = $this->buildFilters($filters);
        $sql = "SELECT COUNT(*) FROM projects p {$whereSql}";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function count(): int {
        $stmt = $this->pdo->query("
            SELECT COUNT(*)
            FROM projects
            WHERE deleted_at IS NULL
        ");
        return (int)$stmt->fetchColumn();
    }

    /** Lista resumida para selects/relacionamentos */
    public function getAll(?int $limit = null, int $offset = 0): array {
        $sql = "
            SELECT
                p.id,
                p.name,
                p.nome_cliente,
                p.client_id,
                COALESCE(c.name, p.nome_cliente) AS client_name,
                p.status_pagamento,
                p.valor_projeto,
                p.valor_pendente
            FROM projects p
            LEFT JOIN clients c ON c.id = p.client_id
            WHERE p.deleted_at IS NULL
            ORDER BY p.data_entrada DESC, p.id DESC
        ";

        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }

        $stmt = $this->pdo->prepare($sql);
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Recupera projeto completo */
    public function find(int $id): ?array {
        $sql = "
            SELECT
                p.*,
                COALESCE(u.nome_completo, '—') AS responsavel_nome,
                COALESCE(c.name, p.nome_cliente) AS cliente_nome,
                u.email AS responsavel_email
            FROM projects p
            LEFT JOIN users u ON u.id = p.usuario_responsavel_id
            LEFT JOIN clients c ON c.id = p.client_id
            WHERE p.id = :id AND p.deleted_at IS NULL
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Cria novo projeto */
    public function create(array $data): int {
        $payload = $this->normalizePayload($data);

        $sql = "
            INSERT INTO projects (
                client_id, nome_cliente, name,
                data_entrada, tipo_servico, status_satisfacao,
                status, valor_projeto, status_pagamento,
                valor_pago, valor_pendente, observacoes,
                usuario_responsavel_id, created_at, updated_at
            ) VALUES (
                :client_id, :nome_cliente, :titulo,
                :data_entrada, :tipo_servico, :status_satisfacao,
                :status, :valor_projeto, :status_pagamento,
                :valor_pago, :valor_pendente, :observacoes,
                :usuario_responsavel_id, NOW(), NOW()
            )
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($payload);

        return (int)$this->pdo->lastInsertId();
    }

    /** Atualiza projeto */
    public function update(int $id, array $data): bool {
        $payload = $this->normalizePayload($data);
        $payload[':id'] = $id;

        $sql = "
            UPDATE projects SET
                client_id = :client_id,
                nome_cliente = :nome_cliente,
                name = :titulo,
                data_entrada = :data_entrada,
                tipo_servico = :tipo_servico,
                status_satisfacao = :status_satisfacao,
                status = :status,
                valor_projeto = :valor_projeto,
                status_pagamento = :status_pagamento,
                valor_pago = :valor_pago,
                valor_pendente = :valor_pendente,
                observacoes = :observacoes,
                usuario_responsavel_id = :usuario_responsavel_id,
                updated_at = NOW()
            WHERE id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($payload);
    }

    /** Soft delete */
    public function softDelete(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE projects SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /** Indicadores gerais para cards */
    public function getFinancialSummary(array $filters = []): array {
        [$whereSql, $params] = $this->buildFilters($filters);
        $sql = "
            SELECT
                COALESCE(SUM(valor_projeto),0) AS total_geral,
                COALESCE(SUM(CASE WHEN status_pagamento = 'Pago' THEN valor_projeto ELSE 0 END),0) AS total_pago_projetos,
                COALESCE(SUM(valor_pendente),0) AS total_pendente,
                COALESCE(SUM(valor_pago),0) AS total_recebido
            FROM projects p
            {$whereSql}
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total_geral' => 0,
            'total_pago_projetos' => 0,
            'total_pendente' => 0,
            'total_recebido' => 0,
        ];
    }

    /** Quebra por status_pagamento */
    public function getPaymentBreakdown(array $filters = []): array {
        [$whereSql, $params] = $this->buildFilters($filters);
        $sql = "
            SELECT
                status_pagamento,
                COUNT(*) AS quantidade,
                COALESCE(SUM(valor_projeto),0) AS total_valor,
                COALESCE(SUM(valor_pendente),0) AS total_pendente,
                COALESCE(SUM(valor_pago),0) AS total_pago
            FROM projects p
            {$whereSql}
            GROUP BY status_pagamento
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $indexed = [
            'Pago' => ['quantidade' => 0, 'total_valor' => 0, 'total_pendente' => 0, 'total_pago' => 0],
            'Pendente' => ['quantidade' => 0, 'total_valor' => 0, 'total_pendente' => 0, 'total_pago' => 0],
            'Parcial' => ['quantidade' => 0, 'total_valor' => 0, 'total_pendente' => 0, 'total_pago' => 0],
            'Cancelado' => ['quantidade' => 0, 'total_valor' => 0, 'total_pendente' => 0, 'total_pago' => 0],
        ];

        foreach ($rows as $row) {
            $status = $row['status_pagamento'];
            if (!isset($indexed[$status])) {
                $indexed[$status] = ['quantidade' => 0, 'total_valor' => 0, 'total_pendente' => 0, 'total_pago' => 0];
            }
            $indexed[$status] = [
                'quantidade' => (int)$row['quantidade'],
                'total_valor' => (float)$row['total_valor'],
                'total_pendente' => (float)$row['total_pendente'],
                'total_pago' => (float)$row['total_pago'],
            ];
        }

        return $indexed;
    }

    /** Receitas por tipo de serviço */
    public function getRevenueByService(array $filters = []): array {
        [$whereSql, $params] = $this->buildFilters($filters);
        $sql = "
            SELECT tipo_servico, COALESCE(SUM(valor_projeto),0) AS total
            FROM projects p
            {$whereSql}
            GROUP BY tipo_servico
            ORDER BY tipo_servico
        ";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        $results = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $results[$row['tipo_servico']] = (float)$row['total'];
        }
        return $results;
    }

    /** Evolução mensal (quantidade de projetos) */
    public function getMonthlyEvolution(int $months = 12): array {
        $start = (new DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months');
        $sql = "
            SELECT DATE_FORMAT(data_entrada, '%Y-%m') AS ym, COUNT(*) AS quantidade
            FROM projects
            WHERE deleted_at IS NULL
              AND data_entrada >= :inicio
            GROUP BY ym
            ORDER BY ym ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':inicio', $start->format('Y-m-01'));
        $stmt->execute();

        $map = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[$row['ym']] = (int)$row['quantidade'];
        }

        $series = [];
        $labels = [];
        $cursor = new DateTimeImmutable('first day of this month');
        $cursor = $cursor->modify('-' . ($months - 1) . ' months');
        for ($i = 0; $i < $months; $i++) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->format('m/Y');
            $series[] = $map[$key] ?? 0;
            $cursor = $cursor->modify('+1 month');
        }

        return ['labels' => $labels, 'series' => $series];
    }

    /** Helpers internos */
    private function buildFilters(array $filters): array {
        $clauses = ['p.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['status_pagamento']) && in_array($filters['status_pagamento'], self::PAYMENT_STATUS, true)) {
            $clauses[] = 'p.status_pagamento = :status_pagamento';
            $params[':status_pagamento'] = $filters['status_pagamento'];
        }

        if (!empty($filters['tipo_servico']) && in_array($filters['tipo_servico'], self::SERVICE_TYPES, true)) {
            $clauses[] = 'p.tipo_servico = :tipo_servico';
            $params[':tipo_servico'] = $filters['tipo_servico'];
        }

        if (!empty($filters['status_satisfacao']) && in_array($filters['status_satisfacao'], self::SATISFACTION_STATUS, true)) {
            $clauses[] = 'p.status_satisfacao = :status_satisfacao';
            $params[':status_satisfacao'] = $filters['status_satisfacao'];
        }

        if (!empty($filters['data_inicio'])) {
            $clauses[] = 'p.data_entrada >= :data_inicio';
            $params[':data_inicio'] = $filters['data_inicio'] . ' 00:00:00';
        }

        if (!empty($filters['data_fim'])) {
            $clauses[] = 'p.data_entrada <= :data_fim';
            $params[':data_fim'] = $filters['data_fim'] . ' 23:59:59';
        }

        if (!empty($filters['busca'])) {
            $clauses[] = '(p.nome_cliente LIKE :busca OR p.name LIKE :busca)';
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        if (!empty($filters['responsavel_id'])) {
            $clauses[] = 'p.usuario_responsavel_id = :responsavel_id';
            $params[':responsavel_id'] = (int)$filters['responsavel_id'];
        }

        $whereSql = 'WHERE ' . implode(' AND ', $clauses);
        return [$whereSql, $params];
    }

    private function normalizeOrderBy(string $orderBy, string $orderDir): string {
        $allowed = [
            'data_entrada' => 'p.data_entrada',
            'valor_projeto' => 'p.valor_projeto',
            'nome_cliente' => 'p.nome_cliente',
            'status_pagamento' => 'p.status_pagamento',
            'tipo_servico' => 'p.tipo_servico',
            'default' => 'p.created_at',
        ];
        $dir = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
        $column = $allowed[$orderBy] ?? $allowed['default'];
        return "ORDER BY {$column} {$dir}";
    }

    private function normalizePayload(array $data): array {
        $clientId = isset($data['client_id']) && $data['client_id'] !== '' ? (int)$data['client_id'] : null;
        $valorProjeto = isset($data['valor_projeto']) ? (float)$this->toDecimal($data['valor_projeto']) : 0.0;
        $valorPago = isset($data['valor_pago']) ? (float)$this->toDecimal($data['valor_pago']) : 0.0;
        $valorPago = max(0, min($valorProjeto, $valorPago));
        $valorPendente = max(0, $valorProjeto - $valorPago);

        return [
            ':client_id' => $clientId,
            ':nome_cliente' => Utils::sanitize($data['nome_cliente'] ?? 'Cliente não informado'),
            ':titulo' => Utils::sanitize($data['name'] ?? $data['titulo'] ?? 'Projeto sem título'),
            ':data_entrada' => $this->normalizeDateTime($data['data_entrada'] ?? null),
            ':tipo_servico' => $this->normalizeEnum($data['tipo_servico'] ?? null, self::SERVICE_TYPES, 'Desenvolvimento Web'),
            ':status_satisfacao' => $this->normalizeEnum($data['status_satisfacao'] ?? null, self::SATISFACTION_STATUS, 'Aguardando Feedback'),
            ':status' => $this->normalizeEnum($data['status'] ?? null, ['ativo','pausado','concluido','cancelado'], 'ativo'),
            ':valor_projeto' => $valorProjeto,
            ':status_pagamento' => $this->normalizeEnum($data['status_pagamento'] ?? null, self::PAYMENT_STATUS, 'Pendente'),
            ':valor_pago' => $valorPago,
            ':valor_pendente' => $valorPendente,
            ':observacoes' => $data['observacoes'] !== null ? Utils::sanitize($data['observacoes']) : null,
            ':usuario_responsavel_id' => isset($data['usuario_responsavel_id']) && $data['usuario_responsavel_id'] !== '' ? (int)$data['usuario_responsavel_id'] : null,
        ];
    }

    private function normalizeEnum(?string $value, array $allowed, string $default): string {
        if ($value === null) return $default;
        $value = trim((string)$value);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function normalizeDateTime(?string $value): string {
        if (empty($value)) {
            return (new DateTime())->format('Y-m-d H:i:s');
        }
        try {
            return (new DateTime($value))->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return (new DateTime())->format('Y-m-d H:i:s');
        }
    }

    private function toDecimal($value): float {
        if (is_numeric($value)) {
            return (float)$value;
        }
        $clean = str_replace(['R$', 'r$', '.', ' '], '', (string)$value);
        $clean = str_replace(',', '.', $clean);
        return (float)$clean;
    }
}
