<?php
class AuthService
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(string $email, string $mdp): array|false
    {
        $data = $this->userModel->findByEmail($email);
        if (!$data) return false;
        if (!$this->userModel->verifyPassword($mdp, $data['mot_de_passe'])) return false;
        return $data;
    }

    public function register(
        string $nom, string $prenom,
        string $email, string $mdp,
        string $role = 'CANDIDAT'
    ): int {
        if (empty($nom) || empty($prenom) || empty($email) || empty($mdp)) {
            throw new InvalidArgumentException("Tous les champs sont obligatoires.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Adresse email invalide.");
        }
        if (strlen($mdp) < 6) {
            throw new InvalidArgumentException("Le mot de passe doit contenir au moins 6 caractères.");
        }
        if ($this->userModel->findByEmail($email)) {
            throw new InvalidArgumentException("Cet email est déjà utilisé.");
        }

        $allowed_roles = ['CANDIDAT', 'RECRUTEUR'];
        $role = in_array($role, $allowed_roles) ? $role : 'CANDIDAT';

        $hash = password_hash($mdp, PASSWORD_BCRYPT);
        $this->userModel->createUser($nom, $prenom, $email, $hash, $role);
        return $this->userModel->getLastInsertId();
    }
}
