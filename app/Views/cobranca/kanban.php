<?php
use function htmlspecialchars as h;

$filtersUsed = $filterSelections['raw'] ?? [];
$summary = $summary ?? ['total_amount' => 0, 'total_cards' => 0, 'due_today' => 0, 'due_today_amount' => 0, 'overdue_amount' => 0];
$totalPendente = Utils::formatMoney($summary['total_amount'] ?? 0);
$totalVencido = Utils::formatMoney($summary['overdue_amount'] ?? 0);
$totalCards = (int)($summary['total_cards'] ?? 0);
$dueTodayCount = (int)($summary['due_today'] ?? 0);
$dueTodayAmount = Utils::formatMoney($summary['due_today_amount'] ?? 0);

$statusSelectValue = $filtersUsed['column'] ?? ($filtersUsed['status'] ?? '');
$orderSelectValue = $filtersUsed['order_by'] ?? 'due_date';
$orderDirValue = $filtersUsed['order_dir'] ?? 'asc';
$searchValue = $filtersUsed['search'] ?? '';
$valueMinValue = $filtersUsed['value_min'] ?? '';
$valueMaxValue = $filtersUsed['value_max'] ?? '';
$responsavelValue = $filtersUsed['responsavel_id'] ?? '';
$dueFromValue = $filtersUsed['due_from'] ?? '';
$dueToValue = $filtersUsed['due_to'] ?? '';

$columnMeta = $columnMeta ?? [];
$lostReasonOptions = $lostReasons ?? [];
$lostReasonLabels = [];
foreach ($lostReasonOptions as $opt) {
    $lostReasonLabels[$opt['value']] = $opt['label'];
}

$jsonBoard = json_encode([
    'columns' => $columns,
    'summary' => $summary,
    'filters' => $filterSelections,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$jsonMeta = json_encode([
    'columnMeta' => $columnMeta,
    'lostReasons' => $lostReasonOptions,
    'templates' => $templates,
    'responsaveis' => $responsaveis,
    'statusOptions' => $statusOptions,
    'orderOptions' => $orderOptions,
    'projects' => $projectsList,
    'clients' => $clientsList,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
  <div>
    <h1 class="h3 mb-2">Gestão de Cobranças</h1>
    <p class="text-muted mb-0">Acompanhe e gerencie todas as cobranças pendentes</p>
  </div>
  <div class="d-flex flex-column flex-lg-row align-items-lg-center">
    <div class="kanban-alert badge badge-pill badge-warning mb-2 mb-lg-0 mr-lg-3">
      <span class="mr-2">🔔</span>
      <span><strong><?= $dueTodayCount ?></strong> vencendo hoje</span>
    </div>
    <div class="kanban-view-toggle btn-group" role="group" aria-label="Alternar visualização">
      <a href="/cobranca" class="btn btn-sm btn-primary active" data-view="kanban">Quadro</a>
      <a href="/cobranca?modo=legacy" class="btn btn-sm btn-outline-primary" data-view="lista">Lista</a>
    </div>
  </div>
</div>

<div class="kanban-summary card shadow-sm mb-4">
  <div class="card-body d-flex flex-wrap">
    <div class="summary-item">
      <div class="summary-label">Total Pendente</div>
      <div class="summary-value text-primary"><?= h($totalPendente) ?></div>
    </div>
    <div class="summary-item">
      <div class="summary-label">Cobranças ativas</div>
      <div class="summary-value"><?= $totalCards ?></div>
    </div>
    <div class="summary-item">
      <div class="summary-label">Vencendo hoje</div>
      <div class="summary-value text-warning"><span class="summary-due-today-count"><?= $dueTodayCount ?></span> <small class="text-muted d-block summary-due-today-amount"><?= h($dueTodayAmount) ?></small></div>
    </div>
    <div class="summary-item">
      <div class="summary-label">Total vencido</div>
      <div class="summary-value text-danger"><?= h($totalVencido) ?></div>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form id="kanbanFiltersForm" class="kanban-filters" method="get">
      <div class="form-row">
        <div class="form-group col-md-3 col-sm-6">
          <label class="small text-uppercase text-muted">Filtro por status</label>
          <select name="column" class="form-control form-control-sm">
            <?php foreach ($statusOptions as $opt): ?>
              <option value="<?= h($opt['value']) ?>" <?= ($statusSelectValue === $opt['value']) ? 'selected' : '' ?>><?= h($opt['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-3 col-sm-6">
          <label class="small text-uppercase text-muted">Ordenar por</label>
          <select name="order" class="form-control form-control-sm">
            <?php foreach ($orderOptions as $opt): ?>
              <option value="<?= h($opt['value']) ?>" <?= ($orderSelectValue === $opt['value']) ? 'selected' : '' ?>><?= h($opt['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group col-md-2 col-sm-6">
          <label class="small text-uppercase text-muted">Direção</label>
          <select name="direction" class="form-control form-control-sm">
            <option value="asc" <?= $orderDirValue === 'asc' ? 'selected' : '' ?>>Ascendente</option>
            <option value="desc" <?= $orderDirValue === 'desc' ? 'selected' : '' ?>>Descendente</option>
          </select>
        </div>
        <div class="form-group col-md-4 col-sm-6">
          <label class="small text-uppercase text-muted">Buscar</label>
          <input type="text" name="search" class="form-control form-control-sm" value="<?= h($searchValue) ?>" placeholder="Cliente, projeto ou valor">
        </div>
      </div>

      <div class="collapse" id="advancedFilters">
        <div class="form-row">
          <div class="form-group col-md-2 col-sm-6">
            <label class="small text-uppercase text-muted">Valor mínimo</label>
            <input type="text" name="value_min" class="form-control form-control-sm" value="<?= h($valueMinValue) ?>" placeholder="Ex.: 500">
          </div>
          <div class="form-group col-md-2 col-sm-6">
            <label class="small text-uppercase text-muted">Valor máximo</label>
            <input type="text" name="value_max" class="form-control form-control-sm" value="<?= h($valueMaxValue) ?>" placeholder="Ex.: 2000">
          </div>
          <div class="form-group col-md-3 col-sm-6">
            <label class="small text-uppercase text-muted">Responsável</label>
            <select name="responsavel_id" class="form-control form-control-sm">
              <option value="">Todos</option>
              <?php foreach ($responsaveis as $resp): ?>
                <option value="<?= (int)$resp['id'] ?>" <?= ((string)$responsavelValue === (string)$resp['id']) ? 'selected' : '' ?>><?= h($resp['nome_completo']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-2 col-sm-6">
            <label class="small text-uppercase text-muted">Vencimento a partir</label>
            <input type="text" name="due_from" class="form-control form-control-sm" value="<?= h($dueFromValue) ?>" placeholder="YYYY-MM-DD">
          </div>
          <div class="form-group col-md-2 col-sm-6">
            <label class="small text-uppercase text-muted">Vencimento até</label>
            <input type="text" name="due_to" class="form-control form-control-sm" value="<?= h($dueToValue) ?>" placeholder="YYYY-MM-DD">
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between align-items-center mt-2">
        <button class="btn btn-link p-0" type="button" data-toggle="collapse" data-target="#advancedFilters" aria-expanded="false">
          <span class="small">Filtros avançados</span>
        </button>
        <div>
          <a href="/cobranca" class="btn btn-link btn-sm">Limpar</a>
          <button class="btn btn-primary btn-sm" type="submit">Aplicar filtros</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="kanban-board" id="kanbanBoard">
  <?php foreach ($columns as $status => $data):
    $meta = $columnMeta[$status] ?? null;
    if (!$meta) { continue; }
    $totals = $data['totals'] ?? ['amount' => 0, 'clients' => 0, 'cards' => 0];
    $headerStyle = 'background-color: ' . h($meta['header_bg']) . '; color: ' . h($meta['header_text']) . ';';
    $accentColor = h($meta['accent']);
    $amountFormatted = Utils::formatMoney($totals['amount'] ?? 0);
  ?>
    <div class="kanban-column" data-column="<?= h($status) ?>">
      <div class="kanban-column-header" style="<?= $headerStyle ?>">
        <div class="kanban-column-header-top d-flex align-items-center justify-content-between">
          <div class="kanban-column-title mb-0"><?= h($meta['title']) ?></div>
          <button class="btn btn-sm btn-light kanban-add-card" type="button" data-status="<?= h($status) ?>" data-add-url="/pagamento/create?status=<?= h($status) ?>">
            <i class="fas fa-plus mr-1"></i>Nova cobrança
          </button>
        </div>
        <div class="kanban-column-meta">
          <span class="kanban-column-count" data-role="cards"><?= (int)($totals['cards'] ?? 0) ?> itens</span>
          <span class="kanban-column-count" data-role="clients"><?= (int)($totals['clients'] ?? 0) ?> clientes</span>
          <span class="kanban-column-amount" data-role="amount"><?= h($amountFormatted) ?></span>
        </div>
      </div>
      <div class="kanban-column-body" data-status="<?= h($status) ?>">
        <?php if (empty($data['cards'])): ?>
          <div class="kanban-empty">Sem cobranças aqui.</div>
        <?php else: ?>
          <?php foreach ($data['cards'] as $card):
            $cardClasses = ['kanban-card'];
            if ($status === 'perdido') {
                $cardClasses[] = 'kanban-card-lost';
            }
            $badges = $card['badges'] ?? [];
            $dueDateFormatted = $card['due_date_formatted'] ?? '—';
            $daysOverdue = (int)($card['days_overdue'] ?? 0);
            $daysUntilDue = $card['days_until_due'];
            $lastContactDate = $card['last_contact_at'] ? date('d/m/Y H:i', strtotime($card['last_contact_at'])) : null;
            $phoneDisplay = $card['client_phone'] ?? '';
            $phoneLink = $card['whatsapp_link'] ?? '';
            $lostReason = $card['lost_reason'] ?? null;
            $lostDetails = $card['lost_details'] ?? null;
            $cardBorderStyle = 'border-left-color: ' . $accentColor . ';';
            $priorityLevel = 'baixa';
            if ($daysOverdue > 0) {
                $priorityLevel = 'critica';
            } elseif ($daysUntilDue !== null && $daysUntilDue <= 1) {
                $priorityLevel = 'alta';
            } elseif ($daysUntilDue !== null && $daysUntilDue <= 3) {
                $priorityLevel = 'media';
            }
            $priorityLabels = [
                'critica' => 'Crítica',
                'alta' => 'Alta',
                'media' => 'Média',
                'baixa' => 'Baixa',
            ];
            $priorityLabel = $priorityLabels[$priorityLevel] ?? 'Baixa';
          ?>
            <div class="<?= implode(' ', $cardClasses) ?>" data-payment-id="<?= (int)$card['payment_id'] ?>"
                 data-status="<?= h($status) ?>"
                 data-client-name="<?= h($card['client_name']) ?>"
                 data-project-name="<?= h($card['project_name'] ?? $card['description'] ?? '') ?>"
                 data-amount="<?= h(number_format($card['amount'], 2, '.', '')) ?>"
                 data-amount-formatted="<?= h($card['amount_formatted']) ?>"
                 data-due-date="<?= h($card['due_date'] ?? '') ?>"
                 data-days-overdue="<?= $daysOverdue ?>"
                 data-whatsapp="<?= h($phoneLink) ?>"
                 data-last-contact="<?= h($card['last_contact_at'] ?? '') ?>"
                 data-last-channel="<?= h($card['last_contact_channel'] ?? '') ?>"
                 data-lost-reason="<?= h($lostReason ?? '') ?>"
                 data-lost-details="<?= h($lostDetails ?? '') ?>"
                 data-priority="<?= h($priorityLevel) ?>">
              <div class="kanban-card-inner" style="<?= $cardBorderStyle ?>">
                <div class="kanban-card-header">
                  <div class="d-flex align-items-center">
                    <span class="status-dot" style="background-color: <?= $accentColor ?>"></span>
                    <span class="client-name"><?= h($card['client_name']) ?></span>
                    <span class="priority-tag priority-<?= h($priorityLevel) ?>" title="Prioridade <?= h($priorityLabel) ?>">
                      <?= h($priorityLabel) ?>
                    </span>
                    <?php if (in_array('alto_valor', $badges, true)): ?>
                      <span class="badge badge-soft-warning badge-pill ml-2">Alto valor</span>
                    <?php endif; ?>
                    <?php if (in_array('atencao', $badges, true)): ?>
                      <span class="badge badge-soft-danger badge-pill ml-2">Atenção</span>
                    <?php endif; ?>
                    <?php if (in_array('parcelado', $badges, true)): ?>
                      <span class="badge badge-soft-info badge-pill ml-2">Recorrente</span>
                    <?php endif; ?>
                  </div>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-link text-muted" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                      <button class="dropdown-item js-open-details" data-payment-id="<?= (int)$card['payment_id'] ?>">👁️ Ver detalhes</button>
                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item" href="/pagamento/edit/<?= (int)$card['payment_id'] ?>">✏️ Editar pagamento</a>
                      <button class="dropdown-item js-send-reminder" data-payment-id="<?= (int)$card['payment_id'] ?>">📧 Enviar lembrete rápido</button>
                      <?php if ($phoneLink): ?>
                        <a class="dropdown-item" href="<?= h($phoneLink) ?>" target="_blank">💬 Abrir WhatsApp</a>
                      <?php endif; ?>
                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item text-danger" href="/pagamento/delete/<?= (int)$card['payment_id'] ?>" onclick="return confirm('Excluir esta cobrança?');">🗑️ Remover cobrança</a>
                    </div>
                  </div>
                </div>

                <div class="kanban-card-body">
                  <div class="card-line"><span>📋 Projeto:</span> <?= h($card['project_name'] ?? ($card['description'] ?? '—')) ?></div>
                  <div class="card-line"><span>💰 Valor:</span> <?= h($card['amount_formatted']) ?></div>
                  <div class="card-line"><span>📅 Vencimento:</span> <?= h($dueDateFormatted) ?></div>
                  <?php if ($daysOverdue > 0): ?>
                    <div class="card-line text-danger">⏰ Há <?= $daysOverdue ?> dia<?= $daysOverdue === 1 ? '' : 's' ?> em atraso</div>
                  <?php elseif ($daysUntilDue !== null && $daysUntilDue >= 0): ?>
                    <div class="card-line text-success">⏱️ Vence em <?= $daysUntilDue ?> dia<?= $daysUntilDue === 1 ? '' : 's' ?></div>
                  <?php endif; ?>
                  <?php if ($lastContactDate): ?>
                    <div class="card-line"><span>📧 Último contato:</span> <?= h($lastContactDate) ?></div>
                  <?php endif; ?>
                  <?php if ($phoneDisplay): ?>
                    <div class="card-line"><span>📞 Telefone:</span>
                      <?php if ($phoneLink): ?>
                        <a href="<?= h($phoneLink) ?>" target="_blank"><?= h($phoneDisplay) ?></a>
                      <?php else: ?>
                        <?= h($phoneDisplay) ?>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($status === 'perdido' && $lostReason): ?>
                    <?php $reasonLabel = $lostReasonLabels[$lostReason] ?? $lostReason; ?>
                    <div class="card-line text-muted small">⚠️ Motivo: <?= h($reasonLabel) ?><?php if ($lostDetails): ?> — <?= h($lostDetails) ?><?php endif; ?></div>
                  <?php endif; ?>
                </div>

                <div class="kanban-card-actions">
                  <button class="btn btn-sm btn-outline-primary js-send-reminder" data-payment-id="<?= (int)$card['payment_id'] ?>">📨 Enviar lembrete</button>
                  <button class="btn btn-sm btn-outline-secondary js-register-contact" data-payment-id="<?= (int)$card['payment_id'] ?>">📞 Registrar contato</button>
                  <button class="btn btn-sm btn-outline-info js-open-details" data-payment-id="<?= (int)$card['payment_id'] ?>">👁️ Ver detalhes</button>
                  <?php if ($status !== 'em_cobranca' && $status !== 'perdido'): ?>
                    <button class="btn btn-sm btn-outline-warning js-move-to-collection" data-payment-id="<?= (int)$card['payment_id'] ?>">➡️ Mover p/ Em Cobrança</button>
                  <?php elseif ($status === 'perdido'): ?>
                    <button class="btn btn-sm btn-outline-primary js-reactivate" data-payment-id="<?= (int)$card['payment_id'] ?>">🔄 Reativar cobrança</button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Modais -->
<div class="modal fade" id="cardModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nova cobrança</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <form id="cardForm">
        <input type="hidden" name="payment_id" id="cardPaymentId">
        <input type="hidden" name="status" id="cardStatusInput">
        <div class="modal-body">
          <div class="alert alert-light border mb-3" id="cardStatusBadge">Status: <strong id="cardStatusLabel">—</strong></div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Cliente existente</label>
              <select class="form-control" name="client_id" id="cardClientSelect">
                <option value="">Novo cliente...</option>
                <?php foreach ($clientsList as $client): ?>
                  <option value="<?= (int)$client['id'] ?>"><?= h($client['name']) ?><?= !empty($client['email']) ? ' — ' . h($client['email']) : '' ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-6">
              <label>Nome do cliente</label>
              <input type="text" class="form-control" name="client_name" id="cardClientName" placeholder="Razão social ou contato">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Email</label>
              <input type="email" class="form-control" name="client_email" id="cardClientEmail" placeholder="cliente@email.com">
            </div>
            <div class="form-group col-md-6">
              <label>Telefone</label>
              <input type="text" class="form-control" name="client_phone" id="cardClientPhone" placeholder="(00) 00000-0000">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Projeto (opcional)</label>
              <select class="form-control" name="project_id" id="cardProjectSelect">
                <option value="">Sem projeto vinculado</option>
                <?php foreach ($projectsList as $project): ?>
                  <option value="<?= (int)$project['id'] ?>"><?= h($project['name']) ?><?= !empty($project['client_name']) ? ' — ' . h($project['client_name']) : '' ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Valor</label>
              <input type="number" min="0" step="0.01" class="form-control" name="amount" id="cardAmount" placeholder="0.00" required>
            </div>
            <div class="form-group col-md-3">
              <label>Vencimento</label>
              <input type="date" class="form-control" name="due_date" id="cardDueDate" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-3">
              <label>Moeda</label>
              <select class="form-control" name="currency" id="cardCurrency">
                <option value="BRL">Real (BRL)</option>
                <option value="USD">Dólar (USD)</option>
              </select>
            </div>
            <div class="form-group col-md-3">
              <label>Categoria</label>
              <input type="text" class="form-control" name="category" id="cardCategory" placeholder="Serviços, Manutenção...">
            </div>
            <div class="form-group col-md-6">
              <label>Descrição</label>
              <input type="text" class="form-control" name="description" id="cardDescription" placeholder="Resumo da cobrança">
            </div>
          </div>
          <div class="form-group">
            <label>Observações</label>
            <textarea class="form-control" rows="3" name="notes" id="cardNotes" placeholder="Informações internas sobre a cobrança"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="cardSubmitBtn">Salvar cobrança</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="reminderModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Enviar lembrete</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <form id="reminderForm">
        <input type="hidden" name="payment_id" id="reminderPaymentId">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Canal</label>
              <select name="channel" class="form-control" required>
                <option value="email">Email</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="sms">SMS</option>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>Template</label>
              <select name="template_key" class="form-control" id="reminderTemplateSelect">
                <?php foreach ($templates as $tpl): ?>
                  <option value="<?= h($tpl['key']) ?>"><?= h($tpl['label']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label>Agendar para</label>
              <input type="datetime-local" name="scheduled_at" class="form-control">
            </div>
          </div>
          <div class="form-group">
            <label>Pré-visualização</label>
            <textarea class="form-control" id="reminderPreview" rows="6" readonly></textarea>
          </div>
          <div class="form-group">
            <label>Observações</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Enviar lembrete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="contactModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Registrar contato</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <form id="contactForm">
        <input type="hidden" name="payment_id" id="contactPaymentId">
        <div class="modal-body">
          <div class="form-group">
            <label>Tipo de contato</label>
            <select name="contact_type" class="form-control" required>
              <option value="whatsapp">WhatsApp</option>
              <option value="ligacao">Ligação</option>
              <option value="email">Email</option>
              <option value="sms">SMS</option>
              <option value="outro">Outro</option>
            </select>
          </div>
          <div class="form-group">
            <label>Data e hora</label>
            <input type="datetime-local" name="contacted_at" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Resposta do cliente</label>
            <textarea name="client_response" class="form-control" rows="2"></textarea>
          </div>
          <div class="form-group">
            <label>Previsão de pagamento</label>
            <input type="date" name="expected_payment_at" class="form-control">
          </div>
          <div class="form-group">
            <label>Observações</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
          </div>
          <p class="small text-muted mb-0">Ao salvar, a cobrança será movida para "Em Cobrança" automaticamente.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Registrar contato</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="lostModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Marcar como perdido</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <form id="lostForm">
        <input type="hidden" name="payment_id" id="lostPaymentId">
        <div class="modal-body">
          <div class="form-group">
            <label>Motivo</label>
            <select name="lost_reason" class="form-control" required>
              <?php foreach ($lostReasonOptions as $opt): ?>
                <option value="<?= h($opt['value']) ?>"><?= h($opt['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Detalhes adicionais</label>
            <textarea name="lost_details" class="form-control" rows="3" placeholder="Descreva brevemente o contexto"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-danger">Marcar como perdido</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="collectionModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Mover para "Em Cobrança"</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <form id="collectionForm">
        <input type="hidden" name="payment_id" id="collectionPaymentId">
        <div class="modal-body">
          <div class="form-group">
            <label>Observação</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Contextualize a cobrança"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-warning">Mover para Em Cobrança</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes da cobrança</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body" id="detailsContent">
        <div class="text-center text-muted">Carregando...</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<div id="kanbanToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-delay="4000">
  <div class="toast-header">
    <strong class="mr-auto">Cobranças</strong>
    <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  </div>
  <div class="toast-body"></div>
</div>

<script>
window.COBRANCA_KANBAN = {
  board: <?= $jsonBoard ?>,
  meta: <?= $jsonMeta ?>,
  endpoints: {
    boardData: '/cobranca/board-data',
    move: '/cobranca/move',
    contact: '/cobranca/registrar-contato',
    reminder: '/cobranca/enviar-lembrete',
    details: '/cobranca/detalhes',
    cards: '/cobranca/cards',
    card: '/cobranca/card'
  }
};
</script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" integrity="sha384-vtXc+oykZMhUlCT3VkOITkkpFmS6r30YIOCwVDDDeWGPAHDq6cHruM3aMcMBXNte" crossorigin="anonymous"></script>
<script src="/assets/js/cobranca-kanban.js"></script>
