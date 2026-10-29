<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/src/bootstrap.php';
$id = max(0, (int) ($_GET['id'] ?? 0));
redirect('../payments/remit.php?id=' . $id);
