<?php
if (!defined("WHMCS")) die("This file cannot be accessed directly");

use WHMCS\Database\Capsule;


class jTGAdmin
{
    public static function getSetting($name, $default = null)
    {
        $setting = Capsule::table('tbladdonmodules')
            ->where('module', 'jTelegramAdmin')
            ->where('setting', $name)
            ->first();
        if (!$setting) return $default;
        return $setting->value;
    }

    public static function isTelegramAdmin($userId)
    {
        $adminIds = explode(',', self::getSetting('telegramAdminIds', ''));
        return in_array($userId, $adminIds);
    }

    public static function telegramBotHandler($input, $botToken)
    {
        $message = $input['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'];
        $userId = $message['from']['id'];

        if (!self::isTelegramAdmin($userId)) {
            exit;
        }

        if ($text === '/getcur') {
            $currencies = Capsule::table('tblcurrencies')->get();
            $response = "Currency Exchange Rates:\n";
            foreach ($currencies as $currency) {
                $response .= $currency->code . " - " . $currency->rate . "\n";
            }
            self::telegramSend($botToken, $chatId, $response);
        }

        if (preg_match('/^\/setcur ([A-Z]{3}) ([0-9]+\.?[0-9]*)$/', $text, $matches)) {
            $currencyCode = $matches[1];
            $exchangeRate = (float) $matches[2];
        } elseif (preg_match('/^\/setcur ([0-9]+\.?[0-9]*)$/', $text, $matches)) {
            $currencyCode = '';
            $exchangeRate = (float) $matches[1];
        } else {
            return;
        }

        if (self::submitExchangeRate($currencyCode, $exchangeRate)) {
            self::telegramSend(
                $botToken,
                $chatId,
                "Exchange rate for $currencyCode updated to $exchangeRate."
            );
        }
    }

    public static function telegramMiniAppHandler($initData, $formData, $botToken)
    {
        if (!self::telegramVerifyInitData($initData, $botToken)) {
            return -1;
        }
        // Parse the initData to extract user information
        $params = [];
        parse_str($initData, $params);
        $user = json_decode($params['user'], true);
        $userId = $user['id'] ?? null;

        if (!self::isTelegramAdmin($userId)) {
            return [
                'error' => 'Access denied!'
            ];
        }

        $action = $formData['action'];
        if ($action === 'updateCurrency') {
            if (self::submitExchangeRate(
                $formData['currencyCode'],
                $formData['exchangeRate']
            )) {
                return [
                    'message' => 'Configuration updated!'
                ];
            } else {
                return [
                    'error' => 'Configuration update failed!'
                ];
            }
        }
        if ($action === 'getCurrencies') {
            $currencies = Capsule::table('tblcurrencies')->get();
            $response = [];
            foreach ($currencies as $currency) {
                $response[] = [
                    'code' => $currency->code,
                    'rate' => $currency->rate,
                    'isBase' => $currency->default === 1,
                ];
            }
            return $response;
        }
        return ['error' => 'Invalid action'];
    }

    public static function getTelegramMiniAppUrl()
    {
        return self::getClientAreaLoginUrl() . '&tgadminminiapp=1';
    }

    public static function telegramWebhook()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $botToken = self::getSetting('telegramBotToken');

        if ($input && isset($input['message']) && isset($_GET['tgadminbottoken'])) {
            if ($_GET['tgadminbottoken'] != $botToken) {
                die('invalid code');
            }
            return self::telegramBotHandler($input, $botToken);
        }

        if ($input && isset($input['initData']) && isset($input['formData'])) {
            $initData = $input['initData'];
            $formData = $input['formData'];
            $response = self::telegramMiniAppHandler($initData, $formData, $botToken);
            header('Content-type: application/json');
            echo json_encode($response);
            exit();
        }

        if (isset($_GET['tgadminminiapp'])) {
            include_once __DIR__ . '/miniapp.php';
            exit();
        }

        return -1;
    }

    public static function submitExchangeRate($currencyCode, $exchangeRate)
    {
        if ($currencyCode === '') {
            $currencyCode = self::getSetting('defaultCurrency', 'TMN');
        }
        $currencyId = self::getCurrencyIdByCode($currencyCode);
        if ($currencyId !== null) {
            self::updateExchangeRate($currencyId, $exchangeRate);
            self::updatePricing($currencyId);
            self::updateRecurringPrices($currencyId);
            return true;
        }
    }

    public static function getTelegramEndpoint()
    {
        return self::getSetting('telegramEndpoint', 'https://api.telegram.org');
    }

    public static function telegramSend($botToken, $chatId, $message)
    {
        file_get_contents(
            self::getTelegramEndpoint() .
                "/bot$botToken/sendMessage?chat_id=$chatId&text="
                . urlencode($message)
        );
    }

    public static function telegramVerifyInitData($initData, $botToken)
    {
        $initDataArr = explode('&', rawurldecode($initData));
        $needle = 'hash=';
        $checkHash = FALSE;
        foreach ($initDataArr as &$val) {
            if (substr($val, 0, strlen($needle)) === $needle) {
                $checkHash = substr_replace($val, '', 0, strlen($needle));
                $val = NULL;
            }
        }

        $initDataArr = array_filter($initDataArr);
        sort($initDataArr);

        $initData = implode("\n", $initDataArr);
        $secretKey = hash_hmac('sha256', $botToken, "WebAppData", true);
        $hash = bin2hex(hash_hmac('sha256', $initData, $secretKey, true));
        return strcmp($hash, $checkHash) === 0;
    }


    public static function getClientAreaUrl()
    {
        global $CONFIG;
        return rtrim($CONFIG['SystemURL'], '/') . '/index.php';
    }

    public static function getClientAreaLoginUrl()
    {
        return self::getClientAreaUrl() . '?rp=/login';
    }

    public static function telegramSetWebhook()
    {
        $botToken = self::getSetting('telegramBotToken');
        $webhookUrl = self::getClientAreaLoginUrl() . '&tgadminbottoken=' . $botToken;
        $webhookUrl = urlencode($webhookUrl);
        $r = file_get_contents(
            self::getTelegramEndpoint() .
                "/bot$botToken/setWebhook?url=$webhookUrl"
        );
        return $webhookUrl;
    }

    public static function telegramClearWebhook()
    {
        $botToken = self::getSetting('telegramBotToken');
        return file_get_contents(
            self::getTelegramEndpoint() . "/bot$botToken/setWebhook?url="
        );
    }

    public static function getCurrencyIdByCode($currencyCode)
    {
        $currency = Capsule::table('tblcurrencies')
            ->where('code', $currencyCode)
            ->first();
        if (!$currency) return null;
        return $currency->id;
    }

    public static function updateExchangeRate($id, $rate)
    {
        Capsule::table('tblcurrencies')->where('id', $id)->update(['rate' => $rate]);
    }

    public static function updatePricing($currencyid = "")
    {
        // Fetch the base currency ID
        $baseCurrency = Capsule::table('tblcurrencies')
            ->where('default', '=', 1)
            ->first();

        if (!$baseCurrency) {
            return; // No base currency found
        }

        $baseCurrencyId = $baseCurrency->id;

        // Prepare the query to fetch non-base currencies
        $query = Capsule::table('tblcurrencies')->where('id', '!=', $baseCurrencyId);
        if ($currencyid) {
            $query->where('id', '=', $currencyid);
        }

        $currencies = $query->pluck('rate', 'id'); // Get currencies as [id => rate]

        // Fetch pricing data for the base currency
        $basePricing = Capsule::table('tblpricing')
            ->where('currency', '=', $baseCurrencyId)
            ->get();

        foreach ($basePricing as $data) {
            $type = $data->type;
            $relid = $data->relid;
            $msetupfee = $data->msetupfee;
            $qsetupfee = $data->qsetupfee;
            $ssetupfee = $data->ssetupfee;
            $asetupfee = $data->asetupfee;
            $bsetupfee = $data->bsetupfee;
            $tsetupfee = $data->tsetupfee;
            $monthly = $data->monthly;
            $quarterly = $data->quarterly;
            $semiannually = $data->semiannually;
            $annually = $data->annually;
            $biennially = $data->biennially;
            $triennially = $data->triennially;

            // Determine if this is a domain-related pricing type
            $isDomainType = in_array($type, ['domainregister', 'domaintransfer', 'domainrenew']);
            $allowNegativePrices = $type === 'configoptions';

            // Loop through each currency and update pricing
            foreach ($currencies as $currencyId => $rate) {
                if ($rate <= 0) {
                    continue; // Skip invalid rates
                }

                // Check if pricing already exists for this currency
                $criteria = ['type' => $type, 'currency' => $currencyId, 'relid' => $relid];
                if ($isDomainType) {
                    $criteria['tsetupfee'] = $tsetupfee;
                }

                $existingPricing = Capsule::table('tblpricing')->where($criteria)->first();

                // Calculate new pricing values
                $updateData = [];
                $updateData['msetupfee'] = self::calcPrice($msetupfee, $rate, $allowNegativePrices);
                $updateData['qsetupfee'] = self::calcPrice($qsetupfee, $rate, $allowNegativePrices);
                $updateData['ssetupfee'] = self::calcPrice($ssetupfee, $rate, $allowNegativePrices);
                $updateData['asetupfee'] = self::calcPrice($asetupfee, $rate, $allowNegativePrices);
                $updateData['bsetupfee'] = self::calcPrice($bsetupfee, $rate, $allowNegativePrices);

                if ($isDomainType) {
                    $updateData['tsetupfee'] = $tsetupfee; // Domain types retain the same tsetupfee
                } else {
                    $updateData['tsetupfee'] = self::calcPrice($tsetupfee, $rate, $allowNegativePrices);
                }

                $updateData['monthly'] = self::calcPrice($monthly, $rate, $allowNegativePrices);
                $updateData['quarterly'] = self::calcPrice($quarterly, $rate, $allowNegativePrices);
                $updateData['semiannually'] = self::calcPrice($semiannually, $rate, $allowNegativePrices);
                $updateData['annually'] = self::calcPrice($annually, $rate, $allowNegativePrices);
                $updateData['biennially'] = self::calcPrice($biennially, $rate, $allowNegativePrices);
                $updateData['triennially'] = self::calcPrice($triennially, $rate, $allowNegativePrices);

                // Insert or update the pricing record
                if ($existingPricing) {
                    Capsule::table('tblpricing')->where('id', $existingPricing->id)->update($updateData);
                } else {
                    $updateData['type'] = $type;
                    $updateData['currency'] = $currencyId;
                    $updateData['relid'] = $relid;
                    Capsule::table('tblpricing')->insert($updateData);
                }
            }
        }
    }

    public static function calcPrice($price, $rate, $allowNegativePrices)
    {
        if ($allowNegativePrices || $price > 0) {
            return round($price * $rate, 2);
        }
        return $price;
    }

    public static function updateRecurringPrices($currId)
    {
        $adminUsername = '';
        foreach (Capsule::table('tblclients')->where('currency', '=', $currId)->pluck('id') as $userid) {
            foreach (Capsule::table('tblhosting')->where('userid', '=', $userid)->pluck('id') as $serviceId) {
                localAPI('UpdateClientProduct', array('serviceid' => $serviceId, 'autorecalc' => true), $adminUsername);
            }
            foreach (Capsule::table('tblhostingaddons')->where('userid', '=', $userid)->pluck('id') as $serviceAddonId) {
                localAPI('UpdateClientAddon', array('id' => $serviceAddonId, 'autorecalc' => true), $adminUsername);
            }
            foreach (Capsule::table('tbldomains')->where('userid', '=', $userid)->pluck('id') as $domainId) {
                localAPI('UpdateClientDomain', array('domainid' => $domainId, 'autorecalc' => true), $adminUsername);
            }
        }
        return 'Update Completed';
    }
}
