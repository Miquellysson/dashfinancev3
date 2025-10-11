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

        $st = $this->pdo->prepare("SELECT id FROM status_catalog WHERE name = :n LIMIT 1");
        $st->execute([':n' => $statusName]);
        $id = $st->fetchColumn();
        if ($id) return (int)$id;

        // cria novo status com cor padrão cinza e última ordem
        $max = (int)$this->pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM status_catalog")->fetchColumn();
        $ins = $this->pdo->prepare("INSERT INTO status_catalog (name, color_hex, sort_order, created_at) VALUES (:n, '#6b7280', :ord, NOW())");
        $ins->execute([':n'=>$statusName, ':ord'=>$max+1]);
        return (int)$this->pdo->lastInsertId();
    }

    /** Normaliza payload antigo -> novo schema */
    private function normalizeData(array $data): array {
        $out = [];

        $out['project_id'] = $data['project_id'] ?? null;
        $out['amount']     = $data['amount']     ?? null;

        // kind (default: one_time)
        $out['kind'] = $data['kind'] ?? 'one_time';
        if (!in_array($out['kind'], ['one_time','recurring'], true)) {
            $out['kind'] = 'one_time';
        }

        // status: pode vir status_id (num) ou status (texto)
        if (isset($data['status_id']) && $data['status_id'] !== '') {
            $out['status_id'] = (int)$data['status_id'];
        } elseif (isset($data['status'])) {
            $out['status_id'] = $this->resolveStatusId($data['status']);
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

    /* -----------------------
       Consultas
       ----------------------- */

    public function getAll($limit = null, $offset = 0) {
        $limitSql = '';
        if ($limit !== null) {
            $limit  = (int)$limit;
            $offset = (int)$offset;
            $limitSql = " LIMIT :lim OFFSET :off";
        }

        $sql = "
            SELECT p.*,
                   pr.name AS project_name,
                   c.name  AS client_name,
                   s.name  AS status_name
            FROM payments p
            LEFT JOIN projects pr       ON p.project_id = pr.id
            LEFT JOIN clients  c        ON pr.client_id = c.id
            LEFT JOIN status_catalog s  ON s.id = p.status_id
            ORDER BY COALESCE(p.paid_at, p.due_date) DESC, p.id DESC
            {$limitSql}
        ";

        $st = $this->pdo->prepare($sql);
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
                   (project_id, kind, amount, due_date, paid_at, status_id, created_at, updated_at)
                VALUES
                   (:project_id, :kind, :amount, :due_date, :paid_at, :status_id, NOW(), NOW())";

        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':project_id' => $d['project_id'],
            ':kind'       => $d['kind'],
            ':amount'     => $d['amount'],
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
