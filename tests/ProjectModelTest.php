<?php

registerTest('ProjectModel::getAll retorna apenas projetos ativos ordenados por data', function () {
    $pdo = createMemoryPdo();
    $pdo->exec("
        CREATE TABLE clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT
        );
    ");

    $pdo->exec("
        CREATE TABLE projects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NULL,
            nome_cliente TEXT,
            name TEXT,
            data_entrada TEXT,
            status_pagamento TEXT,
            valor_projeto REAL,
            valor_pendente REAL,
            deleted_at TEXT NULL
        );
    ");

    $pdo->exec("INSERT INTO clients (name) VALUES ('Empresa Alfa'), ('Empresa Beta')");

    $pdo->exec("
        INSERT INTO projects (client_id, nome_cliente, name, data_entrada, status_pagamento, valor_projeto, valor_pendente, deleted_at)
        VALUES
        (1, 'Empresa Alfa', 'Projeto Site', '2024-08-01', 'Pago', 5000, 0, NULL),
        (NULL, 'Cliente Avulso', 'Landing Page', '2024-10-10', 'Pendente', 3200, 3200, NULL),
        (2, 'Empresa Beta', 'Projeto App', '2024-09-15', 'Parcial', 8700, 4200, '2024-10-01')
    ");

    $model = new ProjectModel($pdo);
    $all = $model->getAll();
    expectEquals(2, count($all), 'Projetos deletados devem ser ignorados');

    expectEquals('Landing Page', $all[0]['name'], 'Projeto mais recente deve vir primeiro');
    expectEquals('Cliente Avulso', $all[0]['nome_cliente'], 'Nome de cliente direto deve ser preservado quando não há client_id');

    expectEquals('Empresa Alfa', $all[1]['client_name'], 'Quando há client_id, client_name deve vir da tabela clients');

    $limited = $model->getAll(1, 1);
    expectEquals(1, count($limited), 'A paginação deve respeitar limit');
    expectEquals('Projeto Site', $limited[0]['name'], 'Offset deve trazer o segundo projeto');
});
