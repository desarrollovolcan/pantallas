<?php
class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['admin_id']) && isset($_SESSION['admin_usuario']);
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            header('Location: ' . base_path('login.php'));
            exit();
        }

        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_destroy();
            header('Location: ' . base_path('login.php'));
            exit();
        }

        $_SESSION['last_activity'] = time();
    }

    public static function login(int $adminId, string $usuario): void
    {
        $_SESSION['admin_id'] = $adminId;
        $_SESSION['admin_usuario'] = $usuario;
        $_SESSION['last_activity'] = time();
    }

    public static function logout(): void
    {
        session_destroy();
    }
}
?>
