<?php

class BillingCaseModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function list(array $filters = [], int $limit = 50, int $offset = 0): array {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'c.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['responsavel_id'])) {
            $where[] = 'c.responsavel_id = :resp';
            $params[':resp'] = (int)$filters['responsavel_id'];
        }
        if (!empty($filters['cliente'])) {
            $where[] = '(cl.name LIKE :cliente OR c.titulo LIKE :cliente)';
            $params[':cliente'] = '%' . $filters['cliente'] . '%';
        }

        $sql = "
            SELECT
                c.*,
                cl.name AS cliente_nome,
                u.nome_completo AS responsavel_nome,
                COUNT(t.id) AS total_tarefas,
                SUM(CASE WHEN t.status = 'feito' THEN 1 ELSE 0 END) AS tarefas_concluidas
            FROM billing_cases c
            JOIN clients cl ON cl.id = c.client_id
            LEFT JOIN users u ON u.id = c.responsavel_id
            LEFT JOIN billing_tasks t ON t.case_id = c.id
            " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
            GROUP BY c.id
            ORDER BY c.status, c.proxima_acao_em IS NULL, c.proxima_acao_em ASC, c.updated_at DESC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT
                c.*,
                cl.name AS cliente_nome,
                u.nome_completo AS responsavel_nome,
                u.email AS responsavel_email
            FROM billing_cases c
            JOIN clients cl ON cl.id = c.client_id
            LEFT JOIN users u ON u.id = c.responsavel_id
            WHERE c.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO billing_cases (
                client_id, responsavel_id, origem, origem_id,
                titulo, valor_total, valor_pendente,
                status, prioridade,
                proxima_acao_em, encerrado_em, observacoes,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['client_id'],
            $data['responsavel_id'] ?? null,
            $data['origem'] ?? 'manual',
            $data['origem_id'] ?? null,
            $data['titulo'],
            $data['valor_total'] ?? 0,
            $data['valor_pendente'] ?? ($data['valor_total'] ?? 0),
            $data['status'] ?? 'aberto',
            $data['prioridade'] ?? 'media',
            $data['proxima_acao_em'] ?? null,
            $data['encerrado_em'] ?? null,
            $data['observacoes'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE billing_cases SET
                client_id = ?,
                responsavel_id = ?,
                titulo = ?,
                valor_total = ?,
                valor_pendente = ?,
                status = ?,
                prioridade = ?,
                proxima_acao_em = ?,
                encerrado_em = ?,
                observacoes = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['client_id'],
            $data['responsavel_id'] ?? null,
            $data['titulo'],
            $data['valor_total'] ?? 0,
            $data['valor_pendente'] ?? 0,
            $data['status'] ?? 'aberto',
            $data['prioridade'] ?? 'media',
            $data['proxima_acao_em'] ?? null,
            $data['encerrado_em'] ?? null,
            $data['observacoes'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM billing_cases WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
