# DESCO Prepaid API PHP Wrapper

A lightweight PHP wrapper to authenticate with the new DESCO (Dhaka Electric Supply Company) Prepaid API and fetch linked account details and real-time meter balances.

## Features

* **Authentication:** Seamlessly authenticates with DESCO's new prepaid portal API.
* **Account Discovery:** Fetches all prepaid accounts and meter numbers linked to your profile.
* **Balance & Usage Tracking:** Retrieves current BDT balance, current month consumption, and snapshot timestamps.
* **Standardized Responses:** Wraps upstream API responses into a consistent PHP `stdClass` object format.

## Requirements

* PHP 8.2 or higher
* `JSON` extension enabled (`ext-json`)

## Project Structure

```text
├── phpDESCOPrepaid.php      # Core API wrapper class
├── config.example.php       # Credentials configuration Example
├── README.md                # Readme
└── example/
    └── getBalance.php       # Usage example script
```

## Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/RepulsiveCoder/desco-prepaid-api
   cd desco-prepaid-php

   cd example
   php getBalance.php
   ```

2. **Configure your credentials:**
   Open `config.example.php`, rename it to `config.php` and add your DESCO Prepaid portal username and your **Base64-encoded** password.

   ```php
   <?php

   $username = 'your_username'; // Your DESCO portal username
   $password = 'eW91cl9wYXNzd29yZA=='; // Your Base64-encoded password
   ```

   > **Tip:** You can generate your Base64-encoded password via CLI using:  
   > `echo -n 'your_password' | base64`  
   > `[Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes('your_password'))`  // powershell

## Usage Example

Run the included example script (`example/getBalance.php`) to authenticate and list balances for all linked meters:

```php
<?php

include_once 'phpDESCOPrepaid.php';
include_once 'config.php';

// Initialize the client
$desco = new DescoPrepaid('https://prepaid.desco.org.bd/api/', $username, $password);

// Authenticate
$auth = $desco->auth();
if ($auth) {
    echo "Authentication successful.\n";
} else {
    echo "Authentication failed.\n";
    return;
}

// Fetch linked accounts
$accountOutput = $desco->callApi('accounts');

if (!empty($accountOutput) && $accountOutput->success) {
    if (!empty($accountOutput->result)) {
        $accounts = $accountOutput->result->accounts;
        foreach ($accounts as $account) {
            echo "Account ID: " . $account->accountId . "\n";

            // Fetch balance for each account
            $endpoint = str_replace('$1', $account->accountId, $desco->urls->balance);
            $balanceOutput = $desco->callApi($endpoint);

            if (!empty($balanceOutput) && $balanceOutput->success && !empty($balanceOutput->result)) {
                echo "Balance: " . $balanceOutput->result->balance . "\n";
            }
        }
    }
}
```

## API Response Format

Every call made via `$desco->callApi()` returns a standardized PHP object:

```php
(object) [
    'success' => 'true', // Status indicator
    'result'  => $result // Decoded JSON object from the upstream DESCO API
]
```

### Example `$result` Payloads

#### 1. Accounts Endpoint (`accounts`)
```json
{
    "accounts": [
        {
            "accountId": xxxxx,
            "sourceSystem": "UNIFIED",
            "accountNo": "xxxxxxxx",
            "meterNo": "xxxxxxxxxxxx",
            "status": "ACTIVE",
            "linkStatus": "ACTIVE"
        },
        {
            "accountId": xxxxx,
            "sourceSystem": "TKDES",
            "accountNo": "xxxxxxxx",
            "meterNo": "xxxxxxxxxxxx",
            "status": "ACTIVE",
            "linkStatus": "ACTIVE"
        },
        {
            "accountId": xxxxx,
            "sourceSystem": "xxxxx",
            "accountNo": "xxxxxxxx",
            "meterNo": "xxxxxxxxxxxx",
            "status": "ACTIVE",
            "linkStatus": "ACTIVE"
        }
    ],
    "linkedAccountCount": 3,
    "linkedAccountLimit": 20,
    "remainingAccountSlots": 17,
    "canAddAccount": true,
    "isAboveCurrentLimit": false
}
```

#### 2. Balance Endpoint
```json
// $desco->urls->balance (/accounts/$1/balance), need to replace $1 with accountId
// e.g. $endpoint = str_replace('$1', $account->accountId, $desco->urls->balance);
{
    "accountNo": "xxxxxxxx",
    "meterNo": "xxxxxxxxxxxx",
    "balance": xxxx.xx,
    "meterType": "TKDES",
    "currency": "BDT",
    "balanceSnapshotAt": "2026-11-10T00:00+06:00",
    "timestampSource": "UPSTREAM",
    "timestampAvailable": true,
    "stale": false,
    "consumerMessages": [
        {
            "key": "balance.daily-snapshot",
            "version": 0,
            "placement": "INLINE_INFORMATION",
            "severity": "INFORMATION",
            "title": "Balance update information",
            "body": "This is a daily balance snapshot, not a real-time balance. A recharge or token applied to the meter after the daily balance was recorded will normally be reflected in the next available daily balance update.\n\nA successful recharge may appear in Recharge history before it is included in the displayed balance.",
            "banglaTitle": "ব্যালেন্স হালনাগাদ সম্পর্কিত তথ্য",
            "banglaBody": "এটি রিয়েল-টাইম ব্যালেন্স নয়; এটি একটি দৈনিক ব্যালেন্স স্ন্যাপশট। দৈনিক ব্যালেন্স রেকর্ড হওয়ার পরে রিচার্জ করলে বা মিটারে টোকেন প্রয়োগ করলে, তা সাধারণত পরবর্তী উপলভ্য দৈনিক ব্যালেন্স আপডেটে দেখা যাবে।\n\nসফল রিচার্জটি প্রদর্শিত ব্যালেন্সে যুক্ত হওয়ার আগেই Recharge history-তে দেখা যেতে পারে।",
            "meterType": "TKDES"
        }
    ],
    "currentMonthConsumedTaka": xxx.x,
    "readingTime": "2026-11-10 00:00:00"
}

// The field consumerMessages will be omitted by default as configured in the Main Class; 
// $unsets = ['consumerMessages', 'accountId']
```

#### 3. Customer Info Endpoint
```json
// $desco->urls->info (/accounts/$1/summary), need to replace $1 with accountId
// e.g. $endpoint = str_replace('$1', $account->accountId, $desco->urls->info);
{
    "accountId": xxxxx,
    "accountNo": "xxxxxxxx",
    "meterNo": "xxxxxxxxxxxx",
    "customerName": "xx xxxxxxx xxxxx xxxxxxx",
    "installationAddress": "x/x#xxx/x,xxxxxx,xxxxxx,xxxxx",
    "feederName": "xxxxx xxxxxx (xx)",
    "sdName": "xxxxxxxxxx",
    "phaseType": "xxxxxx xxxxx xxxxx",
    "sanctionLoad": "x",
    "tariffSolution": "Category-A: Residential",
    "registerDate": "2020-08-11 00:00:00",
    "installationDate": "2020-08-11 00:00:00"
}
// The field accountId will be omitted by default as configured in the Main Class; 
// $unsets = ['consumerMessages', 'accountId']
```


## Disclaimer

This is an unofficial wrapper built for personal and educational use. It is not affiliated with, endorsed, or supported by Dhaka Electric Supply Company Limited (DESCO).

## License

This project is open-source and available under the [MIT License](LICENSE).
