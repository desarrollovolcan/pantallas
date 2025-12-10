<?php
class AuthController extends Controller
{
    public function __construct(private AdminModel $adminModel)
    {
    }

    public function showLogin(string $error = ''): void
    {
        $this->view('auth/login', ['error' => $error]);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->showLogin();
            return;
        }

        $usuario = sanitizeInput($_POST['usuario'] ?? '');
        $password = $_POST['contrasena'] ?? '';

        $admin = $this->adminModel->findByUsuario($usuario);
        if (!$admin || !verifyPassword($password, $admin['contrasena'])) {
            $this->showLogin('Usuario o contraseña incorrectos');
            return;
        }

        Auth::login((int) $admin['id'], $admin['usuario']);
        header('Location: /admin/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        header('Location: /');
    }
}
?>
