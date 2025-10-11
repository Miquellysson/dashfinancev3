<?php
require __DIR__ . '/config/database.php';
require __DIR__ . '/app/Helpers/Auth.php';
require __DIR__ . '/app/Helpers/Utils.php';
require __DIR__ . '/app/Controllers/DashboardController.php';

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Tester';
$_SESSION['user_role'] = 'admin';

try {
    $controller = new DashboardController($pdo);
    ob_start();
    $controller->index();
    ob_end_clean();
    echo "Dashboard loaded successfully\n";
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
