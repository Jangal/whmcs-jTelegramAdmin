<?php
if (!defined("WHMCS")) die("This file cannot be accessed directly");

require_once __DIR__ . '/base.php';

add_hook('ClientAreaPageLogin', 1, function ($vars) {
    if (jTGAdmin::telegramWebhook() !== -1) die('done');
});
