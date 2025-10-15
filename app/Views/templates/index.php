<?php
use function htmlspecialchars as h;

$hasFilters = false;
if (!empty($activeFilters)) {
    foreach ($activeFilters as $value) {
        if (is_string($value) && trim($value) !== '') {
            $hasFilters = true;
            break;
        }
    }
}

$currentUri = $_SERVER['REQUEST_URI'] ?? '/templates';
?>

<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4">
  <div>
    <h1 class="h3 text-gray-800 mb-1">Biblioteca de Templates</h1>
    <p class="text-muted mb-0">Busque, filtre e utilize seus templates reutilizáveis.</p>
  </div>
  <div class="mt-3 mt-md-0">
    <a href="/templates/create" class="btn btn-primary">
      <i class="fas fa-plus mr-1"></i>Novo Template
    </a>
  </div>
</div>

<?php if ($flash = Utils::getFlashMessage()): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= h($flash) ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php endif; ?>

<div class="card dashboard-card mb-4">
  <div class="card-header">
    <h6 class="card-title mb-0">Busca e filtros</h6>
  </div>
  <div class="card-body">
    <form method="get" id="templateFilters" class="form-row align-items-end">
      <div class="form-group col-md-4">
        <label class="small text-muted text-uppercase">Palavra-chave</label>
        <input type="search" class="form-control" name="q" placeholder="Nome, descrição ou palavra-chave" value="<?= h($activeFilters['query'] ?? '') ?>">
      </div>
      <div class="form-group col-md-3">
        <label class="small text-muted text-uppercase">Categoria</label>
        <select name="category" class="form-control">
          <option value="">Todas</option>
          <?php foreach ($categories as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= ($activeFilters['category'] ?? '') === $value ? 'selected' : '' ?>>
              <?= h($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group col-md-3">
        <label class="small text-muted text-uppercase">Tipo</label>
        <select name="type" class="form-control">
          <option value="">Todos</option>
          <?php foreach ($templateTypes as $value => $label): ?>
            <option value="<?= h($value) ?>" <?= ($activeFilters['template_type'] ?? '') === $value ? 'selected' : '' ?>>
              <?= h($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group col-md-2 d-flex">
        <button class="btn btn-primary flex-grow-1 mr-2"><i class="fas fa-filter mr-1"></i>Filtrar</button>
        <?php if ($hasFilters): ?>
          <a href="/templates" class="btn btn-light" title="Limpar filtros"><i class="fas fa-times"></i></a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if ($hasFilters): ?>
  <div class="alert alert-info alert-dismissible fade show" role="alert">
    Resultados filtrados conforme os critérios selecionados.
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
<?php endif; ?>

<div class="card dashboard-card mb-4">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h6 class="card-title mb-0">Meus templates salvos</h6>
    <span class="badge badge-primary"><?= count($favorites ?? []) ?></span>
  </div>
  <div class="card-body">
    <?php if (!empty($favorites)): ?>
      <div class="row">
        <?php foreach ($favorites as $favoriteTemplate): ?>
          <?php $templateData = $favoriteTemplate; include __DIR__ . '/partials/template-card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-muted mb-0">Você ainda não salvou templates nos favoritos. Clique na estrela de qualquer template para guardar aqui.</p>
    <?php endif; ?>
  </div>
</div>

<div class="row">
  <?php foreach ($templates as $template): ?>
    <?php $templateData = $template; include __DIR__ . '/partials/template-card.php'; ?>
  <?php endforeach; ?>

  <?php if (empty($templates)): ?>
    <div class="col-12">
      <div class="card dashboard-card">
        <div class="card-body text-center text-muted py-5">
          <?php if ($hasFilters): ?>
            Nenhum template encontrado para os filtros informados.
          <?php else: ?>
            Nenhum template cadastrado ainda. Clique em <strong>“Novo Template”</strong> para começar.
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="/assets/js/templates.js"></script>
