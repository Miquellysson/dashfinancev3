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

<div class="row">
  <?php foreach ($templates as $template): ?>
    <div class="col-xl-4 col-md-6 mb-4">
      <div class="card dashboard-card h-100 d-flex flex-column">
        <?php if (!empty($template['screenshot_path'])): ?>
          <img src="<?= h($template['screenshot_path']) ?>" class="card-img-top" alt="<?= h($template['name']) ?>">
        <?php else: ?>
          <div class="card-img-top d-flex align-items-center justify-content-center" style="height:180px; background:#f1f5f9;">
            <i class="fas fa-image fa-3x text-muted"></i>
          </div>
        <?php endif; ?>
        <div class="card-body d-flex flex-column">
          <div class="d-flex flex-wrap gap-1 mb-2">
            <span class="badge badge-soft-secondary text-uppercase">
              <?= h($categories[$template['category']] ?? ucfirst($template['category'])) ?>
            </span>
            <span class="badge badge-soft-primary text-uppercase">
              <?= h($templateTypes[$template['template_type']] ?? ucfirst($template['template_type'])) ?>
            </span>
          </div>
          <h5 class="card-title mb-2"><?= h($template['name']) ?></h5>
          <p class="card-text text-muted flex-grow-1">
            <?= h(mb_strimwidth($template['description'] ?? 'Sem descrição.', 0, 130, '...')) ?>
          </p>
          <?php
            $keywordsList = [];
            if (!empty($template['keywords'])) {
              $keywordsList = array_filter(array_map('trim', explode(',', $template['keywords'])));
            }
          ?>
          <?php if (!empty($keywordsList)): ?>
            <p class="text-muted small mb-0">
              <i class="fas fa-tags mr-1"></i>
              <?php foreach ($keywordsList as $tag): ?>
                <span class="badge badge-light mr-1"><?= h($tag) ?></span>
              <?php endforeach; ?>
            </p>
          <?php endif; ?>
          <?php if (!empty($template['source_path'])): ?>
            <p class="text-muted small mt-1 mb-0">Fonte: <code>templates/<?= h($template['source_path']) ?></code></p>
          <?php endif; ?>
        </div>
        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="btn-group btn-group-sm" role="group">
            <?php if (!empty($template['link'])): ?>
              <a href="<?= h($template['link']) ?>" target="_blank" class="btn btn-outline-primary" data-toggle="tooltip" title="Visualizar">
                <i class="fas fa-external-link-alt"></i>
              </a>
            <?php endif; ?>
            <?php if (!empty($template['file_path'])): ?>
              <a href="/templates/download/<?= (int)$template['id'] ?>" class="btn btn-outline-primary" data-toggle="tooltip" title="Download">
                <i class="fas fa-download"></i>
              </a>
            <?php endif; ?>
            <a href="/templates/use-template/<?= (int)$template['id'] ?>" class="btn btn-outline-success" data-toggle="tooltip" title="Usar Template">
              <i class="fas fa-clone"></i>
            </a>
            <a href="/templates/edit/<?= (int)$template['id'] ?>" class="btn btn-outline-secondary" data-toggle="tooltip" title="Editar">
              <i class="fas fa-edit"></i>
            </a>
            <form method="post" action="/templates/delete/<?= (int)$template['id'] ?>" onsubmit="return confirm('Deseja realmente excluir este template?')" style="display:inline;">
              <button type="submit" class="btn btn-outline-danger" data-toggle="tooltip" title="Excluir">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
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
