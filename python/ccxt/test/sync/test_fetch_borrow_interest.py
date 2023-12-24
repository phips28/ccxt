import os
import sys

root = os.path.dirname(os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__)))))
sys.path.append(root)

# ----------------------------------------------------------------------------

# PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
# https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

# ----------------------------------------------------------------------------
# -*- coding: utf-8 -*-

from ccxt.test.base import test_borrow_interest  # noqa E402

def test_fetch_borrow_interest(exchange, skipped_properties, code, symbol):
    method = 'fetchBorrowInterest'
    borrow_interest = exchange.fetch_borrow_interest(code, symbol)
    assert isinstance(borrow_interest, list), exchange.id + ' ' + method + ' ' + code + ' must return an array. ' + exchange.json(borrow_interest)
    for i in range(0, len(borrow_interest)):
        test_borrow_interest(exchange, skipped_properties, method, borrow_interest[i], code, symbol)
