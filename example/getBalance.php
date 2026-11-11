<?php

include_once '../phpDESCOPrepaid.php';
include_once '../config.php';

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

