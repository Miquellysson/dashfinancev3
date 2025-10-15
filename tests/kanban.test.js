const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');

function loadKanbanScript() {
  const context = {
    window: {
      COBRANCA_KANBAN: {
        board: { columns: {}, summary: {}, filters: {} },
        meta: { columnMeta: {}, templates: [], lostReasons: [] },
        endpoints: {},
      },
    },
    document: {
      addEventListener: () => {},
      getElementById: () => null,
      querySelector: () => null,
      querySelectorAll: () => [],
    },
    console,
    setTimeout,
    clearTimeout,
  };

  context.window.window = context.window;
  context.window.document = context.document;
  context.Sortable = undefined;
  context.window.Sortable = undefined;

  const scriptPath = path.join(__dirname, '..', 'assets/js/cobranca-kanban.js');
  const code = fs.readFileSync(scriptPath, 'utf8');
  vm.createContext(context);
  vm.runInContext(code, context);

  const exposed = context.window.__ARKA_COBRANCA_TESTS__;
  if (!exposed) {
    throw new Error('Componentes de teste do Kanban não foram expostos.');
  }
  return exposed;
}

function testCreateBoardStore(storeFactory) {
  const initial = {
    columns: {
      a_vencer: { cards: [{ id: 1 }], totals: { cards: 1 } },
    },
    summary: {
      total_cards: 1,
    },
    filters: { order: 'due_date' },
  };

  const store = storeFactory(initial);
  const state = store.getState();
  assert.strictEqual(state.summary.total_cards, 1, 'Estado inicial deve ser carregado.');
  assert.ok(state.columns.a_vencer, 'Coluna inicial deve existir.');

  let calls = 0;
  let lastState = null;
  store.subscribe((next) => {
    calls += 1;
    lastState = next;
  });
  assert.strictEqual(calls, 1, 'subscribe deve disparar imediatamente com estado atual.');

  const nextBoard = {
    columns: {
      vencido: { cards: [], totals: { cards: 0 } },
    },
    summary: {
      total_cards: 0,
    },
  };
  store.setBoard(nextBoard);
  assert.strictEqual(store.getState().summary.total_cards, 0, 'Estado deve atualizar após setBoard.');
  assert.ok(lastState.columns.vencido, 'Assinante deve receber nova coluna.');
  assert.strictEqual(calls, 2, 'Assinante deve ser notificado após setBoard.');
}

function testDeterminePriority(determinePriority, labels) {
  assert.strictEqual(determinePriority({ days_overdue: 5 }), 'critica', 'Atrasos devem ser críticos.');
  assert.strictEqual(determinePriority({ days_overdue: 0, days_until_due: 1 }), 'alta', 'Vencimento em 1 dia é alta prioridade.');
  assert.strictEqual(determinePriority({ days_overdue: 0, days_until_due: 3 }), 'media', 'Vencimento em até 3 dias é prioridade média.');
  assert.strictEqual(determinePriority({ days_overdue: 0, days_until_due: 10 }), 'baixa', 'Vencimento distante é prioridade baixa.');
  assert.strictEqual(determinePriority({ days_overdue: 0, days_until_due: null }), 'baixa', 'Sem data deve ser baixa por padrão.');

  assert.strictEqual(labels.critica, 'Crítica', 'Rótulo de prioridade crítica deve existir.');
  assert.strictEqual(labels.baixa, 'Baixa', 'Rótulo de prioridade baixa deve existir.');
}

function run() {
  const { createBoardStore, determinePriority, PRIORITY_LABELS } = loadKanbanScript();
  testCreateBoardStore(createBoardStore);
  testDeterminePriority(determinePriority, PRIORITY_LABELS);
  console.log('✔ Testes do Kanban executados com sucesso.');
}

if (require.main === module) {
  run();
}
