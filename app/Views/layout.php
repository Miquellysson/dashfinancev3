
<?php /* layout.php usando SB Admin 2 */ ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Painel') ?> - Arka Gestão Financeira</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- SB Admin 2 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/css/sb-admin-2.min.css" rel="stylesheet" />
  <link href="/assets/css/custom.css" rel="stylesheet" />
</head>
<body id="page-top">
<div id="wrapper">
  <!-- Sidebar -->
  <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="/dashboard">
      <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-coins"></i></div>
      <div class="sidebar-brand-text mx-3">Arka Finance</div>
    </a>
    <hr class="sidebar-divider my-0">
    <li class="nav-item"><a class="nav-link" href="/dashboard"><i class="fas fa-fw fa-tachometer-alt"></i><span>Dashboard</span></a></li>
       <li class="nav-item"><a class="nav-link" href="/goals"><i class="fas fa-fw fa-tachometer-alt"></i><span>Metas</span></a></li>
    <li class="nav-item"><a class="nav-link" href="/cliente"><i class="fas fa-users"></i><span>Clientes</span></a></li>
    <li class="nav-item"><a class="nav-link" href="/projeto"><i class="fas fa-project-diagram"></i><span>Projetos</span></a></li>
    <li class="nav-item"><a class="nav-link" href="/pagamento"><i class="fas fa-cash-register"></i><span>Pagamentos</span></a></li>
    <li class="nav-item"><a class="nav-link" href="/cobranca"><i class="fas fa-hand-holding-usd"></i><span>Cobranças</span></a></li>
    <li class="nav-item"><a class="nav-link" href="/templates"><i class="fas fa-th-large"></i><span>Templates</span></a></li>
    <?php if (Auth::isAdmin()): ?>
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Admin</div>
    <li class="nav-item"><a class="nav-link" href="/usuario"><i class="fas fa-user-cog"></i><span>Usuários</span></a></li>
    <?php endif; ?>
  </ul>

  <!-- Content Wrapper -->
  <div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
      <!-- Topbar -->
      <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
        <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3"><i class="fa fa-bars"></i></button>
        <ul class="navbar-nav ml-auto">
          <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="notificationsBell" role="button" data-toggle="dropdown">
              <i class="fas fa-bell"></i>
              <span class="badge badge-danger badge-counter notification-count d-none"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
              <h6 class="dropdown-header">Notificações</h6>
              <ul class="list-unstyled mb-0" id="notificationsList">
                <li class="dropdown-item text-muted small">Carregando...</li>
              </ul>
            </div>
          </li>
          <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown">
              <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?></span>
              <img class="img-profile rounded-circle" src="https://placehold.co/60x60/4e73df/ffffff?text=<?= substr($_SESSION['user_name'] ?? 'U', 0, 1) ?>" />
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow">
              <div class="dropdown-header">Perfil: <?= htmlspecialchars($_SESSION['user_role'] ?? 'user') ?></div>
              <a class="dropdown-item" href="/perfil"><i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>Meu Perfil</a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="/auth/logout"><i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>Logout</a>
            </div>
          </li>
        </ul>
      </nav>

      <!-- Begin Page Content -->
      <div class="container-fluid">
        <?php $flashMessage = Utils::getFlashMessage(); ?>
        <?php if ($flashMessage): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flashMessage) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'permission'): ?>
          <div class="alert alert-danger">Acesso negado. Você não tem permissão para esta ação.</div>
        <?php endif; ?>

        <?= $content ?? '' ?>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/startbootstrap-sb-admin-2/4.1.4/js/sb-admin-2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.2"></script>
<script src="/assets/js/app.js"></script>
<audio id="notificationSound" src="/assets/sounds/notify.mp3" preload="auto"></audio>
</body>
</html>
