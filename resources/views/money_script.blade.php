<script type="text/javascript">

    var currencies = {!! \Cache::get('currencies') !!};
    var currencyMap = {};
    for (var i=0; i<currencies.length; i++) {
        var currency = currencies[i];
        currencyMap[currency.id] = currency;
        currencyMap[currency.code] = currency;
    }

    var countries = {!! \Cache::get('countries') !!};
    var countryMap = {};
    for (var i=0; i<countries.length; i++) {
        var country = countries[i];
        countryMap[country.id] = country;
    }

    var NINJA = NINJA || {};
    @if (Auth::check())
    NINJA.primaryColor = "{{ Auth::user()->account->primary_color }}";
    NINJA.secondaryColor = "{{ Auth::user()->account->secondary_color }}";
    NINJA.fontSize = {{ Auth::user()->account->font_size ?: DEFAULT_FONT_SIZE }};
    NINJA.headerFont = {!! json_encode(Auth::user()->account->getHeaderFontName()) !!};
    NINJA.bodyFont = {!! json_encode(Auth::user()->account->getBodyFontName()) !!};
    @else
    NINJA.fontSize = {{ DEFAULT_FONT_SIZE }};
    @endif

    NINJA.parseFloat = function(str) {
        if (! str) {
            return '';
        } else {
            str = str + '';
        }

        // check for comma as decimal separator
        if (str.match(/,[\d]{1,2}$/)) {
            str = str.replace(',', '.');
        }

        str = str.replace(/[^0-9\.\-]/g, '');

        return window.parseFloat(str);
    }

    function formatMoneyInvoice(value, invoice, decorator, precision) {
        var account = invoice.account;
        var client = invoice.client;

        return formatMoneyAccount(value, account, client, decorator, precision);
    }

    function formatMoneyAccount(value, account, client, decorator, precision) {
        var currencyId = false;
        var countryId = false;

        if (client && client.currency_id) {
            currencyId = client.currency_id;
        } else if (account && account.currency_id) {
            currencyId = account.currency_id;
        }

        if (client && client.country_id) {
            countryId = client.country_id;
        } else if (account && account.country_id) {
            countryId = account.country_id;
        }

        if (account && ! decorator) {
            decorator = parseInt(account.show_currency_code) ? 'code' : 'symbol';
        }

        return formatMoney(value, currencyId, countryId, decorator, precision)
    }

    function formatAmount(value, currencyId, precision) {
        if (!value) {
            return '';
        }

        if (!currencyId) {
            currencyId = {{ Session::get(SESSION_CURRENCY, DEFAULT_CURRENCY) }};
        }

        if (!precision) {
            precision = 2;
        }

        var currency = currencyMap[currencyId];
        var decimal = currency.decimal_separator;

        value = roundToPrecision(NINJA.parseFloat(value), precision) + '';

        if (decimal == '.') {
            return value;
        } else {
            return value.replace('.', decimal);
        }
    }
    function formatMoney(value, currencyId, countryId, decorator) {
        value = NINJA.parseFloat(value);

        if (!currencyId) {
            currencyId = {{ Session::get(SESSION_CURRENCY, DEFAULT_CURRENCY) }};
        }

        if (!decorator) {
            decorator = '{{ Session::get(SESSION_CURRENCY_DECORATOR, CURRENCY_DECORATOR_SYMBOL) }}';
        }

        var currency = currencyMap[currencyId];
        var precision = currency.precision;
        var thousand = currency.thousand_separator;
        var decimal = currency.decimal_separator;
        var code = currency.code;
        var swapSymbol = currency.swap_currency_symbol;

        if (countryId && currencyId == {{ CURRENCY_EURO }}) {
            var country = countryMap[countryId];
            swapSymbol = country.swap_currency_symbol;
            if (country.thousand_separator) {
                thousand = country.thousand_separator;
            }
            if (country.decimal_separator) {
                decimal = country.decimal_separator;
            }
        }

        var is_negative = false;
        if(value < 0) {
            is_negative = true;
            value = -value;
        }

        value = accounting.formatMoney(value, '', precision, thousand, decimal);
        var symbol = currency.symbol;

        if (decorator == 'none') {
            value;
        } else if (decorator == '{{ CURRENCY_DECORATOR_CODE }}' || ! symbol) {
            value = value + ' ' + code;
        } else if (swapSymbol) {
            value = value + ' ' + symbol.trim();
        } else {
            value = symbol + value;
        }
        if(is_negative) value = "-"+value;
        return value;
    }

</script>
