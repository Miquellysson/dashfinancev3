<?php
class ProjectModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /* -----------------------
       Helpers de compatibilidade
       ----------------------- */

    /** Converte status textual (ex.: 'Pending','Paid','Overdue','Dropped') para status_id em status_catalog. Cria se não existir. */
    private function resolveStatusId(?string $statusName): ?int {
        if (!$statusName) return null;
        $statusName = trim($statusName);
        if ($statusName === '') return null;

        // tenta achar
        $st = $this->pdo->prepare("SELECT id FROM status_catalog WHERE name = :n LIMIT 1");
        $st->execute([':n' => $statusName]);
        $id = $st->fetchColumn();
        if ($id) return (int)$id;

        // cria com sort_order no fim
        $max = (int)$this->pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM status_catalog")->fetchColumn();
        $ins = $this->pdo->prepare("INSERT INTO status_catalog (name, color_hex, sort_order, created_at) VALUES (:n, '#6b7280', :ord, NOW())");
        $ins->execute([':n'=>$statusName, ':ord'=>$max+1]);

        return (int)$this->pdo->lastInsertId();
    }

    /** Normaliza payload antigo -> novo schema */
    private function normalizeData(array $data): array {
        $out = [];

        // obrigatórios
        $out['client_id']     = $data['client_id'] ?? null;
        $out['name']          = $data['name'] ?? null;

        // map antiga -> nova
        $out['project_value'] = $data['project_value'] ?? ($data['budget'] ?? null);

        // status pode vir como id ou texto
        if (isset($data['status_id'])) {
            $out['status_id'] = $data['status_id'] !== '' ? (int)$data['status_id'] : null;
        } elseif (isset($data['status'])) {
            $out['status_id'] = $this->resolveStatusId($data['status']);
        } else {
            $out['status_id'] = null;
        }

        // datas
        $out['due_date']  = $data['due_date']  ?? ($data['start_date'] ?? null);
        $out['paid_at']   = $data['paid_at']   ?? ($data['end_date']   ?? null);

        // recorrência (se vier)
        $out['recurrence_active']     = isset($data['recurrence_active']) ? (int)!!$data['recurrence_active'] : 0;
        $out['recurrence_value']      = $data['recurrence_value']      ?? null;
        $out['recurrence_frequency']  = $data['recurrence_frequency']  ?? null; // 'daily','weekly','biweekly','monthly'
        $out['recurrence_next_date']  = $data['recurrence_next_date']  ?? null;

        return $out;
    }

    /* -----------------------
       Consultas
       ----------------------- */

    public function getAll($limit = null, $offset = 0) {
        $limitSql  = '';
        $params    = [];

        if ($limit !== null) {
            $limit  = (int)$limit;
            $offset = (int)$offset;
            $limitSql = " LIMIT :lim OFFSET :off";
        }

        $sql = "
            SELECT p.*,
                   c.name AS client_name,
                   s.name AS status_name
            FROM projects p
            LEFT JOIN clients c ON p.client_id = c.id
            LEFT JOIN status_catalog s ON s.id = p.status_id
            ORDER BY p.created_at DESC
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
                   c.name AS client_name,
                   s.name AS status_name
            FROM projects p
            LEFT JOIN clients c ON p.client_id = c.id
            LEFT JOIN status_catalog s ON s.id = p.status_id
            WHERE p.id = :id
            LIMIT 1
        ");
        $st->execute([':id' => (int)$id]);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $d = $this->normalizeData($data);

        $sql = "INSERT INTO projects
                   (client_id, name, project_value, status_id, due_date, paid_at,
                    recurrence_active, recurrence_value, recurrence_frequency, recurrence_next_date,
                    created_at, updated_at)
                VALUES
                   (:client_id, :name, :project_value, :status_id, :due_date, :paid_at,
                    :recurrence_active, :recurrence_value, :recurrence_frequency, :recurrence_next_date,
                    NOW(), NOW())";

        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':client_id'           => $d['client_id'],
            ':name'                => $d['name'],
            ':project_value'       => $d['project_value'],
            ':status_id'           => $d['status_id'],
            ':due_date'            => $d['due_date'],
            ':paid_at'             => $d['paid_at'],
            ':recurrence_active'   => $d['recurrence_active'],
            ':recurrence_value'    => $d['recurrence_value'],
            ':recurrence_frequency'=> $d['recurrence_frequency'],
            ':recurrence_next_date'=> $d['recurrence_next_date'],
        ]);
    }

    public function update($id, $data) {
        $d = $this->normalizeData($data);

        $sql = "UPDATE projects SET
                    client_id = :client_id,
                    name = :name,
                    project_value = :project_value,
                    status_id = :status_id,
                    due_date = :due_date,
                    paid_at = :paid_at,
                    recurrence_active = :recurrence_active,
                    recurrence_value = :recurrence_value,
                    recurrence_frequency = :recurrence_frequency,
                    recurrence_next_date = :recurrence_next_date,
                    updated_at = NOW()
                WHERE id = :id";

        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':client_id'            => $d['client_id'],
            ':name'                 => $d['name'],
            ':project_value'        => $d['project_value'],
            ':status_id'            => $d['status_id'],
            ':due_date'             => $d['due_date'],
            ':paid_at'              => $d['paid_at'],
            ':recurrence_active'    => $d['recurrence_active'],
            ':recurrence_value'     => $d['recurrence_value'],
            ':recurrence_frequency' => $d['recurrence_frequency'],
            ':recurrence_next_date' => $d['recurrence_next_date'],
            ':id'                   => (int)$id,
        ]);
    }

    public function delete($id) {
        $st = $this->pdo->prepare("DELETE FROM projects WHERE id = :id");
        return $st->execute([':id' => (int)$id]);
    }

    public function count() {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    }
}
