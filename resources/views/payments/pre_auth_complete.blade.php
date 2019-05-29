@extends('header')

@section('head')
    @parent

    

@stop

@section('content')

    @include('payments.payment_css')

    <div class="container">
        <p>&nbsp;</p>

        <div class="panel panel-default">
            <div class="panel-body">
         <div>

                     {!! Former::vertical_open()
            ->autocomplete('on')
            ->addClass('payment-form')
            ->id('payment-form')
            ->rules(array(
                
                'authorize_ach' => 'required'
                
            )) !!}


   
    

    

   


    
    <div class="row">
        <div class="col-md-12">
            {{ Former::populateField('amount', ($payment->amount > $payment->invoice->balance)?$payment->invoice->balance:$payment->amount ) }}
            {!! Former::text('amount')
                        ->placeholder(trans('texts.pre_auth_complete'))
                        ->label(trans('texts.pre_auth_complete')) !!}
        </div>
    </div>
    <div class="col-md-12">
        <div id="js-error-message" style="display:none" class="alert alert-danger"></div>
    </div>

    <p>&nbsp;</p>
    <center>
        {!! Button::success(strtoupper(trans('texts.pre_auth_completion_now')   ))
                            ->submit()
                            ->large() !!}
                            
        @if (isset($invitation))
            {!! Button::normal(strtoupper(trans('texts.cancel')))->large()->asLinkTo("/invoices") !!}
            &nbsp;&nbsp;
        @endif
        
    </center>
    <p>&nbsp;</p>

    {!! Former::close() !!}

                </div>

            </div>

            </div>
        </div>


        <p>&nbsp;</p>
        <p>&nbsp;</p>

    </div>



    <script type="text/javascript">

        $(function() {
            $('select').change(function() {
                $(this).css({color:'#444444'});
            });

            $('#country_id').combobox();
            $('#currency_id').combobox();
            $('#first_name').focus();
        });

    </script>


@stop

