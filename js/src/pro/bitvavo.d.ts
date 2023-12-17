import bitvavoRest from '../bitvavo.js';
import type { Int, Str, OrderBook, Order, Trade, Ticker, OHLCV } from '../base/types.js';
import Client from '../base/ws/Client.js';
export default class bitvavo extends bitvavoRest {
    describe(): any;
    watchPublic(name: any, symbol: any, params?: {}): Promise<any>;
    watchTicker(symbol: string, params?: {}): Promise<Ticker>;
    handleTicker(client: Client, message: any): any;
    watchTrades(symbol: string, since?: Int, limit?: Int, params?: {}): Promise<Trade[]>;
    handleTrade(client: Client, message: any): void;
    watchOHLCV(symbol: string, timeframe?: string, since?: Int, limit?: Int, params?: {}): Promise<OHLCV[]>;
    handleOHLCV(client: Client, message: any): void;
    watchOrderBook(symbol: string, limit?: Int, params?: {}): Promise<OrderBook>;
    handleDelta(bookside: any, delta: any): void;
    handleDeltas(bookside: any, deltas: any): void;
    handleOrderBookMessage(client: Client, message: any, orderbook: any): any;
    handleOrderBook(client: Client, message: any): void;
    watchOrderBookSnapshot(client: any, message: any, subscription: any): Promise<any>;
    handleOrderBookSnapshot(client: Client, message: any): any;
    handleOrderBookSubscription(client: Client, message: any, subscription: any): void;
    handleOrderBookSubscriptions(client: Client, message: any, marketIds: any): void;
    watchOrders(symbol?: Str, since?: Int, limit?: Int, params?: {}): Promise<Order[]>;
    watchMyTrades(symbol?: Str, since?: Int, limit?: Int, params?: {}): Promise<Trade[]>;
    handleOrder(client: Client, message: any): void;
    handleMyTrade(client: Client, message: any): void;
    handleSubscriptionStatus(client: Client, message: any): any;
    authenticate(params?: {}): any;
    handleAuthenticationMessage(client: Client, message: any): void;
    handleMessage(client: Client, message: any): any;
}
