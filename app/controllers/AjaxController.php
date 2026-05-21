<?php
class AjaxController
{
    private CandidatureService  $candidatureService;
    private NotificationService $notifService;

    public function __construct()
    {
        $this->candidatureService = new CandidatureService();
        $this->notifService       = new NotificationService();
    }

    /** GET /ajax/candidatures?statut=&id_offre=&search=&page= */
    public function candidatures(): void
    {
        $this->jsonHeaders();
        Auth::require();
        $filters = [
            'statut'   => $_GET['statut']   ?? '',
            'id_offre' => $_GET['id_offre'] ?? '',
            'search'   => $_GET['search']   ?? '',
        ];

        $per_page = 10;
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $filters['limit']  = $per_page;
        $filters['offset'] = ($page - 1) * $per_page;

        if (Auth::role() === 'CANDIDAT') {
            $profil = $this->candidatureService->getProfilByUser(Auth::id());
            if ($profil) $filters['id_profil'] = $profil['id_profil'];
        }

        $data  = $this->candidatureService->getAll($filters);
        $total = $this->candidatureService->countAll(array_diff_key($filters, array_flip(['limit','offset'])));

        echo json_encode([
            'success' => true,
            'data'    => $data,
            'total'   => $total,
            'pages'   => (int)ceil($total / $per_page),
            'page'    => $page,
        ]);
    }

    /** GET /ajax/notifications */
    public function notifications(): void
    {
        $this->jsonHeaders();
        Auth::require();
        $notifs = $this->notifService->getByUser(Auth::id(), 10);
        $count  = $this->notifService->countUnread(Auth::id());
        echo json_encode(['success' => true, 'data' => $notifs, 'unread' => $count]);
    }

    /** POST /ajax/mark_read */
    public function markRead(): void
    {
        $this->jsonHeaders();
        Auth::require();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Methode non autorisee.', 405);
            return;
        }
        try {
            Csrf::requireValid();
        } catch (Exception $e) {
            $this->jsonError($e->getMessage(), 400);
            return;
        }
        $this->notifService->markAllRead(Auth::id());
        echo json_encode(['success' => true]);
    }

    private function jsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    private function jsonError(string $message, int $status): void
    {
        http_response_code($status);
        echo json_encode(['success' => false, 'message' => $message]);
    }
}
