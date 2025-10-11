<?php
use function htmlspecialchars as h;
?>

<div class="card dashboard-card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h6 class="card-title mb-0">Documentação do template</h6>
    <a href="/templates" class="btn btn-outline-primary btn-sm"><i class="fas fa-arrow-left mr-1"></i>Voltar</a>
  </div>
  <div class="card-body">
    <?php if (empty($documentContent)): ?>
      <p class="text-muted">Documento vazio.</p>
    <?php else: ?>
      <pre style="white-space: pre-wrap; background:#f8fafc; padding:1rem; border-radius:0.75rem; font-size:0.9rem;">
<?= h($documentContent) ?>
      </pre>
    <?php endif; ?>
  </div>
</div>
