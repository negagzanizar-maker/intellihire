<?php
class DashboardService
{
    private CandidatureService $candidatureService;
    private OffreService $offreService;
    private EntretienService $entretienService;
    private AiService $aiService;
    private User $userModel;

    public function __construct()
    {
        $this->candidatureService = new CandidatureService();
        $this->offreService = new OffreService();
        $this->entretienService = new EntretienService();
        $this->aiService = new AiService();
        $this->userModel = new User();
    }

    public function buildRecruiterDashboard(): array
    {
        $stats_candidatures = $this->candidatureService->getStats();
        $stats_entretiens = $this->entretienService->getStats();
        $offres_publiees = count($this->offreService->getAll(['statut' => 'Publiee']));
        $total_users = count($this->userModel->getAll());

        $entretiens_upcoming = $this->entretienService->getUpcoming(5);
        $dernieres_candidatures = array_slice($this->candidatureService->getAll(), 0, 6);

        $chart_donut = [
            'labels' => ['Reçues', 'En cours', 'Entretien', 'Acceptées', 'Refusées'],
            'values' => [
                $stats_candidatures['recues'],
                $stats_candidatures['en_cours'],
                $stats_candidatures['entretiens'],
                $stats_candidatures['acceptees'],
                $stats_candidatures['refusees'],
            ],
        ];

        $raw_bar = $this->offreService->getStatsByOffre();
        $chart_bar = [
            'labels' => array_column($raw_bar, 'titre'),
            'values' => array_map('intval', array_column($raw_bar, 'nb')),
        ];

        $raw_line = $this->candidatureService->getParMois();
        $months_map = $this->buildLastTwelveMonths($raw_line);
        $chart_line = [
            'labels' => array_map(fn($k) => date('M y', strtotime($k . '-01')), array_keys($months_map)),
            'values' => array_values($months_map),
        ];

        $ai_insights = $this->aiService->buildDashboardInsights(
            $stats_candidatures,
            $stats_entretiens,
            $offres_publiees,
            $total_users,
            $raw_bar,
            array_map(fn($total) => ['total' => $total], array_values($months_map)),
            $entretiens_upcoming,
            $dernieres_candidatures
        );

        return compact(
            'stats_candidatures',
            'stats_entretiens',
            'offres_publiees',
            'total_users',
            'entretiens_upcoming',
            'dernieres_candidatures',
            'chart_donut',
            'chart_bar',
            'chart_line',
            'ai_insights'
        );
    }

    private function buildLastTwelveMonths(array $rows): array
    {
        $months_map = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-$i months"));
            $months_map[$key] = 0;
        }

        foreach ($rows as $row) {
            if (isset($months_map[$row['mois']])) {
                $months_map[$row['mois']] = (int)$row['total'];
            }
        }

        return $months_map;
    }
}
