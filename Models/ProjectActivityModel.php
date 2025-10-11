<?php

class ProjectActivityModel {
    private $pdo;

    public const STATUS = ['Não Iniciada','Em Andamento','Concluída','Bloqueada','Cancelada'];
    public const PRIORIDADES = ['Baixa','Média','Alta','Urgente'];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listByProject(int $projetoId, array $filters = []): array {
        $clauses = ['pa.deleted_at IS NULL', 'pa.projeto_id = :projeto_id'];
        $params = [':projeto_id' => $projetoId];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUS, true)) {
            $clauses[] = 'pa.status_atividade = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['responsavel_id'])) {
            $clauses[] = 'pa.responsavel_id = :responsavel';
            $params[':responsavel'] = (int)$filters['responsavel_id'];
        }

        $order = ' ORDER BY FIELD(pa.status_atividade, \'Bloqueada\', \'Não Iniciada\', \'Em Andamento\', \'Concluída\', \'Cancelada\'), FIELD(pa.prioridade, \'Urgente\', \'Alta\', \'Média\', \'Baixa\'), pa.data_inicio DESC ';

        $sql = "
            SELECT pa.*, COALESCE(u.nome_completo,'—') AS responsavel_nome
            FROM project_activities pa
            LEFT JOIN users u ON u.id = pa.responsavel_id
            WHERE " . implode(' AND ', $clauses) . $order;

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array {
        $sql = "
            SELECT pa.*, COALESCE(u.nome_completo,'—') AS responsavel_nome
            FROM project_activities pa
            LEFT JOIN users u ON u.id = pa.responsavel_id
            WHERE pa.id = :id AND pa.deleted_at IS NULL
            LIMIT 1
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $payload = $this->normalizePayload($data);
        $sql = "
            INSERT INTO project_activities (
                projeto_id, titulo_atividade, descricao,
                data_inicio, data_conclusao, status_atividade,
                prioridade, responsavel_id, horas_estimadas,
                horas_reais, created_at, updated_at
            ) VALUES (
                :projeto_id, :titulo_atividade, :descricao,
                :data_inicio, :data_conclusao, :status_atividade,
                :prioridade, :responsavel_id, :horas_estimadas,
                :horas_reais, NOW(), NOW()
            )
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($payload);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $payload = $this->normalizePayload($data);
        $payload[':id'] = $id;
        $sql = "
            UPDATE project_activities SET
                titulo_atividade = :titulo_atividade,
                descricao = :descricao,
                data_inicio = :data_inicio,
                data_conclusao = :data_conclusao,
                status_atividade = :status_atividade,
                prioridade = :prioridade,
                responsavel_id = :responsavel_id,
                horas_estimadas = :horas_estimadas,
                horas_reais = :horas_reais,
                updated_at = NOW()
            WHERE id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($payload);
    }

    public function softDelete(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE project_activities SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getMetrics(int $projetoId): array {
        $sql = "
            SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status_atividade = 'Concluída' THEN 1 ELSE 0 END) AS concluidas,
                SUM(CASE WHEN status_atividade != 'Concluída' AND data_conclusao IS NOT NULL AND data_conclusao < NOW() THEN 1 ELSE 0 END) AS atrasadas,
                COALESCE(SUM(horas_estimadas),0) AS horas_estimadas,
                COALESCE(SUM(horas_reais),0) AS horas_reais
            FROM project_activities
            WHERE projeto_id = :projeto_id AND deleted_at IS NULL
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':projeto_id' => $projetoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total' => 0,
            'concluidas' => 0,
            'atrasadas' => 0,
            'horas_estimadas' => 0,
            'horas_reais' => 0,
        ];

        $taxaConclusao = 0.0;
        if ((int)$row['total'] > 0) {
            $taxaConclusao = ((int)$row['concluidas'] / (int)$row['total']) * 100;
        }

        $row['taxa_conclusao'] = round($taxaConclusao, 2);
        return $row;
    }

    private function normalizePayload(array $data): array {
        $responsavel = isset($data['responsavel_id']) && $data['responsavel_id'] !== '' ? (int)$data['responsavel_id'] : null;
        $horasEstimadas = $this->toDecimal($data['horas_estimadas'] ?? null);
        $horasReais = $this->toDecimal($data['horas_reais'] ?? null);

        return [
            ':projeto_id' => (int)$data['projeto_id'],
            ':titulo_atividade' => Utils::sanitize($data['titulo_atividade'] ?? 'Atividade'),
            ':descricao' => Utils::sanitize($data['descricao'] ?? ''),
            ':data_inicio' => $this->normalizeDateTime($data['data_inicio']),
            ':data_conclusao' => $this->normalizeNullableDateTime($data['data_conclusao'] ?? null),
            ':status_atividade' => $this->normalizeEnum($data['status_atividade'] ?? null, self::STATUS, 'Não Iniciada'),
            ':prioridade' => $this->normalizeEnum($data['prioridade'] ?? null, self::PRIORIDADES, 'Média'),
            ':responsavel_id' => $responsavel,
            ':horas_estimadas' => $horasEstimadas,
            ':horas_reais' => $horasReais,
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

    private function normalizeNullableDateTime(?string $value): ?string {
        if (empty($value)) {
            return null;
        }
        try {
            return (new DateTime($value))->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return null;
        }
    }

    private function toDecimal($value): ?float {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float)$value;
        }
        $clean = str_replace(['.', ' '], '', (string)$value);
        $clean = str_replace(',', '.', $clean);
        return is_numeric($clean) ? (float)$clean : null;
    }
}
