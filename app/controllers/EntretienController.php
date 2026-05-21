<?php
class EntretienController
{
    private EntretienService   $entretienService;
    private CandidatureService $candidatureService;

    public function __construct()
    {
        $this->entretienService   = new EntretienService();
        $this->candidatureService = new CandidatureService();
    }

    public function index(): void
    {
        Auth::require(['RECRUTEUR']);
        $filters = [
            'decision'       => $_GET['decision']       ?? '',
            'type_entretien' => $_GET['type_entretien'] ?? '',
        ];
        $entretiens = $this->entretienService->getAll($filters);
        require ROOT . '/app/views/entretiens/index.php';
    }

    public function create(?string $id_candidature): void
    {
        Auth::require(['RECRUTEUR']);
        $candidature = $this->candidatureService->findById((int)$id_candidature);
        if (!$candidature) { http_response_code(404); require ROOT . '/app/views/errors/404.php'; exit; }
        $existant = $this->entretienService->findByCandidature((int)$id_candidature);
        require ROOT . '/app/views/entretiens/create.php';
    }

    public function store(): void
    {
        Auth::require(['RECRUTEUR']);
        try {
            Csrf::requireValid();
            $data = [
                'id_candidature' => (int)($_POST['id_candidature'] ?? 0),
                'date_entretien' => $_POST['date_entretien'] ?? '',
                'type_entretien' => $_POST['type_entretien'] ?? 'Presentiel',
                'lieu_ou_lien'   => trim($_POST['lieu_ou_lien'] ?? ''),
            ];
            $this->entretienService->planifierEntretien($data);
            header('Location: ' . BASE_URL . '/index.php?url=entretiens');
            exit;
        } catch (Exception $e) {
            die("Erreur : " . htmlspecialchars($e->getMessage()));
        }
    }

    public function updateDecision(?string $id): void
    {
        Auth::require(['RECRUTEUR']);
        try {
            Csrf::requireValid();
            $decision     = $_POST['decision']     ?? 'En_attente';
            $compte_rendu = trim($_POST['compte_rendu'] ?? '');
            $this->entretienService->enregistrerDecision((int)$id, $decision, $compte_rendu);
            header('Location: ' . BASE_URL . '/index.php?url=entretiens');
            exit;
        } catch (Exception $e) {
            die("Erreur : " . htmlspecialchars($e->getMessage()));
        }
    }
}
