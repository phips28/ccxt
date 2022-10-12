
//  ---------------------------------------------------------------------------

import ftx from './ftx.js';

// ---------------------------------------------------------------------------

export default class ftxus extends ftx {
    describe () {
        return this.deepExtend (super.describe (), {
            'id': 'ftxus',
            'name': 'FTX.us',
            'countries': [ 'US' ],
            'hostname': 'ftx.us',
            'has': {
                'future': false,
            },
            'urls': {
                'logo': 'https://user-images.githubusercontent.com/1294454/141506670-12f6115f-f425-4cd8-b892-b51d157ca01f.jpg',
                'www': 'https://ftx.us/',
                'docs': 'https://docs.ftx.us/',
                'fees': 'https://help.ftx.us/hc/en-us/articles/360043579273-Fees',
            },
        });
    }
}
