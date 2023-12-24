<?php
namespace ccxt;

// ----------------------------------------------------------------------------

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

// -----------------------------------------------------------------------------
use React\Async;
use React\Promise;
include_once PATH_TO_CCXT . '/test/base/test_ticker.php';

function test_fetch_tickers($exchange, $skipped_properties, $symbol) {
    return Async\async(function () use ($exchange, $skipped_properties, $symbol) {
        $method = 'fetchTickers';
        // log ('fetching all tickers at once...')
        $tickers = null;
        $checked_symbol = null;
        try {
            $tickers = Async\await($exchange->fetch_tickers());
        } catch(\Throwable $e) {
            $tickers = Async\await($exchange->fetch_tickers([$symbol]));
            $checked_symbol = $symbol;
        }
        assert(is_array($tickers), $exchange->id . ' ' . $method . ' ' . $checked_symbol . ' must return an object. ' . $exchange->json($tickers));
        $values = is_array($tickers) ? array_values($tickers) : array();
        for ($i = 0; $i < count($values); $i++) {
            $ticker = $values[$i];
            test_ticker($exchange, $skipped_properties, $method, $ticker, $checked_symbol);
        }
    }) ();
}
