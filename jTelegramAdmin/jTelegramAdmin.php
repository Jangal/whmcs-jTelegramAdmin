<?php
if (!defined("WHMCS")) die("This file cannot be accessed directly");

require_once __DIR__ . '/base.php';

function jTelegramAdmin_config()
{
    return [
        "name" => "jTelegramAdmin",
        "description" => "Configure Telegram Bot for currency exchange rate updates.",
        "version" => "1.0",
        "author" => "Jangal",
        "fields" => [
            "telegramEndpoint" => [
                "FriendlyName" => "Telegram Endpoint",
                "Type" => "text",
                "Size" => "256",
                "Description" => "Custom telegram api endpoint when using proxy like https://github.com/TsSaltan/Telegram-bot-api-php-proxy. e.g: https://api.telegram.org",
                "Default" => "https://api.telegram.org",
            ],
            "telegramBotToken" => [
                "FriendlyName" => "Telegram Bot API Key",
                "Type" => "text",
                "Size" => "50",
                "Description" => "Enter your Telegram Bot API Token. (https://t.me/BotFather)",
                "Default" => "",
            ],
            "telegramAdminIds" => [
                "FriendlyName" => "Admin User IDs",
                "Type" => "text",
                "Size" => "50",
                "Description" => "Comma-separated Telegram user IDs allowed to use bot.",
                "Default" => "",
            ],
            "defaultCurrency" => [
                "FriendlyName" => "Default Currency",
                "Type" => "text",
                "Size" => "10",
                "Description" => "Default currency code for /setcur <value> commands.",
                "Default" => "TMN",
            ],
        ],
    ];
}

function jTelegramAdmin_activate()
{

    return [
        'status' => 'success',
        'description' => 'jTelegramAdmin activated',
    ];
}

function jTelegramAdmin_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'jTelegramAdmin deactivated',
    ];
}

function jTelegramAdmin_output($vars)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setTelegramWebhook'])) {
        $webhookurl = jTGAdmin::telegramSetWebhook();
        echo '<div class="alert alert-success">Telegram webhook set to ' . $webhookurl . '</div>';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clearTelegramWebhook'])) {
        $webhookurl = jTGAdmin::telegramClearWebhook();
        echo '<div class="alert alert-success">Telegram webhook cleared</div>';
    }

    // Display the configuration form
    echo '
    <form method="post" action="">
        <button type="submit" name="setTelegramWebhook" class="btn btn-primary">Set telegram webhook</button>
        <button type="submit" name="clearTelegramWebhook" class="btn btn-secondary">Clear telegram webhook</button>
    </form>
    <hr>
    <label>MiniApp URL</label>
    <code>' . jTGAdmin::getTelegramMiniAppUrl() . '</code>
    ';
}

function jTelegramAdmin_clientarea($vars)
{
    header('Location: ' . jTGAdmin::getTelegramMiniAppUrl(), true, 302);
    exit();
}
