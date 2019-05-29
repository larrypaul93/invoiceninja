@extends('header')

@section('head')
    @parent

    @include('money_script')

    <style type="text/css">
        .input-group-addon {
            min-width: 40px;
        }
    </style>
@stop

@section('content')

	{!! Former::open($url)
        ->addClass('warn-on-exit main-form')
        ->onsubmit('onFormSubmit(event)')
        ->method($method)
        ->rules(array(
    		'client' => 'required',
    		'invoice' => 'required',
    		'amount' => 'required',
    	)) !!}

    @if ($payment)
        {!! Former::populate($payment) !!}
        {!! Former::populateField('deposited', $payment->present()->deposited) !!}
    @else
        @if ($account->payment_type_id)
            {!! Former::populateField('payment_type_id', $account->payment_type_id) !!}
        @endif
    @endif

    <span style="display:none">
        {!! Former::text('public_id') !!}
        {!! Former::text('action') !!}
        {!! Former::text('data')->data_bind('value: ko.mapping.toJSON(model)') !!}
    </span>

	

            <div class="panel panel-default">
            <div class="panel-body">
                <div class="row">
		            <div class="col-md-6">
                        @if ($payment)
                        {!! Former::plaintext()->label('client')->value($payment->client->present()->link) !!}
                        {!! Former::plaintext()->label('invoice')->value($payment->invoice->present()->link) !!}
                        {!! Former::plaintext()->label('amount')->value($payment->present()->amount) !!}
                        @else
                        {!! Former::select('client')->addOption('', '')->addGroupClass('client-select') !!}
                        {!! Former::select('invoice')->addOption('', '')->addGroupClass('invoice-select') !!}
                        {!! Former::text('amount') !!}

                        @if (isset($paymentTypeId) && $paymentTypeId)
                        {!! Former::populateField('payment_type_id', $paymentTypeId) !!}
                        @endif
                        @endif

                        @if (!$payment || !$payment->account_gateway_id)
                        {!! Former::select('payment_type_id')
                                ->addOption('','')
                                ->fromQuery($paymentTypes, 'name', 'id')
                                ->addGroupClass('payment-type-select') !!}
                        @endif

                        {!! Former::text('payment_date')
                                    ->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT))
                                    ->addGroupClass('payment_date')
                                    ->append('<i class="glyphicon glyphicon-calendar"></i>') !!}
                        {!! Former::text('deposited')->label('Deposited')
                                    ->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT))
                                    ->addGroupClass('payment_deposited')
                                    ->append('<i class="glyphicon glyphicon-calendar"></i>') !!}            
                        {!! Former::text('transaction_reference') !!}

                        @if (!$payment)
                            {!! Former::checkbox('email_receipt')->label('&nbsp;')->text(trans('texts.email_receipt'))->value(1) !!}
                        @endif

                    </div>
                    <div class="col-md-6">
                        {!! Former::textarea('private_notes')->rows($account->hasFeature(FEATURE_DOCUMENTS) ? 6 : 10) !!}
                        {!! Former::textarea('public_notes')->rows( $account->hasFeature(FEATURE_DOCUMENTS) ? 6 : 10) !!}  
                        @if ($account->hasFeature(FEATURE_DOCUMENTS))
                        <div class="form-group">
                            <label for="public_notes" class="control-label col-lg-4 col-sm-4">
                                {{trans('texts.documents')}}
                            </label>
                            <div class="col-lg-8 col-sm-8">
                                <div role="tabpanel" class="tab-pane" id="attached-documents" style="position:relative;z-index:9">
                                    <div id="document-upload">
                                        <div class="dropzone">
                                            <div data-bind="foreach: documents">
                                                <input type="hidden" name="document_ids[]" data-bind="value: public_id"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

		    </div>
	    </div>


	<center class="buttons">
        {!! Button::normal(trans('texts.cancel'))->appendIcon(Icon::create('remove-circle'))->asLinkTo(URL::to('/payments'))->large() !!}
        @if (!$payment || !$payment->is_deleted)
            {!! Button::success(trans('texts.save'))->withAttributes(['id' => 'saveButton'])->appendIcon(Icon::create('floppy-disk'))->submit()->large() !!}
        @endif

        @if ($payment)
            {!! DropdownButton::normal(trans('texts.more_actions'))
                  ->withContents($actions)
                  ->large()
                  ->dropup() !!}
        @endif

	</center>

    @include('partials/refund_payment')

	{!! Former::close() !!}

	<script type="text/javascript">

	
    @if(!$payment)
    var invoices = {!! $invoices !!};
	var clients = {!! $clients !!};
    @else
    var invoices = [];
    var clients = [];
    @endif
	$(function() {

        @if ($payment)
          $('#payment_date').datepicker('update', '{{ $payment->payment_date }}')
          $('#deposited').datepicker('update', '{{ $payment->present()->deposited }}')
          
          @if ($payment->payment_type_id != PAYMENT_TYPE_CREDIT)
            $("#payment_type_id option[value='{{ PAYMENT_TYPE_CREDIT }}']").remove();
          @endif
        @else
          $('#payment_date').datepicker('update', new Date());
          $('#deposited').datepicker('update', new Date());
          
		  populateInvoiceComboboxes({{ $clientPublicId }}, {{ $invoicePublicId }});
        @endif

		$('#payment_type_id').combobox();

        @if (!$payment && !$clientPublicId)
            $('.client-select input.form-control').focus();
        @elseif (!$payment && !$invoicePublicId)
            $('.invoice-select input.form-control').focus();
        @elseif (!$payment)
            $('#amount').focus();
        @endif

        $('.payment_date .input-group-addon').click(function() {
            toggleDatePicker('payment_date');
        });
        $('.payment_deposited .input-group-addon').click(function() {
            toggleDatePicker('deposited');
        });
	});

    function onFormSubmit(event) {
        $('#saveButton').attr('disabled', true);
    }

    function submitAction(action) {
        $('#action').val(action);
        $('.main-form').submit();
    }

    function submitForm_payment(action) {
        submitAction(action);
    }

    function onDeleteClick() {
        sweetConfirm(function() {
            submitAction('delete');
        });
    }

    var ViewModel = function(data) {
                    var self = this;

                    self.documents = ko.observableArray();
                    self.mapping = {
                        'documents': {
                            create: function(options) {
                                return new DocumentModel(options.data);
                            }
                        }
                    }

                    if (data) {
                        ko.mapping.fromJS(data, self.mapping, this);
                    }



                    self.addDocument = function() {
                        var documentModel = new DocumentModel();
                        self.documents.push(documentModel);
                        return documentModel;
                    }

                    self.removeDocument = function(doc) {
                        var public_id = doc.public_id?doc.public_id():doc;
                        self.documents.remove(function(document) {
                            return document.public_id() == public_id;
                        });
                    }
                };
        function DocumentModel(data) {
            var self = this;
            self.public_id = ko.observable(0);
            self.size = ko.observable(0);
            self.name = ko.observable('');
            self.type = ko.observable('');
            self.url = ko.observable('');

            self.update = function(data){
                ko.mapping.fromJS(data, {}, this);
            }

            if (data) {
                self.update(data);
            }
        }

        function addDocument(file) {
            file.index = model.documents().length;
            model.addDocument({name:file.name, size:file.size, type:file.type});
    	}

    	function addedDocument(file, response) {
            model.documents()[file.index].update(response.document);
    	}

    	function deleteDocument(file) {
            model.removeDocument(file.public_id);
    	}

        
                // otherwise create blank model
        window.model = new ViewModel({!! $payment !!});
       
        ko.applyBindings(model);

        @if (Auth::user()->account->hasFeature(FEATURE_DOCUMENTS))
                    $('.main-form').submit(function(){
                        if($('#document-upload .fallback input').val())$(this).attr('enctype', 'multipart/form-data')
                        else $(this).removeAttr('enctype')
                    })

                    @include('partials.dropzone', ['documentSource' => 'model.documents()'])
        @endif

	</script>

@stop
