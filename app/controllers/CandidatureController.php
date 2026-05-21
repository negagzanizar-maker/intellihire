<?php
class CandidatureController
{
    private CandidatureService  $candidatureService;
    private OffreService        $offreService;
    private EntretienService    $entretienService;
    private Competence          $competenceModel;

    public function __construct()
    {
        $this->candidatureService = new CandidatureService();
        $this->offreService       = new OffreService();
        $this->entretienService   = new EntretienService();
        $this->competenceModel    = new Competence();
    }

    public function index(): void
    {
        Auth::require(['RECRUTEUR', 'CANDIDAT']);

        $filters = [
            'statut'   => $_GET['statut']   ?? '',
            'id_offre' => $_GET['id_offre'] ?? '',
            'search'   => $_GET['search']   ?? '',
        ];

        // Pagination
        $per_page = 10;
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $filters['limit']  = $per_page;
        $filters['offset'] = ($page - 1) * $per_page;

        if (Auth::role() === 'CANDIDAT') {
            $profil = $this->candidatureService->getProfilByUser(Auth::id());
            if ($profil) $filters['id_profil'] = $profil['id_profil'];
            $candidatures = $profil ? $this->candidatureService->getAll($filters) : [];
        } else {
            $candidatures = $this->candidatureService->getAll($filters);
        }

        $total_count = $this->candidatureService->countAll(array_diff_key($filters, array_flip(['limit','offset'])));
        $total_pages = max(1, (int)ceil($total_count / $per_page));
        $offres      = $this->offreService->getAll(['statut' => 'Publiee']);

        require ROOT . '/app/views/candidatures/index.php';
    }

    public function postuler(?string $id_offre): void
    {
        Auth::require(['CANDIDAT']);
        $offre       = $this->offreService->findById((int)$id_offre);
        if (!$offre) { http_response_code(404); require ROOT . '/app/views/errors/404.php'; exit; }
        $competences = $this->competenceModel->getAll();
        $profil      = $this->candidatureService->getProfilByUser(Auth::id());
        $error       = '';
        if ($profil && $this->candidatureService->findByProfilAndOffre($profil['id_profil'], (int)$id_offre)) {
            $error = "Vous avez déjà postulé à cette offre.";
        }
        require ROOT . '/app/views/candidatures/postuler.php';
    }

    public function store(): void
    {
        Auth::require(['CANDIDAT']);
        try {
            Csrf::requireValid();
            $result = $this->candidatureService->soumettreDepuisFormulaire(
                Auth::id(),
                Auth::user() ?? [],
                $_POST,
                $_FILES
            );
            $id_cand  = $result['id_candidature'];
            $cv_parse = $result['cv_parse'];

            if ($cv_parse) {
                $_SESSION['cv_parse_result'] = [
                    'id_candidature' => $id_cand,
                    'skills'         => $cv_parse['skills'] ?? [],
                    'experience'     => $cv_parse['experience'] ?? 0,
                    'summary'        => $cv_parse['summary'] ?? '',
                    'success'        => $cv_parse['success'] ?? false,
                ];
            }

            header('Location: ' . BASE_URL . '/index.php?url=candidatures/detail/' . $id_cand);
            exit;
        } catch (Exception $e) {
            $error       = $e->getMessage();
            $offre       = $this->offreService->findById((int)($_POST['id_offre'] ?? 0));
            $competences = $this->competenceModel->getAll();
            $profil      = $this->candidatureService->getProfilByUser(Auth::id());
            $id_offre    = $_POST['id_offre'] ?? 0;
            require ROOT . '/app/views/candidatures/postuler.php';
        }
    }

    public function detail(?string $id): void
    {
        Auth::require();
        $candidature = $this->candidatureService->findById((int)$id);
        if (!$candidature) { http_response_code(404); require ROOT . '/app/views/errors/404.php'; exit; }

        // Un candidat ne peut consulter qu'une candidature qui lui appartient.
        if (Auth::role() === 'CANDIDAT') {
            $profil = $this->candidatureService->getProfilByUser(Auth::id());
            if (!$profil || (int)$profil['id_profil'] !== (int)$candidature['id_profil']) {
                http_response_code(403);
                require ROOT . '/app/views/errors/404.php';
                exit;
            }
        }

        $historique  = $this->candidatureService->getHistorique((int)$id);
        $competences = $this->competenceModel->getByProfil($candidature['id_profil']);
        $entretien   = $this->entretienService->findByCandidature((int)$id);
        $matching    = $this->candidatureService->calculerScore(
            (int)$candidature['id_profil'],
            (int)$candidature['id_offre']
        );
        $cv_parse_result = null;
        if (!empty($_SESSION['cv_parse_result'])
            && (int)$_SESSION['cv_parse_result']['id_candidature'] === (int)$id) {
            $cv_parse_result = $_SESSION['cv_parse_result'];
            unset($_SESSION['cv_parse_result']);
        }
        require ROOT . '/app/views/candidatures/detail.php';
    }

    public function updateStatut(?string $id): void
    {
        Auth::require(['RECRUTEUR']);
        try {
            Csrf::requireValid();
            $nouveau_statut  = $_POST['statut'] ?? '';
            $note            = trim($_POST['note'] ?? '');
            $this->candidatureService->changerStatutEtNotifier((int)$id, $nouveau_statut, $note, Auth::id());

            header('Location: ' . BASE_URL . '/index.php?url=candidatures/detail/' . $id);
            exit;
        } catch (Exception $e) {
            die("Erreur : " . htmlspecialchars($e->getMessage()));
        }
    }
}
