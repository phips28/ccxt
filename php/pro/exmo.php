<?php

namespace ccxt\pro;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import
use ccxt\NotSupported;
use React\Async;

class exmo extends \ccxt\async\exmo {

    public function describe() {
        return $this->deep_extend(parent::describe(), array(
            'has' => array(
                'ws' => true,
                'watchBalance' => true,
                'watchTicker' => true,
                'watchTickers' => false,
                'watchTrades' => true,
                'watchMyTrades' => true,
                'watchOrders' => false, // TODO
                'watchOrderBook' => true,
                'watchOHLCV' => false,
            ),
            'urls' => array(
                'api' => array(
                    'ws' => array(
                        'public' => 'wss://ws-api.exmo.com:443/v1/public',
                        'spot' => 'wss://ws-api.exmo.com:443/v1/private',
                        'margin' => 'wss://ws-api.exmo.com:443/v1/margin/private',
                    ),
                ),
            ),
            'options' => array(
            ),
            'streaming' => array(
            ),
            'exceptions' => array(
            ),
        ));
    }

    public function request_id() {
        $requestId = $this->sum($this->safe_integer($this->options, 'requestId', 0), 1);
        $this->options['requestId'] = $requestId;
        return $requestId;
    }

    public function watch_balance($params = array ()) {
        return Async\async(function () use ($params) {
            /**
             * watch balance and get the amount of funds available for trading or funds locked in orders
             * @param {array} [$params] extra parameters specific to the exmo api endpoint
             * @return {array} a ~@link https://docs.ccxt.com/#/?id=balance-structure balance structure~
             */
            Async\await($this->authenticate($params));
            list($type, $query) = $this->handle_market_type_and_params('watchBalance', null, $params);
            $messageHash = 'balance:' . $type;
            $url = $this->urls['api']['ws'][$type];
            $subscribe = array(
                'method' => 'subscribe',
                'topics' => array( $type . '/wallet' ),
                'id' => $this->request_id(),
            );
            $request = $this->deep_extend($subscribe, $query);
            return Async\await($this->watch($url, $messageHash, $request, $messageHash, $request));
        }) ();
    }

    public function handle_balance(Client $client, $message) {
        //
        //  spot
        //     {
        //         "ts" => 1654208766007,
        //         "event" => "snapshot",
        //         "topic" => "spot/wallet",
        //         "data" => {
        //             "balances" => array(
        //                 "ADA" => "0",
        //                 "ALGO" => "0",
        //                 ...
        //             ),
        //             "reserved" => {
        //                 "ADA" => "0",
        //                 "ALGO" => "0",
        //                 ...
        //             }
        //         }
        //     }
        //
        //  margin
        //     {
        //         "ts" => 1624370076651,
        //         "event" => "snapshot",
        //         "topic" => "margin/wallets",
        //         "data" => {
        //             "RUB" => array(
        //                 "balance" => "1000000",
        //                 "used" => "0",
        //                 "free" => "1000000"
        //             ),
        //             "USD" => {
        //                 "balance" => "1000000",
        //                 "used" => "1831.925",
        //                 "free" => "998168.075"
        //             }
        //         }
        //     }
        //     {
        //         "ts" => 1624370185720,
        //         "event" => "update",
        //         "topic" => "margin/wallets",
        //         "data" => {
        //             "USD" => {
        //                 "balance" => "1000123",
        //                 "used" => "1831.925",
        //                 "free" => "998291.075"
        //             }
        //         }
        //     }
        //
        $topic = $this->safe_string($message, 'topic');
        $parts = explode('/', $topic);
        $type = $this->safe_string($parts, 0);
        if ($type === 'spot') {
            $this->parse_spot_balance($message);
        } elseif ($type === 'margin') {
            $this->parse_margin_balance($message);
        }
        $messageHash = 'balance:' . $type;
        $client->resolve ($this->balance, $messageHash);
    }

    public function parse_spot_balance($message) {
        //
        //     {
        //         "balances" => array(
        //             "BTC" => "3",
        //             "USD" => "1000",
        //             "RUB" => "0"
        //         ),
        //         "reserved" => {
        //             "BTC" => "0.5",
        //             "DASH" => "0",
        //             "RUB" => "0"
        //         }
        //     }
        //
        $event = $this->safe_string($message, 'event');
        $data = $this->safe_value($message, 'data');
        $this->balance['info'] = $data;
        if ($event === 'snapshot') {
            $balances = $this->safe_value($data, 'balances', array());
            $reserved = $this->safe_value($data, 'reserved', array());
            $currencies = is_array($balances) ? array_keys($balances) : array();
            for ($i = 0; $i < count($currencies); $i++) {
                $currencyId = $currencies[$i];
                $code = $this->safe_currency_code($currencyId);
                $account = $this->account();
                $account['free'] = $this->safe_string($balances, $currencyId);
                $account['used'] = $this->safe_string($reserved, $currencyId);
                $this->balance[$code] = $account;
            }
        } elseif ($event === 'update') {
            $currencyId = $this->safe_string($data, 'currency');
            $code = $this->safe_currency_code($currencyId);
            $account = $this->account();
            $account['free'] = $this->safe_string($data, 'balance');
            $account['used'] = $this->safe_string($data, 'reserved');
            $this->balance[$code] = $account;
        }
        $this->balance = $this->safe_balance($this->balance);
    }

    public function parse_margin_balance($message) {
        //
        //     {
        //         "RUB" => array(
        //             "balance" => "1000000",
        //             "used" => "0",
        //             "free" => "1000000"
        //         ),
        //         "USD" => {
        //             "balance" => "1000000",
        //             "used" => "1831.925",
        //             "free" => "998168.075"
        //         }
        //     }
        //
        $data = $this->safe_value($message, 'data');
        $this->balance['info'] = $data;
        $currencies = is_array($data) ? array_keys($data) : array();
        for ($i = 0; $i < count($currencies); $i++) {
            $currencyId = $currencies[$i];
            $code = $this->safe_currency_code($currencyId);
            $wallet = $this->safe_value($data, $currencyId);
            $account = $this->account();
            $account['free'] = $this->safe_string($wallet, 'free');
            $account['used'] = $this->safe_string($wallet, 'used');
            $account['total'] = $this->safe_string($wallet, 'balance');
            $this->balance[$code] = $account;
            $this->balance = $this->safe_balance($this->balance);
        }
    }

    public function watch_ticker(string $symbol, $params = array ()) {
        return Async\async(function () use ($symbol, $params) {
            /**
             * watches a price ticker, a statistical calculation with the information calculated over the past 24 hours for a specific $market
             * @param {string} $symbol unified $symbol of the $market to fetch the ticker for
             * @param {array} [$params] extra parameters specific to the exmo api endpoint
             * @return {array} a ~@link https://docs.ccxt.com/#/?id=ticker-structure ticker structure~
             */
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $symbol = $market['symbol'];
            $url = $this->urls['api']['ws']['public'];
            $messageHash = 'ticker:' . $symbol;
            $message = array(
                'method' => 'subscribe',
                'topics' => [
                    'spot/ticker:' . $market['id'],
                ],
                'id' => $this->request_id(),
            );
            $request = $this->deep_extend($message, $params);
            return Async\await($this->watch($url, $messageHash, $request, $messageHash, $request));
        }) ();
    }

    public function handle_ticker(Client $client, $message) {
        //
        //  spot
        //      {
        //          "ts" => 1654205085473,
        //          "event" => "update",
        //          "topic" => "spot/ticker:BTC_USDT",
        //          "data" => {
        //              "buy_price" => "30285.84",
        //              "sell_price" => "30299.97",
        //              "last_trade" => "30295.01",
        //              "high" => "30386.7",
        //              "low" => "29542.76",
        //              "avg" => "29974.16178449",
        //              "vol" => "118.79538518",
        //              "vol_curr" => "3598907.38200826",
        //              "updated" => 1654205084
        //          }
        //      }
        //
        $topic = $this->safe_string($message, 'topic');
        $topicParts = explode(':', $topic);
        $marketId = $this->safe_string($topicParts, 1);
        $symbol = $this->safe_symbol($marketId);
        $ticker = $this->safe_value($message, 'data', array());
        $market = $this->safe_market($marketId);
        $parsedTicker = $this->parse_ticker($ticker, $market);
        $messageHash = 'ticker:' . $symbol;
        $this->tickers[$symbol] = $parsedTicker;
        $client->resolve ($parsedTicker, $messageHash);
    }

    public function watch_trades(string $symbol, ?int $since = null, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $since, $limit, $params) {
            /**
             * get the list of most recent $trades for a particular $symbol
             * @param {string} $symbol unified $symbol of the $market to fetch $trades for
             * @param {int} [$since] timestamp in ms of the earliest trade to fetch
             * @param {int} [$limit] the maximum amount of $trades to fetch
             * @param {array} [$params] extra parameters specific to the exmo api endpoint
             * @return {array[]} a list of ~@link https://docs.ccxt.com/#/?id=public-$trades trade structures~
             */
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $symbol = $market['symbol'];
            $url = $this->urls['api']['ws']['public'];
            $messageHash = 'trades:' . $symbol;
            $message = array(
                'method' => 'subscribe',
                'topics' => [
                    'spot/trades:' . $market['id'],
                ],
                'id' => $this->request_id(),
            );
            $request = $this->deep_extend($message, $params);
            $trades = Async\await($this->watch($url, $messageHash, $request, $messageHash, $request));
            return $this->filter_by_since_limit($trades, $since, $limit, 'timestamp', true);
        }) ();
    }

    public function handle_trades(Client $client, $message) {
        //
        //      {
        //          "ts" => 1654206084001,
        //          "event" => "update",
        //          "topic" => "spot/trades:BTC_USDT",
        //          "data" => [array(
        //              "trade_id" => 389704729,
        //              "type" => "sell",
        //              "price" => "30310.95",
        //              "quantity" => "0.0197",
        //              "amount" => "597.125715",
        //              "date" => 1654206083
        //          )]
        //      }
        //
        $topic = $this->safe_string($message, 'topic');
        $parts = explode(':', $topic);
        $marketId = $this->safe_string($parts, 1);
        $symbol = $this->safe_symbol($marketId);
        $market = $this->safe_market($marketId);
        $trades = $this->safe_value($message, 'data', array());
        $messageHash = 'trades:' . $symbol;
        $stored = $this->safe_value($this->trades, $symbol);
        if ($stored === null) {
            $limit = $this->safe_integer($this->options, 'tradesLimit', 1000);
            $stored = new ArrayCache ($limit);
            $this->trades[$symbol] = $stored;
        }
        for ($i = 0; $i < count($trades); $i++) {
            $trade = $trades[$i];
            $parsed = $this->parse_trade($trade, $market);
            $stored->append ($parsed);
        }
        $this->trades[$symbol] = $stored;
        $client->resolve ($this->trades[$symbol], $messageHash);
    }

    public function watch_my_trades(?string $symbol = null, ?int $since = null, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $since, $limit, $params) {
            /**
             * get the list of $trades associated with the user
             * @param {string} $symbol unified $symbol of the $market to fetch $trades for
             * @param {int} [$since] timestamp in ms of the earliest trade to fetch
             * @param {int} [$limit] the maximum amount of $trades to fetch
             * @param {array} [$params] extra parameters specific to the exmo api endpoint
             * @return {array[]} a list of ~@link https://docs.ccxt.com/#/?id=public-$trades trade structures~
             */
            Async\await($this->load_markets());
            Async\await($this->authenticate($params));
            list($type, $query) = $this->handle_market_type_and_params('watchMyTrades', null, $params);
            $url = $this->urls['api']['ws'][$type];
            $messageHash = null;
            if ($symbol === null) {
                $messageHash = 'myTrades:' . $type;
            } else {
                $market = $this->market($symbol);
                $symbol = $market['symbol'];
                $messageHash = 'myTrades:' . $market['symbol'];
            }
            $message = array(
                'method' => 'subscribe',
                'topics' => array(
                    $type . '/user_trades',
                ),
                'id' => $this->request_id(),
            );
            $request = $this->deep_extend($message, $query);
            $trades = Async\await($this->watch($url, $messageHash, $request, $messageHash, $request));
            return $this->filter_by_symbol_since_limit($trades, $symbol, $since, $limit, true);
        }) ();
    }

    public function handle_my_trades(Client $client, $message) {
        //
        //  spot
        //     {
        //         "ts" => 1654210290219,
        //         "event" => "update",
        //         "topic" => "spot/user_trades",
        //         "data" => {
        //             "trade_id" => 389715807,
        //             "type" => "buy",
        //             "price" => "30527.77",
        //             "quantity" => "0.0001",
        //             "amount" => "3.052777",
        //             "date" => 1654210290,
        //             "order_id" => 27352777112,
        //             "client_id" => 0,
        //             "pair" => "BTC_USDT",
        //             "exec_type" => "taker",
        //             "commission_amount" => "0.0000001",
        //             "commission_currency" => "BTC",
        //             "commission_percent" => "0.1"
        //         }
        //     }
        //
        //  margin
        //     {
        //         "ts":1624369720168,
        //         "event":"snapshot",
        //         "topic":"margin/user_trades",
        //         "data":array(
        //            {
        //               "trade_id":"692844278081167054",
        //               "trade_dt":"1624369773990729200",
        //               "type":"buy",
        //               "order_id":"692844278081167033",
        //               "pair":"BTC_USD",
        //               "quantity":"0.1",
        //               "price":"36638.5",
        //               "is_maker":false
        //            }
        //         )
        //     }
        //     {
        //         "ts":1624370368612,
        //         "event":"update",
        //         "topic":"margin/user_trades",
        //         "data":{
        //            "trade_id":"692844278081167693",
        //            "trade_dt":"1624370368569092500",
        //            "type":"buy",
        //            "order_id":"692844278081167674",
        //            "pair":"BTC_USD",
        //            "quantity":"0.1",
        //            "price":"36638.5",
        //            "is_maker":false
        //         }
        //     }
        //
        $topic = $this->safe_string($message, 'topic');
        $parts = explode('/', $topic);
        $type = $this->safe_string($parts, 0);
        $messageHash = 'myTrades:' . $type;
        $event = $this->safe_string($message, 'event');
        $rawTrades = array();
        $myTrades = null;
        if ($this->myTrades === null) {
            $limit = $this->safe_integer($this->options, 'tradesLimit', 1000);
            $myTrades = new ArrayCacheBySymbolById ($limit);
            $this->myTrades = $myTrades;
        } else {
            $myTrades = $this->myTrades;
        }
        if ($event === 'snapshot') {
            $rawTrades = $this->safe_value($message, 'data', array());
        } elseif ($event === 'update') {
            $rawTrade = $this->safe_value($message, 'data', array());
            $rawTrades = array( $rawTrade );
        }
        $trades = $this->parse_trades($rawTrades);
        $symbols = array();
        for ($j = 0; $j < count($trades); $j++) {
            $trade = $trades[$j];
            $myTrades->append ($trade);
            $symbols[$trade['symbol']] = true;
        }
        $symbolKeys = is_array($symbols) ? array_keys($symbols) : array();
        for ($i = 0; $i < count($symbolKeys); $i++) {
            $symbol = $symbolKeys[$i];
            $symbolSpecificMessageHash = 'myTrades:' . $symbol;
            $client->resolve ($myTrades, $symbolSpecificMessageHash);
        }
        $client->resolve ($myTrades, $messageHash);
    }

    public function watch_order_book(string $symbol, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $limit, $params) {
            /**
             * watches information on open orders with bid (buy) and ask (sell) prices, volumes and other data
             * @param {string} $symbol unified $symbol of the $market to fetch the order book for
             * @param {int} [$limit] the maximum amount of order book entries to return
             * @param {array} [$params] extra parameters specific to the exmo api endpoint
             * @return {array} A dictionary of ~@link https://docs.ccxt.com/#/?id=order-book-structure order book structures~ indexed by $market symbols
             */
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $symbol = $market['symbol'];
            $url = $this->urls['api']['ws']['public'];
            $messageHash = 'orderbook:' . $symbol;
            $params = $this->omit($params, 'aggregation');
            $subscribe = array(
                'method' => 'subscribe',
                'id' => $this->request_id(),
                'topics' => [
                    'spot/order_book_updates:' . $market['id'],
                ],
            );
            $request = $this->deep_extend($subscribe, $params);
            $orderbook = Async\await($this->watch($url, $messageHash, $request, $messageHash));
            return $orderbook->limit ();
        }) ();
    }

    public function handle_order_book(Client $client, $message) {
        //
        //     {
        //         "ts" => 1574427585174,
        //         "event" => "snapshot",
        //         "topic" => "spot/order_book_updates:BTC_USD",
        //         "data" => {
        //             "ask" => [
        //                 ["100", "3", "300"],
        //                 ["200", "4", "800"]
        //             ],
        //             "bid" => [
        //                 ["99", "2", "198"],
        //                 ["98", "1", "98"]
        //             ]
        //         }
        //     }
        //
        //     {
        //         "ts" => 1574427585174,
        //         "event" => "update",
        //         "topic" => "spot/order_book_updates:BTC_USD",
        //         "data" => {
        //             "ask" => [
        //                 ["100", "1", "100"],
        //                 ["200", "2", "400"]
        //             ],
        //             "bid" => [
        //                 ["99", "1", "99"],
        //                 ["98", "0", "0"]
        //             ]
        //         }
        //     }
        //
        $topic = $this->safe_string($message, 'topic');
        $parts = explode(':', $topic);
        $marketId = $this->safe_string($parts, 1);
        $symbol = $this->safe_symbol($marketId);
        $orderBook = $this->safe_value($message, 'data', array());
        $messageHash = 'orderbook:' . $symbol;
        $timestamp = $this->safe_integer($message, 'ts');
        $storedOrderBook = $this->safe_value($this->orderbooks, $symbol);
        if ($storedOrderBook === null) {
            $storedOrderBook = $this->order_book(array());
            $this->orderbooks[$symbol] = $storedOrderBook;
        }
        $event = $this->safe_string($message, 'event');
        if ($event === 'snapshot') {
            $snapshot = $this->parse_order_book($orderBook, $symbol, $timestamp, 'bid', 'ask');
            $storedOrderBook->reset ($snapshot);
        } else {
            $asks = $this->safe_value($orderBook, 'ask', array());
            $bids = $this->safe_value($orderBook, 'bid', array());
            $this->handle_deltas($storedOrderBook['asks'], $asks);
            $this->handle_deltas($storedOrderBook['bids'], $bids);
            $storedOrderBook['timestamp'] = $timestamp;
            $storedOrderBook['datetime'] = $this->iso8601($timestamp);
        }
        $client->resolve ($storedOrderBook, $messageHash);
    }

    public function handle_delta($bookside, $delta) {
        $bidAsk = $this->parse_bid_ask($delta, 0, 1);
        $bookside->storeArray ($bidAsk);
    }

    public function handle_deltas($bookside, $deltas) {
        for ($i = 0; $i < count($deltas); $i++) {
            $this->handle_delta($bookside, $deltas[$i]);
        }
    }

    public function handle_message(Client $client, $message) {
        //
        // {
        //     "ts" => 1654206362552,
        //     "event" => "info",
        //     "code" => 1,
        //     "message" => "connection established",
        //     "session_id" => "7548931b-c2a4-45dd-8d71-877881a7251a"
        // }
        //
        // {
        //     "ts" => 1654206491399,
        //     "event" => "subscribed",
        //     "id" => 1,
        //     "topic" => "spot/ticker:BTC_USDT"
        // }
        $event = $this->safe_string($message, 'event');
        $events = array(
            'logged_in' => array($this, 'handle_authentication_message'),
            'info' => array($this, 'handle_info'),
            'subscribed' => array($this, 'handle_subscribed'),
        );
        $eventHandler = $this->safe_value($events, $event);
        if ($eventHandler !== null) {
            return $eventHandler($client, $message);
        }
        if (($event === 'update') || ($event === 'snapshot')) {
            $topic = $this->safe_string($message, 'topic');
            if ($topic !== null) {
                $parts = explode(':', $topic);
                $channel = $this->safe_string($parts, 0);
                $handlers = array(
                    'spot/ticker' => array($this, 'handle_ticker'),
                    'spot/wallet' => array($this, 'handle_balance'),
                    'margin/wallet' => array($this, 'handle_balance'),
                    'margin/wallets' => array($this, 'handle_balance'),
                    'spot/trades' => array($this, 'handle_trades'),
                    'margin/trades' => array($this, 'handle_trades'),
                    'spot/order_book_updates' => array($this, 'handle_order_book'),
                    // 'spot/orders' => $this->handleOrders,
                    // 'margin/orders' => $this->handleOrders,
                    'spot/user_trades' => array($this, 'handle_my_trades'),
                    'margin/user_trades' => array($this, 'handle_my_trades'),
                );
                $handler = $this->safe_value($handlers, $channel);
                if ($handler !== null) {
                    return $handler($client, $message);
                }
            }
        }
        throw new NotSupported($this->id . ' received an unsupported $message => ' . $this->json($message));
    }

    public function handle_subscribed(Client $client, $message) {
        //
        // {
        //     "method" => "subscribe",
        //     "id" => 2,
        //     "topics" => ["spot/orders"]
        // }
        //
        return $message;
    }

    public function handle_info(Client $client, $message) {
        //
        // {
        //     "ts" => 1654215731659,
        //     "event" => "info",
        //     "code" => 1,
        //     "message" => "connection established",
        //     "session_id" => "4c496262-e259-4c27-b805-f20b46209c17"
        // }
        //
        return $message;
    }

    public function handle_authentication_message(Client $client, $message) {
        //
        //     {
        //         "method" => "login",
        //         "id" => 1,
        //         "api_key" => "K-************************",
        //         "sign" => "******************************************************************",
        //         "nonce" => 1654215729887
        //     }
        //
        $messageHash = 'authenticated';
        $client->resolve ($message, $messageHash);
    }

    public function authenticate($params = array ()) {
        $messageHash = 'authenticated';
        list($type, $query) = $this->handle_market_type_and_params('authenticate', null, $params);
        $url = $this->urls['api']['ws'][$type];
        $client = $this->client($url);
        $future = $this->safe_value($client->subscriptions, $messageHash);
        if ($future === null) {
            $time = $this->milliseconds();
            $this->check_required_credentials();
            $requestId = $this->request_id();
            $signData = $this->apiKey . (string) $time;
            $sign = $this->hmac($this->encode($signData), $this->encode($this->secret), 'sha512', 'base64');
            $request = array(
                'method' => 'login',
                'id' => $requestId,
                'api_key' => $this->apiKey,
                'sign' => $sign,
                'nonce' => $time,
            );
            $message = array_merge($request, $query);
            $future = $this->watch($url, $messageHash, $message);
            $client->subscriptions[$messageHash] = $future;
        }
        return $future;
    }
}
