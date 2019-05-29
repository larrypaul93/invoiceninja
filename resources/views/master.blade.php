<!DOCTYPE html>
<html lang="{{App::getLocale()}}">
<head>
    <!-- Source: https://github.com/invoiceninja/invoiceninja -->
    <!-- Version: {{ NINJA_VERSION }} -->
    <meta charset="utf-8">

    @if (Utils::isWhiteLabel())
        <title>{{ trans('texts.client_portal') }}</title>
       
        <link href="https://www.greaseducks.com/images/mobile_icons/apple-touch-icon.png" rel="apple-touch-icon"/>
        <link href="https://www.greaseducks.com/images/mobile_icons/apple-touch-icon-76x76.png" rel="apple-touch-icon" sizes="76x76"/>
        <link href="https://www.greaseducks.com/images/mobile_icons/apple-touch-icon-120x120.png" rel="apple-touch-icon" sizes="120x120"/>
        <link href="https://www.greaseducks.com/images/mobile_icons/apple-touch-icon-152x152.png" rel="apple-touch-icon" sizes="152x152"/>
        <link href="https://www.greaseducks.com/images/mobile_icons/apple-touch-icon-180x180.png" rel="apple-touch-icon" sizes="180x180"/>
        <link href="https://www.greaseducks.com/images/mobile_icons/icon-hires.png" rel="icon" sizes="192x192"/>
        <link href="https://www.greaseducks.com/images/mobile_icons/icon-normal.png" rel="icon" sizes="128x128"/>
        <link rel="icon" type="image/x-icon" href="https://www.greaseducks.com/images/favicon.ico"/> 
        <meta name="apple-mobile-web-app-title" content="Grease Ducks Billing">
        <meta name="application-name" content="Grease Ducks Billing">
    @else
        <title>{{ isset($title) ? ($title . ' | Grease Ducks Billing') : ('Grease Ducks Billing | ' . trans('texts.app_title')) }}</title>
        <meta name="description" content="{{ isset($description) ? $description : trans('texts.app_description') }}"/>
        <link href="http://www.greaseducks.com/images/favicon.ico" rel="shortcut icon" type="image/png">

        <meta property="og:site_name" content="Grease Ducks Billing"/>
        <meta property="og:url" content="{{ SITE_URL }}"/>
        <meta property="og:title" content="Grease Ducks Billing"/>
        <meta property="og:image" content="{{ SITE_URL }}/images/round_logo.png"/>
        <meta property="og:description" content="Simple, Intuitive Invoicing."/>

        <!-- http://realfavicongenerator.net -->
        <link href="http://www.greaseducks.com/images/mobile_icons/apple-touch-icon.png" rel="apple-touch-icon"/>
        <link href="http://www.greaseducks.com/images/mobile_icons/apple-touch-icon-76x76.png" rel="apple-touch-icon" sizes="76x76"/>
        <link href="http://www.greaseducks.com/images/mobile_icons/apple-touch-icon-120x120.png" rel="apple-touch-icon" sizes="120x120"/>
        <link href="http://www.greaseducks.com/images/mobile_icons/apple-touch-icon-152x152.png" rel="apple-touch-icon" sizes="152x152"/>
        <link href="http://www.greaseducks.com/images/mobile_icons/apple-touch-icon-180x180.png" rel="apple-touch-icon" sizes="180x180"/>
        <link href="http://www.greaseducks.com/images/mobile_icons/icon-hires.png" rel="icon" sizes="192x192"/>
        <link href="http://www.greaseducks.com/images/mobile_icons/icon-normal.png" rel="icon" sizes="128x128"/>
        <link rel="icon" type="image/x-icon" href="images/favicon.ico"/> 
        <meta name="apple-mobile-web-app-title" content="Grease Ducks Billing">
        <meta name="application-name" content="Grease Ducks Billing">
        <meta name="theme-color" content="#ffffff">
    @endif

    <!-- http://stackoverflow.com/questions/19012698/browser-cache-issues-in-laravel-4-application -->
    <meta http-equiv="cache-control" content="max-age=0"/>
    <meta http-equiv="cache-control" content="no-cache"/>
    <meta http-equiv="cache-control" content="no-store"/>
    <meta http-equiv="cache-control" content="must-revalidate"/>
    <meta http-equiv="expires" content="0"/>
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT"/>
    <meta http-equiv="pragma" content="no-cache"/>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="canonical" href="{{ NINJA_APP_URL }}/{{ Request::path() }}"/>

    <script src="{{ asset('built.js') }}?no_cache={{ NINJA_VERSION }}" type="text/javascript"></script>

    <script type="text/javascript">
        var TODAY_DATE = '{{date('M d, Y')}}';
        var NINJA = NINJA || {};
        NINJA.fontSize = 9;
        NINJA.isRegistered = {{ \Utils::isRegistered() ? 'true' : 'false' }};

        window.onerror = function (errorMsg, url, lineNumber, column, error) {
            if (errorMsg.indexOf('Script error.') > -1) {
                return;
            }

            try {
                // Use StackTraceJS to parse the error context
                if (error) {
                    var message = error.message ? error.message : error;
                    StackTrace.fromError(error).then(function (result) {
                        var gps = new StackTraceGPS();
                        gps.findFunctionName(result[0]).then(function (result) {
                            logError(errorMsg + ': ' + JSON.stringify(result));
                        });
                    });
                } else {
                    logError(errorMsg);
                }

                trackEvent('/error', errorMsg);
            } catch (err) {
            }

            return false;
        }

        function logError(message) {
            $.ajax({
                type: 'GET',
                url: '{{ URL::to('log_error') }}',
                data: 'error=' + encodeURIComponent(message) + '&url=' + encodeURIComponent(window.location)
            });
        }

        // http://t4t5.github.io/sweetalert/
        function sweetConfirm(success, text, title) {
            title = title || "{!! trans("texts.are_you_sure") !!}";
            swal({
                //type: "warning",
                //confirmButtonColor: "#DD6B55",
                title: title,
                text: text,
                cancelButtonText: "{!! trans("texts.no") !!}",
                confirmButtonText: "{!! trans("texts.yes") !!}",
                showCancelButton: true,
                closeOnConfirm: false,
                allowOutsideClick: true,
            }).then(function() {
                success();
                swal.close();
            });
        }

        /* Set the defaults for DataTables initialisation */
        $.extend(true, $.fn.dataTable.defaults, {
            "bSortClasses": false,
            "sDom": "t<'row-fluid'<'span6 dt-left'i><'span6 dt-right'p>>l",
            "sPaginationType": "bootstrap",
            "bInfo": true,
            "oLanguage": {
                'sEmptyTable': "{{ trans('texts.empty_table') }}",
                'sLengthMenu': '_MENU_ {{ trans('texts.rows') }}',
                'sSearch': ''
            }
        });

        /* This causes problems with some languages. ie, fr_CA
         var appLocale = '{{App::getLocale()}}';
         */

        @if (env('FACEBOOK_PIXEL'))
        <!-- Facebook Pixel Code -->
        !function (f, b, e, v, n, t, s) {
            if (f.fbq)return;
            n = f.fbq = function () {
                n.callMethod ?
                        n.callMethod.apply(n, arguments) : n.queue.push(arguments)
            };
            if (!f._fbq)f._fbq = n;
            n.push = n;
            n.loaded = !0;
            n.version = '2.0';
            n.queue = [];
            t = b.createElement(e);
            t.async = !0;
            t.src = v;
            s = b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t, s)
        }(window,
                document, 'script', '//connect.facebook.net/en_US/fbevents.js');

        fbq('init', '{{ env('FACEBOOK_PIXEL') }}');
        fbq('track', "PageView");

        (function () {
            var _fbq = window._fbq || (window._fbq = []);
            if (!_fbq.loaded) {
                var fbds = document.createElement('script');
                fbds.async = true;
                fbds.src = '//connect.facebook.net/en_US/fbds.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(fbds, s);
                _fbq.loaded = true;
            }
        })();

        @else
        function fbq() {
            // do nothing
        }
        ;
        @endif

                window._fbq = window._fbq || [];

    </script>


    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    @yield('head')

</head>

<body class="body">

@if (Utils::isNinjaProd() && isset($_ENV['TAG_MANAGER_KEY']) && $_ENV['TAG_MANAGER_KEY'])
    <!-- Google Tag Manager -->
    <noscript>
        <iframe src="//www.googletagmanager.com/ns.html?id={{ $_ENV['TAG_MANAGER_KEY'] }}"
                height="0" width="0" style="display:none;visibility:hidden"></iframe>
    </noscript>
    <script>(function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(), event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                    j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                    '//www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', '{{ $_ENV['TAG_MANAGER_KEY'] }}');</script>
    <!-- End Google Tag Manager -->

    <script>
        function trackEvent(category, action) {
        }
    </script>
@elseif (Utils::isNinjaProd() && isset($_ENV['ANALYTICS_KEY']) && $_ENV['ANALYTICS_KEY'])
    <script>
        (function (i, s, o, g, r, a, m) {
            i['GoogleAnalyticsObject'] = r;
            i[r] = i[r] || function () {
                        (i[r].q = i[r].q || []).push(arguments)
                    }, i[r].l = 1 * new Date();
            a = s.createElement(o),
                    m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            m.parentNode.insertBefore(a, m)
        })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

        ga('create', '{{ $_ENV['ANALYTICS_KEY'] }}', 'auto');
        ga('send', 'pageview');

        function trackEvent(category, action) {
            ga('send', 'event', category, action, this.src);
        }
    </script>
@else
    <script>
        function trackEvent(category, action) {
        }
    </script>
@endif

@yield('body')

<div class="modal fade" id="viodInvoiceModal" tabindex="-1" role="dialog" aria-labelledby="viodInvoiceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h4 class="modal-title" id="myModalLabel">Void Invoice</h4>
      </div>
       {!! Former::open("")->method("POST")->rules(array(
                        'voided_reason' => 'required',
                    )); !!}
      <div class="container" style="width: 100%; padding-bottom: 0px !important">
        <div class="panel panel-default">
            <div class="panel-body">

                <div >
                   
                     {!! Former::text('voided_reason') !!}
                     
                </div>

            

            

            </div>
        </div>

        <div class="modal-footer" id="" style="margin-top: 0px;padding-right:0px">
            <button type="button" class="btn btn-default" id="closeSignUpButton" data-dismiss="modal">Close <i class="glyphicon glyphicon-remove-circle"></i></button>
            <button type="submit" class="btn btn-primary" >Save <i class="glyphicon glyphicon-floppy-disk"></i></button>
        </div>
      </div>
       {!! Former::close() !!}
    </div>
  </div>
</div>
<script type="text/javascript">
    NINJA.formIsChanged = {{ isset($formIsChanged) && $formIsChanged ? 'true' : 'false' }};

    $(function () {
        $('form.warn-on-exit input, form.warn-on-exit textarea, form.warn-on-exit select').change(function () {
            NINJA.formIsChanged = true;
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        @if (Session::has('trackEventCategory') && Session::has('trackEventAction'))
            @if (Session::get('trackEventAction') === '/buy_pro_plan')
                fbq('track', 'Purchase', {value: '{{ session('trackEventAmount') }}', currency: 'USD'});
            @endif
        @endif

        @if (Session::has('onReady'))
        {{ Session::get('onReady') }}
        @endif
    });
    $('form').submit(function () {
        NINJA.formIsChanged = false;
    });
    $(window).on('beforeunload', function () {
        if (NINJA.formIsChanged) {
            return "{{ trans('texts.unsaved_changes') }}";
        } else {
            return undefined;
        }
    });
    function openUrl(url, track) {
        trackEvent('/view_link', track ? track : url);
        window.open(url, '_blank');
    }
</script>

</body>

</html>
