# jTelegramAdmin

Manage WHMCS using Telegram MiniApp and bot commands.


## Features
- [x] Update currency exchange rates.
  - [x] Automatic recurring price updates
  - [x] Automatic product price updates
  - [x] Bot commands
  - [x] MiniApp
- [x] Customizable Telegram API Endpoint (to bypass restricted networks)
- [x] Multi-admin.
- [ ] Receive system notifications.
- [ ] Reports and Statistics


## Requirements
- PHP 8.x
- WHMCS 8.x

## Installation
- Create new Telegram Bot using [@BotFather](https://t.me/BotFather) and keep the bot token code.
- Get your chat ID using bots like https://t.me/myidbot, https://t.me/chatIDrobot.
- Copy `jTelegramAdmin` directory to `<your-whmcs-installation>/modules/addons/jTelegramAdmin`.
- Go to admin panel -> Configuration -> Addon modules, activate `jTelegramAdmin` and set configuration values like telegram bot token and admin chat IDs.
- Go to WHCMS admin panel > addon modules > jTelegramAdmin
- Click on `Set Telegram Webhook` button
- Copy MiniApp URL, go to your bot settings in [@BotFather](https://t.me/BotFather) and use it to enable MiniApp.
