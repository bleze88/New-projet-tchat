<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use App\Support\View;

$until = (string) ($_GET['until'] ?? '');
$reason = (string) ($_GET['reason'] ?? '');

View::render('banned', [
    'pageTitle' => 'Compte banni',
    'until' => $until !== '' ? $until : null,
    'reason' => $reason !== '' ? $reason : null,
]);
