(function () {
  const root = window.COBRANCA_KANBAN || {};
  const state = {
    board: root.board || { columns: {}, summary: {} },
    meta: root.meta || { columnMeta: {}, lostReasons: [], templates: [] },
    endpoints: root.endpoints || {},
    sortables: [],
    pendingLost: null,
    pendingCollection: null,
  };

  const lostReasonLookup = (state.meta.lostReasons || []).reduce((acc, item) => {
    acc[item.value] = item.label;
    return acc;
  }, {});

  document.addEventListener('DOMContentLoaded', init);

  function init() {
    bindDelegatedEvents();
    bindForms();
    initSortable();
    prepareReminderPreview();
  }

  function bindDelegatedEvents() {
    document.addEventListener('click', (event) => {
      const reminderBtn = event.target.closest('.js-send-reminder');
      if (reminderBtn) {
        event.preventDefault();
        openReminderModal(reminderBtn.closest('.kanban-card'));
        return;
      }

      const contactBtn = event.target.closest('.js-register-contact');
      if (contactBtn) {
        event.preventDefault();
        openContactModal(contactBtn.closest('.kanban-card'));
        return;
      }

      const detailsBtn = event.target.closest('.js-open-details');
      if (detailsBtn) {
        event.preventDefault();
        openDetailsModal(detailsBtn.dataset.paymentId || detailsBtn.closest('.kanban-card').dataset.paymentId);
        return;
      }

      const collectionBtn = event.target.closest('.js-move-to-collection');
      if (collectionBtn) {
        event.preventDefault();
        openCollectionModal(collectionBtn.closest('.kanban-card'));
        return;
      }

      const reactivateBtn = event.target.closest('.js-reactivate');
      if (reactivateBtn) {
        event.preventDefault();
        const paymentId = reactivateBtn.dataset.paymentId || reactivateBtn.closest('.kanban-card').dataset.paymentId;
        moveCard(paymentId, 'em_cobranca', { reason_code: 'reactivate', notes: 'Reativado manualmente' });
        return;
      }
    });

    const templateSelect = document.getElementById('reminderTemplateSelect');
    if (templateSelect) {
      templateSelect.addEventListener('change', updateReminderPreview);
    }
  }

  function bindForms() {
    const reminderForm = document.getElementById('reminderForm');
    if (reminderForm) {
      reminderForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(reminderForm);
        const payload = Object.fromEntries(formData.entries());
        const paymentId = payload.payment_id;
        try {
          await postJSON(state.endpoints.reminder, payload);
          $('#reminderModal').modal('hide');
          showToast('Lembrete registrado com sucesso.', 'success');
          await refreshBoard();
        } catch (error) {
          showToast(error.message || 'Não foi possível enviar o lembrete.', 'danger');
        }
      });
    }

    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
      contactForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(contactForm);
        const payload = Object.fromEntries(formData.entries());
        try {
          await postJSON(state.endpoints.contact, payload);
          $('#contactModal').modal('hide');
          showToast('Contato registrado e cobrança movida.', 'success');
          await refreshBoard();
        } catch (error) {
          showToast(error.message || 'Não foi possível registrar o contato.', 'danger');
        }
      });
    }

    const lostForm = document.getElementById('lostForm');
    if (lostForm) {
      lostForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!state.pendingLost) {
          $('#lostModal').modal('hide');
          return;
        }
        const formData = new FormData(lostForm);
        const payload = Object.fromEntries(formData.entries());
        payload.reason_code = 'lost_manual';
        try {
          await moveCard(state.pendingLost.paymentId, 'perdido', payload);
          state.pendingLost = null;
          $('#lostModal').modal('hide');
        } catch (error) {
          showToast(error.message || 'Não foi possível mover para perdido.', 'danger');
        }
      });
    }

    const collectionForm = document.getElementById('collectionForm');
    if (collectionForm) {
      collectionForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!state.pendingCollection) {
          $('#collectionModal').modal('hide');
          return;
        }
        const formData = new FormData(collectionForm);
        const payload = Object.fromEntries(formData.entries());
        payload.reason_code = 'manual_collection';
        try {
          await moveCard(state.pendingCollection.paymentId, 'em_cobranca', payload);
          state.pendingCollection = null;
          $('#collectionModal').modal('hide');
        } catch (error) {
          showToast(error.message || 'Não foi possível mover para Em Cobrança.', 'danger');
        }
      });
    }

    $('#reminderModal, #contactModal, #lostModal, #collectionModal').on('hidden.bs.modal', function () {
      const form = this.querySelector('form');
      if (form) {
        form.reset();
      }
      if (this.id === 'reminderModal') {
        delete this.dataset.cardInfo;
      }
      if (this.id === 'lostModal') {
        state.pendingLost = null;
      }
      if (this.id === 'collectionModal') {
        state.pendingCollection = null;
      }
    });
  }

  function initSortable() {
    destroySortables();
    document.querySelectorAll('.kanban-column-body').forEach((column) => {
      const sortable = Sortable.create(column, {
        group: 'cobranca-board',
        animation: 160,
        ghostClass: 'kanban-card-ghost',
        dragClass: 'kanban-card-dragging',
        onEnd: handleDrop,
      });
      state.sortables.push(sortable);
    });
  }

  function destroySortables() {
    state.sortables.forEach((sortable) => sortable.destroy());
    state.sortables = [];
  }

  function handleDrop(evt) {
    const cardEl = evt.item;
    const paymentId = cardEl.dataset.paymentId;
    const toColumn = evt.to.dataset.status;
    const fromColumn = evt.from.dataset.status;

    if (!paymentId || !toColumn || toColumn === fromColumn) {
      revertCard(evt);
      return;
    }

    if (toColumn === 'perdido') {
      revertCard(evt);
      openLostModal(cardEl, paymentId);
      return;
    }

    if (toColumn === 'em_cobranca') {
      revertCard(evt);
      openCollectionModal(cardEl);
      return;
    }

    revertCard(evt);
    moveCard(paymentId, toColumn, { reason_code: 'drag_drop' });
  }

  function revertCard(evt) {
    if (!evt || !evt.from || !evt.item) {
      return;
    }
    const referenceNode = evt.from.children[evt.oldIndex] || null;
    evt.from.insertBefore(evt.item, referenceNode);
  }

  async function moveCard(paymentId, status, extra = {}) {
    if (!paymentId) {
      showToast('Cobrança inválida.', 'danger');
      return;
    }
    const payload = Object.assign({ payment_id: paymentId, to_status: status }, extra);
    try {
      await postJSON(state.endpoints.move, payload);
      showToast('Cobrança atualizada.', 'success');
      await refreshBoard();
    } catch (error) {
      showToast(error.message || 'Não foi possível atualizar a cobrança.', 'danger');
    }
  }

  async function refreshBoard() {
    try {
      const url = state.endpoints.boardData + (window.location.search || '');
      const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!response.ok) {
        throw new Error('Erro ao atualizar quadro.');
      }
      const data = await response.json();
      state.board = data;
      renderBoard();
      renderSummary();
      initSortable();
    } catch (error) {
      showToast(error.message || 'Não foi possível atualizar o quadro.', 'danger');
    }
  }

  function renderBoard() {
    const columns = state.board.columns || {};
    Object.keys(columns).forEach((status) => {
      const columnEl = document.querySelector(`.kanban-column[data-column="${status}"]`);
      if (!columnEl) {
        return;
      }
      const columnData = columns[status];
      const totals = columnData.totals || { amount: 0, clients: 0 };
      const amountLabel = formatCurrency(totals.amount || 0);

      const metaEl = columnEl.querySelector('.kanban-column-meta');
      if (metaEl) {
        metaEl.innerHTML = `<span>${totals.clients || 0} clientes</span><span>${amountLabel}</span>`;
      }

      const bodyEl = columnEl.querySelector('.kanban-column-body');
      if (!bodyEl) {
        return;
      }
      if (!columnData.cards || !columnData.cards.length) {
        bodyEl.innerHTML = '<div class="kanban-empty">Sem cobranças aqui.</div>';
        return;
      }

      const cardsHtml = columnData.cards.map((card) => buildCardHtml(card, status)).join('');
      bodyEl.innerHTML = cardsHtml;
    });
  }

  function renderSummary() {
    const summary = state.board.summary || {};
    const totalPendenteEl = document.querySelector('.kanban-summary .summary-item:nth-child(1) .summary-value');
    if (totalPendenteEl) {
      totalPendenteEl.textContent = formatCurrency(summary.total_amount || 0);
    }
    const totalAtivasEl = document.querySelector('.kanban-summary .summary-item:nth-child(2) .summary-value');
    if (totalAtivasEl) {
      totalAtivasEl.textContent = summary.total_cards || 0;
    }
    const vencendoHojeEl = document.querySelector('.kanban-summary .summary-item:nth-child(3) .summary-value');
    if (vencendoHojeEl) {
      vencendoHojeEl.innerHTML = `${summary.due_today || 0} <small class="text-muted d-block">${formatCurrency(summary.due_today_amount || 0)}</small>`;
    }
    const vencidoEl = document.querySelector('.kanban-summary .summary-item:nth-child(4) .summary-value');
    if (vencidoEl) {
      vencidoEl.textContent = formatCurrency(summary.overdue_amount || 0);
    }
    const alertEl = document.querySelector('.kanban-alert strong');
    if (alertEl) {
      alertEl.textContent = summary.due_today || 0;
    }
  }

  function buildCardHtml(card, status) {
    const meta = (state.meta.columnMeta || {})[status] || {};
    const accent = meta.accent || '#10B981';
    const amountFormatted = card.amount_formatted || formatCurrency(card.amount || 0);
    const dueFormatted = card.due_date ? formatDate(card.due_date) : '—';
    const daysOverdue = Number(card.days_overdue || 0);
    const daysUntil = card.days_until_due !== null ? Number(card.days_until_due) : null;
    const lastContact = card.last_contact_at ? formatDateTime(card.last_contact_at) : '';
    const phoneLabel = card.client_phone || '';
    const phoneLink = card.whatsapp_link || '';
    const badges = card.badges || [];
    const lostReason = card.lost_reason || '';
    const lostLabel = lostReasonLookup[lostReason] || lostReason;
    const lostDetails = card.lost_details || '';

    const badgeHtml = [
      badges.includes('alto_valor') ? '<span class="badge badge-soft-warning badge-pill ml-2">Alto valor</span>' : '',
      badges.includes('atencao') ? '<span class="badge badge-soft-danger badge-pill ml-2">Atenção</span>' : '',
      badges.includes('parcelado') ? '<span class="badge badge-soft-info badge-pill ml-2">Recorrente</span>' : '',
    ].filter(Boolean).join('');

    const overdueHtml = daysOverdue > 0
      ? `<div class="card-line text-danger">⏰ Há ${daysOverdue} dia${daysOverdue === 1 ? '' : 's'} em atraso</div>`
      : (daysUntil !== null && daysUntil >= 0
        ? `<div class="card-line text-success">⏱️ Vence em ${daysUntil} dia${daysUntil === 1 ? '' : 's'}</div>`
        : '');

    const contactHtml = lastContact
      ? `<div class="card-line"><span>📧 Último contato:</span> ${escapeHtml(lastContact)}</div>`
      : '';

    const phoneHtml = phoneLabel
      ? `<div class="card-line"><span>📞 Telefone:</span> ${phoneLink ? `<a href="${escapeAttr(phoneLink)}" target="_blank">${escapeHtml(phoneLabel)}</a>` : escapeHtml(phoneLabel)}</div>`
      : '';

    const lostHtml = (status === 'perdido' && lostReason)
      ? `<div class="card-line text-muted small">⚠️ Motivo: ${escapeHtml(lostLabel)}${lostDetails ? ` — ${escapeHtml(lostDetails)}` : ''}</div>`
      : '';

    const actionsHtml = status === 'perdido'
      ? `<button class="btn btn-sm btn-outline-primary js-reactivate" data-payment-id="${card.payment_id}">🔄 Reativar cobrança</button>`
      : `<button class="btn btn-sm btn-outline-warning js-move-to-collection" data-payment-id="${card.payment_id}">➡️ Mover p/ Em Cobrança</button>`;

    return `
      <div class="kanban-card ${status === 'perdido' ? 'kanban-card-lost' : ''}" data-payment-id="${card.payment_id}" data-status="${escapeAttr(status)}"
        data-client-name="${escapeAttr(card.client_name || '')}" data-project-name="${escapeAttr(card.project_name || card.description || '')}"
        data-amount="${Number(card.amount || 0).toFixed(2)}" data-amount-formatted="${escapeAttr(amountFormatted)}"
        data-due-date="${escapeAttr(card.due_date || '')}" data-days-overdue="${daysOverdue}" data-whatsapp="${escapeAttr(phoneLink)}"
        data-last-contact="${escapeAttr(card.last_contact_at || '')}" data-last-channel="${escapeAttr(card.last_contact_channel || '')}"
        data-lost-reason="${escapeAttr(lostReason)}" data-lost-details="${escapeAttr(lostDetails)}">
        <div class="kanban-card-inner" style="border-left-color: ${escapeAttr(accent)};">
          <div class="kanban-card-header">
            <div class="d-flex align-items-center">
              <span class="status-dot" style="background-color: ${escapeAttr(accent)}"></span>
              <span class="client-name">${escapeHtml(card.client_name || 'Cliente')}</span>
              ${badgeHtml}
            </div>
            <div class="dropdown">
              <button class="btn btn-sm btn-link text-muted" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-ellipsis-v"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-right">
                <button class="dropdown-item js-open-details" data-payment-id="${card.payment_id}">👁️ Ver detalhes</button>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="/pagamento/edit/${card.payment_id}">✏️ Editar pagamento</a>
                <button class="dropdown-item js-send-reminder" data-payment-id="${card.payment_id}">📧 Enviar lembrete rápido</button>
                ${phoneLink ? `<a class="dropdown-item" href="${escapeAttr(phoneLink)}" target="_blank">💬 Abrir WhatsApp</a>` : ''}
              </div>
            </div>
          </div>
          <div class="kanban-card-body">
            <div class="card-line"><span>📋 Projeto:</span> ${escapeHtml(card.project_name || card.description || '—')}</div>
            <div class="card-line"><span>💰 Valor:</span> ${escapeHtml(amountFormatted)}</div>
            <div class="card-line"><span>📅 Vencimento:</span> ${escapeHtml(dueFormatted)}</div>
            ${overdueHtml}
            ${contactHtml}
            ${phoneHtml}
            ${lostHtml}
          </div>
          <div class="kanban-card-actions">
            <button class="btn btn-sm btn-outline-primary js-send-reminder" data-payment-id="${card.payment_id}">📨 Enviar lembrete</button>
            <button class="btn btn-sm btn-outline-secondary js-register-contact" data-payment-id="${card.payment_id}">📞 Registrar contato</button>
            <button class="btn btn-sm btn-outline-info js-open-details" data-payment-id="${card.payment_id}">👁️ Ver detalhes</button>
            ${actionsHtml}
          </div>
        </div>
      </div>`;
  }

  function openReminderModal(cardEl) {
    if (!cardEl) return;
    const paymentId = cardEl.dataset.paymentId;
    document.getElementById('reminderPaymentId').value = paymentId;
    document.querySelector('#reminderModal [name="channel"]').value = 'email';
    const now = new Date();
    const iso = new Date(now.getTime() + 30 * 60000).toISOString().slice(0, 16);
    document.querySelector('#reminderModal [name="scheduled_at"]').value = iso;
    document.getElementById('reminderModal').dataset.cardInfo = JSON.stringify(extractCardInfo(cardEl));
    updateReminderPreview();
    $('#reminderModal').modal('show');
  }

  function prepareReminderPreview() {
    $('#reminderModal').on('shown.bs.modal', updateReminderPreview);
  }

  function updateReminderPreview() {
    const modal = document.getElementById('reminderModal');
    if (!modal) return;
    const dataAttr = modal.dataset.cardInfo;
    if (!dataAttr) {
      document.getElementById('reminderPreview').value = '';
      return;
    }
    const cardInfo = JSON.parse(dataAttr);
    const templateKey = document.getElementById('reminderTemplateSelect').value;
    const templates = state.meta.templates || [];
    const template = templates.find((tpl) => tpl.key === templateKey) || templates[0];
    if (!template) {
      document.getElementById('reminderPreview').value = '';
      return;
    }
    const body = template.body
      .replace(/\[NOME\]/g, cardInfo.clientName || '')
      .replace(/\[PROJETO\]/g, cardInfo.projectName || '')
      .replace(/\[VALOR\]/g, cardInfo.amountFormatted || '')
      .replace(/\[DATA\]/g, cardInfo.dueDateFormatted || '')
      .replace(/\[DIAS\]/g, cardInfo.daysOverdue || 0);
    document.getElementById('reminderPreview').value = body;
  }

  function extractCardInfo(cardEl) {
    const amount = parseFloat(cardEl.dataset.amount || '0');
    return {
      paymentId: cardEl.dataset.paymentId,
      clientName: cardEl.dataset.clientName || '',
      projectName: cardEl.dataset.projectName || '',
      amount,
      amountFormatted: cardEl.dataset.amountFormatted || formatCurrency(amount),
      dueDate: cardEl.dataset.dueDate || '',
      dueDateFormatted: cardEl.dataset.dueDate ? formatDate(cardEl.dataset.dueDate) : '',
      daysOverdue: Number(cardEl.dataset.daysOverdue || 0),
    };
  }

  function openContactModal(cardEl) {
    if (!cardEl) return;
    const paymentId = cardEl.dataset.paymentId;
    document.getElementById('contactPaymentId').value = paymentId;
    const contactInput = document.querySelector('#contactModal [name="contacted_at"]');
    if (contactInput) {
      contactInput.value = new Date().toISOString().slice(0, 16);
    }
    $('#contactModal').modal('show');
  }

  function openLostModal(cardEl, paymentId) {
    state.pendingLost = { paymentId: paymentId || cardEl.dataset.paymentId };
    document.getElementById('lostPaymentId').value = state.pendingLost.paymentId;
    $('#lostModal').modal('show');
  }

  function openCollectionModal(cardEl) {
    if (!cardEl) return;
    state.pendingCollection = { paymentId: cardEl.dataset.paymentId };
    document.getElementById('collectionPaymentId').value = cardEl.dataset.paymentId;
    $('#collectionModal').modal('show');
  }

  async function openDetailsModal(paymentId) {
    if (!paymentId) return;
    const modal = $('#detailsModal');
    const contentEl = document.getElementById('detailsContent');
    contentEl.innerHTML = '<div class="text-center text-muted">Carregando...</div>';
    modal.modal('show');
    try {
      const response = await fetch(`${state.endpoints.details}/${paymentId}`, { headers: { 'Accept': 'application/json' } });
      if (!response.ok) {
        throw new Error('Não foi possível carregar os detalhes.');
      }
      const data = await response.json();
      contentEl.innerHTML = buildDetailsHtml(data);
    } catch (error) {
      contentEl.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message || 'Falha ao carregar detalhes.')}</div>`;
    }
  }

  function buildDetailsHtml(data) {
    const card = data.card || {};
    const payment = data.payment || {};
    const client = data.client || {};
    const contacts = data.contacts || [];
    const movements = data.movements || [];

    const contactList = contacts.length
      ? contacts.map((item) => `
        <li class="list-group-item">
          <div><strong>${formatDateTime(item.contacted_at)}</strong> - ${escapeHtml(item.contact_type || '')}</div>
          ${item.client_response ? `<div>Retorno: ${escapeHtml(item.client_response)}</div>` : ''}
          ${item.expected_payment_at ? `<div>Previsão: ${formatDate(item.expected_payment_at)}</div>` : ''}
          ${item.notes ? `<div class="text-muted small">${escapeHtml(item.notes)}</div>` : ''}
        </li>`).join('')
      : '<li class="list-group-item text-muted">Nenhum contato registrado.</li>';

    const movementList = movements.length
      ? movements.map((item) => `
        <li class="list-group-item">
          <div><strong>${formatDateTime(item.created_at)}</strong> - ${escapeHtml(item.from_status || 'auto')} → ${escapeHtml(item.to_status)}</div>
          ${item.notes ? `<div class="text-muted small">${escapeHtml(item.notes)}</div>` : ''}
        </li>`).join('')
      : '<li class="list-group-item text-muted">Sem movimentações registradas.</li>';

    const amountFormatted = formatCurrency(payment.amount || card.amount || 0);

    return `
      <div class="row">
        <div class="col-md-6">
          <h6 class="text-uppercase text-muted">Cobrança</h6>
          <p class="mb-1"><strong>Cliente:</strong> ${escapeHtml(card.client_name || client.name || '—')}</p>
          <p class="mb-1"><strong>Projeto:</strong> ${escapeHtml(card.project_name || payment.project_name || '—')}</p>
          <p class="mb-1"><strong>Valor:</strong> ${escapeHtml(amountFormatted)}</p>
          <p class="mb-1"><strong>Vencimento:</strong> ${card.due_date ? formatDate(card.due_date) : (payment.due_date ? formatDate(payment.due_date) : '—')}</p>
          ${card.days_overdue ? `<p class="mb-1 text-danger"><strong>${card.days_overdue} dia${card.days_overdue === 1 ? '' : 's'} em atraso</strong></p>` : ''}
        </div>
        <div class="col-md-6">
          <h6 class="text-uppercase text-muted">Cliente</h6>
          <p class="mb-1"><strong>Email:</strong> ${escapeHtml(client.email || card.client_email || '—')}</p>
          <p class="mb-1"><strong>Telefone:</strong> ${escapeHtml(UtilsFormatPhone(client.phone || card.client_phone || ''))}</p>
        </div>
      </div>
      <hr>
      <div class="row">
        <div class="col-md-6">
          <h6 class="text-uppercase text-muted">Contatos</h6>
          <ul class="list-group list-group-flush">${contactList}</ul>
        </div>
        <div class="col-md-6">
          <h6 class="text-uppercase text-muted">Movimentações</h6>
          <ul class="list-group list-group-flush">${movementList}</ul>
        </div>
      </div>`;
  }

  async function postJSON(url, data) {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(data),
    });
    const body = await response.json().catch(() => ({}));
    if (!response.ok || body.success === false) {
      throw new Error(body.message || 'Falha na operação.');
    }
    return body;
  }

  function showToast(message, type = 'info') {
    const toastEl = $('#kanbanToast');
    if (!toastEl.length) return;
    const body = toastEl.find('.toast-body');
    body.removeClass('text-info text-success text-danger text-warning');
    const classMap = { info: 'text-info', success: 'text-success', danger: 'text-danger', warning: 'text-warning' };
    body.addClass(classMap[type] || 'text-info');
    body.text(message);
    toastEl.toast('show');
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
  }

  function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr + 'T00:00:00');
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleDateString('pt-BR');
  }

  function formatDateTime(dateTime) {
    if (!dateTime) return '';
    const date = new Date(dateTime.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
  }

  function escapeHtml(value) {
    if (value === undefined || value === null) return '';
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escapeAttr(value) {
    return escapeHtml(value).replace(/"/g, '&quot;');
  }

  function UtilsFormatPhone(value) {
    if (!value) return '—';
    const digits = String(value).replace(/\D+/g, '');
    if (digits.length === 10) {
      return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
    }
    if (digits.length === 11) {
      return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    }
    return value;
  }
})();
