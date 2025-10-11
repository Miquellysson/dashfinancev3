<?php

require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/../Models/ProjectModel.php';
require_once __DIR__ . '/../Models/TemplateModel.php';

class AdminController {
    private PDO $pdo;
    private TemplateModel $templates;

    public function __construct(PDO $pdo) {
        Auth::requireAdmin();
        $this->pdo = $pdo;
        $this->templates = new TemplateModel($pdo);
    }

    public function index() {
        $userModel    = new UserModel($this->pdo);
        $projectModel = new ProjectModel($this->pdo);

        $stats = [
            'total_users'      => $userModel->countAll(),
            'active_users'     => $userModel->countActive(),
            'total_projects'   => $projectModel->count(),
            'pending_projects' => $projectModel->getFinancialSummary()['total_pendente'] ?? 0,
            'templates_total'  => $this->templates->countAll(),
        ];

        $title = 'Admin';
        ob_start();
        include __DIR__ . '/../Views/admin/index.php';
        $content = ob_get_clean();
        include __DIR__ . '/../Views/layout.php';
    }

}
