<?php

registerTest('PaymentModel::normalizeData converte valores e moeda', function () {
    $pdo = createMemoryPdo();
    $pdo->exec("CREATE TABLE status_catalog (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, color_hex TEXT, sort_order INT, created_at TEXT)");
    $pdo->exec("CREATE TABLE payments (id INTEGER PRIMARY KEY AUTOINCREMENT, project_id INT, kind TEXT, amount REAL, currency TEXT, transaction_type TEXT, description TEXT, category TEXT, due_date TEXT, paid_at TEXT, status_id INT, created_at TEXT, updated_at TEXT)");
    $pdo->exec("CREATE TABLE projects (id INTEGER PRIMARY KEY AUTOINCREMENT)");

    $model = new PaymentModel($pdo);
    $model->create([
        'project_id' => 1,
        'amount' => 'US$ 1,234.56',
        'currency' => 'USD',
        'kind' => 'recurring',
        'transaction_type' => 'receita',
        'status' => 'paid',
        'date' => '2024-10-10',
    ]);

    $row = $pdo->query("SELECT amount, currency, kind, transaction_type FROM payments LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    expectEquals(1234.56, (float)$row['amount'], 'Valor deve ser convertido para decimal');
    expectEquals('USD', $row['currency'], 'Moeda deve ser preservada');
    expectEquals('recurring', $row['kind'], 'Tipo deve ser mantido');
    expectEquals('receita', $row['transaction_type'], 'Tipo de transação deve ser armazenado');
});
