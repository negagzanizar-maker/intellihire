<?php
class NotificationService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create(int $id_user, string $message, ?string $lien = null): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notification (id_user, message, lien) VALUES (?, ?, ?)"
        );
        $stmt->execute([$id_user, $message, $lien]);
    }

    public function getByUser(int $id_user, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM notification WHERE id_user = ?
             ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$id_user, $limit]);
        return $stmt->fetchAll();
    }

    public function countUnread(int $id_user): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notification WHERE id_user = ? AND lu = 0"
        );
        $stmt->execute([$id_user]);
        return (int) $stmt->fetchColumn();
    }

    public function markAllRead(int $id_user): void
    {
        $stmt = $this->db->prepare(
            "UPDATE notification SET lu = 1 WHERE id_user = ?"
        );
        $stmt->execute([$id_user]);
    }

    /** Notifie tous les recruteurs d'une nouvelle candidature */
    public function notifyNewApplication(string $candidatName, string $offreTitle, int $id_candidature): void
    {
        $stmt = $this->db->query(
            "SELECT id_user FROM utilisateur WHERE id_role = 1 AND actif = 1"
        );
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $msg   = "📋 Nouvelle candidature de {$candidatName} pour \"{$offreTitle}\"";
        $lien  = "candidatures/detail/{$id_candidature}";
        foreach ($users as $uid) {
            $this->create((int)$uid, $msg, $lien);
        }
    }
}
