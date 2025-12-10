<?php
class DashboardController extends Controller
{
    public function __construct(private AdminModel $adminModel)
    {
    }

    public function index(): void
    {
        Auth::requireAuth();
        $stats = $this->adminModel->stats();
        $this->view('admin/dashboard', ['stats' => $stats]);
    }
}
?>
