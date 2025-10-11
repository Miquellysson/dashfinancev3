<?php

class BillingTaskModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listByCase(int $caseId): array {
        $stmt = $this->pdo->prepare("
            SELECT
                t.*,
                u.nome_completo AS responsavel_nome
            FROM billing_tasks t
            LEFT JOIN users u ON u.id = t.responsavel_id
            WHERE t.case_id = ?
            ORDER BY t.status = 'feito', t.due_at IS NULL, t.due_at ASC, t.created_at DESC
        ");
        $stmt->execute([$caseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO billing_tasks (
                case_id, responsavel_id, titulo, descricao,
                tipo, status, due_at, completed_at, lembrete_minutos,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['case_id'],
            $data['responsavel_id'] ?? null,
            $data['titulo'],
            $data['descricao'] ?? null,
            $data['tipo'] ?? 'outro',
            $data['status'] ?? 'pendente',
            $data['due_at'] ?? null,
            $data['completed_at'] ?? null,
            $data['lembrete_minutos'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE billing_tasks SET
                responsavel_id = ?,
                titulo = ?,
                descricao = ?,
                tipo = ?,
                status = ?,
                due_at = ?,
                completed_at = ?,
                lembrete_minutos = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['responsavel_id'] ?? null,
            $data['titulo'],
            $data['descricao'] ?? null,
            $data['tipo'] ?? 'outro',
            $data['status'] ?? 'pendente',
            $data['due_at'] ?? null,
            $data['completed_at'] ?? null,
            $data['lembrete_minutos'] ?? null,
            $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM billing_tasks WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
