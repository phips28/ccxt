import os
import sys

root = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))))
sys.path.append(root)

# ----------------------------------------------------------------------------

# PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
# https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

# ----------------------------------------------------------------------------
# -*- coding: utf-8 -*-

from ccxt.test.base import test_shared_methods  # noqa E402
from ccxt.test.base import test_position  # noqa E402

async def test_fetch_positions(exchange, skipped_properties, symbol):
    method = 'fetchPositions'
    now = exchange.milliseconds()
    # without symbol
    positions = await exchange.fetch_positions()
    assert isinstance(positions, list), exchange.id + ' ' + method + ' must return an array, returned ' + exchange.json(positions)
    for i in range(0, len(positions)):
        test_position(exchange, skipped_properties, method, positions[i], None, now)
    # testSharedMethods.assertTimestampOrder (exchange, method, undefined, positions); # currently order of positions does not make sense
    # with symbol
    positions_for_symbol = await exchange.fetch_positions([symbol])
    assert isinstance(positions_for_symbol, list), exchange.id + ' ' + method + ' must return an array, returned ' + exchange.json(positions_for_symbol)
    positions_for_symbol_length = len(positions_for_symbol)
    assert positions_for_symbol_length <= 4, exchange.id + ' ' + method + ' positions length for particular symbol should be less than 4, returned ' + exchange.json(positions_for_symbol)
    for i in range(0, len(positions_for_symbol)):
        test_position(exchange, skipped_properties, method, positions_for_symbol[i], symbol, now)
