<?php

registerTest('ClientModel::create preenche entry_date automaticamente', function () {
    $pdo = createMemoryPdo();
    $pdo->exec("
        CREATE TABLE clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT,
            phone TEXT,
            entry_date TEXT,
            notes TEXT,
            created_at TEXT,
            updated_at TEXT
        )
    ");

    $model = new ClientModel($pdo);
    $result = $model->create([
        'name' => 'Cliente Teste',
        'email' => 'teste@example.com',
        'phone' => '11999999999',
        'notes' => 'Primeiro cliente teste',
    ]);

    expectTrue($result, 'Insert deve retornar true');

    $row = $pdo->query("SELECT entry_date FROM clients LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    expectEquals(date('Y-m-d'), $row['entry_date'], 'entry_date deve assumir a data atual quando não informado');
});

registerTest('ClientModel::update mantém entry_date e notes informados', function () {
    $pdo = createMemoryPdo();
    $pdo->exec("
        CREATE TABLE clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT,
            phone TEXT,
            entry_date TEXT,
            notes TEXT,
            created_at TEXT,
            updated_at TEXT
        )
    ");

    $pdo->exec("
        INSERT INTO clients (name, email, phone, entry_date, notes, created_at, updated_at)
        VALUES ('Cliente Original', 'orig@example.com', '1188888888', '2024-10-01', 'Briefing inicial', datetime('now'), datetime('now'))
    ");

    $model = new ClientModel($pdo);
    $model->update(1, [
        'name' => 'Cliente Atualizado',
        'email' => 'novo@example.com',
        'phone' => '11977777777',
        'entry_date' => '2024-10-05',
        'notes' => 'Briefing atualizado',
    ]);

    $row = $pdo->query("SELECT name, entry_date, notes FROM clients WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
    expectEquals('Cliente Atualizado', $row['name'], 'Nome deveria ser atualizado');
    expectEquals('2024-10-05', $row['entry_date'], 'entry_date deveria refletir o valor enviado no update');
    expectEquals('Briefing atualizado', $row['notes'], 'Notas (briefing) deveriam ser atualizadas');
});

registerTest('ClientModel::getAll aceita ordenação dinâmica', function () {
    $pdo = createMemoryPdo();
    $pdo->exec("
        CREATE TABLE clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT,
            phone TEXT,
            entry_date TEXT,
            notes TEXT,
            created_at TEXT,
            updated_at TEXT
        )
    ");

    $pdo->exec("
        INSERT INTO clients (name, entry_date, created_at, updated_at) VALUES
        ('Beta', '2024-10-02', datetime('now'), datetime('now')),
        ('Alfa', '2024-10-01', datetime('now'), datetime('now')),
        ('Gama', '2024-10-03', datetime('now'), datetime('now'))
    ");

    $model = new ClientModel($pdo);
    $orderedByName = $model->getAll(null, 0, 'name', 'ASC');
    expectEquals('Alfa', $orderedByName[0]['name'], 'Ordem ASC por nome deve iniciar em Alfa');

    $orderedByEntry = $model->getAll(null, 0, 'entry_date', 'DESC');
    expectEquals('2024-10-03', $orderedByEntry[0]['entry_date'], 'Ordem DESC por data deve iniciar na mais recente');
});
