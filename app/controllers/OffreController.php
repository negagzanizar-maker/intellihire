<?php
class OffreController
{
    private OffreService $offreService;
    private Competence   $competenceModel;

    public function __construct()
    {
        $this->offreService    = new OffreService();
        $this->competenceModel = new Competence();
    }

    public function index(): void
    {
        $filters = [
            'statut'       => $_GET['statut']       ?? '',
            'type_contrat' => $_GET['type_contrat'] ?? '',
            'search'       => $_GET['search']       ?? '',
        ];

        $offres = $this->offreService->getVisibleForListing(
            $filters,
            Auth::check(),
            Auth::role(),
            Auth::id()
        );

        require ROOT . '/app/views/offres/index.php';
    }

    public function detail(?string $id): void
    {
        if (!$id) { header('Location: ' . BASE_URL . '/index.php?url=offres'); exit; }
        $offre       = $this->offreService->findById((int)$id);
        $competences = $this->offreService->getCompetences((int)$id);
        if (!$offre) { http_response_code(404); require ROOT . '/app/views/errors/404.php'; exit; }
        require ROOT . '/app/views/offres/detail.php';
    }

    public function create(): void
    {
        Auth::require(['RECRUTEUR']);
        $competences = $this->competenceModel->getAll();
        require ROOT . '/app/views/offres/create.php';
    }

    public function store(): void
    {
        Auth::require(['RECRUTEUR']);
        try {
            Csrf::requireValid();
            $data = [
                'titre'        => trim($_POST['titre']        ?? ''),
                'description'  => trim($_POST['description']  ?? ''),
                'type_contrat' => $_POST['type_contrat'] ?? 'CDI',
                'localisation' => trim($_POST['localisation'] ?? ''),
                'statut'       => $_POST['statut'] ?? 'Brouillon',
                'id_recruteur' => Auth::id(),
            ];
            $id_offre = $this->offreService->creerOffre($data);
            if (!empty($_POST['competences'])) {
                $this->offreService->attacheCompetences($id_offre, $_POST['competences']);
            }
            header('Location: ' . BASE_URL . '/index.php?url=offres/detail/' . $id_offre);
            exit;
        } catch (Exception $e) {
            $error       = $e->getMessage();
            $competences = $this->competenceModel->getAll();
            require ROOT . '/app/views/offres/create.php';
        }
    }

    public function edit(?string $id): void
    {
        Auth::require(['RECRUTEUR']);
        $offre       = $this->offreService->findById((int)$id);
        $competences = $this->competenceModel->getAll();
        $selected    = array_column($this->offreService->getCompetences((int)$id), 'id_competence');
        if (!$offre) { http_response_code(404); require ROOT . '/app/views/errors/404.php'; exit; }
        require ROOT . '/app/views/offres/edit.php';
    }

    public function update(?string $id): void
    {
        Auth::require(['RECRUTEUR']);
        try {
            Csrf::requireValid();
            $data = [
                'titre'        => trim($_POST['titre']        ?? ''),
                'description'  => trim($_POST['description']  ?? ''),
                'type_contrat' => $_POST['type_contrat'] ?? 'CDI',
                'localisation' => trim($_POST['localisation'] ?? ''),
                'statut'       => $_POST['statut'] ?? 'Brouillon',
            ];
            $this->offreService->update((int)$id, $data);
            $this->offreService->attacheCompetences((int)$id, $_POST['competences'] ?? []);
            header('Location: ' . BASE_URL . '/index.php?url=offres/detail/' . $id);
            exit;
        } catch (Exception $e) {
            $error       = $e->getMessage();
            $offre       = $this->offreService->findById((int)$id);
            $competences = $this->competenceModel->getAll();
            $selected    = array_column($this->offreService->getCompetences((int)$id), 'id_competence');
            require ROOT . '/app/views/offres/edit.php';
        }
    }

    public function delete(?string $id): void
    {
        Auth::require(['RECRUTEUR']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die("Méthode non autorisée.");
        }
        try {
            Csrf::requireValid();
            if ($id) $this->offreService->delete((int)$id);
            header('Location: ' . BASE_URL . '/index.php?url=offres');
            exit;
        } catch (Exception $e) {
            die("Erreur : " . htmlspecialchars($e->getMessage()));
        }
    }
}
