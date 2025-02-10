# jTelegramAdmin

Manage WHMCS using Telegram MiniApp and bot commands.


## Features
- [x] Update currency exchange rates.
  - [x] Automatic recurring price updates
  - [x] Automatic product price updates
  - [x] Bot commands
  - [x] MiniApp
- [x] Customizable Telegram API Endpoint (to [bypass](https://github.com/TsSaltan/Telegram-bot-api-php-proxy) restricted networks)
- [x] Multi-admin.
- [ ] Receive system notifications.
- [ ] Reports and Statistics


## Requirements
- PHP 8.x
- WHMCS 8.x
- HTTPS Enabled webserver

## Installation
- Create new Telegram Bot using [@BotFather](https://t.me/BotFather) and keep the bot token code.
- Get your chat ID using bots like https://t.me/myidbot, https://t.me/chatIDrobot.
- Copy `jTelegramAdmin` directory to `<your-whmcs-installation>/modules/addons/jTelegramAdmin`.
- Go to admin panel -> Configuration -> Addon modules, activate `jTelegramAdmin` and set configuration values like telegram bot token and admin chat IDs.
- Go to WHCMS admin panel > addon modules > jTelegramAdmin
- Click on `Set Telegram Webhook` button
- Copy MiniApp URL, go to your bot settings in [@BotFather](https://t.me/BotFather) and use it to enable MiniApp.

## Usage

### MiniApp
Go Telegram and use `Open App` button on Bot's profile.

![image](https://github.com/user-attachments/assets/62c1f2ed-a01b-4d3d-bfa4-68d597fb4674)

### Bot commands
```
/getcur - Get currencies list
/setcur <exchangeRate> - Set default currency exchange rate
/setcur <currencyCode> <exchangeRate> - Set specific currency exchange rate
```


