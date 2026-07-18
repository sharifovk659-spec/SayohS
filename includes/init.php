<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/translations.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/favorites.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/catalog.php'; // fallback only

handle_lang_switch();

$app = app_config();
