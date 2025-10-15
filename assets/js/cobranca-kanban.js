(function () {
  const boot = window.COBRANCA_KANBAN || {};
  const meta = boot.meta || { columnMeta: {}, templates: [], lostReasons: [], responsaveis: [] };
  const endpoints = boot.endpoints || {};
  const store = createBoardStore(boot.board || { columns: {}, summary: {}, filters: {} });
  const pending = createPendingState();
  let boardInstance = null;

  document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('kanbanBoard');
    if (!root) {
      return;
    }
    boardInstance = new KanbanBoard({
      root,
      store,
      meta,
      endpoints,
      pending,
    });
    boardInstance.init();
  });

  /**
   * Estado principal do quadro com um padrão de subscription simples.
   */
  function createBoardStore(initialBoard) {
    let state = normalizeBoard(initialBoard);
    const listeners = new Set();

    function normalizeBoard(board) {
      return {
        columns: (board && board.columns) || {},
        summary: (board && board.summary) || {},
        filters: (board && board.filters) || {},
      };
    }

    return {
      getState() {
        return state;
      },
      setBoard(board) {
        state = normalizeBoard(board);
        listeners.forEach((listener) => listener(state));
      },
      subscribe(listener) {
        if (typeof listener !== 'function') {
          return () => {};
        }
        listeners.add(listener);
        listener(state);
        return () => listeners.delete(listener);
      },
    };
  }

  /**
   * Guarda estados transitórios usados em modais.
   */
  function createPendingState() {
    let lost = null;
    let collection = null;
    return {
      get lost() {
        return lost;
      },
      setLost(value) {
        lost = value;
      },
      clearLost() {
        lost = null;
      },
      get collection() {
        return collection;
      },
      setCollection(value) {
        collection = value;
      },
      clearCollection() {
        collection = null;
      },
    };
  }

  class KanbanBoard {
    constructor({ root, store, meta, endpoints, pending }) {
      this.root = root;
      this.store = store;
      this.meta = meta || { columnMeta: {} };
      this.endpoints = endpoints || {};
      this.pending = pending || createPendingState();
      this.columns = new Map();
      this.dragManager = null;
      this.unsubscribe = null;
       this.cardForm = null;
      this.handleClick = this.handleClick.bind(this);
      this.handleTemplateChange = this.handleTemplateChange.bind(this);
    }

    init() {
      this.cacheSummaryEls();
      this.buildColumns();
      this.bindEvents();
      this.bindForms();
      this.dragManager = createDragManager(this);
      this.unsubscribe = this.store.subscribe((state) => this.render(state));
      prepareReminderPreview();
      this.refreshEmptyStates();
      this.cardForm = new CardFormManager(this);
    }

    cacheSummaryEls() {
      this.summaryTotalPendenteEl = document.querySelector('.kanban-summary .summary-item:nth-child(1) .summary-value');
      this.summaryTotalCardsEl = document.querySelector('.kanban-summary .summary-item:nth-child(2) .summary-value');
      this.summaryDueTodayCountEl = document.querySelector('.summary-due-today-count');
      this.summaryDueTodayAmountEl = document.querySelector('.summary-due-today-amount');
      this.summaryOverdueEl = document.querySelector('.kanban-summary .summary-item:nth-child(4) .summary-value');
      this.alertDueTodayEl = document.querySelector('.kanban-alert strong');
    }

    buildColumns() {
      this.columns.clear();
      const columnEls = this.root.querySelectorAll('.kanban-column');
      columnEls.forEach((columnEl) => {
        const status = columnEl.dataset.column;
        if (!status) {
          return;
        }
        const columnMeta = (this.meta.columnMeta || {})[status] || {};
        const column = new KanbanColumn(columnEl, status, columnMeta);
        this.columns.set(status, column);
      });
    }

    bindEvents() {
      document.addEventListener('click', this.handleClick);
      const templateSelect = document.getElementById('reminderTemplateSelect');
      if (templateSelect) {
        templateSelect.addEventListener('change', this.handleTemplateChange);
      }
    }

    bindForms() {
      const reminderForm = document.getElementById('reminderForm');
      if (reminderForm) {
        reminderForm.addEventListener('submit', async (event) => {
          event.preventDefault();
          const formData = new FormData(reminderForm);
          const payload = Object.fromEntries(formData.entries());
          try {
            await postJSON(this.endpoints.reminder, payload);
            $('#reminderModal').modal('hide');
            showToast('Lembrete registrado com sucesso.', 'success');
            await this.refreshBoard(false);
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
            await postJSON(this.endpoints.contact, payload);
            $('#contactModal').modal('hide');
            showToast('Contato registrado e cobrança movida.', 'success');
            await this.refreshBoard(false);
          } catch (error) {
            showToast(error.message || 'Não foi possível registrar o contato.', 'danger');
          }
        });
      }

      const lostForm = document.getElementById('lostForm');
      if (lostForm) {
        lostForm.addEventListener('submit', async (event) => {
          event.preventDefault();
          const pendingLost = this.pending.lost;
          if (!pendingLost) {
            $('#lostModal').modal('hide');
            return;
          }
          const formData = new FormData(lostForm);
          const payload = Object.fromEntries(formData.entries());
          payload.reason_code = 'lost_manual';
          try {
            await this.moveCard(pendingLost.paymentId, 'perdido', payload);
            this.pending.clearLost();
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
          const pendingCollection = this.pending.collection;
          if (!pendingCollection) {
            $('#collectionModal').modal('hide');
            return;
          }
          const formData = new FormData(collectionForm);
          const payload = Object.fromEntries(formData.entries());
          payload.reason_code = 'manual_collection';
          try {
            await this.moveCard(pendingCollection.paymentId, 'em_cobranca', payload);
            this.pending.clearCollection();
            $('#collectionModal').modal('hide');
          } catch (error) {
            showToast(error.message || 'Não foi possível mover para Em Cobrança.', 'danger');
          }
        });
      }

      $('#reminderModal, #contactModal, #lostModal, #collectionModal').on('hidden.bs.modal', (event) => {
        const form = event.currentTarget.querySelector('form');
        if (form) {
          form.reset();
        }
        if (event.currentTarget.id === 'reminderModal') {
          delete event.currentTarget.dataset.cardInfo;
        }
        if (event.currentTarget.id === 'lostModal') {
          this.pending.clearLost();
        }
        if (event.currentTarget.id === 'collectionModal') {
          this.pending.clearCollection();
        }
      });
    }

    handleTemplateChange() {
      updateReminderPreview(meta.templates || []);
    }

    handleClick(event) {
      const addBtn = event.target.closest('.kanban-add-card');
      if (addBtn) {
        event.preventDefault();
        if (this.cardForm) {
          this.cardForm.openCreate(addBtn.dataset.status || 'a_vencer');
        }
        return;
      }

      const editBtn = event.target.closest('.js-edit-card');
      if (editBtn) {
        event.preventDefault();
        if (this.cardForm) {
          this.cardForm.openEdit(editBtn.closest('.kanban-card'));
        }
        return;
      }

      const deleteBtn = event.target.closest('.js-delete-card');
      if (deleteBtn) {
        event.preventDefault();
        const cardEl = deleteBtn.closest('.kanban-card');
        const paymentId = deleteBtn.dataset.paymentId || (cardEl && cardEl.dataset.paymentId);
        if (!paymentId) {
          return;
        }
        const clientLabel = cardEl ? (cardEl.dataset.clientName || 'esta cobrança') : 'esta cobrança';
        if (!window.confirm(`Remover ${clientLabel}?`)) {
          return;
        }
        this.deleteCard(paymentId);
        return;
      }

      const reminderBtn = event.target.closest('.js-send-reminder');
      if (reminderBtn) {
        event.preventDefault();
        this.openReminderModal(reminderBtn.closest('.kanban-card'));
        return;
      }

      const contactBtn = event.target.closest('.js-register-contact');
      if (contactBtn) {
        event.preventDefault();
        this.openContactModal(contactBtn.closest('.kanban-card'));
        return;
      }

      const detailsBtn = event.target.closest('.js-open-details');
      if (detailsBtn) {
        event.preventDefault();
        const paymentId = detailsBtn.dataset.paymentId || detailsBtn.closest('.kanban-card').dataset.paymentId;
        this.openDetailsModal(paymentId);
        return;
      }

      const collectionBtn = event.target.closest('.js-move-to-collection');
      if (collectionBtn) {
        event.preventDefault();
        this.openCollectionModal(collectionBtn.closest('.kanban-card'));
        return;
      }

      const reactivateBtn = event.target.closest('.js-reactivate');
      if (reactivateBtn) {
        event.preventDefault();
        const paymentId = reactivateBtn.dataset.paymentId || reactivateBtn.closest('.kanban-card').dataset.paymentId;
        this.moveCard(paymentId, 'em_cobranca', { reason_code: 'reactivate', notes: 'Reativado manualmente' });
        return;
      }
    }

    handleDragStart() {
      this.root.classList.add('is-dragging');
    }

    handleDragMove(evt) {
      this.clearDropHighlights();
      const column = evt.to ? evt.to.closest('.kanban-column') : null;
      if (column) {
        column.classList.add('is-drop-target');
      }
    }

    handleDrop(evt, revert) {
      this.root.classList.remove('is-dragging');
      this.clearDropHighlights();
      const cardEl = evt.item;
      const paymentId = cardEl ? cardEl.dataset.paymentId : null;
      const toColumn = evt.to && evt.to.dataset.status;
      const fromColumn = evt.from && evt.from.dataset.status;

      if (!paymentId || !toColumn || !fromColumn || toColumn === fromColumn) {
        revert();
        this.refreshEmptyStates();
        return;
      }

      if (toColumn === 'perdido') {
        revert();
        this.openLostModal(cardEl, paymentId);
        this.refreshEmptyStates();
        return;
      }

      if (toColumn === 'em_cobranca') {
        revert();
        this.openCollectionModal(cardEl);
        this.refreshEmptyStates();
        return;
      }

      const confirmed = this.confirmMove(cardEl, fromColumn, toColumn);
      revert();
      this.refreshEmptyStates();
      if (!confirmed) {
        return;
      }
      this.moveCard(paymentId, toColumn, { reason_code: 'drag_drop' });
    }

    confirmMove(cardEl, fromColumn, toColumn) {
      const fromMeta = (this.meta.columnMeta || {})[fromColumn] || {};
      const toMeta = (this.meta.columnMeta || {})[toColumn] || {};
      const clientName = cardEl ? cardEl.dataset.clientName || 'este cliente' : 'este cliente';
      const message = `Mover ${clientName} de "${fromMeta.title || fromColumn}" para "${toMeta.title || toColumn}"?`;
      return window.confirm(message);
    }

    clearDropHighlights() {
      this.root.querySelectorAll('.kanban-column.is-drop-target').forEach((el) => el.classList.remove('is-drop-target'));
    }

    refreshEmptyStates() {
      this.columns.forEach((column) => column.syncEmptyState());
    }

    async moveCard(paymentId, status, extra = {}) {
      if (!paymentId) {
        showToast('Cobrança inválida.', 'danger');
        return;
      }
      const payload = Object.assign({ payment_id: paymentId, to_status: status }, extra);
      try {
        await postJSON(this.endpoints.move, payload);
        showToast('Cobrança atualizada.', 'success');
        await this.refreshBoard(false);
      } catch (error) {
        showToast(error.message || 'Não foi possível atualizar a cobrança.', 'danger');
      }
    }

    async deleteCard(paymentId) {
      if (!paymentId) {
        showToast('Cobrança inválida.', 'danger');
        return;
      }
      try {
        await requestJSON(`${this.endpoints.card}/${paymentId}`, 'DELETE');
        showToast('Cobrança removida.', 'success');
        await this.refreshBoard(false);
      } catch (error) {
        showToast(error.message || 'Não foi possível remover a cobrança.', 'danger');
      }
    }

    async refreshBoard(showError = true) {
      try {
        const url = this.endpoints.boardData + (window.location.search || '');
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
          throw new Error('Erro ao atualizar quadro.');
        }
        const data = await response.json();
        this.store.setBoard(data);
      } catch (error) {
        if (showError) {
          showToast(error.message || 'Não foi possível atualizar o quadro.', 'danger');
        }
      }
    }

    render(state) {
      const columns = state.columns || {};
      this.columns.forEach((column, status) => {
        column.render(columns[status], (card) => KanbanCard.render(card, status, column.meta));
      });
      this.renderSummary(state.summary || {});
      this.refreshEmptyStates();
      if (this.dragManager) {
        this.dragManager.refresh();
      }
    }

    renderSummary(summary) {
      if (this.summaryTotalPendenteEl) {
        this.summaryTotalPendenteEl.textContent = formatCurrency(summary.total_amount || 0);
      }
      if (this.summaryTotalCardsEl) {
        this.summaryTotalCardsEl.textContent = summary.total_cards || 0;
      }
      if (this.summaryDueTodayCountEl) {
        this.summaryDueTodayCountEl.textContent = summary.due_today || 0;
      }
      if (this.summaryDueTodayAmountEl) {
        this.summaryDueTodayAmountEl.textContent = formatCurrency(summary.due_today_amount || 0);
      }
      if (this.summaryOverdueEl) {
        this.summaryOverdueEl.textContent = formatCurrency(summary.overdue_amount || 0);
      }
      if (this.alertDueTodayEl) {
        this.alertDueTodayEl.textContent = summary.due_today || 0;
      }
    }

    openReminderModal(cardEl) {
      if (!cardEl) return;
      const paymentId = cardEl.dataset.paymentId;
      document.getElementById('reminderPaymentId').value = paymentId;
      const channelInput = document.querySelector('#reminderModal [name="channel"]');
      if (channelInput) {
        channelInput.value = 'email';
      }
      const now = new Date();
      const iso = new Date(now.getTime() + 30 * 60000).toISOString().slice(0, 16);
      document.querySelector('#reminderModal [name="scheduled_at"]').value = iso;
      const modal = document.getElementById('reminderModal');
      modal.dataset.cardInfo = JSON.stringify(extractCardInfo(cardEl));
      updateReminderPreview(meta.templates || []);
      $('#reminderModal').modal('show');
    }

    openContactModal(cardEl) {
      if (!cardEl) return;
      const paymentId = cardEl.dataset.paymentId;
      document.getElementById('contactPaymentId').value = paymentId;
      const contactInput = document.querySelector('#contactModal [name="contacted_at"]');
      if (contactInput) {
        contactInput.value = new Date().toISOString().slice(0, 16);
      }
      $('#contactModal').modal('show');
    }

    openLostModal(cardEl, paymentId) {
      const id = paymentId || (cardEl ? cardEl.dataset.paymentId : null);
      if (!id) {
        return;
      }
      this.pending.setLost({ paymentId: id });
      document.getElementById('lostPaymentId').value = id;
      $('#lostModal').modal('show');
    }

    openCollectionModal(cardEl) {
      if (!cardEl) return;
      const paymentId = cardEl.dataset.paymentId;
      this.pending.setCollection({ paymentId });
      document.getElementById('collectionPaymentId').value = paymentId;
      $('#collectionModal').modal('show');
    }

    async openDetailsModal(paymentId) {
      if (!paymentId) return;
      const modal = $('#detailsModal');
      const contentEl = document.getElementById('detailsContent');
      contentEl.innerHTML = '<div class="text-center text-muted">Carregando...</div>';
      modal.modal('show');
      try {
        const response = await fetch(`${this.endpoints.details}/${paymentId}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) {
          throw new Error('Não foi possível carregar os detalhes.');
        }
        const data = await response.json();
        contentEl.innerHTML = buildDetailsHtml(data);
      } catch (error) {
        contentEl.innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message || 'Falha ao carregar detalhes.')}</div>`;
      }
    }
  }

  class CardFormManager {
    constructor(board) {
      this.board = board;
      this.meta = board.meta || {};
      this.form = document.getElementById('cardForm');
      if (!this.form) {
        return;
      }
      this.modal = $('#cardModal');
      this.modalTitle = document.querySelector('#cardModal .modal-title');
      this.paymentIdInput = document.getElementById('cardPaymentId');
      this.statusInput = document.getElementById('cardStatusInput');
      this.statusLabelEl = document.getElementById('cardStatusLabel');
      this.submitBtn = document.getElementById('cardSubmitBtn');
      this.clientSelect = document.getElementById('cardClientSelect');
      this.clientNameInput = document.getElementById('cardClientName');
      this.clientEmailInput = document.getElementById('cardClientEmail');
      this.clientPhoneInput = document.getElementById('cardClientPhone');
      this.projectSelect = document.getElementById('cardProjectSelect');
      this.amountInput = document.getElementById('cardAmount');
      this.dueInput = document.getElementById('cardDueDate');
      this.currencyInput = document.getElementById('cardCurrency');
      this.categoryInput = document.getElementById('cardCategory');
      this.descriptionInput = document.getElementById('cardDescription');
      this.notesInput = document.getElementById('cardNotes');
      this.mode = 'create';
      this.currentStatus = 'a_vencer';

      if (this.clientSelect) {
        this.clientSelect.addEventListener('change', () => this.fillClientFieldsFromSelect());
      }
      this.form.addEventListener('submit', (event) => this.handleSubmit(event));
      if (this.modal && this.modal.length) {
        this.modal.on('hidden.bs.modal', () => this.reset());
      }
    }

    fillClientFieldsFromSelect() {
      if (!this.clientSelect) return;
      const selectedId = this.clientSelect.value;
      if (!selectedId) {
        return;
      }
      const clients = this.meta.clients || [];
      const found = clients.find((client) => String(client.id) === String(selectedId));
      if (found) {
        if (this.clientNameInput) this.clientNameInput.value = found.name || '';
        if (this.clientEmailInput) this.clientEmailInput.value = found.email || '';
        if (this.clientPhoneInput) this.clientPhoneInput.value = found.phone || '';
      }
    }

    openCreate(status) {
      this.reset();
      this.mode = 'create';
      this.currentStatus = status || 'a_vencer';
      this.updateStatusLabel();
      if (this.submitBtn) {
        this.submitBtn.textContent = 'Salvar cobrança';
      }
      if (this.modalTitle) {
        this.modalTitle.textContent = 'Nova cobrança';
      }
      if (this.modal && this.modal.length) {
        this.modal.modal('show');
      }
    }

    openEdit(cardEl) {
      if (!cardEl) return;
      this.reset();
      this.mode = 'edit';
      const data = cardEl.dataset || {};
      if (this.paymentIdInput) {
        this.paymentIdInput.value = data.paymentId || '';
      }
      this.currentStatus = data.status || 'a_vencer';
      this.updateStatusLabel();
      this.setSelectValue(this.projectSelect, data.projectId, data.projectName || '');
      this.setSelectValue(this.clientSelect, data.clientId, data.clientName || '');
      if (!this.clientSelect || !this.clientSelect.value) {
        if (this.clientNameInput) this.clientNameInput.value = data.clientName || '';
        if (this.clientEmailInput) this.clientEmailInput.value = data.clientEmail || '';
        if (this.clientPhoneInput) this.clientPhoneInput.value = data.clientPhone || '';
      } else {
        this.fillClientFieldsFromSelect();
      }
      if (this.clientNameInput && data.clientName) this.clientNameInput.value = data.clientName;
      if (this.clientEmailInput && data.clientEmail) this.clientEmailInput.value = data.clientEmail;
      if (this.clientPhoneInput && data.clientPhone) this.clientPhoneInput.value = data.clientPhone;
      if (this.amountInput) this.amountInput.value = data.amount || '';
      if (this.dueInput) this.dueInput.value = data.dueDate || '';
      if (this.currencyInput) this.currencyInput.value = data.currency || 'BRL';
      if (this.categoryInput) this.categoryInput.value = data.category || '';
      if (this.descriptionInput) this.descriptionInput.value = data.description || '';
      if (this.notesInput) this.notesInput.value = data.notes || '';
      if (this.submitBtn) {
        this.submitBtn.textContent = 'Atualizar cobrança';
      }
      if (this.modalTitle) {
        this.modalTitle.textContent = 'Editar cobrança';
      }
      if (this.modal && this.modal.length) {
        this.modal.modal('show');
      }
    }

    async handleSubmit(event) {
      event.preventDefault();
      const payload = this.serialize();
      if (!payload) {
        return;
      }
      try {
        this.setSubmitting(true);
        let response;
        if (this.mode === 'create') {
          payload.status = this.currentStatus;
          response = await requestJSON(this.board.endpoints.cards, 'POST', payload);
          showToast('Cobrança criada com sucesso.', 'success');
        } else {
          const paymentId = payload.payment_id;
          if (!paymentId) {
            throw new Error('Cobrança inválida.');
          }
          response = await requestJSON(`${this.board.endpoints.card}/${paymentId}`, 'PATCH', payload);
          showToast('Cobrança atualizada.', 'success');
        }
        if (response && response.card) {
          this.registerClientFromCard(response.card);
          this.registerProjectFromCard(response.card);
        }
        if (this.modal && this.modal.length) {
          this.modal.modal('hide');
        }
        await this.board.refreshBoard(false);
      } catch (error) {
        showToast(error.message || 'Não foi possível salvar a cobrança.', 'danger');
      } finally {
        this.setSubmitting(false);
      }
    }

    serialize() {
      if (!this.form) {
        return null;
      }
      const formData = new FormData(this.form);
      const payload = {};
      formData.forEach((value, key) => {
        if (typeof value === 'string') {
          const trimmed = value.trim();
          if (trimmed !== '') {
            payload[key] = trimmed;
          }
        } else if (value != null) {
          payload[key] = value;
        }
      });
      delete payload.status;
      if (this.mode === 'edit' && this.paymentIdInput && this.paymentIdInput.value) {
        payload.payment_id = this.paymentIdInput.value;
      }
      return payload;
    }

    setSubmitting(isSubmitting) {
      if (!this.submitBtn) return;
      this.submitBtn.disabled = isSubmitting;
      if (isSubmitting) {
        this.submitBtn.textContent = 'Salvando...';
      } else if (this.mode === 'create') {
        this.submitBtn.textContent = 'Salvar cobrança';
      } else {
        this.submitBtn.textContent = 'Atualizar cobrança';
      }
    }

    reset() {
      if (!this.form) return;
      this.form.reset();
      if (this.clientSelect) this.clientSelect.value = '';
      if (this.clientNameInput) this.clientNameInput.value = '';
      if (this.clientEmailInput) this.clientEmailInput.value = '';
      if (this.clientPhoneInput) this.clientPhoneInput.value = '';
      if (this.projectSelect) this.projectSelect.value = '';
      if (this.categoryInput) this.categoryInput.value = '';
      if (this.descriptionInput) this.descriptionInput.value = '';
      if (this.notesInput) this.notesInput.value = '';
      if (this.paymentIdInput) this.paymentIdInput.value = '';
      this.mode = 'create';
      this.currentStatus = 'a_vencer';
      this.updateStatusLabel();
      if (this.submitBtn) {
        this.submitBtn.textContent = 'Salvar cobrança';
      }
      if (this.modalTitle) {
        this.modalTitle.textContent = 'Nova cobrança';
      }
    }

    updateStatusLabel() {
      if (this.statusInput) {
        this.statusInput.value = this.currentStatus;
      }
      if (this.statusLabelEl) {
        const meta = (this.meta.columnMeta || {})[this.currentStatus] || {};
        this.statusLabelEl.textContent = meta.title || this.currentStatus;
      }
    }

    setSelectValue(select, value, label) {
      if (!select) return;
      if (!value && value !== 0) {
        select.value = '';
        return;
      }
      const valueStr = String(value);
      let option = Array.from(select.options).find((opt) => opt.value === valueStr);
      if (!option && label) {
        option = new Option(label, valueStr, true, true);
        select.appendChild(option);
        if (select === this.clientSelect) {
          const clients = this.meta.clients || (this.meta.clients = []);
          if (!clients.find((client) => String(client.id) === valueStr)) {
            clients.push({ id: valueStr, name: label });
          }
        } else if (select === this.projectSelect) {
          const projects = this.meta.projects || (this.meta.projects = []);
          if (!projects.find((project) => String(project.id) === valueStr)) {
            projects.push({ id: valueStr, name: label });
          }
        }
      } else if (option) {
        option.selected = true;
      } else {
        select.value = '';
      }
    }

    registerClientFromCard(card) {
      if (!card || !card.client_id) {
        return;
      }
      const valueStr = String(card.client_id);
      const clients = this.meta.clients || (this.meta.clients = []);
      if (!clients.find((client) => String(client.id) === valueStr)) {
        clients.push({
          id: card.client_id,
          name: card.client_name || '',
          email: card.client_email || '',
          phone: card.client_phone || '',
        });
      }
      if (this.clientSelect) {
        let option = Array.from(this.clientSelect.options).find((opt) => opt.value === valueStr);
        if (!option) {
          option = new Option(card.client_name || `Cliente ${valueStr}`, valueStr);
          this.clientSelect.appendChild(option);
        } else if (card.client_name) {
          option.textContent = card.client_name;
        }
      }
    }

    registerProjectFromCard(card) {
      if (!card || !card.project_id) {
        return;
      }
      const valueStr = String(card.project_id);
      const projects = this.meta.projects || (this.meta.projects = []);
      if (!projects.find((project) => String(project.id) === valueStr)) {
        projects.push({
          id: card.project_id,
          name: card.project_name || '',
          client_id: card.client_id || null,
          client_name: card.client_name || '',
        });
      }
      if (this.projectSelect) {
        let option = Array.from(this.projectSelect.options).find((opt) => opt.value === valueStr);
        if (!option) {
          option = new Option(card.project_name || `Projeto ${valueStr}`, valueStr);
          this.projectSelect.appendChild(option);
        } else if (card.project_name) {
          option.textContent = card.project_name;
        }
      }
    }
  }

  class KanbanColumn {
    constructor(element, status, meta) {
      this.element = element;
      this.status = status;
      this.meta = meta || {};
      this.body = element.querySelector('.kanban-column-body');
      this.cardsCountEl = element.querySelector('[data-role="cards"]');
      this.clientsCountEl = element.querySelector('[data-role="clients"]');
      this.amountEl = element.querySelector('[data-role="amount"]');
    }

    render(columnData, cardRenderer) {
      const totals = (columnData && columnData.totals) || { amount: 0, clients: 0, cards: 0 };
      if (this.cardsCountEl) {
        this.cardsCountEl.textContent = `${totals.cards || 0} itens`;
      }
      if (this.clientsCountEl) {
        this.clientsCountEl.textContent = `${totals.clients || 0} clientes`;
      }
      if (this.amountEl) {
        this.amountEl.textContent = formatCurrency(totals.amount || 0);
      }

      if (!this.body) {
        return;
      }

      const cards = (columnData && columnData.cards) || [];
      if (!cards.length) {
        this.body.innerHTML = '<div class="kanban-empty">Sem cobranças aqui.</div>';
        return;
      }

      const cardsHtml = cards.map((card) => cardRenderer(card)).join('');
      this.body.innerHTML = cardsHtml;
    }

    syncEmptyState() {
      if (!this.body) {
        return;
      }
      const hasCards = this.body.querySelector('.kanban-card');
      if (!hasCards) {
        this.body.innerHTML = '<div class="kanban-empty">Sem cobranças aqui.</div>';
      }
    }
  }

  class KanbanCard {
    static render(card, status, meta) {
      if (!card) {
        return '';
      }
      const accent = (meta && meta.accent) || '#10B981';
      const priority = determinePriority(card);
      const priorityLabel = PRIORITY_LABELS[priority] || 'Baixa';
      const amountFormatted = card.amount_formatted || formatCurrency(card.amount || 0);
      const dueFormatted = card.due_date_formatted || (card.due_date ? formatDate(card.due_date) : '—');
      const daysOverdue = Number(card.days_overdue || 0);
      const daysUntil = card.days_until_due !== null ? Number(card.days_until_due) : null;
      const lastContact = card.last_contact_at ? formatDateTime(card.last_contact_at) : '';
      const phoneLabel = card.client_phone || '';
      const phoneLink = card.whatsapp_link || '';
      const badges = Array.isArray(card.badges) ? card.badges : [];
      const lostReason = card.lost_reason || '';
      const lostDetails = card.lost_details || '';
      const lostLabel = lookupLostReason(lostReason);

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
        ? `<button class="btn btn-sm btn-outline-primary js-reactivate" data-payment-id="${card.payment_id}">🔄 Reativar cobrança</button>
           <button class="btn btn-sm btn-outline-danger js-delete-card" data-payment-id="${card.payment_id}">🗑️ Excluir</button>`
        : `<button class="btn btn-sm btn-outline-warning js-move-to-collection" data-payment-id="${card.payment_id}">➡️ Mover p/ Em Cobrança</button>
           <button class="btn btn-sm btn-outline-danger js-delete-card" data-payment-id="${card.payment_id}">🗑️ Excluir</button>`;

      return `
        <div class="kanban-card ${status === 'perdido' ? 'kanban-card-lost' : ''}" data-payment-id="${card.payment_id}"
          data-status="${escapeAttr(status)}"
          data-client-name="${escapeAttr(card.client_name || '')}"
          data-project-name="${escapeAttr(card.project_name || card.description || '')}"
          data-amount="${Number(card.amount || 0).toFixed(2)}"
          data-amount-formatted="${escapeAttr(amountFormatted)}"
          data-due-date="${escapeAttr(card.due_date || '')}"
          data-days-overdue="${daysOverdue}"
          data-whatsapp="${escapeAttr(phoneLink)}"
          data-last-contact="${escapeAttr(card.last_contact_at || '')}"
          data-last-channel="${escapeAttr(card.last_contact_channel || '')}"
          data-lost-reason="${escapeAttr(lostReason)}"
          data-lost-details="${escapeAttr(lostDetails)}"
          data-project-id="${card.project_id || ''}"
          data-client-id="${card.client_id || ''}"
          data-description="${escapeAttr(card.description || '')}"
          data-category="${escapeAttr(card.category || '')}"
          data-notes="${escapeAttr(card.notes || '')}"
          data-currency="${escapeAttr(card.currency || 'BRL')}"
          data-client-email="${escapeAttr(card.client_email || '')}"
          data-client-phone="${escapeAttr(card.client_phone || '')}"
          data-priority="${escapeAttr(priority)}">
          <div class="kanban-card-inner" style="border-left-color: ${escapeAttr(accent)};">
            <div class="kanban-card-header">
              <div class="d-flex align-items-center">
                <span class="status-dot" style="background-color: ${escapeAttr(accent)}"></span>
                <span class="client-name">${escapeHtml(card.client_name || 'Cliente')}</span>
                <span class="priority-tag priority-${escapeAttr(priority)}" title="Prioridade ${escapeAttr(priorityLabel)}">${escapeHtml(priorityLabel)}</span>
                ${badgeHtml}
              </div>
              <div class="dropdown">
                <button class="btn btn-sm btn-link text-muted" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                  <button class="dropdown-item js-open-details" data-payment-id="${card.payment_id}">👁️ Ver detalhes</button>
                  <button class="dropdown-item js-edit-card" data-payment-id="${card.payment_id}">✏️ Editar cobrança</button>
                  <div class="dropdown-divider"></div>
                  <button class="dropdown-item js-send-reminder" data-payment-id="${card.payment_id}">📧 Enviar lembrete rápido</button>
                  ${phoneLink ? `<a class="dropdown-item" href="${escapeAttr(phoneLink)}" target="_blank">💬 Abrir WhatsApp</a>` : ''}
                  <div class="dropdown-divider"></div>
                  <button class="dropdown-item text-danger js-delete-card" data-payment-id="${card.payment_id}">🗑️ Remover cobrança</button>
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
  }

  function determinePriority(card) {
    const daysOverdue = Number(card.days_overdue || 0);
    const daysUntil = card.days_until_due !== null ? Number(card.days_until_due) : null;
    if (daysOverdue > 0) {
      return 'critica';
    }
    if (daysUntil !== null && daysUntil <= 1) {
      return 'alta';
    }
    if (daysUntil !== null && daysUntil <= 3) {
      return 'media';
    }
    return 'baixa';
  }

  const PRIORITY_LABELS = {
    critica: 'Crítica',
    alta: 'Alta',
    media: 'Média',
    baixa: 'Baixa',
  };

  function lookupLostReason(reasonKey) {
    const options = meta.lostReasons || [];
    const found = options.find((opt) => opt.value === reasonKey);
    return found ? found.label : reasonKey;
  }

  function createDragManager(board) {
    let sortables = [];

    function destroy() {
      sortables.forEach((sortable) => sortable.destroy());
      sortables = [];
    }

    function init() {
      destroy();
      if (!board.root || typeof Sortable === 'undefined') {
        return;
      }
      const bodies = board.root.querySelectorAll('.kanban-column-body');
      bodies.forEach((body) => {
        const sortable = Sortable.create(body, {
          group: 'cobranca-board',
          animation: 180,
          ghostClass: 'kanban-card-ghost',
          dragClass: 'kanban-card-dragging',
          onStart: () => board.handleDragStart(),
          onEnd: (evt) => {
            const revert = () => revertCard(evt);
            board.handleDrop(evt, revert);
          },
          onMove: (evt) => {
            board.handleDragMove(evt);
          },
        });
        sortables.push(sortable);
      });
    }

    init();

    return {
      refresh: init,
      destroy,
    };
  }

  function revertCard(evt) {
    if (!evt || !evt.from || !evt.item) {
      return;
    }
    const referenceNode = evt.from.children[evt.oldIndex] || null;
    evt.from.insertBefore(evt.item, referenceNode);
  }

  async function requestJSON(url, method = 'GET', data) {
    const options = {
      method,
      headers: { Accept: 'application/json' },
    };
    if (data !== undefined) {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(data);
    }
    const response = await fetch(url, options);
    const body = await response.json().catch(() => ({}));
    if (!response.ok || body.success === false) {
      throw new Error(body.message || 'Falha na operação.');
    }
    return body;
  }

  async function postJSON(url, data) {
    return requestJSON(url, 'POST', data);
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

  function prepareReminderPreview() {
    $('#reminderModal').on('shown.bs.modal', () => updateReminderPreview(meta.templates || []));
  }

  function updateReminderPreview(templates) {
    const modal = document.getElementById('reminderModal');
    if (!modal) return;
    const dataAttr = modal.dataset.cardInfo;
    const previewEl = document.getElementById('reminderPreview');
    if (!dataAttr) {
      if (previewEl) previewEl.value = '';
      return;
    }
    const cardInfo = JSON.parse(dataAttr);
    const templateSelect = document.getElementById('reminderTemplateSelect');
    const templateKey = templateSelect ? templateSelect.value : null;
    const template = templates.find((tpl) => tpl.key === templateKey) || templates[0];
    if (!template) {
      if (previewEl) previewEl.value = '';
      return;
    }
    const body = template.body
      .replace(/\[NOME\]/g, cardInfo.clientName || '')
      .replace(/\[PROJETO\]/g, cardInfo.projectName || '')
      .replace(/\[VALOR\]/g, cardInfo.amountFormatted || '')
      .replace(/\[DATA\]/g, cardInfo.dueDateFormatted || '')
      .replace(/\[DIAS\]/g, cardInfo.daysOverdue || 0);
    if (previewEl) {
      previewEl.value = body;
    }
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

  if (typeof window !== 'undefined') {
    window.__ARKA_COBRANCA_TESTS__ = {
      createBoardStore,
      determinePriority,
      PRIORITY_LABELS,
    };
  }
})();
