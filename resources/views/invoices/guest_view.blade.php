@extends('public.header')

@section('head')
	@parent

		@include('money_script')

		@foreach ($invoice->client->account->getFontFolders() as $font)
        	<script src="{{ asset('js/vfs_fonts/'.$font.'.js') }}" type="text/javascript"></script>
    	@endforeach

        <script src="{{ asset('pdf.built.js') }}?no_cache={{ NINJA_VERSION }}" type="text/javascript"></script>

		@if ($account->showSignature($invoice))
			<script src="{{ asset('js/jSignature.min.js') }}"></script>
		@endif

		<style type="text/css">
			body {
				background-color: #f8f8f8;
			}


            .dropdown-menu li a{
                overflow:hidden;
                margin-top:5px;
                margin-bottom:5px;
            }

			#signature {
		        border: 2px dotted black;
		        background-color:lightgrey;
		    }
		</style>

    
@stop

@section('content')

	<div class="container">

        @if (!empty($partialView))
            @include($partialView)
        @else
            <div id="paymentButtons" class="pull-right" style="text-align:right">
                {!! Button::normal('Print PDF')
                        ->withAttributes(['onclick' => 'printPDF()', 'id' => 'printPdfButton'])
                        ->appendIcon(Icon::create('print'))->large() !!} 
                <script type="text/javascript">
                    function printPDF(){
                        $("body").addClass("pdf-print");
                        window.print();
                        $("body").removeClass("pdf-print");
                    }
                </script>        
            @if ($invoice->isQuote())
                {!! Button::normal(trans('texts.download_pdf'))->withAttributes(['onclick' => 'onDownloadClick()'])->large() !!}&nbsp;&nbsp;
                
			@elseif ( ! $invoice->canBePaid())
				{!! Button::normal(trans('texts.download_pdf'))->withAttributes(['onclick' => 'onDownloadClick()'])->large() !!}
    		@elseif ($invoice->client->account->isGatewayConfigured() && floatval($invoice->balance) && !$invoice->is_recurring)
                {!! Button::normal(trans('texts.download_pdf'))->withAttributes(['onclick' => 'onDownloadClick()'])->large() !!}&nbsp;&nbsp;
                @if (count($paymentTypes) > 1)
                    {!! DropdownButton::success(trans('texts.pay_now'))->withContents($paymentTypes)->large() !!}
                @elseif (count($paymentTypes) == 1)
                    <a href='{!! $paymentURL !!}' class="btn btn-success btn-lg">{{ trans('texts.pay_now') }} {{ $invoice->present()->gatewayFee($gatewayTypeId) }}</a>
                @endif
    		@else
    			{!! Button::normal(trans('texts.download_pdf'))->withAttributes(['onclick' => 'onDownloadClick()'])->large() !!}
    		@endif

			@if ($account->isNinjaAccount())
				{!! Button::primary(trans('texts.return_to_app'))->asLinkTo(URL::to('/settings/account_management'))->large() !!}
			@endif
    		</div>
        @endif

        <div class="pull-left">
            @if(!empty($documentsZipURL))
                {!! Button::normal(trans('texts.download_documents', array('size'=>Form::human_filesize($documentsZipSize))))->asLinkTo($documentsZipURL)->large() !!}
            @endif
        </div>

		<div class="clearfix"></div><p>&nbsp;</p>
        
        @if ($account->hasFeature(FEATURE_DOCUMENTS) && $account->invoice_embed_documents)
            @foreach ($invoice->documents as $document)
                @if($document->isPDFEmbeddable())
                    <script src="{{ $document->getClientVFSJSUrl() }}" type="text/javascript" async></script>
                @endif
            @endforeach
            @foreach ($invoice->expenses as $expense)
                @foreach ($expense->documents as $document)
                    @if($document->isPDFEmbeddable())
                        <script src="{{ $document->getClientVFSJSUrl() }}" type="text/javascript" async></script>
                    @endif
                @endforeach
            @endforeach
        @endif
		<script type="text/javascript">

			window.invoice = {!! $invoice->toJson() !!};
			invoice.features = {
                customize_invoice_design:{{ $invoice->client->account->hasFeature(FEATURE_CUSTOMIZE_INVOICE_DESIGN) ? 'true' : 'false' }},
                remove_created_by:{{ $invoice->client->account->hasFeature(FEATURE_REMOVE_CREATED_BY) ? 'true' : 'false' }},
                invoice_settings:{{ $invoice->client->account->hasFeature(FEATURE_INVOICE_SETTINGS) ? 'true' : 'false' }}
            };
			invoice.is_quote = {{ $invoice->isQuote() ? 'true' : 'false' }};
			invoice.contact = {!! $contact->toJson() !!};

			function getPDFString(cb) {
    	  	    return generatePDF(invoice, invoice.invoice_design.javascript, true, cb);
			}

            if (window.hasOwnProperty('pjsc_meta')) {
                window['pjsc_meta'].remainingTasks++;
            }

			$(function() {
                @if (Input::has('phantomjs'))
                    doc = getPDFString();
                    doc.getDataUrl(function(pdfString) {
                        document.write(pdfString);
                        document.close();

                        if (window.hasOwnProperty('pjsc_meta')) {
                            window['pjsc_meta'].remainingTasks--;
                        }
                    });
                @else
                    refreshPDF();
                @endif

				@if ($account->requiresAuthorization($invoice))
					$('#paymentButtons a').on('click', function(e) {
						e.preventDefault();
						window.pendingPaymentHref = $(this).attr('href');
						@if ($account->showSignature($invoice))
							if (window.pendingPaymentInit) {
								$("#signature").jSignature('reset');
							}
						@endif
						@if ($account->showAcceptTerms($invoice))
							$('#termsCheckbox').attr('checked', false);
						@endif
						$('#authenticationModal').modal('show');
					});

					@if ($account->showSignature($invoice))
						$('#authenticationModal').on('shown.bs.modal', function () {
							if ( ! window.pendingPaymentInit) {
								window.pendingPaymentInit = true;
								$("#signature").jSignature().bind('change', function(e) {
									setModalPayNowEnabled();
								});;
							}
						});
					@endif
				@endif
			});

			function onDownloadClick() {
				var doc = generatePDF(invoice, invoice.invoice_design.javascript, true);
                var fileName = invoice.is_quote ? invoiceLabels.quote : invoiceLabels.invoice;
				doc.save(fileName + '-' + invoice.invoice_number + '.pdf');
			}

            function showCustomModal() {
                $('#customGatewayModal').modal('show');
            }

			
			function redirectToPayment() {
				$('#authenticationModal').modal('hide');
				location.href = window.pendingPaymentHref;
			}

			function setModalPayNowEnabled() {
				var disabled = false;

				@if ($account->showAcceptTerms($invoice))
					if ( ! $('#termsCheckbox').is(':checked')) {
						disabled = true;
					}
				@endif

				@if ($account->showSignature($invoice))
					if ( ! $('#signature').jSignature('isModified')) {
						disabled = true;
					}
				@endif

				$('#modalPayNowButton').attr('disabled', disabled);
			}


		</script>

		@include('invoices.pdf', ['account' => $invoice->client->account])

		<p>&nbsp;</p>

	</div>


    @if (isset($customGatewayName))
        <div class="modal fade" id="customGatewayModal" tabindex="-1" role="dialog" aria-labelledby="customGatewayModalLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ $customGatewayName }}</h4>
              </div>

             <div class="panel-body">
                  {!! nl2br(e($customGatewayText)) !!}
              </div>

              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('texts.close') }}</button>
              </div>
            </div>
          </div>
        </div>
    @endif

	@if ($account->requiresAuthorization($invoice))
		<div class="modal fade" id="authenticationModal" tabindex="-1" role="dialog" aria-labelledby="authenticationModalLabel" aria-hidden="true">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				<h4 class="modal-title">{{ trans('texts.authorization') }}</h4>
			  </div>

			 <div class="panel-body">
				 @if ($invoice->terms)
					 <div class="well" style="max-height:300px;overflow-y:scroll">
						 {!! nl2br(e($invoice->terms)) !!}
					 </div>
				 @endif
				 @if ($account->showSignature($invoice))
				 	<div>
						{{ trans('texts.sign_here') }}
					</div>
				 	<div id="signature"></div><br/>
				 @endif
			  </div>

			  <div class="modal-footer">
				 @if ($account->showAcceptTerms($invoice))
 					<div class="pull-left">
 						<label for="termsCheckbox" style="font-weight:normal">
 							<input id="termsCheckbox" type="checkbox" onclick="setModalPayNowEnabled()"/>
 							&nbsp;{{ trans('texts.i_agree') }}
 						</label>
 					</div>
 				 @endif
				<button id="modalPayNowButton" type="button" class="btn btn-success" onclick="onModalPayNowClick()" disabled="">
					{{ $invoice->isQuote() ? trans('texts.approve') : trans('texts.pay_now') }}
				</button>
			  </div>
			</div>
		  </div>
		</div>
	@endif

@stop
