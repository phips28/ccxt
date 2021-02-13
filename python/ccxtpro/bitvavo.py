# -*- coding: utf-8 -*-

# PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
# https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

from ccxtpro.base.exchange import Exchange
import ccxt.async_support as ccxt
from ccxtpro.base.cache import ArrayCache, ArrayCacheByTimestamp
from ccxt.base.errors import AuthenticationError
from ccxt.base.errors import ArgumentsRequired


class bitvavo(Exchange, ccxt.bitvavo):

    def describe(self):
        return self.deep_extend(super(bitvavo, self).describe(), {
            'has': {
                'ws': True,
                'watchOrderBook': True,
                'watchTrades': True,
                'watchTicker': True,
                'watchOHLCV': True,
                'watchOrders': True,
                'watchMyTrades': True,
            },
            'urls': {
                'api': {
                    'ws': 'wss://ws.bitvavo.com/v2',
                },
            },
            'options': {
                'tradesLimit': 1000,
                'ordersLimit': 1000,
                'OHLCVLimit': 1000,
            },
        })

    async def watch_public(self, name, symbol, params={}):
        await self.load_markets()
        market = self.market(symbol)
        messageHash = name + '@' + market['id']
        url = self.urls['api']['ws']
        request = {
            'action': 'subscribe',
            'channels': [
                {
                    'name': name,
                    'markets': [
                        market['id'],
                    ],
                },
            ],
        }
        message = self.extend(request, params)
        return await self.watch(url, messageHash, message, messageHash)

    async def watch_ticker(self, symbol, params={}):
        return await self.watch_public('ticker24h', symbol, params)

    def handle_ticker(self, client, message):
        #
        #     {
        #         event: 'ticker24h',
        #         data: [
        #             {
        #                 market: 'ETH-EUR',
        #                 open: '193.5',
        #                 high: '202.72',
        #                 low: '192.46',
        #                 last: '199.01',
        #                 volume: '3587.05020246',
        #                 volumeQuote: '708030.17',
        #                 bid: '199.56',
        #                 bidSize: '4.14730803',
        #                 ask: '199.57',
        #                 askSize: '6.13642074',
        #                 timestamp: 1590770885217
        #             }
        #         ]
        #     }
        #
        event = self.safe_string(message, 'event')
        tickers = self.safe_value(message, 'data', [])
        for i in range(0, len(tickers)):
            data = tickers[i]
            marketId = self.safe_string(data, 'market')
            market = self.safe_market(marketId, None, '-')
            messageHash = event + '@' + marketId
            ticker = self.parse_ticker(data, market)
            symbol = ticker['symbol']
            self.tickers[symbol] = ticker
            client.resolve(ticker, messageHash)
        return message

    async def watch_trades(self, symbol, since=None, limit=None, params={}):
        trades = await self.watch_public('trades', symbol, params)
        return self.filter_by_since_limit(trades, since, limit, 'timestamp', True)

    def handle_trade(self, client, message):
        #
        #     {
        #         event: 'trade',
        #         timestamp: 1590779594547,
        #         market: 'ETH-EUR',
        #         id: '450c3298-f082-4461-9e2c-a0262cc7cc2e',
        #         amount: '0.05026233',
        #         price: '198.46',
        #         side: 'buy'
        #     }
        #
        marketId = self.safe_string(message, 'market')
        market = self.safe_market(marketId, None, '-')
        symbol = market['symbol']
        name = 'trades'
        messageHash = name + '@' + marketId
        trade = self.parse_trade(message, market)
        array = self.safe_value(self.trades, symbol)
        if array is None:
            limit = self.safe_integer(self.options, 'tradesLimit', 1000)
            array = ArrayCache(limit)
        array.append(trade)
        self.trades[symbol] = array
        client.resolve(array, messageHash)

    async def watch_ohlcv(self, symbol, timeframe='1m', since=None, limit=None, params={}):
        await self.load_markets()
        market = self.market(symbol)
        name = 'candles'
        marketId = market['id']
        interval = self.timeframes[timeframe]
        messageHash = name + '@' + marketId + '_' + interval
        url = self.urls['api']['ws']
        request = {
            'action': 'subscribe',
            'channels': [
                {
                    'name': 'candles',
                    'interval': [interval],
                    'markets': [marketId],
                },
            ],
        }
        message = self.extend(request, params)
        ohlcv = await self.watch(url, messageHash, message, messageHash)
        return self.filter_by_since_limit(ohlcv, since, limit, 0, True)

    def handle_ohlcv(self, client, message):
        #
        #     {
        #         event: 'candle',
        #         market: 'BTC-EUR',
        #         interval: '1m',
        #         candle: [
        #             [
        #                 1590797160000,
        #                 '8480.9',
        #                 '8480.9',
        #                 '8480.9',
        #                 '8480.9',
        #                 '0.01038628'
        #             ]
        #         ]
        #     }
        #
        name = 'candles'
        marketId = self.safe_string(message, 'market')
        market = self.safe_market(marketId, None, '-')
        symbol = market['symbol']
        interval = self.safe_string(message, 'interval')
        # use a reverse lookup in a static map instead
        timeframe = self.find_timeframe(interval)
        messageHash = name + '@' + marketId + '_' + interval
        candles = self.safe_value(message, 'candle')
        self.ohlcvs[symbol] = self.safe_value(self.ohlcvs, symbol, {})
        stored = self.safe_value(self.ohlcvs[symbol], timeframe)
        if stored is None:
            limit = self.safe_integer(self.options, 'OHLCVLimit', 1000)
            stored = ArrayCacheByTimestamp(limit)
            self.ohlcvs[symbol][timeframe] = stored
        for i in range(0, len(candles)):
            candle = candles[i]
            parsed = self.parse_ohlcv(candle, market)
            stored.append(parsed)
        client.resolve(stored, messageHash)

    async def watch_order_book(self, symbol, limit=None, params={}):
        await self.load_markets()
        market = self.market(symbol)
        name = 'book'
        messageHash = name + '@' + market['id']
        url = self.urls['api']['ws']
        request = {
            'action': 'subscribe',
            'channels': [
                {
                    'name': name,
                    'markets': [
                        market['id'],
                    ],
                },
            ],
        }
        subscription = {
            'messageHash': messageHash,
            'name': name,
            'symbol': symbol,
            'marketId': market['id'],
            'method': self.handle_order_book_subscription,
            'limit': limit,
            'params': params,
        }
        message = self.extend(request, params)
        orderbook = await self.watch(url, messageHash, message, messageHash, subscription)
        return self.limit_order_book(orderbook, symbol, limit, params)

    def handle_delta(self, bookside, delta):
        price = self.safe_float(delta, 0)
        amount = self.safe_float(delta, 1)
        bookside.store(price, amount)

    def handle_deltas(self, bookside, deltas):
        for i in range(0, len(deltas)):
            self.handle_delta(bookside, deltas[i])

    def handle_order_book_message(self, client, message, orderbook):
        #
        #     {
        #         event: 'book',
        #         market: 'BTC-EUR',
        #         nonce: 36947383,
        #         bids: [
        #             ['8477.8', '0']
        #         ],
        #         asks: [
        #             ['8550.9', '0']
        #         ]
        #     }
        #
        nonce = self.safe_integer(message, 'nonce')
        if nonce > orderbook['nonce']:
            self.handle_deltas(orderbook['asks'], self.safe_value(message, 'asks', []))
            self.handle_deltas(orderbook['bids'], self.safe_value(message, 'bids', []))
            orderbook['nonce'] = nonce
        return orderbook

    def handle_order_book(self, client, message):
        #
        #     {
        #         event: 'book',
        #         market: 'BTC-EUR',
        #         nonce: 36729561,
        #         bids: [
        #             ['8513.3', '0'],
        #             ['8518.8', '0.64236203'],
        #             ['8513.6', '0.32435481'],
        #         ],
        #         asks: []
        #     }
        #
        event = self.safe_string(message, 'event')
        marketId = self.safe_string(message, 'market')
        market = self.safe_market(marketId, None, '-')
        symbol = market['symbol']
        messageHash = event + '@' + market['id']
        orderbook = self.safe_value(self.orderbooks, symbol)
        if orderbook is None:
            return
        if orderbook['nonce'] is None:
            subscription = self.safe_value(client.subscriptions, messageHash, {})
            watchingOrderBookSnapshot = self.safe_value(subscription, 'watchingOrderBookSnapshot')
            if watchingOrderBookSnapshot is None:
                subscription['watchingOrderBookSnapshot'] = True
                client.subscriptions[messageHash] = subscription
                options = self.safe_value(self.options, 'watchOrderBookSnapshot', {})
                delay = self.safe_integer(options, 'delay', self.rateLimit)
                # fetch the snapshot in a separate async call after a warmup delay
                self.delay(delay, self.watch_order_book_snapshot, client, message, subscription)
            orderbook.cache.append(message)
        else:
            self.handle_order_book_message(client, message, orderbook)
            client.resolve(orderbook, messageHash)

    async def watch_order_book_snapshot(self, client, message, subscription):
        symbol = self.safe_string(subscription, 'symbol')
        limit = self.safe_integer(subscription, 'limit')
        params = self.safe_value(subscription, 'params')
        marketId = self.safe_string(subscription, 'marketId')
        name = 'getBook'
        messageHash = name + '@' + marketId
        url = self.urls['api']['ws']
        request = {
            'action': name,
            'market': marketId,
        }
        orderbook = await self.watch(url, messageHash, request, messageHash, subscription)
        return self.limit_order_book(orderbook, symbol, limit, params)

    def handle_order_book_snapshot(self, client, message):
        #
        #     {
        #         action: 'getBook',
        #         response: {
        #             market: 'BTC-EUR',
        #             nonce: 36946120,
        #             bids: [
        #                 ['8494.9', '0.24399521'],
        #                 ['8494.8', '0.34884085'],
        #                 ['8493.9', '0.14535128'],
        #             ],
        #             asks: [
        #                 ['8495', '0.46982463'],
        #                 ['8495.1', '0.12178267'],
        #                 ['8496.2', '0.21924143'],
        #             ]
        #         }
        #     }
        #
        response = self.safe_value(message, 'response')
        if response is None:
            return message
        marketId = self.safe_string(response, 'market')
        symbol = None
        if marketId in self.markets_by_id:
            market = self.markets_by_id[marketId]
            symbol = market['symbol']
        name = 'book'
        messageHash = name + '@' + marketId
        orderbook = self.orderbooks[symbol]
        snapshot = self.parse_order_book(response)
        snapshot['nonce'] = self.safe_integer(response, 'nonce')
        orderbook.reset(snapshot)
        # unroll the accumulated deltas
        messages = orderbook.cache
        for i in range(0, len(messages)):
            message = messages[i]
            self.handle_order_book_message(client, message, orderbook)
        self.orderbooks[symbol] = orderbook
        client.resolve(orderbook, messageHash)

    def handle_order_book_subscription(self, client, message, subscription):
        symbol = self.safe_string(subscription, 'symbol')
        limit = self.safe_integer(subscription, 'limit')
        if symbol in self.orderbooks:
            del self.orderbooks[symbol]
        self.orderbooks[symbol] = self.order_book({}, limit)

    def handle_order_book_subscriptions(self, client, message, marketIds):
        name = 'book'
        for i in range(0, len(marketIds)):
            marketId = self.safe_string(marketIds, i)
            if marketId in self.markets_by_id:
                market = self.markets_by_id[marketId]
                symbol = market['symbol']
                messageHash = name + '@' + marketId
                if not (symbol in self.orderbooks):
                    subscription = self.safe_value(client.subscriptions, messageHash)
                    method = self.safe_value(subscription, 'method')
                    if method is not None:
                        method(client, message, subscription)

    async def watch_orders(self, symbol=None, since=None, limit=None, params={}):
        if symbol is None:
            raise ArgumentsRequired(self.id + ' watchOrders requires a symbol argument')
        await self.load_markets()
        await self.authenticate()
        market = self.market(symbol)
        marketId = market['id']
        url = self.urls['api']['ws']
        name = 'account'
        subscriptionHash = name + '@' + marketId
        messageHash = subscriptionHash + '_' + 'order'
        request = {
            'action': 'subscribe',
            'channels': [
                {
                    'name': name,
                    'markets': [marketId],
                },
            ],
        }
        orders = await self.watch(url, messageHash, request, subscriptionHash)
        return self.filter_by_symbol_since_limit(orders, symbol, since, limit)

    async def watch_my_trades(self, symbol=None, since=None, limit=None, params={}):
        if symbol is None:
            raise ArgumentsRequired(self.id + ' watchMyTrades requires a symbol argument')
        await self.load_markets()
        await self.authenticate()
        market = self.market(symbol)
        marketId = market['id']
        url = self.urls['api']['ws']
        name = 'account'
        subscriptionHash = name + '@' + marketId
        messageHash = subscriptionHash + '_' + 'fill'
        request = {
            'action': 'subscribe',
            'channels': [
                {
                    'name': name,
                    'markets': [marketId],
                },
            ],
        }
        trades = await self.watch(url, messageHash, request, subscriptionHash)
        return self.filter_by_symbol_since_limit(trades, symbol, since, limit)

    def handle_order(self, client, message):
        #
        #     {
        #         event: 'order',
        #         orderId: 'f0e5180f-9497-4d05-9dc2-7056e8a2de9b',
        #         market: 'ETH-EUR',
        #         created: 1590948500319,
        #         updated: 1590948500319,
        #         status: 'new',
        #         side: 'sell',
        #         orderType: 'limit',
        #         amount: '0.1',
        #         amountRemaining: '0.1',
        #         price: '300',
        #         onHold: '0.1',
        #         onHoldCurrency: 'ETH',
        #         selfTradePrevention: 'decrementAndCancel',
        #         visible: True,
        #         timeInForce: 'GTC',
        #         postOnly: False
        #     }
        #
        name = 'account'
        event = self.safe_string(message, 'event')
        marketId = self.safe_string(message, 'market')
        messageHash = name + '@' + marketId + '_' + event
        symbol = marketId
        market = None
        if marketId in self.markets_by_id:
            market = self.markets_by_id[marketId]
            symbol = market['symbol']
        order = self.parse_order(message, market)
        orderId = order['id']
        defaultKey = self.safe_value(self.orders, symbol, {})
        defaultKey[orderId] = order
        self.orders[symbol] = defaultKey
        result = []
        values = list(self.orders.values())
        for i in range(0, len(values)):
            orders = list(values[i].values())
            result = self.array_concat(result, orders)
        # del older orders from our structure to prevent memory leaks
        limit = self.safe_integer(self.options, 'ordersLimit', 1000)
        result = self.sort_by(result, 'timestamp')
        resultLength = len(result)
        if resultLength > limit:
            toDelete = resultLength - limit
            for i in range(0, toDelete):
                id = result[i]['id']
                symbol = result[i]['symbol']
                del self.orders[symbol][id]
            result = result[toDelete:resultLength]
        client.resolve(result, messageHash)

    def handle_my_trade(self, client, message):
        #
        #     {
        #         event: 'fill',
        #         timestamp: 1590964470132,
        #         market: 'ETH-EUR',
        #         orderId: '85d082e1-eda4-4209-9580-248281a29a9a',
        #         fillId: '861d2da5-aa93-475c-8d9a-dce431bd4211',
        #         side: 'sell',
        #         amount: '0.1',
        #         price: '211.46',
        #         taker: True,
        #         fee: '0.056',
        #         feeCurrency: 'EUR'
        #     }
        #
        name = 'account'
        event = self.safe_string(message, 'event')
        marketId = self.safe_string(message, 'market')
        messageHash = name + '@' + marketId + '_' + event
        market = self.safe_market(marketId, None, '-')
        trade = self.parse_trade(message, market)
        if self.myTrades is None:
            limit = self.safe_integer(self.options, 'tradesLimit', 1000)
            self.myTrades = ArrayCache(limit)
        array = self.myTrades
        array.append(trade)
        self.myTrades = array
        client.resolve(array, messageHash)

    def handle_subscription_status(self, client, message):
        #
        #     {
        #         event: 'subscribed',
        #         subscriptions: {
        #             book: ['BTC-EUR']
        #         }
        #     }
        #
        subscriptions = self.safe_value(message, 'subscriptions', {})
        methods = {
            'book': self.handle_order_book_subscriptions,
        }
        names = list(subscriptions.keys())
        for i in range(0, len(names)):
            name = names[i]
            method = self.safe_value(methods, name)
            if method is not None:
                subscription = self.safe_value(subscriptions, name)
                method(client, message, subscription)
        return message

    async def authenticate(self, params={}):
        url = self.urls['api']['ws']
        client = self.client(url)
        future = client.future('authenticated')
        action = 'authenticate'
        authenticated = self.safe_value(client.subscriptions, action)
        if authenticated is None:
            try:
                self.check_required_credentials()
                timestamp = self.milliseconds()
                stringTimestamp = str(timestamp)
                auth = stringTimestamp + 'GET/' + self.version + '/websocket'
                signature = self.hmac(self.encode(auth), self.encode(self.secret))
                request = {
                    'action': action,
                    'key': self.apiKey,
                    'signature': signature,
                    'timestamp': timestamp,
                }
                self.spawn(self.watch, url, action, request, action)
            except Exception as e:
                client.reject(e, 'authenticated')
                # allows further authentication attempts
                if action in client.subscriptions:
                    del client.subscriptions[action]
        return await future

    def handle_authentication_message(self, client, message):
        #
        #     {
        #         event: 'authenticate',
        #         authenticated: True
        #     }
        #
        authenticated = self.safe_value(message, 'authenticated', False)
        if authenticated:
            # we resolve the future here permanently so authentication only happens once
            future = self.safe_value(client.futures, 'authenticated')
            future.resolve(True)
        else:
            error = AuthenticationError(self.json(message))
            client.reject(error, 'authenticated')
            # allows further authentication attempts
            event = self.safe_value(message, 'event')
            if event in client.subscriptions:
                del client.subscriptions[event]

    def handle_message(self, client, message):
        #
        #     {
        #         event: 'subscribed',
        #         subscriptions: {
        #             book: ['BTC-EUR']
        #         }
        #     }
        #
        #
        #     {
        #         event: 'book',
        #         market: 'BTC-EUR',
        #         nonce: 36729561,
        #         bids: [
        #             ['8513.3', '0'],
        #             ['8518.8', '0.64236203'],
        #             ['8513.6', '0.32435481'],
        #         ],
        #         asks: []
        #     }
        #
        #     {
        #         action: 'getBook',
        #         response: {
        #             market: 'BTC-EUR',
        #             nonce: 36946120,
        #             bids: [
        #                 ['8494.9', '0.24399521'],
        #                 ['8494.8', '0.34884085'],
        #                 ['8493.9', '0.14535128'],
        #             ],
        #             asks: [
        #                 ['8495', '0.46982463'],
        #                 ['8495.1', '0.12178267'],
        #                 ['8496.2', '0.21924143'],
        #             ]
        #         }
        #     }
        #
        #     {
        #         event: 'authenticate',
        #         authenticated: True
        #     }
        #
        methods = {
            'subscribed': self.handle_subscription_status,
            'book': self.handle_order_book,
            'getBook': self.handle_order_book_snapshot,
            'trade': self.handle_trade,
            'candle': self.handle_ohlcv,
            'ticker24h': self.handle_ticker,
            'authenticate': self.handle_authentication_message,
            'order': self.handle_order,
            'fill': self.handle_my_trade,
        }
        event = self.safe_string(message, 'event')
        method = self.safe_value(methods, event)
        if method is None:
            action = self.safe_string(message, 'action')
            method = self.safe_value(methods, action)
            if method is None:
                return message
            else:
                return method(client, message)
        else:
            return method(client, message)
