<?php
class Competence extends BaseModel
{
    protected string $table = 'competence';
    protected string $pk    = 'id_competence';

    public function create(array $data): int|bool
    {
        $stmt = $this->db->prepare("INSERT INTO competence (nom) VALUES (?)");
        $stmt->execute([$data['nom']]);
        return (int) $this->db->lastInsertId();
    }

    public function getByProfil(int $id_profil): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.* FROM competence c
             JOIN competence_candidat cc ON c.id_competence = cc.id_competence
             WHERE cc.id_profil = ?"
        );
        $stmt->execute([$id_profil]);
        return $stmt->fetchAll();
    }

    public function getByOffre(int $id_offre): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.* FROM competence c
             JOIN competence_offre co ON c.id_competence = co.id_competence
             WHERE co.id_offre = ?"
        );
        $stmt->execute([$id_offre]);
        return $stmt->fetchAll();
    }
}
