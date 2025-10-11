<?php
class Goal {
  private PDO $db;
  public function __construct(PDO $db){ $this->db = $db; }

  public function all(): array {
    $st = $this->db->query("SELECT * FROM goals ORDER BY target_date DESC");
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public function find(int $id): ?array {
    $st = $this->db->prepare("SELECT * FROM goals WHERE id=?");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public function create(array $data): int {
    $sql = "INSERT INTO goals (title, description, target_amount, current_amount, target_date, achieved)
            VALUES (:title,:description,:target_amount,:current_amount,:target_date,:achieved)";
    $st = $this->db->prepare($sql);
    $st->execute([
      ':title'=>$data['title'],
      ':description'=>$data['description'] ?? null,
      ':target_amount'=>$data['target_amount'],
      ':current_amount'=>$data['current_amount'] ?? 0,
      ':target_date'=>$data['target_date'] ?? null,
      ':achieved'=>!empty($data['achieved'])?1:0
    ]);
    return (int)$this->db->lastInsertId();
  }

  public function update(int $id, array $data): bool {
    $sql = "UPDATE goals SET title=:title, description=:description, target_amount=:target_amount,
            current_amount=:current_amount, target_date=:target_date, achieved=:achieved
            WHERE id=:id";
    $st = $this->db->prepare($sql);
    return $st->execute([
      ':title'=>$data['title'],
      ':description'=>$data['description'] ?? null,
      ':target_amount'=>$data['target_amount'],
      ':current_amount'=>$data['current_amount'] ?? 0,
      ':target_date'=>$data['target_date'] ?? null,
      ':achieved'=>!empty($data['achieved'])?1:0,
      ':id'=>$id
    ]);
  }

  public function delete(int $id): bool {
    $st = $this->db->prepare("DELETE FROM goals WHERE id=?");
    return $st->execute([$id]);
  }
}
