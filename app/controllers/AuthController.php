<?php
class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function login(): void
    {
        if (Auth::check()) {
            header('Location: ' . BASE_URL . '/index.php?url=dashboard');
            exit;
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Csrf::requireValid();
                $email = trim($_POST['email'] ?? '');
                $mdp   = $_POST['mot_de_passe'] ?? '';

                $user = $this->authService->login($email, $mdp);

                if ($user) {
                    $_SESSION['user']    = $user;
                    $_SESSION['id_user'] = $user['id_user'];
                    $_SESSION['role']    = $user['nom_role'];

                    // Régénérer l'ID de session (sécurité)
                    session_regenerate_id(true);

                    header('Location: ' . BASE_URL . '/index.php?url=dashboard');
                    exit;
                }
                $error = "Email ou mot de passe incorrect.";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        require ROOT . '/app/views/auth/login.php';
    }

    public function register(): void
    {
        if (Auth::check()) {
            header('Location: ' . BASE_URL . '/index.php?url=dashboard');
            exit;
        }

        $error   = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Csrf::requireValid();
                $nom    = trim($_POST['nom']    ?? '');
                $prenom = trim($_POST['prenom'] ?? '');
                $email  = trim($_POST['email']  ?? '');
                $mdp    = $_POST['mot_de_passe'] ?? '';
                $role   = $_POST['role_choice'] ?? 'CANDIDAT';

                $this->authService->register($nom, $prenom, $email, $mdp, $role);

                header('Location: ' . BASE_URL . '/index.php?url=auth/login&registered=1');
                exit;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        }

        require ROOT . '/app/views/auth/register.php';
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die("Méthode non autorisée.");
        }

        Csrf::requireValid();
        Auth::logout();
        header('Location: ' . BASE_URL . '/index.php?url=auth/login');
        exit;
    }
}
