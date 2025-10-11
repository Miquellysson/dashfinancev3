<?php

class NotificationModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function unreadForUser(int $userId, int $limit = 20): array {
        $stmt = $this->pdo->prepare("\n            SELECT id, resource_type, resource_id, title, message, trigger_at, read_at\n            FROM notifications\n            WHERE user_id = ?\n              AND (read_at IS NULL OR read_at = '0000-00-00 00:00:00')\n              AND trigger_at <= NOW()\n            ORDER BY trigger_at DESC\n            LIMIT ?\n        ");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead(int $userId, array $ids): void {
        if (empty($ids)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        foreach ($ids as $index => $id) {
            $stmt->bindValue($index + 2, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
    }

    public function schedule(array $data): int {
        $stmt = $this->pdo->prepare("\n            INSERT INTO notifications (user_id, resource_type, resource_id, title, message, trigger_at, sound, created_at, updated_at)\n            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())\n        ");
        $stmt->execute([
            $data['user_id'],
            $data['resource_type'],
            $data['resource_id'] ?? null,
            $data['title'],
            $data['message'] ?? null,
            $data['trigger_at'],
            isset($data['sound']) ? (int)$data['sound'] : 1,
        ]);
        return (int)$this->pdo->lastInsertId();
    }
}
