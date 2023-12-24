// ----------------------------------------------------------------------------

// PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
// https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code
// EDIT THE CORRESPONDENT .ts FILE INSTEAD

import assert from 'assert';
import testTrade from '../../../test/Exchange/base/test.trade.js';
import testSharedMethods from '../../../test/Exchange/base/test.sharedMethods.js';
async function testWatchMyTrades(exchange, skippedProperties, symbol) {
    const method = 'watchMyTrades';
    let now = exchange.milliseconds();
    const ends = now + 15000;
    while (now < ends) {
        let response = undefined;
        try {
            const response = await exchange.watchMyTrades(symbol);
        }
        catch (e) {
            if (!testSharedMethods.isTemporaryFailure(e)) {
                throw e;
            }
            now = exchange.milliseconds();
            continue;
        }
        assert(Array.isArray(response), exchange.id + ' ' + method + ' ' + symbol + ' must return an array. ' + exchange.json(response));
        now = exchange.milliseconds();
        for (let i = 0; i < response.length; i++) {
            testTrade(exchange, skippedProperties, method, response[i], symbol, now);
        }
        testSharedMethods.assertTimestampOrder(exchange, method, symbol, response);
    }
}
export default testWatchMyTrades;
