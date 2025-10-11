<?php
class ClientModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll($limit = null, $offset = 0, string $orderBy = 'name', string $orderDir = 'ASC') {
        $allowedOrder = [
            'id' => 'id',
            'name' => 'name',
            'email' => 'email',
            'phone' => 'phone',
            'entry_date' => 'entry_date',
            'created_at' => 'created_at',
        ];

        $column = $allowedOrder[$orderBy] ?? 'name';
        $direction = $orderDir === 'DESC' ? 'DESC' : 'ASC';

        $sql = "
            SELECT id, name, email, phone, entry_date, notes, created_at, updated_at
            FROM clients
            ORDER BY {$column} {$direction}
        ";

        if ($limit !== null) {
            $sql .= " LIMIT :lim OFFSET :off";
            $st = $this->pdo->prepare($sql);
            $st->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $st->bindValue(':off', (int)$offset, PDO::PARAM_INT);
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $st = $this->pdo->prepare("
            SELECT id, name, email, phone, entry_date, notes, created_at, updated_at
            FROM clients
            WHERE id = :id
            LIMIT 1
        ");
        $st->execute([':id' => (int)$id]);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        // Compat: se vier 'address', grava em 'notes'
        $notes = $data['notes'] ?? ($data['address'] ?? null);

        // entry_date obrigatório no schema — se não vier, usa hoje
        $entryDate = $data['entry_date'] ?? date('Y-m-d');

        $sql = "INSERT INTO clients
                    (name, email, phone, entry_date, notes, created_at, updated_at)
                VALUES
                    (:name, :email, :phone, :entry_date, :notes, NOW(), NOW())";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':name'       => $data['name']  ?? null,
            ':email'      => $data['email'] ?? null,
            ':phone'      => $data['phone'] ?? null,
            ':entry_date' => $entryDate,
            ':notes'      => $notes,
        ]);
    }

    public function update($id, $data) {
        $notes = $data['notes'] ?? ($data['address'] ?? null);
        $entryDate = $data['entry_date'] ?? date('Y-m-d');

        $sql = "UPDATE clients SET
                    name       = :name,
                    email      = :email,
                    phone      = :phone,
                    entry_date = :entry_date,
                    notes      = :notes,
                    updated_at = NOW()
                WHERE id = :id";
        $st = $this->pdo->prepare($sql);
        return $st->execute([
            ':name'       => $data['name']  ?? null,
            ':email'      => $data['email'] ?? null,
            ':phone'      => $data['phone'] ?? null,
            ':entry_date' => $entryDate,
            ':notes'      => $notes,
            ':id'         => (int)$id,
        ]);
    }

    public function delete($id) {
        $st = $this->pdo->prepare("DELETE FROM clients WHERE id = :id");
        return $st->execute([':id' => (int)$id]);
    }

    public function count() {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    }
}
