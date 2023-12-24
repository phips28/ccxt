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

def test_currency(exchange, skipped_properties, method, entry):
    format = {
        'id': 'btc',
        'code': 'BTC',
    }
    # todo: remove fee from empty
    empty_allowed_for = ['name', 'fee']
    # todo: info key needs to be added in base, when exchange does not have fetchCurrencies
    is_native = exchange.has['fetchCurrencies'] and exchange.has['fetchCurrencies'] != 'emulated'
    currency_type = exchange.safe_string(entry, 'type')
    if is_native:
        format['info'] = {}
        # todo: 'name': 'Bitcoin', # uppercase string, base currency, 2 or more letters
        # these two fields are being dynamically added a bit below
        # format['withdraw'] = true; # withdraw enabled
        # format['deposit'] = true; # deposit enabled
        format['precision'] = exchange.parse_number('0.0001')  # in case of SIGNIFICANT_DIGITS it will be 4 - number of digits "after the dot"
        format['fee'] = exchange.parse_number('0.001')
        format['networks'] = {}
        format['limits'] = {
            'withdraw': {
                'min': exchange.parse_number('0.01'),
                'max': exchange.parse_number('1000'),
            },
            'deposit': {
                'min': exchange.parse_number('0.01'),
                'max': exchange.parse_number('1000'),
            },
        }
        # todo: format['type'] = 'fiat|crypto'; # after all exchanges have `type` defined, romove "if" check
        if currency_type is not None:
            test_shared_methods.assert_in_array(exchange, skipped_properties, method, entry, 'type', ['fiat', 'crypto', 'other'])
        # only require "deposit" & "withdraw" values, when currency is not fiat, or when it's fiat, but not skipped
        if currency_type == 'crypto' or not ('depositForNonCrypto' in skipped_properties):
            format['deposit'] = True
        if currency_type == 'crypto' or not ('withdrawForNonCrypto' in skipped_properties):
            format['withdraw'] = True
    test_shared_methods.assert_structure(exchange, skipped_properties, method, entry, format, empty_allowed_for)
    test_shared_methods.assert_currency_code(exchange, skipped_properties, method, entry, entry['code'])
    #
    test_shared_methods.check_precision_accuracy(exchange, skipped_properties, method, entry, 'precision')
    test_shared_methods.assert_greater_or_equal(exchange, skipped_properties, method, entry, 'fee', '0')
    if not ('limits' in skipped_properties):
        limits = exchange.safe_value(entry, 'limits', {})
        withdraw_limits = exchange.safe_value(limits, 'withdraw', {})
        deposit_limits = exchange.safe_value(limits, 'deposit', {})
        test_shared_methods.assert_greater_or_equal(exchange, skipped_properties, method, withdraw_limits, 'min', '0')
        test_shared_methods.assert_greater_or_equal(exchange, skipped_properties, method, withdraw_limits, 'max', '0')
        test_shared_methods.assert_greater_or_equal(exchange, skipped_properties, method, deposit_limits, 'min', '0')
        test_shared_methods.assert_greater_or_equal(exchange, skipped_properties, method, deposit_limits, 'max', '0')
        # max should be more than min (withdrawal limits)
        min_string_withdrawal = exchange.safe_string(withdraw_limits, 'min')
        if min_string_withdrawal is not None:
            test_shared_methods.assert_greater_or_equal(exchange, skipped_properties, method, withdraw_limits, 'max', min_string_withdrawal)
        # max should be more than min (deposit limits)
        min_string_deposit = exchange.safe_string(deposit_limits, 'min')
        if min_string_deposit is not None:
            test_shared_methods.assert_greater_or_equal(exchange, skipped_properties, method, deposit_limits, 'max', min_string_deposit)
        # check valid ID & CODE
        test_shared_methods.assert_valid_currency_id_and_code(exchange, skipped_properties, method, entry, entry['id'], entry['code'])
