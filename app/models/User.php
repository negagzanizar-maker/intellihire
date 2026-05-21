<?php
/**
 * app/models/User.php
 * Modèle utilisateur de base — méthodes communes à tous les rôles
 */
class User extends BaseModel
{
    protected string $table = 'utilisateur';
    protected string $pk    = 'id_user';

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, r.nom_role
             FROM utilisateur u
             JOIN role r ON u.id_role = r.id_role
             WHERE u.email = ? AND u.actif = 1"
        );
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function getRoleId(string $nom_role): ?int
    {
        $stmt = $this->db->prepare("SELECT id_role FROM role WHERE nom_role = ?");
        $stmt->execute([$nom_role]);
        $row = $stmt->fetch();
        return $row ? (int)$row['id_role'] : null;
    }

    public function createUser(string $nom, string $prenom, string $email, string $hash, string $role = 'CANDIDAT'): bool
    {
        $id_role = $this->getRoleId($role) ?? $this->getRoleId('CANDIDAT');
        $stmt = $this->db->prepare(
            "INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, id_role) VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$nom, $prenom, $email, $hash, $id_role]);
    }

    public function getAll(array $filters = []): array
    {
        $stmt = $this->db->query(
            "SELECT u.*, r.nom_role FROM utilisateur u JOIN role r ON u.id_role = r.id_role ORDER BY u.created_at DESC"
        );
        return $stmt->fetchAll();
    }
}
