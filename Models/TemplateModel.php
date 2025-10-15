<?php

class TemplateModel {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function all(): array {
        return $this->search();
    }

    public function search(array $filters = [], ?int $limit = null, ?int $offset = null): array {
        $sql = "
            SELECT *
            FROM templates_library
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['query'])) {
            $sql .= " AND (name LIKE :query OR description LIKE :query OR keywords LIKE :query)";
            $params[':query'] = '%' . $filters['query'] . '%';
        }

        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }

        if (!empty($filters['template_type'])) {
            $sql .= " AND template_type = :template_type";
            $params[':template_type'] = $filters['template_type'];
        }

        $sql .= " ORDER BY created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
                $params[':offset'] = (int)$offset;
            }
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM templates_library
            WHERE id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare("
            INSERT INTO templates_library
                (name, category, template_type, link, description, source_path, keywords, screenshot_path, file_path, created_at, updated_at)
            VALUES
                (:name, :category, :template_type, :link, :description, :source_path, :keywords, :screenshot_path, :file_path, NOW(), NOW())
        ");
        $stmt->execute([
            ':name'            => $data['name'],
            ':category'        => $data['category'],
            ':template_type'   => $data['template_type'],
            ':link'            => $data['link'],
            ':description'     => $data['description'],
            ':source_path'     => $data['source_path'],
            ':keywords'        => $data['keywords'],
            ':screenshot_path' => $data['screenshot_path'],
            ':file_path'       => $data['file_path'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->pdo->prepare("
            UPDATE templates_library SET
                name = :name,
                category = :category,
                template_type = :template_type,
                link = :link,
                description = :description,
                source_path = :source_path,
                keywords = :keywords,
                screenshot_path = :screenshot_path,
                file_path = :file_path,
                updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':name'            => $data['name'],
            ':category'        => $data['category'],
            ':template_type'   => $data['template_type'],
            ':link'            => $data['link'],
            ':description'     => $data['description'],
            ':source_path'     => $data['source_path'],
            ':keywords'        => $data['keywords'],
            ':screenshot_path' => $data['screenshot_path'],
            ':file_path'       => $data['file_path'],
            ':id'              => $id,
        ]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM templates_library WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countAll(): int {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM templates_library");
        return (int)$stmt->fetchColumn();
    }

    public function listFavorites(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT t.*, tf.created_at AS favorited_at
            FROM template_favorites tf
            INNER JOIN templates_library t ON t.id = tf.template_id
            WHERE tf.user_id = :user_id
            ORDER BY tf.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFavoriteIds(int $userId): array {
        $stmt = $this->pdo->prepare("
            SELECT template_id
            FROM template_favorites
            WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function addFavorite(int $userId, int $templateId): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO template_favorites (user_id, template_id)
            VALUES (:user_id, :template_id)
            ON DUPLICATE KEY UPDATE created_at = created_at
        ");
        return $stmt->execute([
            ':user_id' => $userId,
            ':template_id' => $templateId,
        ]);
    }

    public function removeFavorite(int $userId, int $templateId): bool {
        $stmt = $this->pdo->prepare("
            DELETE FROM template_favorites
            WHERE user_id = :user_id AND template_id = :template_id
        ");
        return $stmt->execute([
            ':user_id' => $userId,
            ':template_id' => $templateId,
        ]);
    }
}
