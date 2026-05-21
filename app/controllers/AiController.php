<?php
class AiController
{
    private CandidatureService $candidatureService;
    private AiService          $aiService;

    public function __construct()
    {
        $this->candidatureService = new CandidatureService();
        $this->aiService          = new AiService();
    }

    public function candidateSummary(?string $id): void
    {
        $this->jsonHeaders();

        if (!$this->requireRecruiter()) {
            return;
        }

        $id_candidature = (int)$id;
        if ($id_candidature <= 0) {
            $this->jsonError('Candidature invalide.', 400);
            return;
        }

        $candidature = $this->candidatureService->findById($id_candidature);
        if (!$candidature) {
            $this->jsonError('Candidature introuvable.', 404);
            return;
        }

        $matching = $this->candidatureService->calculerScore(
            (int)$candidature['id_profil'],
            (int)$candidature['id_offre']
        );

        echo json_encode([
            'success' => true,
            'data'    => $this->aiService->buildCandidateSummary($candidature, $matching),
        ]);
    }

    private function requireRecruiter(): bool
    {
        if (!Auth::check()) {
            $this->jsonError('Authentification requise.', 401);
            return false;
        }

        if (!Auth::hasRole(['RECRUTEUR'])) {
            $this->jsonError('Acces refuse.', 403);
            return false;
        }

        return true;
    }

    private function jsonHeaders(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    private function jsonError(string $message, int $status): void
    {
        http_response_code($status);
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);
    }
}
