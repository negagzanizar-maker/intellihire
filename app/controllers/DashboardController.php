<?php
class DashboardController
{
    private DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    public function index(): void
    {
        Auth::require();

        if (Auth::role() === 'CANDIDAT') {
            header('Location: ' . BASE_URL . '/index.php?url=offres');
            exit;
        }

        extract($this->dashboardService->buildRecruiterDashboard(), EXTR_SKIP);

        require ROOT . '/app/views/dashboard/index.php';
    }
}
