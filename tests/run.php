#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/UtilsTest.php';
require __DIR__ . '/ClientModelTest.php';
require __DIR__ . '/ProjectModelTest.php';
require __DIR__ . '/PaymentModelTest.php';
require __DIR__ . '/FinancialReserveModelTest.php';

$success = runTests();
exit($success ? 0 : 1);
