<?php
use function htmlspecialchars as h;

$templateData = $templateData ?? [];
$isFavorite = !empty($templateData['is_favorite']);
$returnUri = $currentUri ?? '/templates';

$keywordsList = [];
if (!empty($templateData['keywords'])) {
    $keywordsList = array_filter(array_map('trim', explode(',', (string)$templateData['keywords'])));
}
?>
<div class="col-xl-4 col-md-6 mb-4">
  <div class="card dashboard-card h-100 d-flex flex-column position-relative">
    <?php if (!empty($templateData['screenshot_path'])): ?>
      <img src="<?= h($templateData['screenshot_path']) ?>" class="card-img-top" alt="<?= h($templateData['name'] ?? 'Template') ?>">
    <?php else: ?>
      <div class="card-img-top d-flex align-items-center justify-content-center" style="height:180px; background:#f1f5f9;">
        <i class="fas fa-image fa-3x text-muted"></i>
      </div>
    <?php endif; ?>

    <form method="post" action="/templates/toggle-favorite/<?= (int)($templateData['id'] ?? 0) ?>" class="position-absolute" style="top:12px; right:12px;">
      <input type="hidden" name="favorite" value="<?= $isFavorite ? '0' : '1' ?>">
      <input type="hidden" name="return_to" value="<?= h($returnUri) ?>">
      <button type="submit" class="btn btn-sm <?= $isFavorite ? 'btn-warning' : 'btn-outline-secondary' ?> shadow-sm" data-toggle="tooltip" title="<?= $isFavorite ? 'Remover dos favoritos' : 'Salvar nos favoritos' ?>" aria-label="<?= $isFavorite ? 'Remover dos favoritos' : 'Salvar nos favoritos' ?>">
        <i class="<?= $isFavorite ? 'fas fa-star' : 'far fa-star' ?>"></i>
      </button>
    </form>

    <div class="card-body d-flex flex-column">
      <div class="d-flex flex-wrap align-items-center gap-1 mb-2 pr-4">
        <span class="badge badge-soft-secondary text-uppercase">
          <?= h($categories[$templateData['category']] ?? ucfirst((string)($templateData['category'] ?? ''))) ?>
        </span>
        <span class="badge badge-soft-primary text-uppercase ml-1">
          <?= h($templateTypes[$templateData['template_type']] ?? ucfirst((string)($templateData['template_type'] ?? ''))) ?>
        </span>
        <?php if (!empty($templateData['favorited_at'])): ?>
          <span class="badge badge-light ml-2" title="Salvo em">
            <i class="fas fa-clock mr-1"></i><?= h(date('d/m/Y H:i', strtotime($templateData['favorited_at']))) ?>
          </span>
        <?php endif; ?>
      </div>

      <h5 class="card-title mb-2"><?= h($templateData['name'] ?? 'Template sem título') ?></h5>
      <p class="card-text text-muted flex-grow-1">
        <?= h(mb_strimwidth($templateData['description'] ?? 'Sem descrição.', 0, 130, '...')) ?>
      </p>

      <?php if (!empty($keywordsList)): ?>
        <p class="text-muted small mb-0">
          <i class="fas fa-tags mr-1"></i>
          <?php foreach ($keywordsList as $tag): ?>
            <span class="badge badge-light mr-1"><?= h($tag) ?></span>
          <?php endforeach; ?>
        </p>
      <?php endif; ?>

      <?php if (!empty($templateData['source_path'])): ?>
        <p class="text-muted small mt-1 mb-0">Fonte: <code>templates/<?= h($templateData['source_path']) ?></code></p>
      <?php endif; ?>
    </div>

    <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
      <div class="btn-group btn-group-sm" role="group">
        <?php if (!empty($templateData['link'])): ?>
          <a href="<?= h($templateData['link']) ?>" target="_blank" class="btn btn-outline-primary" data-toggle="tooltip" title="Visualizar">
            <i class="fas fa-external-link-alt"></i>
          </a>
        <?php endif; ?>
        <?php if (!empty($templateData['file_path'])): ?>
          <a href="/templates/download/<?= (int)($templateData['id'] ?? 0) ?>" class="btn btn-outline-primary" data-toggle="tooltip" title="Download">
            <i class="fas fa-download"></i>
          </a>
        <?php endif; ?>
        <a href="/templates/use-template/<?= (int)($templateData['id'] ?? 0) ?>" class="btn btn-outline-success" data-toggle="tooltip" title="Usar Template">
          <i class="fas fa-clone"></i>
        </a>
        <a href="/templates/edit/<?= (int)($templateData['id'] ?? 0) ?>" class="btn btn-outline-secondary" data-toggle="tooltip" title="Editar">
          <i class="fas fa-edit"></i>
        </a>
        <form method="post" action="/templates/delete/<?= (int)($templateData['id'] ?? 0) ?>" onsubmit="return confirm('Deseja realmente excluir este template?')" style="display:inline;">
          <button type="submit" class="btn btn-outline-danger" data-toggle="tooltip" title="Excluir">
            <i class="fas fa-trash"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
