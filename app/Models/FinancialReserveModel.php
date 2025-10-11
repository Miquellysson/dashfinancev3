<?php

class FinancialReserveModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getBalance(): float {
        return $this->guard(function () {
            $stmt = $this->pdo->query("
                SELECT COALESCE(SUM(CASE WHEN operation_type = 'deposit' THEN amount ELSE -amount END), 0)
                FROM financial_reserve_entries
                WHERE deleted_at IS NULL
            ");
            return (float)$stmt->fetchColumn();
        }, 0.0);
    }

    public function getTotals(array $filters = []): array {
        [$where, $params] = $this->buildFilters($filters);
        return $this->guard(function () use ($where, $params) {
            $sql = "
                SELECT
                    COALESCE(SUM(CASE WHEN operation_type = 'deposit' THEN amount END), 0) AS deposits,
                    COALESCE(SUM(CASE WHEN operation_type = 'withdraw' THEN amount END), 0) AS withdrawals
                FROM financial_reserve_entries
                {$where}
            ";
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['deposits' => 0, 'withdrawals' => 0];
            return [
                'deposits' => (float)$row['deposits'],
                'withdrawals' => (float)$row['withdrawals'],
            ];
        }, ['deposits' => 0.0, 'withdrawals' => 0.0]);
    }

    public function paginate(array $filters, int $limit, int $offset): array {
        [$where, $params] = $this->buildFilters($filters);
        return $this->guard(function () use ($where, $params, $limit, $offset) {
            $sql = "
                SELECT id, operation_type, amount, reference_date, description, category, notes, created_by, created_at, updated_at
                FROM financial_reserve_entries
                {$where}
                ORDER BY reference_date DESC, id DESC
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
        }, []);
    }

    public function count(array $filters): int {
        [$where, $params] = $this->buildFilters($filters);
        return $this->guard(function () use ($where, $params) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*)
                FROM financial_reserve_entries
                {$where}
            ");
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        }, 0);
    }

    public function export(array $filters): array {
        [$where, $params] = $this->buildFilters($filters);
        return $this->guard(function () use ($where, $params) {
            $sql = "
                SELECT id, operation_type, amount, reference_date, description, category, notes, created_by, created_at, updated_at
                FROM financial_reserve_entries
                {$where}
                ORDER BY reference_date DESC, id DESC
            ";
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, []);
    }

    public function find(int $id): ?array {
        return $this->guard(function () use ($id) {
            $stmt = $this->pdo->prepare("
                SELECT id, operation_type, amount, reference_date, description, category, notes, created_by, created_at, updated_at
                FROM financial_reserve_entries
                WHERE id = :id AND deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        }, null);
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO financial_reserve_entries
                (operation_type, amount, reference_date, description, category, notes, created_by)
            VALUES
                (:operation_type, :amount, :reference_date, :description, :category, :notes, :created_by)
        ");
        $stmt->execute([
            ':operation_type' => $data['operation_type'],
            ':amount' => $data['amount'],
            ':reference_date' => $data['reference_date'],
            ':description' => $data['description'],
            ':category' => $data['category'],
            ':notes' => $data['notes'],
            ':created_by' => $data['created_by'],
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE financial_reserve_entries SET
                operation_type = :operation_type,
                amount = :amount,
                reference_date = :reference_date,
                description = :description,
                category = :category,
                notes = :notes,
                updated_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute([
            ':operation_type' => $data['operation_type'],
            ':amount' => $data['amount'],
            ':reference_date' => $data['reference_date'],
            ':description' => $data['description'],
            ':category' => $data['category'],
            ':notes' => $data['notes'],
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("
            UPDATE financial_reserve_entries
            SET deleted_at = NOW()
            WHERE id = :id AND deleted_at IS NULL
        ");
        return $stmt->execute([':id' => $id]);
    }

    private function buildFilters(array $filters): array {
        $where = ['deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['type']) && in_array($filters['type'], ['deposit', 'withdraw'], true)) {
            $where[] = 'operation_type = :type';
            $params[':type'] = $filters['type'];
        }

        if (!empty($filters['from'])) {
            $where[] = 'reference_date >= :from';
            $params[':from'] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $where[] = 'reference_date <= :to';
            $params[':to'] = $filters['to'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(description LIKE :search OR category LIKE :search OR notes LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sqlWhere = 'WHERE ' . implode(' AND ', $where);
        return [$sqlWhere, $params];
    }

    private function guard(callable $callback, $default) {
        try {
            return $callback();
        } catch (PDOException $e) {
            if (in_array($e->getCode(), ['42S02', '42S22'], true)) {
                return $default;
            }
            throw $e;
        }
    }
}
