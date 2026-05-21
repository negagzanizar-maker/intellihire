<?php
class OffreService extends BaseService
{
    public function __construct() { $this->model = new Offre(); }

    public function creerOffre(array $data): int
    {
        if (empty($data['titre'])) throw new InvalidArgumentException("Le titre est obligatoire.");
        if (empty($data['description'])) throw new InvalidArgumentException("La description est obligatoire.");
        return $this->model->create($data);
    }

    public function attacheCompetences(int $id_offre, array $ids): void
    {
        $this->model->attachCompetences($id_offre, $ids);
    }

    public function getCompetences(int $id_offre): array
    {
        return $this->model->getCompetences($id_offre);
    }

    public function getStatsByOffre(): array { return $this->model->getStatsByOffre(); }

    public function getVisibleForListing(array $filters, bool $isAuthenticated, ?string $role, int $id_user): array
    {
        if ($role === 'CANDIDAT') {
            $filters['statut'] = 'Publiee';
            $candidatureModel = new Candidature();
            $profil = $candidatureModel->getProfilByUser($id_user);

            if ($profil) {
                return $this->getAllForCandidatWithScore(
                    (int)$profil['id_profil'],
                    (int)$profil['experience'],
                    $filters
                );
            }

            return $this->getAll($filters);
        }

        if (!$isAuthenticated) {
            $filters['statut'] = 'Publiee';
        }

        return $this->getAll($filters);
    }

    /**
     * Retourne les offres publiées enrichies du score de matching
     * pour un profil candidat donné, triées par score décroissant.
     */
    public function getAllForCandidatWithScore(int $id_profil, int $years, array $filters = []): array
    {
        $rows = $this->model->getAllForCandidat($id_profil, $filters);

        foreach ($rows as &$row) {
            $math = Candidature::computeScore(
                (int)$row['skills_matched'],
                (int)$row['skills_total'],
                $years
            );
            $row['match_score']    = $math['score'];
            $row['match_skills']   = $math['skill_score'];
            $row['already_applied'] = (bool)($row['already_applied'] ?? false);
        }
        unset($row);

        // Tri : meilleur score d'abord, puis par date de publication.
        usort($rows, function ($a, $b) {
            if ($a['match_score'] === $b['match_score']) {
                return strcmp($b['created_at'], $a['created_at']);
            }
            return $b['match_score'] <=> $a['match_score'];
        });

        return $rows;
    }
}
