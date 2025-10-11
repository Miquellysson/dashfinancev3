<?php
require_once __DIR__ . '/../Models/NotificationModel.php';

class NotificationsController {
    private PDO $pdo;
    private NotificationModel $model;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['user_id'])) { Utils::redirect('/auth/login'); }
        $this->model = new NotificationModel($pdo);
    }

    public function feed() {
        header('Content-Type: application/json');
        $userId = (int)$_SESSION['user_id'];
        $items = $this->model->unreadForUser($userId, 20);
        echo json_encode(['items' => $items]);
    }

    public function markRead() {
        header('Content-Type: application/json');
        $userId = (int)$_SESSION['user_id'];
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $ids = $payload['ids'] ?? [];
        $this->model->markAsRead($userId, array_map('intval', $ids));
        echo json_encode(['status' => 'ok']);
    }
}
