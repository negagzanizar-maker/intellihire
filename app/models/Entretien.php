<?php
class Entretien extends BaseModel
{
    protected string $table = 'entretien';
    protected string $pk    = 'id_entretien';

    public function getAll(array $filters = []): array
    {
        $sql = "SELECT e.*, o.titre AS titre_offre,
                       u.nom, u.prenom, u.email
                FROM entretien e
                JOIN candidature c    ON e.id_candidature = c.id_candidature
                JOIN offre_emploi o   ON c.id_offre       = o.id_offre
                JOIN profil_candidat p ON c.id_profil     = p.id_profil
                JOIN utilisateur u    ON p.id_user        = u.id_user
                WHERE 1=1";
        $params = [];

        if (!empty($filters['decision'])) {
            $sql .= " AND e.decision = ?"; $params[] = $filters['decision'];
        }
        if (!empty($filters['type_entretien'])) {
            $sql .= " AND e.type_entretien = ?"; $params[] = $filters['type_entretien'];
        }

        $sql .= " ORDER BY e.date_entretien ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, o.titre AS titre_offre, u.nom, u.prenom, u.email
             FROM entretien e
             JOIN candidature c    ON e.id_candidature = c.id_candidature
             JOIN offre_emploi o   ON c.id_offre       = o.id_offre
             JOIN profil_candidat p ON c.id_profil     = p.id_profil
             JOIN utilisateur u    ON p.id_user        = u.id_user
             WHERE e.id_entretien = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int|bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO entretien (id_candidature, date_entretien, type_entretien, lieu_ou_lien)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['id_candidature'],
            $data['date_entretien'],
            $data['type_entretien'],
            $data['lieu_ou_lien'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findByCandidature(int $id_candidature): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM entretien WHERE id_candidature = ?"
        );
        $stmt->execute([$id_candidature]);
        return $stmt->fetch();
    }

    public function updateDecision(int $id, string $decision, string $compte_rendu): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE entretien SET decision = ?, compte_rendu = ? WHERE id_entretien = ?"
        );
        return $stmt->execute([$decision, $compte_rendu, $id]);
    }

    public function getUpcoming(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            "SELECT e.*, o.titre AS titre_offre, u.nom, u.prenom
             FROM entretien e
             JOIN candidature c    ON e.id_candidature = c.id_candidature
             JOIN offre_emploi o   ON c.id_offre       = o.id_offre
             JOIN profil_candidat p ON c.id_profil     = p.id_profil
             JOIN utilisateur u    ON p.id_user        = u.id_user
             WHERE e.date_entretien >= NOW() AND e.decision = 'En_attente'
             ORDER BY e.date_entretien ASC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getStats(): array
    {
        $stmt   = $this->db->query("SELECT decision, COUNT(*) AS total FROM entretien GROUP BY decision");
        $rows   = $stmt->fetchAll();
        $result = ['total' => 0, 'en_attente' => 0, 'valides' => 0, 'refuses' => 0];
        $map    = ['En_attente' => 'en_attente', 'Valide' => 'valides', 'Refuse' => 'refuses'];
        foreach ($rows as $row) {
            $result['total'] += $row['total'];
            if (isset($map[$row['decision']])) $result[$map[$row['decision']]] = (int)$row['total'];
        }
        return $result;
    }
}
