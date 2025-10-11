<?php

class RateLimiter {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function allow(string $ip, string $route, int $maxAttempts, int $minutes): bool {
        $stmt = $this->pdo->prepare("SELECT id, tentativas, primeiro_registro FROM rate_limits WHERE ip = :ip AND rota = :rota");
        $stmt->execute([':ip' => $ip, ':rota' => $route]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $now = new DateTimeImmutable('now');
        $windowStart = $now->modify("-{$minutes} minutes");

        if ($row) {
            $first = new DateTimeImmutable($row['primeiro_registro']);
            if ($first < $windowStart) {
                $upd = $this->pdo->prepare("UPDATE rate_limits SET tentativas = 1, primeiro_registro = NOW(), ultimo_registro = NOW() WHERE id = :id");
                $upd->execute([':id' => $row['id']]);
                return true;
            }

            if ((int)$row['tentativas'] >= $maxAttempts) {
                $upd = $this->pdo->prepare("UPDATE rate_limits SET ultimo_registro = NOW() WHERE id = :id");
                $upd->execute([':id' => $row['id']]);
                return false;
            }

            $upd = $this->pdo->prepare("UPDATE rate_limits SET tentativas = tentativas + 1, ultimo_registro = NOW() WHERE id = :id");
            $upd->execute([':id' => $row['id']]);
            return true;
        }

        $ins = $this->pdo->prepare("INSERT INTO rate_limits (ip, rota, tentativas) VALUES (:ip, :rota, 1)");
        $ins->execute([':ip' => $ip, ':rota' => $route]);
        return true;
    }
}
