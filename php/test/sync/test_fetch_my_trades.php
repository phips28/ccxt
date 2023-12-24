<?php
namespace ccxt;

// ----------------------------------------------------------------------------

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

// -----------------------------------------------------------------------------
include_once PATH_TO_CCXT . '/test/base/test_trade.php';

function test_fetch_my_trades($exchange, $skipped_properties, $symbol) {
    $method = 'fetchMyTrades';
    $trades = $exchange->fetch_my_trades($symbol);
    assert(gettype($trades) === 'array' && array_keys($trades) === array_keys(array_keys($trades)), $exchange->id . ' ' . $method . ' ' . $symbol . ' must return an array. ' . $exchange->json($trades));
    $now = $exchange->milliseconds();
    for ($i = 0; $i < count($trades); $i++) {
        test_trade($exchange, $skipped_properties, $method, $trades[$i], $symbol, $now);
    }
    assert_timestamp_order($exchange, $method, $symbol, $trades);
}
