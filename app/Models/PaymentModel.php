<?php
class PaymentModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* -----------------------
       Helpers
       ----------------------- */

    /** Converte status textual para status_id em status_catalog. Cria se não existir. */
    private function resolveStatusId(?string $statusName): ?int {
        if (!$statusName) return null;
        $statusName = trim($statusName);
        if ($statusName === '') return null;

        $st = $this->pdo->prepare("SELECT id FROM status_catalog WHERE name = ? LIMIT 1");
        $st->execute([$statusName]);
        $id = $st->fetchColumn();
        if ($id) return (int)$id;

        $max = (int)$this->pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM status_catalog")->fetchColumn();
        $ins = $this->pdo->prepare("INSERT INTO status_catalog (name, color_hex, sort_order, created_at) VALUES (?, '#6b7280', ?, NOW())");
        $ins->execute([$statusName, $max + 1]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Normaliza payload antigo -> novo schema */
    private function normalizeData(array $data): array {
        $out = [];

        $out['project_id'] = $data['project_id'] ?? null;

        $amount = $data['amount'] ?? null;
        if (is_string($amount)) {
            $clean = str_replace(['R$', 'US$', ' ', "\xC2\xA0", "\xE2\x80\xAF"], '', $amount);
            $hasComma = strpos($clean, ',') !== false;
            $hasDot = strpos($clean, '.') !== false;

            if ($hasComma && $hasDot) {
                $lastComma = strrpos($clean, ',');
                $lastDot = strrpos($clean, '.');
                if ($lastComma > $lastDot) {
                    $clean = str_replace('.', '', $clean);
                    $clean = str_replace(',', '.', $clean);
                } else {
                    $clean = str_replace(',', '', $clean);
                }
            } elseif ($hasComma) {
                $clean = str_replace(',', '.', $clean);
            }

            $amount = is_numeric($clean) ? (float)$clean : null;
        }
        $out['amount'] = $amount !== null && $amount !== '' ? (float)$amount : null;

        $currency = strtoupper($data['currency'] ?? 'BRL');
        $out['currency'] = in_array($currency, ['BRL','USD'], true) ? $currency : 'BRL';

        $type = strtolower($data['transaction_type'] ?? 'receita');
        $out['transaction_type'] = in_array($type, ['receita','despesa'], true) ? $type : 'receita';

        $descriptionRaw = isset($data['description']) ? trim((string)$data['description']) : '';
        $out['description'] = $descriptionRaw !== '' ? strip_tags($descriptionRaw) : null;

        $categoryRaw = isset($data['category']) ? trim((string)$data['category']) : '';
        $out['category'] = $categoryRaw !== '' ? strip_tags($categoryRaw) : null;

        // kind (default: one_time)
        $out['kind'] = $data['kind'] ?? 'one_time';
        if (!in_array($out['kind'], ['one_time','recurring'], true)) {
            $out['kind'] = 'one_time';
        }

        // status: pode vir status_id (num) ou status (texto)
        if (isset($data['status_id']) && $data['status_id'] !== '') {
            $out['status_id'] = (int)$data['status_id'];
        } elseif (isset($data['status'])) {
            $statusText = $this->normalizeStatusName($data['status'], $out['transaction_type']);
            $out['status_id'] = $this->resolveStatusId($statusText);
        } else {
            $out['status_id'] = null;
        }

        // datas: no antigo havia 'date'; hoje temos due_date / paid_at
        // Regra: se status textual for 'Paid' (ou status_id que corresponda a 'Paid'), usa como paid_at;
        // caso contrário, assume due_date.
        $legacyDate = $data['date'] ?? null;

        // Checa se explicitamente veio paid_at/due_date
        $out['paid_at']  = $data['paid_at']  ?? null;
        $out['due_date'] = $data['due_date'] ?? null;

        // Se só veio 'date', decide para onde vai:
        if ($legacyDate && !$out['paid_at'] && !$out['due_date']) {
            // tenta inferir pelo status textual, se fornecido
            $statusText = isset($data['status']) ? strtolower(trim($data['status'])) : null;
            if ($statusText === 'paid') {
                $out['paid_at'] = $legacyDate;
            } else {
                $out['due_date'] = $legacyDate;
            }
        }

        // description não existe no schema; se for importante, adicionar coluna 'notes' depois
        return $out;
    }

    private function normalizeStatusName(string $status, string $type): string {
        $key = mb_strtolower(trim($status), 'UTF-8');
        $aliases = [
            'paid'      => $type === 'despesa' ? 'Pago' : 'Recebido',
            'pending'   => $type === 'despesa' ? 'Pendente' : 'A Receber',
            'overdue'   => $type === 'despesa' ? 'Vencido' : 'Em Atraso',
            'dropped'   => 'Cancelado',
            'recebido'  => 'Recebido',
            'a receber' => 'A Receber',
            'em atraso' => 'Em Atraso',
            'cancelado' => 'Cancelado',
            'pago'      => 'Pago',
            'pendente'  => 'Pendente',
            'vencido'   => 'Vencido',
            'parcelado' => 'Parcelado',
        ];

        return $aliases[$key] ?? $status;
    }

    /* -----------------------
       Consultas
       ----------------------- */

    public function getAll($limit = null, $offset = 0, array $filters = []) {
        $limitSql = '';
        if ($limit !== null) {
            $limit  = (int)$limit;
            $offset = (int)$offset;
            $limitSql = " LIMIT :lim OFFSET :off";
        }

        $conditions = [];
        $params = [];

        if (!empty($filters['transaction_type']) && in_array($filters['transaction_type'], ['receita', 'despesa'], true)) {
            $conditions[] = "COALESCE(p.transaction_type, 'receita') = :transaction_type";
            $params[':transaction_type'] = $filters['transaction_type'];
        }

        if (!empty($filters['category'])) {
            $conditions[] = 'p.category LIKE :category';
            $params[':category'] = '%' . $filters['category'] . '%';
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(p.description LIKE :search OR p.category LIKE :search OR pr.name LIKE :search OR c.name LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $sql = "
            SELECT p.*,
                   pr.name AS project_name,
                   c.name  AS client_name,
                   s.name  AS status_name
            FROM payments p
            LEFT JOIN projects pr       ON p.project_id = pr.id
            LEFT JOIN clients  c        ON pr.client_id = c.id
            LEFT JOIN status_catalog s  ON s.id = p.status_id
            {$whereSql}
            ORDER BY COALESCE(p.paid_at, p.due_date) DESC, p.id DESC
            {$limitSql}
        ";

        $st = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $st->bindValue($key, $value, PDO::PARAM_STR);
        }
        if ($limitSql) {
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->bindValue(':off', $offset, PDO::PARAM_INT);
        }
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $st = $this->pdo->prepare("
            SELECT p.*,
                   pr.name AS project_name,
                   pr.client_id AS project_client_id,
                   s.name  AS status_name
            FROM payments p
            LEFT JOIN projects pr      ON p.project_id = pr.id
            LEFT JOIN status_catalog s ON s.id = p.status_id
            WHERE p.id = :id
            LIMIT 1
        ");
        $st->execute([':id' => (int)$id]);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $d = $this->normalizeData($data);

        $sql = "INSERT INTO payments
                   (project_id, kind, amount, currency, transaction_type, description, category, due_date, paid_at, status_id, created_at, updated_at)
                VALUES
                   (:project_id, :kind, :amount, :currency, :transaction_type, :description, :category, :due_date, :paid_at, :status_id, NOW(), NOW())";

        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':project_id' => $d['project_id'],
            ':kind'       => $d['kind'],
            ':amount'     => $d['amount'],
            ':currency'   => $d['currency'],
            ':transaction_type' => $d['transaction_type'],
            ':description' => $d['description'],
            ':category'   => $d['category'],
            ':due_date'   => $d['due_date'],
            ':paid_at'    => $d['paid_at'],
            ':status_id'  => $d['status_id'],
        ]);
    }

    public function update($id, $data) {
        $d = $this->normalizeData($data);

        $sql = "UPDATE payments SET
                    project_id = :project_id,
                    kind       = :kind,
                    amount     = :amount,
                    currency   = :currency,
                    transaction_type = :transaction_type,
                    description = :description,
                    category    = :category,
                    due_date   = :due_date,
                    paid_at    = :paid_at,
                    status_id  = :status_id,
                    updated_at = NOW()
                WHERE id = :id";

        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':project_id' => $d['project_id'],
            ':kind'       => $d['kind'],
            ':amount'     => $d['amount'],
            ':currency'   => $d['currency'],
            ':transaction_type' => $d['transaction_type'],
            ':description' => $d['description'],
            ':category'   => $d['category'],
            ':due_date'   => $d['due_date'],
            ':paid_at'    => $d['paid_at'],
            ':status_id'  => $d['status_id'],
            ':id'         => (int)$id,
        ]);
    }

    public function delete($id) {
        $st = $this->pdo->prepare("DELETE FROM payments WHERE id = :id");
        return $st->execute([':id' => (int)$id]);
    }

    public function count() {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM payments")->fetchColumn();
    }
}
