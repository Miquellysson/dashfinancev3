<?php

registerTest('FinancialReserveModel calcula saldo e filtros corretamente', function () {
    $pdo = createMemoryPdo();
    $pdo->exec("
        CREATE TABLE financial_reserve_entries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            operation_type TEXT NOT NULL,
            amount REAL NOT NULL,
            reference_date TEXT NOT NULL,
            description TEXT NULL,
            category TEXT NULL,
            notes TEXT NULL,
            created_by INTEGER NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            deleted_at TEXT NULL
        );
    ");

    $model = new FinancialReserveModel($pdo);

    $pdo->exec("
        INSERT INTO financial_reserve_entries (operation_type, amount, reference_date)
        VALUES
            ('deposit', 1000.00, '2024-10-01'),
            ('withdraw', 300.00, '2024-10-05'),
            ('deposit', 500.00, '2024-09-15')
    ");

    expectEquals(1200.0, $model->getBalance(), 'Saldo deve considerar todos os lançamentos ativos');

    $totals = $model->getTotals();
    expectEquals(1500.0, $totals['deposits'], 'Depósitos precisam somar corretamente');
    expectEquals(300.0, $totals['withdrawals'], 'Retiradas precisam somar corretamente');

    $filtered = $model->paginate(['from' => '2024-10-01', 'type' => 'deposit'], 10, 0);
    expectEquals(1, count($filtered), 'Filtro deve retornar apenas depósitos após a data informada');

    $model->delete($filtered[0]['id']);
    expectEquals(200.0, $model->getBalance(), 'Saldo deve ignorar lançamentos removidos');
});
