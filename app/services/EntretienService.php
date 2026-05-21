<?php
class EntretienService extends BaseService
{
    public function __construct() { $this->model = new Entretien(); }

    public function planifierEntretien(array $data): int
    {
        if (empty($data['id_candidature']) || empty($data['date_entretien'])) {
            throw new InvalidArgumentException("Candidature et date obligatoires.");
        }
        if ($this->model->findByCandidature($data['id_candidature'])) {
            throw new InvalidArgumentException("Un entretien est déjà planifié pour cette candidature.");
        }
        return $this->model->create($data);
    }

    public function enregistrerDecision(int $id, string $decision, string $compte_rendu): void
    {
        if (!in_array($decision, ['Valide', 'Refuse'])) {
            throw new InvalidArgumentException("Décision invalide.");
        }
        $this->model->updateDecision($id, $decision, $compte_rendu);
    }

    public function findByCandidature(int $id_candidature): array|false
    {
        return $this->model->findByCandidature($id_candidature);
    }

    public function getUpcoming(int $limit = 5): array { return $this->model->getUpcoming($limit); }
    public function getStats(): array                  { return $this->model->getStats(); }
}
