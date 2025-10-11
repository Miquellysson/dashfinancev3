<?php

class UserModel {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function listActive(): array {
        $stmt = $this->pdo->query("
            SELECT id, nome_completo, email, tipo_usuario
            FROM users
            WHERE ativo = 1 AND deleted_at IS NULL
            ORDER BY nome_completo
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM users
            WHERE id = :id AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM users
            WHERE email = :email AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public function create(array $data): int {
        $payload = $this->normalizePayload($data, true);
        $sql = "
            INSERT INTO users (
                nome_completo, email, password,
                tipo_usuario, telefone, cargo, foto_perfil,
                ativo, data_cadastro, ultimo_acesso, senha_atualizada_em,
                password_reset_token, password_reset_expires,
                created_at, updated_at
            ) VALUES (
                :nome_completo, :email, :password,
                :tipo_usuario, :telefone, :cargo, :foto_perfil,
                :ativo, NOW(), :ultimo_acesso, NOW(),
                NULL, NULL,
                NOW(), NOW()
            )
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($payload);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $payload = $this->normalizePayload($data, false);
        $payload[':id'] = $id;

        $setPassword = '';
        if (!empty($payload[':password'])) {
            $setPassword = ', password = :password, senha_atualizada_em = NOW()';
        } else {
            unset($payload[':password']);
        }

        $sql = "
            UPDATE users SET
                nome_completo = :nome_completo,
                email = :email,
                tipo_usuario = :tipo_usuario,
                telefone = :telefone,
                cargo = :cargo,
                foto_perfil = :foto_perfil,
                ativo = :ativo,
                ultimo_acesso = :ultimo_acesso,
                updated_at = NOW()
                {$setPassword}
            WHERE id = :id
        ";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($payload);
    }

    public function toggleActive(int $id, bool $active): bool {
        $stmt = $this->pdo->prepare("
            UPDATE users SET ativo = :ativo, updated_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([':ativo' => $active ? 1 : 0, ':id' => $id]);
    }

    public function countAll(): int {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL");
        return (int)$stmt->fetchColumn();
    }

    public function countActive(): int {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND ativo = 1");
        return (int)$stmt->fetchColumn();
    }

    public function softDelete(int $id): bool {
        $stmt = $this->pdo->prepare("UPDATE users SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function logAction(int $usuarioId, string $acao, array $detalhes = []): void {
        $stmt = $this->pdo->prepare("
            INSERT INTO user_audit_logs (usuario_id, acao, detalhes, ip, user_agent)
            VALUES (:usuario_id, :acao, :detalhes, :ip, :agent)
        ");
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':acao' => $acao,
            ':detalhes' => !empty($detalhes) ? json_encode($detalhes) : null,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        ]);
    }

    private function normalizePayload(array $data, bool $isCreate): array {
        $nomeCompleto = trim($data['nome_completo'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $tipo = $data['tipo_usuario'] ?? 'Colaborador';
        if (!in_array($tipo, ['Admin','Gerente','Colaborador','Cliente'], true)) {
            $tipo = 'Colaborador';
        }

        $hash = null;
        if ($isCreate || !empty($data['password'])) {
            $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $telefone = Utils::normalizePhone($data['telefone'] ?? '');
        $cargo = trim($data['cargo'] ?? '');

        return [
            ':nome_completo' => $nomeCompleto,
            ':email' => $email,
            ':password' => $hash,
            ':tipo_usuario' => $tipo,
            ':telefone' => $telefone,
            ':cargo' => $cargo !== '' ? $cargo : null,
            ':foto_perfil' => $data['foto_perfil'] ?? null,
            ':ativo' => isset($data['ativo']) ? (int)$data['ativo'] : 1,
            ':ultimo_acesso' => $data['ultimo_acesso'] ?? null,
        ];
    }
}
