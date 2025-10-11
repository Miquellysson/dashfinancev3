<?php

declare(strict_types=1);

$GLOBALS['__registeredTests'] = [];

function registerTest(string $description, callable $callback): void {
    $GLOBALS['__registeredTests'][] = [$description, $callback];
}

function runTests(): bool {
    $allPassed = true;
    $total = count($GLOBALS['__registeredTests']);
    $index = 1;

    foreach ($GLOBALS['__registeredTests'] as [$description, $callback]) {
        try {
            $callback();
            echo "[PASS] {$index}/{$total} {$description}\n";
        } catch (Throwable $e) {
            $allPassed = false;
            echo "[FAIL] {$index}/{$total} {$description}\n";
            echo "       " . $e->getMessage() . "\n";
        }
        $index++;
    }

    echo $allPassed ? "✔ Todos os testes passaram.\n" : "✖ Ocorreram falhas.\n";
    return $allPassed;
}

function expectTrue(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expectEquals($expected, $actual, string $message): void {
    if ($expected !== $actual) {
        $expectedStr = var_export($expected, true);
        $actualStr = var_export($actual, true);
        throw new RuntimeException("{$message} (esperado {$expectedStr}, obtido {$actualStr})");
    }
}

function createMemoryPdo(): PDO {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->sqliteCreateFunction('now', fn() => date('Y-m-d H:i:s'));
    $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));
    $pdo->sqliteCreateFunction('coalesce', fn(...$values) => collectCoalesce($values));
    return $pdo;
}

function collectCoalesce(array $values) {
    foreach ($values as $value) {
        if ($value !== null) {
            return $value;
        }
    }
    return null;
}

require_once __DIR__ . '/../app/Helpers/Utils.php';
require_once __DIR__ . '/../app/Models/ClientModel.php';
require_once __DIR__ . '/../app/Models/ProjectModel.php';
require_once __DIR__ . '/../app/Models/PaymentModel.php';
