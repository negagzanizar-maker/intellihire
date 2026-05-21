<?php
class Candidature extends BaseModel
{
    protected string $table = 'candidature';
    protected string $pk    = 'id_candidature';

    /** Poids des compétences vs. expérience dans le score final (∈ [0,1]) */
    private const ALPHA = 0.75;

    /** Raison de la suite géométrique : chaque année vaut r × la précédente */
    private const EXP_RATIO = 0.70;

    public function getAll(array $filters = []): array
    {
        $sql = "SELECT c.*, o.titre AS titre_offre, o.type_contrat,
                       u.nom, u.prenom, u.email,
                       p.cv_path, p.experience, p.id_profil
                FROM candidature c
                JOIN offre_emploi o    ON c.id_offre  = o.id_offre
                JOIN profil_candidat p ON c.id_profil = p.id_profil
                JOIN utilisateur u     ON p.id_user   = u.id_user
                WHERE 1=1";
        $params = [];

        if (!empty($filters['id_offre'])) {
            $sql .= " AND c.id_offre = ?"; $params[] = $filters['id_offre'];
        }
        if (!empty($filters['statut'])) {
            $sql .= " AND c.statut = ?"; $params[] = $filters['statut'];
        }
        if (!empty($filters['id_profil'])) {
            $sql .= " AND c.id_profil = ?"; $params[] = $filters['id_profil'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR o.titre LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql .= " ORDER BY c.score_matching DESC, c.created_at DESC";

        // Pagination
        if (!empty($filters['limit'])) {
            $offset = (int)($filters['offset'] ?? 0);
            $sql   .= " LIMIT " . (int)$filters['limit'] . " OFFSET $offset";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM candidature c
                JOIN offre_emploi o    ON c.id_offre  = o.id_offre
                JOIN profil_candidat p ON c.id_profil = p.id_profil
                JOIN utilisateur u     ON p.id_user   = u.id_user
                WHERE 1=1";
        $params = [];
        if (!empty($filters['id_offre']))  { $sql .= " AND c.id_offre = ?";  $params[] = $filters['id_offre']; }
        if (!empty($filters['statut']))    { $sql .= " AND c.statut = ?";    $params[] = $filters['statut']; }
        if (!empty($filters['id_profil'])) { $sql .= " AND c.id_profil = ?"; $params[] = $filters['id_profil']; }
        if (!empty($filters['search'])) {
            $sql .= " AND (u.nom LIKE ? OR u.prenom LIKE ? OR o.titre LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, o.titre AS titre_offre, o.type_contrat, o.localisation,
                    u.nom, u.prenom, u.email,
                    p.cv_path, p.experience, p.id_profil
             FROM candidature c
             JOIN offre_emploi o    ON c.id_offre  = o.id_offre
             JOIN profil_candidat p ON c.id_profil = p.id_profil
             JOIN utilisateur u     ON p.id_user   = u.id_user
             WHERE c.id_candidature = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data): int|bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO candidature (id_offre, id_profil, lettre_motiv, score_matching)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['id_offre'],
            $data['id_profil'],
            $data['lettre_motiv']    ?? '',
            $data['score_matching']  ?? 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function findByProfilAndOffre(int $id_profil, int $id_offre): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM candidature WHERE id_profil = ? AND id_offre = ?"
        );
        $stmt->execute([$id_profil, $id_offre]);
        return $stmt->fetch();
    }

    public function updateStatut(int $id, string $statut): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE candidature SET statut = ? WHERE id_candidature = ?"
        );
        return $stmt->execute([$statut, $id]);
    }

    public function addHistorique(
        int $id_candidature, string $ancien,
        string $nouveau, string $note, int $id_user
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO candidature_historique
                (id_candidature, ancien_statut, nouveau_statut, note, id_user)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$id_candidature, $ancien, $nouveau, $note, $id_user]);
    }

    public function getHistorique(int $id_candidature): array
    {
        $stmt = $this->db->prepare(
            "SELECT h.*, u.nom, u.prenom
             FROM candidature_historique h
             LEFT JOIN utilisateur u ON h.id_user = u.id_user
             WHERE h.id_candidature = ?
             ORDER BY h.created_at ASC"
        );
        $stmt->execute([$id_candidature]);
        return $stmt->fetchAll();
    }

    public function getProfilByUser(int $id_user): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM profil_candidat WHERE id_user = ?"
        );
        $stmt->execute([$id_user]);
        return $stmt->fetch();
    }

    public function createProfil(int $id_user, string $cv_path, int $experience): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO profil_candidat (id_user, cv_path, experience) VALUES (?, ?, ?)"
        );
        $stmt->execute([$id_user, $cv_path, $experience]);
        return (int) $this->db->lastInsertId();
    }

    public function updateProfil(int $id_profil, string $cv_path, int $experience): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE profil_candidat SET cv_path = ?, experience = ? WHERE id_profil = ?"
        );
        return $stmt->execute([$cv_path, $experience, $id_profil]);
    }

    public function attachCompetencesCandidat(int $id_profil, array $ids): void
    {
        $this->db->prepare("DELETE FROM competence_candidat WHERE id_profil = ?")->execute([$id_profil]);
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO competence_candidat (id_profil, id_competence) VALUES (?, ?)"
        );
        foreach ($ids as $id) {
            $stmt->execute([$id_profil, (int)$id]);
        }
    }

    public function getStatsByStatut(): array
    {
        $stmt = $this->db->query(
            "SELECT statut, COUNT(*) AS total FROM candidature GROUP BY statut"
        );
        $rows   = $stmt->fetchAll();
        $result = ['total' => 0, 'recues' => 0, 'en_cours' => 0, 'entretiens' => 0, 'acceptees' => 0, 'refusees' => 0];
        $map    = ['Recue' => 'recues', 'En_cours' => 'en_cours', 'Entretien' => 'entretiens', 'Acceptee' => 'acceptees', 'Refusee' => 'refusees'];
        foreach ($rows as $row) {
            $result['total'] += $row['total'];
            if (isset($map[$row['statut']])) $result[$map[$row['statut']]] = (int)$row['total'];
        }
        return $result;
    }

    /** Candidatures regroupées par mois (12 derniers mois) */
    public function getParMois(): array
    {
        $stmt = $this->db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(*) AS total
             FROM candidature
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY mois ORDER BY mois ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Calcule le score de matching candidat/offre.
     *
     *   score = α · (compétences possédées / requises) · 100
     *         + (1 − α) · (1 − rⁿ) · 100        ← suite géométrique sur n années
     *
     * La somme partielle d'une suite géométrique de raison r ∈ (0,1) est
     *   Sₙ = (1 − rⁿ) / (1 − r),
     * normalisée par sa limite 1/(1−r) on obtient f(n) = 1 − rⁿ ∈ [0,1).
     * Cette fonction modélise l'apport décroissant de chaque année supplémentaire.
     *
     * @return array{score:float,matched:string[],missing:string[],total:int,skill_score:float,exp_factor:float,years:int}
     */
    public function calculerScore(int $id_profil, int $id_offre): array
    {
        $stmt = $this->db->prepare(
            "SELECT  c.nom,
                     (cc.id_competence IS NOT NULL) AS possede,
                     p.experience
             FROM    competence_offre co
             JOIN    competence c        ON c.id_competence = co.id_competence
             JOIN    profil_candidat p   ON p.id_profil     = ?
             LEFT JOIN competence_candidat cc
                    ON cc.id_competence = co.id_competence
                   AND cc.id_profil     = p.id_profil
             WHERE   co.id_offre = ?
             ORDER BY possede DESC, c.nom ASC"
        );
        $stmt->execute([$id_profil, $id_offre]);
        $rows = $stmt->fetchAll();

        // Aucune compétence requise → on ne peut pas comparer, on neutralise.
        if (empty($rows)) {
            return [
                'score'       => 100.0,
                'matched'     => [],
                'missing'     => [],
                'total'       => 0,
                'skill_score' => 100.0,
                'exp_factor'  => 0.0,
                'years'       => 0,
            ];
        }

        $matched = array_values(array_map(
            fn($r) => $r['nom'],
            array_filter($rows, fn($r) => (int)$r['possede'] === 1)
        ));
        $missing = array_values(array_map(
            fn($r) => $r['nom'],
            array_filter($rows, fn($r) => (int)$r['possede'] === 0)
        ));
        $total = count($rows);
        $years = (int)($rows[0]['experience'] ?? 0);

        $math = self::computeScore(count($matched), $total, $years);

        return [
            'score'       => $math['score'],
            'matched'     => $matched,
            'missing'     => $missing,
            'total'       => $total,
            'skill_score' => $math['skill_score'],
            'exp_factor'  => $math['exp_factor'],
            'years'       => $years,
        ];
    }

    /**
     * Cœur mathématique du matching, isolé pour être testable sans base de données.
     *
     *   score = α · (matched / total) · 100  +  (1 − α) · (1 − rⁿ) · 100
     *
     * @return array{score:float,skill_score:float,exp_factor:float}
     */
    public static function computeScore(int $matched, int $total, int $years): array
    {
        $exp_factor = 1 - (self::EXP_RATIO ** $years);

        if ($total === 0) {
            return [
                'score'       => 100.0,
                'skill_score' => 100.0,
                'exp_factor'  => round($exp_factor, 4),
            ];
        }

        $skill_score = $matched / $total * 100;
        $final       = self::ALPHA * $skill_score
                     + (1 - self::ALPHA) * $exp_factor * 100;

        return [
            'score'       => round($final,       2),
            'skill_score' => round($skill_score, 2),
            'exp_factor'  => round($exp_factor,  4),
        ];
    }
}
