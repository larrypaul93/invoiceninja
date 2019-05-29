
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, maximum-scale=1">
    <title>{{ ($entityType == "invoice")? "Invoice":"Quote"}} from GREASE DUCKS LTD</title>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,400italic,700&subset=latin,cyrillic" rel="stylesheet" type="text/css">
    <style media="all" type="text/css">
        *
        {
            -webkit-text-size-adjust:none;
            -ms-text-size-adjust:none
        }
        #outlook a
        {
            padding:0
        }
        .ReadMsgBody,.ExternalClass
        {
            width:100%
        }
        .ExternalClass span,.ExternalClass font,.ExternalClass td,.ExternalClass div,.ExternalClass a,.ExternalClass p,.ExternalClass br
        {
            line-height:inherit
        }
        .ExternalClass p
        {
            Margin-top:0!important;
            Margin-bottom:0!important
        }
        .ecxicon,.ecximage,.ecxlogo,.ecxfull-image
        {
            line-height:0
        }
        html
        {
            width:100%
        }
        body
        {
            width:100%;
            height:100%;
            margin:0;
            padding:0;
            -webkit-font-smoothing:antialiased
        }
        .yshortcuts a
        {
            border-bottom:none!important
        }
        p
        {
            margin:0
        }
        .full-image img
        {
            width:100%!important
        }
        .sp-tablet
        {
            height:70px!important;
            line-height:70px!important
        }
        @media (min-width: 641px) and (max-width: 768px) {
            table[class="main-table"],table[class="table-wrap"]
            {
                width:100%!important;
                clear:both;
                min-width:0!important
            }
            td[class~="image"] img
            {
                width:100%!important;
                height:auto!important
            }
            td[class~="sp-tablet"]
            {
                height:45px!important;
                line-height:45px!important
            }
        }
    </style>
    <style type="text/css">
        @media only screen and (max-width: 640px) {
            table[class="main-table"],table[class="table-wrap"]
            {
                width:100%!important;
                clear:both;
                min-width:0!important
            }
            table[class="container"]
            {
                width:320px!important;
                min-width:0!important
            }
            th[class~="m-cell"]
            {
                display:block!important;
                float:left!important;
                width:100%!important;
                max-width:none!important;
                min-width:0!important
            }
            table[class*="m-w100"],td[class*="m-w100"]
            {
                width:100%!important;
                max-width:none!important;
                min-width:0!important
            }
            table[class="inner"]
            {
                width:200px!important
            }
            td[class~="img-bg"],td[class~="header"]
            {
                background-size:cover
            }
            td[class~="sp-tablet"]
            {
                height:70px!important;
                line-height:70px!important
            }
            table[class="nav"]
            {
                width:100%!important;
                clear:both;
                text-align:center
            }
            table[class="nav"] .text
            {
                width:31%
            }
            td[class~="h1"]
            {
                font-size:38px!important
            }
            td[class~="h2"],td[class~="h4"]
            {
                font-size:32px!important
            }
            td[class="h80"]
            {
                height:80px!important
            }
            td[class~="h0"],th[class~="h0"]
            {
                display:none!important
            }
            table[class="btn"] td,table[class="btn-1"] td,table[class="btn-2"] td
            {
                padding:0!important
            }
            table[class="btn"] td a
            {
                display:block;
                padding:13px 25px
            }
            table[class="btn-1"] td a,table[class="btn-2"] td a
            {
                display:block;
                padding:14px 25px
            }
            td[class~="image"] img
            {
                width:100%!important;
                height:auto!important
            }
            table[class="footer"] .text
            {
                text-align:center!important
            }
        }
        @media only screen and (max-width : 380px) {
            table[class="container"]
            {
                width:280px!important
            }
        }

        v\:* { behavior: url(#default#VML); display: inline-block; }
    </style>
    <!--[if gte mso 9]>   <style type="text/css">     body, table, tr, td, h1, h2, h3, h4, h5, h6, ul, li, ol, dl, dd, dt {       font-family: Helvetica, Arial, sans-serif !important;     }     .date {       line-height: 1 !important;     }   </style>   <![endif]-->
    <!--[if gte mso 9]><xml>   <o:OfficeDocumentSettings>     <o:AllowPNG/>     <o:PixelsPerInch>96</o:PixelsPerInch>   </o:OfficeDocumentSettings> </xml><![endif]-->
    
</head>
<body style="-webkit-text-size-adjust: none; margin: 0; padding: 0; font-family: 'Open Sans', Arial, sans-serif; background-color: #e6e6e6; color: #5d5d5d;">
@include('emails.partials.client_view_action')
{!! $body !!}
</body>
</html>