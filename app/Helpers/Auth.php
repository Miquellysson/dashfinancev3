<?php
class Auth {
    /** Verifica se usuário está logado, senão redireciona para /auth/login */
    public static function check() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // Se não está logado
        if (empty($_SESSION['user_id'])) {
            // Permitir acessar somente rotas de auth (login/doLogin/logout)
            if (stripos($uri, '/auth/login') === false &&
                stripos($uri, '/auth/doLogin') === false &&
                stripos($uri, '/auth/logout') === false) {

                header('Location: /auth/login');
                exit;
            }
        }
    }

    /** Retorna se o usuário é admin */
    public static function isAdmin(): bool {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }

    /** Exige perfil admin, senão redireciona */
    public static function requireAdmin() {
        if (!self::isAdmin()) {
            header('Location: /dashboard?error=permission');
            exit;
        }
    }
}
