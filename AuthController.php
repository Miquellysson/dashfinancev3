<?php
class AuthController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    // GET /auth/login
    public function login() {
        $error = $_GET['error'] ?? '';
        include __DIR__ . '/../Views/login.php';
    }

    // POST /auth/doLogin
    public function doLogin() {
        // Coleta e valida
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
            Utils::redirect('/auth/login?error=1');
            return;
        }

        // Busca usuário (tabela em inglês)
        $stmt = $this->pdo->prepare('SELECT id, nome_completo, email, password, tipo_usuario, ativo FROM users WHERE email = :e AND deleted_at IS NULL LIMIT 1');
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Não revelar qual parte falhou
        if (!$user || (int)$user['ativo'] !== 1) {
            Utils::redirect('/auth/login?error=1');
            return;
        }

        // Verifica senha
        if (!password_verify($senha, $user['password'])) {
            Utils::redirect('/auth/login?error=1');
            return;
        }

        // (Opcional) Rehash se necessário
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
            $newHash = password_hash($senha, PASSWORD_BCRYPT);
            $upd = $this->pdo->prepare('UPDATE users SET password = :h, updated_at = NOW() WHERE id = :id');
            $upd->execute([':h' => $newHash, ':id' => $user['id']]);
        }

        // Sessão
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['nome_completo'];
        $_SESSION['user_role'] = strtolower($user['tipo_usuario']) === 'admin' ? 'admin' : strtolower($user['tipo_usuario']);
        $_SESSION['user_email']= $user['email'];

        $updAccess = $this->pdo->prepare('UPDATE users SET ultimo_acesso = NOW() WHERE id = :id');
        $updAccess->execute([':id' => $user['id']]);

        // Redireciona para o dashboard
        Utils::redirect('/dashboard');
    }

    // GET /auth/logout
    public function logout() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();
        Utils::redirect('/auth/login');
    }
}
