<?php
class Offre extends BaseModel
{
    protected string $table = 'offre_emploi';
    protected string $pk    = 'id_offre';

    public function getAll(array $filters = []): array
    {
        $sql    = "SELECT o.*, u.nom, u.prenom, u.email,
                          (SELECT COUNT(*) FROM candidature c WHERE c.id_offre = o.id_offre) AS nb_candidatures
                   FROM offre_emploi o
                   LEFT JOIN utilisateur u ON o.id_recruteur = u.id_user
                   WHERE 1=1";
        $params = [];

        if (!empty($filters['statut'])) {
            $sql .= " AND o.statut = ?"; $params[] = $filters['statut'];
        }
        if (!empty($filters['type_contrat'])) {
            $sql .= " AND o.type_contrat = ?"; $params[] = $filters['type_contrat'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (o.titre LIKE ? OR o.description LIKE ? OR o.localisation LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql .= " ORDER BY o.created_at DESC";
        $stmt  = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT o.*, u.nom, u.prenom, u.email
             FROM offre_emploi o
             LEFT JOIN utilisateur u ON o.id_recruteur = u.id_user
             WHERE o.id_offre = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int|bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO offre_emploi (titre, description, type_contrat, localisation, statut, id_recruteur)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['titre'],
            $data['description'],
            $data['type_contrat'] ?? 'CDI',
            $data['localisation'] ?? '',
            $data['statut']       ?? 'Brouillon',
            $data['id_recruteur'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function attachCompetences(int $id_offre, array $ids): void
    {
        $this->db->prepare("DELETE FROM competence_offre WHERE id_offre = ?")->execute([$id_offre]);
        $stmt = $this->db->prepare("INSERT IGNORE INTO competence_offre (id_offre, id_competence) VALUES (?, ?)");
        foreach ($ids as $id) {
            $stmt->execute([$id_offre, (int)$id]);
        }
    }

    public function getCompetences(int $id_offre): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.* FROM competence c
             JOIN competence_offre co ON c.id_competence = co.id_competence
             WHERE co.id_offre = ?"
        );
        $stmt->execute([$id_offre]);
        return $stmt->fetchAll();
    }

    public function getStatsByOffre(): array
    {
        $stmt = $this->db->query(
            "SELECT o.titre, COUNT(c.id_candidature) AS nb
             FROM offre_emploi o
             LEFT JOIN candidature c ON c.id_offre = o.id_offre
             WHERE o.statut = 'Publiee'
             GROUP BY o.id_offre
             ORDER BY nb DESC
             LIMIT 8"
        );
        return $stmt->fetchAll();
    }

    /**
     * Liste les offres avec les compteurs nécessaires pour calculer
     * un score de matching côté candidat (compétences requises totales,
     * compétences en commun avec le profil, années d'expérience).
     * Le score lui-même est calculé en PHP via Candidature::computeScore.
     *
     * Le candidat ne voit jamais que des offres publiées.
     */
    public function getAllForCandidat(int $id_profil, array $filters = []): array
    {
        $sql = "SELECT  o.*, u.nom, u.prenom, u.email,
                        (SELECT COUNT(*) FROM candidature c
                         WHERE c.id_offre = o.id_offre) AS nb_candidatures,
                        (SELECT COUNT(*) FROM competence_offre
                         WHERE id_offre = o.id_offre) AS skills_total,
                        (SELECT COUNT(*) FROM competence_offre co
                         JOIN competence_candidat cc
                           ON cc.id_competence = co.id_competence
                         WHERE co.id_offre = o.id_offre
                           AND cc.id_profil = ?) AS skills_matched,
                        (SELECT 1 FROM candidature c
                         WHERE c.id_offre  = o.id_offre
                           AND c.id_profil = ?
                         LIMIT 1) AS already_applied
                FROM offre_emploi o
                LEFT JOIN utilisateur u ON o.id_recruteur = u.id_user
                WHERE o.statut = 'Publiee'";
        $params = [$id_profil, $id_profil];

        if (!empty($filters['type_contrat'])) {
            $sql .= " AND o.type_contrat = ?"; $params[] = $filters['type_contrat'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (o.titre LIKE ? OR o.description LIKE ? OR o.localisation LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql .= " ORDER BY o.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

}
