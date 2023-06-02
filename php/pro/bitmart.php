<?php

namespace ccxt\pro;

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

use Exception; // a common import
use ccxt\ArgumentsRequired;
use ccxt\AuthenticationError;
use React\Async;

class bitmart extends \ccxt\async\bitmart {

    public function describe() {
        return $this->deep_extend(parent::describe(), array(
            'has' => array(
                'ws' => true,
                'watchTicker' => true,
                'watchOrderBook' => true,
                'watchOrders' => true,
                'watchTrades' => true,
                'watchOHLCV' => true,
            ),
            'urls' => array(
                'api' => array(
                    'ws' => array(
                        'public' => 'wss://ws-manager-compress.{hostname}/api?protocol=1.1',
                        'private' => 'wss://ws-manager-compress.{hostname}/user?protocol=1.1',
                    ),
                ),
            ),
            'options' => array(
                'defaultType' => 'spot',
                'watchOrderBook' => array(
                    'depth' => 'depth5', // depth5, depth20, depth50
                ),
                'ws' => array(
                    'inflate' => true,
                ),
                'timeframes' => array(
                    '1m' => '1m',
                    '3m' => '3m',
                    '5m' => '5m',
                    '15m' => '15m',
                    '30m' => '30m',
                    '45m' => '45m',
                    '1h' => '1H',
                    '2h' => '2H',
                    '3h' => '3H',
                    '4h' => '4H',
                    '1d' => '1D',
                    '1w' => '1W',
                    '1M' => '1M',
                ),
            ),
            'streaming' => array(
                'keepAlive' => 15000,
            ),
        ));
    }

    public function subscribe($channel, $symbol, $params = array ()) {
        return Async\async(function () use ($channel, $symbol, $params) {
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $url = $this->implode_hostname($this->urls['api']['ws']['public']);
            $messageHash = $market['type'] . '/' . $channel . ':' . $market['id'];
            $request = array(
                'op' => 'subscribe',
                'args' => array( $messageHash ),
            );
            return Async\await($this->watch($url, $messageHash, $this->deep_extend($request, $params), $messageHash));
        }) ();
    }

    public function subscribe_private($channel, $symbol, $params = array ()) {
        return Async\async(function () use ($channel, $symbol, $params) {
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $url = $this->implode_hostname($this->urls['api']['ws']['private']);
            $messageHash = $channel . ':' . $market['id'];
            Async\await($this->authenticate());
            $request = array(
                'op' => 'subscribe',
                'args' => array( $messageHash ),
            );
            return Async\await($this->watch($url, $messageHash, $this->deep_extend($request, $params), $messageHash));
        }) ();
    }

    public function watch_trades(string $symbol, ?int $since = null, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $since, $limit, $params) {
            /**
             * get the list of most recent $trades for a particular $symbol
             * @param {string} $symbol unified $symbol of the market to fetch $trades for
             * @param {int|null} $since timestamp in ms of the earliest trade to fetch
             * @param {int|null} $limit the maximum amount of $trades to fetch
             * @param {array} $params extra parameters specific to the bitmart api endpoint
             * @return {[array]} a list of ~@link https://docs.ccxt.com/en/latest/manual.html?#public-$trades trade structures~
             */
            Async\await($this->load_markets());
            $symbol = $this->symbol($symbol);
            $trades = Async\await($this->subscribe('trade', $symbol, $params));
            if ($this->newUpdates) {
                $limit = $trades->getLimit ($symbol, $limit);
            }
            return $this->filter_by_since_limit($trades, $since, $limit, 'timestamp', true);
        }) ();
    }

    public function watch_ticker(string $symbol, $params = array ()) {
        return Async\async(function () use ($symbol, $params) {
            /**
             * watches a price ticker, a statistical calculation with the information calculated over the past 24 hours for a specific market
             * @param {string} $symbol unified $symbol of the market to fetch the ticker for
             * @param {array} $params extra parameters specific to the bitmart api endpoint
             * @return {array} a ~@link https://docs.ccxt.com/#/?id=ticker-structure ticker structure~
             */
            return Async\await($this->subscribe('ticker', $symbol, $params));
        }) ();
    }

    public function watch_orders(?string $symbol = null, ?int $since = null, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $since, $limit, $params) {
            /**
             * watches information on multiple $orders made by the user
             * @param {string|null} $symbol unified $market $symbol of the $market $orders were made in
             * @param {int|null} $since the earliest time in ms to fetch $orders for
             * @param {int|null} $limit the maximum number of  orde structures to retrieve
             * @param {array} $params extra parameters specific to the bitmart api endpoint
             * @return {[array]} a list of ~@link https://docs.ccxt.com/#/?id=order-structure order structures~
             */
            if ($symbol === null) {
                throw new ArgumentsRequired($this->id . ' watchOrders requires a $symbol argument');
            }
            Async\await($this->load_markets());
            $market = $this->market($symbol);
            $symbol = $market['symbol'];
            if ($market['type'] !== 'spot') {
                throw new ArgumentsRequired($this->id . ' watchOrders supports spot markets only');
            }
            $channel = 'spot/user/order';
            $orders = Async\await($this->subscribe_private($channel, $symbol, $params));
            if ($this->newUpdates) {
                $limit = $orders->getLimit ($symbol, $limit);
            }
            return $this->filter_by_symbol_since_limit($orders, $symbol, $since, $limit);
        }) ();
    }

    public function handle_orders(Client $client, $message) {
        //
        // {
        //     "data":array(
        //         {
        //             $symbol => 'LTC_USDT',
        //             notional => '',
        //             side => 'buy',
        //             last_fill_time => '0',
        //             ms_t => '1646216634000',
        //             type => 'limit',
        //             filled_notional => '0.000000000000000000000000000000',
        //             last_fill_price => '0',
        //             size => '0.500000000000000000000000000000',
        //             price => '50.000000000000000000000000000000',
        //             last_fill_count => '0',
        //             filled_size => '0.000000000000000000000000000000',
        //             margin_trading => '0',
        //             state => '8',
        //             order_id => '24807076628',
        //             order_type => '0'
        //           }
        //     ),
        //     "table":"spot/user/order"
        // }
        //
        $channel = $this->safe_string($message, 'table');
        $orders = $this->safe_value($message, 'data', array());
        $ordersLength = count($orders);
        if ($ordersLength > 0) {
            $limit = $this->safe_integer($this->options, 'ordersLimit', 1000);
            if ($this->orders === null) {
                $this->orders = new ArrayCacheBySymbolById ($limit);
            }
            $stored = $this->orders;
            $marketIds = array();
            for ($i = 0; $i < count($orders); $i++) {
                $order = $this->parse_ws_order($orders[$i]);
                $stored->append ($order);
                $symbol = $order['symbol'];
                $market = $this->market($symbol);
                $marketIds[] = $market['id'];
            }
            for ($i = 0; $i < count($marketIds); $i++) {
                $messageHash = $channel . ':' . $marketIds[$i];
                $client->resolve ($this->orders, $messageHash);
            }
        }
    }

    public function parse_ws_order($order, $market = null) {
        //
        // {
        //     $symbol => 'LTC_USDT',
        //     notional => '',
        //     $side => 'buy',
        //     last_fill_time => '0',
        //     ms_t => '1646216634000',
        //     $type => 'limit',
        //     filled_notional => '0.000000000000000000000000000000',
        //     last_fill_price => '0',
        //     size => '0.500000000000000000000000000000',
        //     $price => '50.000000000000000000000000000000',
        //     last_fill_count => '0',
        //     filled_size => '0.000000000000000000000000000000',
        //     margin_trading => '0',
        //     state => '8',
        //     order_id => '24807076628',
        //     order_type => '0'
        //   }
        //
        $marketId = $this->safe_string($order, 'symbol');
        $market = $this->safe_market($marketId, $market);
        $id = $this->safe_string($order, 'order_id');
        $clientOrderId = $this->safe_string($order, 'clientOid');
        $price = $this->safe_string($order, 'price');
        $filled = $this->safe_string($order, 'filled_size');
        $amount = $this->safe_string($order, 'size');
        $type = $this->safe_string($order, 'type');
        $rawState = $this->safe_string($order, 'state');
        $status = $this->parseOrderStatusByType ($market['type'], $rawState);
        $timestamp = $this->safe_integer($order, 'ms_t');
        $symbol = $market['symbol'];
        $side = $this->safe_string_lower($order, 'side');
        return $this->safe_order(array(
            'info' => $order,
            'symbol' => $symbol,
            'id' => $id,
            'clientOrderId' => $clientOrderId,
            'timestamp' => null,
            'datetime' => null,
            'lastTradeTimestamp' => $timestamp,
            'type' => $type,
            'timeInForce' => null,
            'postOnly' => null,
            'side' => $side,
            'price' => $price,
            'stopPrice' => null,
            'triggerPrice' => null,
            'amount' => $amount,
            'cost' => null,
            'average' => null,
            'filled' => $filled,
            'remaining' => null,
            'status' => $status,
            'fee' => null,
            'trades' => null,
        ), $market);
    }

    public function handle_trade(Client $client, $message) {
        //
        //     {
        //         $table => 'spot/trade',
        //         $data => array(
        //             array(
        //                 price => '52700.50',
        //                 s_t => 1630982050,
        //                 side => 'buy',
        //                 size => '0.00112',
        //                 $symbol => 'BTC_USDT'
        //             ),
        //         )
        //     }
        //
        $table = $this->safe_string($message, 'table');
        $data = $this->safe_value($message, 'data', array());
        $tradesLimit = $this->safe_integer($this->options, 'tradesLimit', 1000);
        for ($i = 0; $i < count($data); $i++) {
            $trade = $this->parse_trade($data[$i]);
            $symbol = $trade['symbol'];
            $marketId = $this->safe_string($trade['info'], 'symbol');
            $messageHash = $table . ':' . $marketId;
            $stored = $this->safe_value($this->trades, $symbol);
            if ($stored === null) {
                $stored = new ArrayCache ($tradesLimit);
                $this->trades[$symbol] = $stored;
            }
            $stored->append ($trade);
            $client->resolve ($stored, $messageHash);
        }
        return $message;
    }

    public function handle_ticker(Client $client, $message) {
        //
        //     {
        //         $data => array(
        //             {
        //                 base_volume_24h => '78615593.81',
        //                 high_24h => '52756.97',
        //                 last_price => '52638.31',
        //                 low_24h => '50991.35',
        //                 open_24h => '51692.03',
        //                 s_t => 1630981727,
        //                 $symbol => 'BTC_USDT'
        //             }
        //         ),
        //         $table => 'spot/ticker'
        //     }
        //
        $table = $this->safe_string($message, 'table');
        $data = $this->safe_value($message, 'data', array());
        for ($i = 0; $i < count($data); $i++) {
            $ticker = $this->parse_ticker($data[$i]);
            $symbol = $ticker['symbol'];
            $marketId = $this->safe_string($ticker['info'], 'symbol');
            $messageHash = $table . ':' . $marketId;
            $this->tickers[$symbol] = $ticker;
            $client->resolve ($ticker, $messageHash);
        }
        return $message;
    }

    public function watch_ohlcv(string $symbol, $timeframe = '1m', ?int $since = null, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $timeframe, $since, $limit, $params) {
            /**
             * watches historical candlestick data containing the open, high, low, and close price, and the volume of a market
             * @param {string} $symbol unified $symbol of the market to fetch OHLCV data for
             * @param {string} $timeframe the length of time each candle represents
             * @param {int|null} $since timestamp in ms of the earliest candle to fetch
             * @param {int|null} $limit the maximum amount of candles to fetch
             * @param {array} $params extra parameters specific to the bitmart api endpoint
             * @return {[[int]]} A list of candles ordered, open, high, low, close, volume
             */
            Async\await($this->load_markets());
            $symbol = $this->symbol($symbol);
            $timeframes = $this->safe_value($this->options, 'timeframes', array());
            $interval = $this->safe_string($timeframes, $timeframe);
            $name = 'kline' . $interval;
            $ohlcv = Async\await($this->subscribe($name, $symbol, $params));
            if ($this->newUpdates) {
                $limit = $ohlcv->getLimit ($symbol, $limit);
            }
            return $this->filter_by_since_limit($ohlcv, $since, $limit, 0, true);
        }) ();
    }

    public function handle_ohlcv(Client $client, $message) {
        //
        //     {
        //         $data => array(
        //             {
        //                 $candle => array(
        //                     1631056350,
        //                     '46532.83',
        //                     '46555.71',
        //                     '46511.41',
        //                     '46555.71',
        //                     '0.25'
        //                 ),
        //                 $symbol => 'BTC_USDT'
        //             }
        //         ),
        //         $table => 'spot/kline1m'
        //     }
        //
        $table = $this->safe_string($message, 'table');
        $data = $this->safe_value($message, 'data', array());
        $parts = explode('/', $table);
        $part1 = $this->safe_string($parts, 1);
        $interval = str_replace('kline', '', $part1);
        // use a reverse lookup in a static map instead
        $timeframes = $this->safe_value($this->options, 'timeframes', array());
        $timeframe = $this->find_timeframe($interval, $timeframes);
        $duration = $this->parse_timeframe($timeframe);
        $durationInMs = $duration * 1000;
        for ($i = 0; $i < count($data); $i++) {
            $marketId = $this->safe_string($data[$i], 'symbol');
            $candle = $this->safe_value($data[$i], 'candle');
            $market = $this->safe_market($marketId);
            $symbol = $market['symbol'];
            $parsed = $this->parse_ohlcv($candle, $market);
            $parsed[0] = $this->parse_to_int($parsed[0] / $durationInMs) * $durationInMs;
            $this->ohlcvs[$symbol] = $this->safe_value($this->ohlcvs, $symbol, array());
            $stored = $this->safe_value($this->ohlcvs[$symbol], $timeframe);
            if ($stored === null) {
                $limit = $this->safe_integer($this->options, 'OHLCVLimit', 1000);
                $stored = new ArrayCacheByTimestamp ($limit);
                $this->ohlcvs[$symbol][$timeframe] = $stored;
            }
            $stored->append ($parsed);
            $messageHash = $table . ':' . $marketId;
            $client->resolve ($stored, $messageHash);
        }
    }

    public function watch_order_book(string $symbol, ?int $limit = null, $params = array ()) {
        return Async\async(function () use ($symbol, $limit, $params) {
            /**
             * watches information on open orders with bid (buy) and ask (sell) prices, volumes and other data
             * @param {string} $symbol unified $symbol of the market to fetch the order book for
             * @param {int|null} $limit the maximum amount of order book entries to return
             * @param {array} $params extra parameters specific to the bitmart api endpoint
             * @return {array} A dictionary of ~@link https://docs.ccxt.com/#/?id=order-book-structure order book structures~ indexed by market symbols
             */
            $options = $this->safe_value($this->options, 'watchOrderBook', array());
            $depth = $this->safe_string($options, 'depth', 'depth50');
            $orderbook = Async\await($this->subscribe($depth, $symbol, $params));
            return $orderbook->limit ();
        }) ();
    }

    public function handle_delta($bookside, $delta) {
        $price = $this->safe_float($delta, 0);
        $amount = $this->safe_float($delta, 1);
        $bookside->store ($price, $amount);
    }

    public function handle_deltas($bookside, $deltas) {
        for ($i = 0; $i < count($deltas); $i++) {
            $this->handle_delta($bookside, $deltas[$i]);
        }
    }

    public function handle_order_book_message(Client $client, $message, $orderbook) {
        //
        //     {
        //         $asks => array(
        //             array( '46828.38', '0.21847' ),
        //             array( '46830.68', '0.08232' ),
        //             array( '46832.08', '0.09285' ),
        //             array( '46837.82', '0.02028' ),
        //             array( '46839.43', '0.15068' )
        //         ),
        //         $bids => array(
        //             array( '46820.78', '0.00444' ),
        //             array( '46814.33', '0.00234' ),
        //             array( '46813.50', '0.05021' ),
        //             array( '46808.14', '0.00217' ),
        //             array( '46808.04', '0.00013' )
        //         ),
        //         ms_t => 1631044962431,
        //         $symbol => 'BTC_USDT'
        //     }
        //
        $asks = $this->safe_value($message, 'asks', array());
        $bids = $this->safe_value($message, 'bids', array());
        $this->handle_deltas($orderbook['asks'], $asks);
        $this->handle_deltas($orderbook['bids'], $bids);
        $timestamp = $this->safe_integer($message, 'ms_t');
        $marketId = $this->safe_string($message, 'symbol');
        $symbol = $this->safe_symbol($marketId);
        $orderbook['symbol'] = $symbol;
        $orderbook['timestamp'] = $timestamp;
        $orderbook['datetime'] = $this->iso8601($timestamp);
        return $orderbook;
    }

    public function handle_order_book(Client $client, $message) {
        //
        //     {
        //         $data => array(
        //             {
        //                 asks => array(
        //                     array( '46828.38', '0.21847' ),
        //                     array( '46830.68', '0.08232' ),
        //                     array( '46832.08', '0.09285' ),
        //                     array( '46837.82', '0.02028' ),
        //                     array( '46839.43', '0.15068' )
        //                 ),
        //                 bids => array(
        //                     array( '46820.78', '0.00444' ),
        //                     array( '46814.33', '0.00234' ),
        //                     array( '46813.50', '0.05021' ),
        //                     array( '46808.14', '0.00217' ),
        //                     array( '46808.04', '0.00013' )
        //                 ),
        //                 ms_t => 1631044962431,
        //                 $symbol => 'BTC_USDT'
        //             }
        //         ),
        //         $table => 'spot/depth5'
        //     }
        //
        $data = $this->safe_value($message, 'data', array());
        $table = $this->safe_string($message, 'table');
        $parts = explode('/', $table);
        $lastPart = $this->safe_string($parts, 1);
        $limitString = str_replace('depth', '', $lastPart);
        $limit = intval($limitString);
        for ($i = 0; $i < count($data); $i++) {
            $update = $data[$i];
            $marketId = $this->safe_string($update, 'symbol');
            $symbol = $this->safe_symbol($marketId);
            $orderbook = $this->safe_value($this->orderbooks, $symbol);
            if ($orderbook === null) {
                $orderbook = $this->order_book(array(), $limit);
                $this->orderbooks[$symbol] = $orderbook;
            }
            $orderbook->reset (array());
            $this->handle_order_book_message($client, $update, $orderbook);
            $messageHash = $table . ':' . $marketId;
            $client->resolve ($orderbook, $messageHash);
        }
        return $message;
    }

    public function authenticate($params = array ()) {
        $this->check_required_credentials();
        $url = $this->implode_hostname($this->urls['api']['ws']['private']);
        $messageHash = 'authenticated';
        $client = $this->client($url);
        $future = $this->safe_value($client->subscriptions, $messageHash);
        if ($future === null) {
            $timestamp = (string) $this->milliseconds();
            $memo = $this->uid;
            $path = 'bitmart.WebSocket';
            $auth = $timestamp . '#' . $memo . '#' . $path;
            $signature = $this->hmac($this->encode($auth), $this->encode($this->secret), 'sha256');
            $operation = 'login';
            $request = array(
                'op' => $operation,
                'args' => array(
                    $this->apiKey,
                    $timestamp,
                    $signature,
                ),
            );
            $message = array_merge($request, $params);
            $future = $this->watch($url, $messageHash, $message);
            $client->subscriptions[$messageHash] = $future;
        }
        return $future;
    }

    public function handle_subscription_status(Client $client, $message) {
        //
        //     array("event":"subscribe","channel":"spot/depth:BTC-USDT")
        //
        return $message;
    }

    public function handle_authenticate(Client $client, $message) {
        //
        //     array( event => 'login' )
        //
        $messageHash = 'authenticated';
        $client->resolve ($message, $messageHash);
    }

    public function handle_error_message(Client $client, $message) {
        //
        //     array( event => 'error', $message => 'Invalid sign', $errorCode => 30013 )
        //     array("event":"error","message":"Unrecognized request => array(\"event\":\"subscribe\",\"channel\":\"spot/depth:BTC-USDT\")","errorCode":30039)
        //
        $errorCode = $this->safe_string($message, 'errorCode');
        try {
            if ($errorCode !== null) {
                $feedback = $this->id . ' ' . $this->json($message);
                $this->throw_exactly_matched_exception($this->exceptions['exact'], $errorCode, $feedback);
                $messageString = $this->safe_value($message, 'message');
                if ($messageString !== null) {
                    $this->throw_broadly_matched_exception($this->exceptions['broad'], $messageString, $feedback);
                }
            }
            return false;
        } catch (Exception $e) {
            if ($e instanceof AuthenticationError) {
                $messageHash = 'authenticated';
                $client->reject ($e, $messageHash);
                if (is_array($client->subscriptions) && array_key_exists($messageHash, $client->subscriptions)) {
                    unset($client->subscriptions[$messageHash]);
                }
            }
            return true;
        }
    }

    public function handle_message(Client $client, $message) {
        if ($this->handle_error_message($client, $message)) {
            return;
        }
        //
        //     array("event":"error","message":"Unrecognized request => array(\"event\":\"subscribe\",\"channel\":\"spot/depth:BTC-USDT\")","errorCode":30039)
        //     array("event":"subscribe","channel":"spot/depth:BTC-USDT")
        //     {
        //         $table => "spot/depth",
        //         action => "partial",
        //         data => [
        //             {
        //                 instrument_id =>   "BTC-USDT",
        //                 asks => [
        //                     ["5301.8", "0.03763319", "1"],
        //                     ["5302.4", "0.00305", "2"],
        //                 ],
        //                 bids => [
        //                     ["5301.7", "0.58911427", "6"],
        //                     ["5301.6", "0.01222922", "4"],
        //                 ],
        //                 timestamp => "2020-03-16T03:25:00.440Z",
        //                 checksum => -2088736623
        //             }
        //         ]
        //     }
        //
        //     array( data => '', $table => 'spot/user/order' )
        //
        $table = $this->safe_string($message, 'table');
        if ($table === null) {
            $event = $this->safe_string($message, 'event');
            if ($event !== null) {
                $methods = array(
                    // 'info' => $this->handleSystemStatus,
                    // 'book' => 'handleOrderBook',
                    'login' => array($this, 'handle_authenticate'),
                    'subscribe' => array($this, 'handle_subscription_status'),
                );
                $method = $this->safe_value($methods, $event);
                if ($method === null) {
                    return $message;
                } else {
                    return $method($client, $message);
                }
            }
        } else {
            $parts = explode('/', $table);
            $name = $this->safe_string($parts, 1);
            $methods = array(
                'depth' => array($this, 'handle_order_book'),
                'depth5' => array($this, 'handle_order_book'),
                'depth20' => array($this, 'handle_order_book'),
                'depth50' => array($this, 'handle_order_book'),
                'ticker' => array($this, 'handle_ticker'),
                'trade' => array($this, 'handle_trade'),
                // ...
            );
            $method = $this->safe_value($methods, $name);
            if (mb_strpos($name, 'kline') !== false) {
                $method = array($this, 'handle_ohlcv');
            }
            $privateName = $this->safe_string($parts, 2);
            if ($privateName === 'order') {
                $method = array($this, 'handle_orders');
            }
            if ($method === null) {
                return $message;
            } else {
                return $method($client, $message);
            }
        }
    }
}
