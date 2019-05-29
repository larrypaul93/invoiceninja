@extends('header')

@section('head')
	@parent

    @include('money_script')

    @foreach ($account->getFontFolders() as $font)
        <script src="{{ asset('js/vfs_fonts/'.$font.'.js') }}" type="text/javascript"></script>
    @endforeach
	<script src="{{ asset('pdf.built.js') }}?no_cache={{ NINJA_VERSION }}" type="text/javascript"></script>
    <script src="{{ asset('js/lightbox.min.js') }}" type="text/javascript"></script>
    <link href="{{ asset('css/lightbox.css') }}" rel="stylesheet" type="text/css"/>
	<link href="{{ asset('css/quill.snow.css') }}" rel="stylesheet" type="text/css"/>
	<link href="{{ asset('css/select2.css') }}" rel="stylesheet" type="text/css"/>
	<script src="{{ asset('js/quill.min.js') }}" type="text/javascript"></script>
	<script src="{{ asset('js/select2.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('js/tinymce/tinymce.min.js')}}"></script>
    <script src="{{ asset('js/daterangepicker.min.js') }}" type="text/javascript"></script>
    
	<script src="{{ asset('js/jSignature.min.js') }}"></script>
	

    <style type="text/css">
        select.tax-select {
            width: 50%;
            float: left;
        }

        #scrollable-dropdown-menu .tt-menu {
            max-height: 150px;
            overflow-y: auto;
            overflow-x: hidden;
			min-width: 500px;
        }
        #scrollable-dropdown-menu-1 .tt-menu {
            max-height: 150px;
            overflow-y: auto;
            overflow-x: hidden;

        }

		.signature-wrapper .tooltip-inner {
			width: 600px;
			max-width: 600px;
			padding: 20px;
		}

    </style>
@stop

@section('content')
    @if ($errors->first('invoice_items'))
        <div class="alert alert-danger">{{ trans($errors->first('invoice_items')) }}</div>
    @endif

	@if ($invoice->id)
		<ol class="breadcrumb">
		@if ($invoice->is_recurring)
			<li>{!! link_to('invoices', trans('texts.recurring_invoice')) !!}</li>
		@else
			<li>{!! link_to(($entityType == ENTITY_QUOTE ? 'quotes' : 'invoices'), trans('texts.' . ($entityType == ENTITY_QUOTE ? 'quotes' : 'invoices'))) !!}</li>
			<li class="active">{{ $invoice->invoice_number }}</li>
		@endif
		@if ($invoice->is_recurring && $invoice->isSent() && (! $invoice->last_sent_date || $invoice->last_sent_date == '0000-00-00'))
			{!! $invoice->present()->statusLabel(trans('texts.pending')) !!}
		@else
			{!! $invoice->present()->statusLabel !!}
		@endif
        <span>{{$invoice->voided_reason?"(".$invoice->voided_reason.")":""}}</span>
		</ol>
	@endif

	{!! Former::open($url)
            ->method($method)
            ->addClass('warn-on-exit main-form')
            ->autocomplete('off')
            ->onsubmit('return onFormSubmit(event)')
            ->rules(array(
        		'client' => 'required',
                'invoice_number' => 'required',
                'invoice_date' => 'required',
        		'product_key' => 'max:255'
        	)) !!}

    @include('partials.autocomplete_fix')

	<input type="submit" style="display:none" name="submitButton" id="submitButton">

	<div data-bind="with: invoice">
    <div class="panel panel-default">
    <div class="panel-body">

    <div class="row" style="min-height:195px" onkeypress="formEnterClick(event)">
    	<div class="col-md-4" id="col_1">

    		@if ($invoice->id || $data || $invoice->client->public_id)
                @if(!$showBreadcrumbs && !Auth::user()->is_admin)
                    <div class="form-group">
                        <label for="client" class="control-label col-lg-4 col-sm-4"><b>{{ trans('texts.client') }}</b></label>
                        <div class="col-lg-8 col-sm-8">
                            <h4>
                                @can('view', $invoice->client)
                                <a href="{{url('/clients/'.$invoice->client->public_id)}}" target="_blank"><span data-bind="text: getClientDisplayNameSuffix(ko.toJS(client()))"></span></a>
                                @else
                                    <span data-bind="text: getClientDisplayNameSuffix(ko.toJS(client()))"></span>
                                @endcan
                            
                                @if ($invoice->client->is_deleted)
                                    &nbsp;&nbsp;<div class="label label-danger">{{ trans('texts.deleted') }}</div>
                                @endif
                            </h4>

                            


                        </div>
                    </div>
                @endif

                
				<div style="">
    		@endif
				@if(!$invoice->id && !$invoice->client->public_id)
            		{!! Former::select('client')->addOption('', '')->data_bind("select2: {ajax:{url: '/api/clients-json',dataType: 'json',delay: 250,processResults:LoadClients,cache: true},minimumInputLength: 1 }")->addClass('')->addGroupClass('') !!}
					{!! Former::select('contact')->addOption('', '')->addClass('')->addGroupClass('') !!}
				@else
                    @if($invoice->client->public_id && ($showBreadcrumbs || Auth::user()->is_admin))
                        {{Former::populateField("client",$invoice->client->public_id)}}
                        {!! Former::select('client')->addOption('', '')
                        ->fromQuery([$invoice->client],function($client){ return $client->getDisplayName();},"public_id")
                        ->data_bind("select2: {ajax:{url: '/api/clients-json',dataType: 'json',delay: 250,processResults:LoadClients,cache: true},minimumInputLength: 1 }")->addClass('')->addGroupClass('') !!}
                     @else   
                        <input type="hidden" name="client" id="client" value="{{$invoice->client->public_id}}">
					@endif
                    	{!! Former::select('contact')->addOption('', '')->addClass('')->addGroupClass('') !!}

				@endif

			{{--<div class="form-group" style="margin-bottom: 8px">
				<div class="col-lg-8 col-sm-8 col-lg-offset-4 col-sm-offset-4">
					<a id="createClientLink" class="pointer" data-bind="click: $root.showClientForm, html: $root.clientLinkText"></a>
                    <span data-bind="visible: $root.invoice().client().public_id() > 0" style="display:none">|
                        <a data-bind="attr: {href: '{{ url('/clients') }}/' + $root.invoice().client().public_id()}" target="_blank">{{ trans('texts.view_client') }}</a>
                    </span>
				</div>
			</div>--}}

			@if ($invoice->id || $data || $invoice->client->public_id)
				</div>
			@endif

			{{--<div data-bind="with: client" class="invoice-contact">
				<div style="display:none" class="form-group" data-bind="visible: contacts().length > 0, foreach: contacts">
					<div class="col-lg-8 col-lg-offset-4 col-sm-offset-4">
						<label class="checkbox" data-bind="attr: {for: $index() + '_check'}, visible: email.display" onclick="refreshPDF(true)">
                            <input type="hidden" value="0" data-bind="attr: {name: 'client[contacts][' + $index() + '][send_invoice]'}">
                            
							<input type="checkbox" value="1" data-bind="visible: email() || first_name() || last_name(), checked: send_invoice, attr: {id: $index() + '_check', name: 'client[contacts][' + $index() + '][send_invoice]'}">
							<span data-bind="html: email.display"></span>
                        </label>
                        @if ( ! $invoice->is_deleted && ! $invoice->client->is_deleted)
                        <span data-bind="visible: !$root.invoice().is_recurring()">
                            <span data-bind="html: $data.view_as_recipient"></span>&nbsp;&nbsp;
                            @if (Utils::isConfirmed())
	                            <span style="vertical-align:text-top;color:red" class="fa fa-exclamation-triangle"
	                                    data-bind="visible: $data.email_error, tooltip: {title: $data.email_error}"></span>
	                            <span style="vertical-align:text-top;padding-top:2px" class="fa fa-info-circle"
	                                    data-bind="visible: $data.invitation_status, tooltip: {title: $data.invitation_status, html: true},
	                                    style: {color: $data.info_color}"></span>
								<span class="signature-wrapper">&nbsp;
								<span style="vertical-align:text-top;color:#888" class="fa fa-user"
	                                    data-bind="visible: $data.invitation_signature_svg, tooltip: {title: $data.invitation_signature_svg, html: true}"></span>
								</span>
                            @endif
                        </span>
                        @endif
					</div>
				</div>
			</div>--}}
           @if(!$invoice->id && !$data && !$invoice->client->public_id)
                {{Former::populateField("user_id",Auth::user()->id)}}
           @else
                {{Former::populateField("user_id",$invoice->user_id)}}
           @endif

            @if(Auth::user()->hasPermission("edit_all"))
            
				{!! Former::select('user_id')->label("Account Manger")->fromQuery($accountManger,function($model){return $model->name;},"id") !!}
			@endif
            {!! Former::populateField("tags",explode(",",trim($invoice->tags,","))) !!}
            @if(Auth::user()->hasPermission("edit_all"))
                {!! Former::select('tags')->label('Tags')->type("multiselect")->fromQuery($accountManger,function($model){return $model->name;},"id") !!}
            @endif
            {!! Former::select('visible_to')->label('View Invoice')->options([1=>"Both",2=>"Account",3=>"Association"])!!}
            {!! Former::populateField('attach_pdf',$invoice->attach_pdf) !!}
            {!! Former::populateField('cc_email',$invoice->cc_email) !!}
            {!! Former::populateField('interest',$invoice->interest) !!}
            {!! Former::populateField('work_number',$invoice->work_number) !!}
            
           
            {!! Former::text('cc_email')->label("CC Email") !!}
            
           
            <input type="hidden" name="user_signature" value="" id="user_signature">
            <input type="hidden" name="print_name" value="" id="print_name">  

		</div>
		<div class="col-md-4" id="col_2">
            
			<div data-bind="visible: !is_recurring()">
				{!! Former::text('invoice_date')->data_bind("datePicker: invoice_date, valueUpdate: 'afterkeydown'")->label(trans("texts.{$entityType}_date"))
							->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT, DEFAULT_DATE_PICKER_FORMAT))->appendIcon('calendar')->addGroupClass('invoice_date') !!}
				{!! Former::text('due_date')->data_bind("datePicker2: due_date, valueUpdate: 'afterkeydown'")->label(trans("texts.{$entityType}_due_date"))
							->placeholder($invoice->exists || $invoice->isQuote() ? ' ' : $account->present()->dueDatePlaceholder())
							->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT, DEFAULT_DATE_PICKER_FORMAT))->appendIcon('calendar')->addGroupClass('due_date') !!}

				{!! Former::text('partial')->data_bind("value: partial, valueUpdate: 'afterkeydown'")->onkeyup('onPartialChange()')
							->addGroupClass('partial')!!}
			</div>

            {!! Former::text('discount')->data_bind("value: discount, valueUpdate: 'afterkeydown'")
					->addGroupClass('discount-group')->type('number')->min('0')->step('any')->append(
						Former::select('is_amount_discount')->addOption(trans('texts.discount_percent'), '0')
						->addOption(trans('texts.discount_amount'), '1')->data_bind("value: is_amount_discount")->raw()
			) !!}

            @if ($entityType == ENTITY_INVOICE)
			<div data-bind="visible: is_recurring" style="display: none">
				{!! Former::select('frequency_id')->label('frequency')->options($frequencies)->data_bind("value: frequency_id")
                        ->appendIcon('question-sign')->addGroupClass('frequency_id')->onchange('onFrequencyChange()') !!}
				{!! Former::text('start_date')->data_bind("datePicker: start_date, valueUpdate: 'afterkeydown'")
							->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT, DEFAULT_DATE_PICKER_FORMAT))->appendIcon('calendar')->addGroupClass('start_date') !!}
				{!! Former::text('end_date')->data_bind("datePicker: end_date, valueUpdate: 'afterkeydown'")
							->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT, DEFAULT_DATE_PICKER_FORMAT))->appendIcon('calendar')->addGroupClass('end_date') !!}
                {!! Former::select('recurring_due_date')->label(trans('texts.due_date'))->options($recurringDueDates)->data_bind("value: recurring_due_date")->appendIcon('question-sign')->addGroupClass('recurring_due_date') !!}
			</div>
            @endif

            @if ($account->showCustomField('custom_invoice_text_label1', $invoice))
                {{--{!! Former::text('custom_text_value1')->label($account->custom_invoice_text_label1 ?: ' ')
                ->data_bind("value: custom_text_value1, valueUpdate: 'afterkeydown'") !!}--}}
				{!! Former::select('custom_text_value1')->label($account->custom_invoice_text_label1 ?: ' ')->options(
					[
					'50 / 50'=>'50 / 50',
					'50 / 40 / 10'=>'50 / 40 / 10',
                    '60 / 40'=>'60 / 40',
					'Due on Completion'=>'Due on Completion',
					'Due on Receipt'=>'Due on Receipt',
					'Net 15'=>'Net 15',
					'Net 30'=>'Net 30'
					]
				)->data_bind("value: custom_text_value1") !!}

			@endif
             {!! Former::select('interest')->label('Interest')->options(
					[
					'0'=>'No',
					'1'=>'Yes'
					]
				)->data_bind("value: interest, valueUpdate: 'afterkeydown'") !!}
            
		</div>

		<div class="col-md-4" id="col_2">
            <span data-bind="visible: !is_recurring()">
            {!! Former::text('invoice_number')
                        ->label(trans("texts.{$entityType}_number_short"))
                        ->onchange('checkInvoiceNumber()')
                        ->addGroupClass('invoice-number')
                        ->data_bind("value: invoice_number, valueUpdate: 'afterkeydown'") !!}
            </span>
            {!! Former::text('work_number')->label("Work Order No.")->data_bind("value: work_number, valueUpdate: 'afterkeydown'") !!}
            
            <span data-bind="visible: is_recurring()" style="display: none">
                <div data-bind="visible: !(auto_bill() == {{AUTO_BILL_OPT_IN}} &amp;&amp; client_enable_auto_bill()) &amp;&amp; !(auto_bill() == {{AUTO_BILL_OPT_OUT}} &amp;&amp; !client_enable_auto_bill())" style="display: none">
                {!! Former::select('auto_bill')
                        ->data_bind("value: auto_bill, valueUpdate: 'afterkeydown', event:{change:function(){if(auto_bill()==".AUTO_BILL_OPT_IN.")client_enable_auto_bill(0);if(auto_bill()==".AUTO_BILL_OPT_OUT.")client_enable_auto_bill(1)}}")
                        ->options([
                            AUTO_BILL_OFF => trans('texts.off'),
                            AUTO_BILL_OPT_IN => trans('texts.opt_in'),
                            AUTO_BILL_OPT_OUT => trans('texts.opt_out'),
                            AUTO_BILL_ALWAYS => trans('texts.always'),
                        ]) !!}
                </div>
                <input type="hidden" name="client_enable_auto_bill" data-bind="attr: { value: client_enable_auto_bill() }" />
                <div class="form-group" data-bind="visible: auto_bill() == {{AUTO_BILL_OPT_IN}} &amp;&amp; client_enable_auto_bill()">
                    <div class="col-sm-4 control-label">{{trans('texts.auto_bill')}}</div>
                    <div class="col-sm-8" style="padding-top:10px;padding-bottom:9px">
                        {{trans('texts.opted_in')}} - <a href="#" data-bind="click:function(){client_enable_auto_bill(false)}">({{trans('texts.disable')}})</a>
                    </div>
                </div>
                <div class="form-group" data-bind="visible: auto_bill() == {{AUTO_BILL_OPT_OUT}} &amp;&amp; !client_enable_auto_bill()">
                    <div class="col-sm-4 control-label">{{trans('texts.auto_bill')}}</div>
                    <div class="col-sm-8" style="padding-top:10px;padding-bottom:9px">
                        {{trans('texts.opted_out')}} - <a href="#" data-bind="click:function(){client_enable_auto_bill(true)}">({{trans('texts.enable')}})</a>
                    </div>
                </div>
            </span>
			{!! Former::text('po_number')->label(trans('texts.po_number_short'))->data_bind("value: po_number, valueUpdate: 'afterkeydown'") !!}
			

            @if ($account->showCustomField('custom_invoice_text_label2', $invoice))
               
                {!! Former::select('custom_text_value2')->label($account->custom_invoice_text_label2)->data_bind("value: custom_text_value2, valueUpdate: 'afterkeydown'") !!}
            @endif

            @if ($entityType == ENTITY_INVOICE)
            <div class="form-group" style="margin-bottom: 8px">
                <div class="col-lg-8 col-sm-8 col-sm-offset-4 smaller" style="padding-top: 10px">
                	@if ($invoice->recurring_invoice)
                        {!! trans('texts.created_by_invoice', ['invoice' => link_to('/invoices/'.$invoice->recurring_invoice->public_id, trans('texts.recurring_invoice'))]) !!}
    				@elseif ($invoice->id)
                        @if (isset($lastSent) && $lastSent)
                            {!! trans('texts.last_sent_on', ['date' => link_to('/invoices/'.$lastSent->public_id, $invoice->last_sent_date, ['id' => 'lastSent'])]) !!} <br/>
                        @endif
                        @if ($invoice->is_recurring && $invoice->getNextSendDate())
                           {!! trans('texts.next_send_on', ['date' => '<span data-bind="tooltip: {title: \''.$invoice->getPrettySchedule().'\', html: true}">'.$account->formatDate($invoice->getNextSendDate()).
                                '<span class="glyphicon glyphicon-info-sign" style="padding-left:10px;color:#B1B5BA"></span></span>']) !!}
                            @if ($invoice->getDueDate())
                                <br>
                                {!! trans('texts.next_due_on', ['date' => '<span>'.$account->formatDate($invoice->getDueDate($invoice->getNextSendDate())).'</span>']) !!}
                            @endif
                        @endif
                    @endif
                </div>
            </div>
            @endif

             {!! Former::select('attach_pdf')->label('Attach PDF ')->options([0=>"No",1=>"Yes"])!!}
             @if ($entityType == ENTITY_INVOICE)
                {!! Former::select('attach_signature')->data_bind("value: attach_signature")->label('Required Signature')->options([0=>"No",1=>"Yes"])!!}
            @endif
		</div>
	</div>

	<div class="table-responsive" style="padding-top:4px">
	<table class="table invoice-table">
		<thead>
			<tr>
				<th style="min-width:32px;" class="hide-border"></th>
				<th style="{{ $account->hide_quantity ? 'display:none' : 'min-width:120px' }}" data-bind="text: qtyLabel">{{ $invoiceLabels['quantity'] }}</th>
				<th style="min-width:120px" data-bind="text: costLabel">{{ $invoiceLabels['unit_cost'] }}</th>
				<th style="min-width:120px;width:25%">SKU</th>
				<th style="min-width:300px;width:100%">ITEM</th>

                @if ($account->showCustomField('custom_invoice_item_label1') && ($entityType == ENTITY_INVOICE))
                    <th style="min-width:120px">{{ $account->custom_invoice_item_label1 }}</th>
                @endif
                @if ($account->showCustomField('custom_invoice_item_label2'))
                    <th style="min-width:120px">{{ $account->custom_invoice_item_label2 }}</th>
                @endif


				<th style="min-width:{{ $account->enable_second_tax_rate ? 180 : 120 }}px;display:none;" data-bind="visible: $root.invoice_item_taxes.show">{{ trans('texts.tax') }}</th>
				<th style="min-width:120px;">{{ trans('texts.line_total') }}</th>
				<th style="min-width:32px;" class="hide-border"></th>
			</tr>
		</thead>
		<tbody data-bind="sortable: { data: invoice_items, afterMove: onDragged }">
			<tr data-bind="event: { mouseover: showActions, mouseout: hideActions }" class="sortable-row">
				<td class="hide-border td-icon">
					<i style="display:none" data-bind="visible: actionsVisible() &amp;&amp;
                        $parent.invoice_items().length > 1" class="fa fa-sort"></i>
				</td>
				<td style="{{ $account->hide_quantity ? 'display:none' : '' }}">
					<input data-bind="value: prettyQty, valueUpdate: ['input','afterkeydown'], attr: {name: 'invoice_items[' + $index() + '][qty]'}"
						   style="text-align: right" class="form-control invoice-item" name="quantity"/>
				</td>
				<td>
					<input data-bind="value: prettyCost, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][cost]'}"
						   style="text-align: right" class="form-control invoice-item"/>
				</td>

				<td>
					<div id="scrollable-dropdown-menu-1">
						<input id="sku" type="text" data-bind="productTypeahead: sku, items: $root.products, key: 'sku', valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][sku]'}" class="form-control invoice-item handled"/>
					</div>

				</td>

				<td>

					<div id="scrollable-dropdown-menu">
						<textarea id="product_key" type="text" data-bind="productTypeahead: product_key, items: $root.products, key: 'product_key', valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][product_key]'}" class="form-control invoice-item handled"></textarea>
					</div>


                        <input type="text" data-bind="value: task_public_id, attr: {name: 'invoice_items[' + $index() + '][task_public_id]'}" style="display: none"/>
						<input type="text" data-bind="value: expense_public_id, attr: {name: 'invoice_items[' + $index() + '][expense_public_id]'}" style="display: none"/>
						<input type="text" data-bind="value: invoice_item_type_id, attr: {name: 'invoice_items[' + $index() + '][invoice_item_type_id]'}" style="display: none"/>

						<input data-bind="value: notes, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][notes]'}" style="display: none"/>
				</td>


                @if ($account->showCustomField('custom_invoice_item_label1')  && ($entityType == ENTITY_INVOICE))
                    <td>
						{{--{!! Former::text('custom_value1')->data_bind("datePicker: custom_value1, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][custom_value1]'}")->label(trans("texts.{$entityType}_date"))
							->data_date_format(Session::get(SESSION_DATE_PICKER_FORMAT, DEFAULT_DATE_PICKER_FORMAT))->appendIcon('calendar')->addGroupClass('invoice_date') !!}
                        <input type="date" data-bind="value: custom_value1, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][custom_value1]'}" class="form-control invoice-item"/>--}}
						<input  class="form-control" data-bind="datePicker1: custom_value1, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][custom_value1]'}" data-date-format="mm/dd/yyyy" type="text" >


                    </td>
                @endif
                @if ($account->showCustomField('custom_invoice_item_label2'))
                    <td>
                        <input data-bind="value: custom_value2, valueUpdate: 'afterkeydown', attr: {name: 'invoice_items[' + $index() + '][custom_value2]'}" class="form-control invoice-item"/>
                    </td>
                @endif


				<td style="display:none;" data-bind="visible: $root.invoice_item_taxes.show">
	                    {!! Former::select('')
	                            ->addOption('', '')
	                            ->options($taxRateOptions)
	                            ->data_bind('value: tax1, event:{change:onTax1Change}')
	                            ->addClass($account->enable_second_tax_rate ? 'tax-select' : '')
	                            ->raw() !!}
                    <input type="text" data-bind="value: tax_name1, attr: {name: 'invoice_items[' + $index() + '][tax_name1]'}" style="display:none">
                    <input type="text" data-bind="value: tax_rate1, attr: {name: 'invoice_items[' + $index() + '][tax_rate1]'}" style="display:none">
                    <div data-bind="visible: $root.invoice().account.enable_second_tax_rate == '1'">
                        {!! Former::select('')
                                ->addOption('', '')
                                ->options($taxRateOptions)
                                ->data_bind('value: tax2, event:{change:onTax2Change}')
                                ->addClass('tax-select')
                                ->raw() !!}
                    </div>
                    <input type="text" data-bind="value: tax_name2, attr: {name: 'invoice_items[' + $index() + '][tax_name2]'}" style="display:none">
                    <input type="text" data-bind="value: tax_rate2, attr: {name: 'invoice_items[' + $index() + '][tax_rate2]'}" style="display:none">
				</td>
				<td style="text-align:right;padding-top:9px !important" nowrap>
					<div class="line-total" data-bind="text: totals.total"></div>
				</td>
				<td style="cursor:pointer" class="hide-border td-icon">
                    <i style="padding-left:2px" data-bind="click: $parent.removeItem, visible: actionsVisible() &amp;&amp;
                    $index() < ($parent.invoice_items().length - 1) &amp;&amp;
                    $parent.invoice_items().length > 1" class="fa fa-minus-circle redlink" title="Remove item"/>
				</td>
			</tr>
		</tbody>


		<tfoot>
			<tr>
				<td class="hide-border"/>
				<td class="hide-border" colspan="{{ 2 + ($account->showCustomField('custom_invoice_item_label1') ? 1 : 0) + ($account->showCustomField('custom_invoice_item_label2') ? 1 : 0) }}" rowspan="6" style="vertical-align:top">
					<br/>
                    <div role="tabpanel">

                      <ul class="nav nav-tabs" role="tablist" style="border: none; width: 525px">
                        <li role="presentation" class="active"><a href="#notes" aria-controls="notes" role="tab" data-toggle="tab">{{ trans('texts.note_to_client') }}</a></li>
                        <li role="presentation"><a href="#terms" aria-controls="terms" role="tab" data-toggle="tab">{{ trans("texts.terms") }}</a></li>
                        <li role="presentation"><a href="#footer" aria-controls="footer" role="tab" data-toggle="tab">{{ trans("texts.footer") }}</a></li>
                        @if($entityType == "quote")
						  <li role="presentation"><a href="#addition-info" aria-controls="adition-info" role="tab" data-toggle="tab">Additional Info</a></li>
                        @endif
							  @if ($account->hasFeature(FEATURE_DOCUMENTS))
                            <li role="presentation"><a href="#attached-documents" aria-controls="attached-documents" role="tab" data-toggle="tab">
                                {{ trans("texts.invoice_documents") }}
                                @if (count($invoice->documents))
                                    ({{ count($invoice->documents) }})
                                @endif
                            </a></li>
                        @endif
                    </ul>

                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active" id="notes" style="padding-bottom:44px">
                            {!! Former::textarea('public_notes')->data_bind("value: public_notes, valueUpdate: 'afterkeydown'")
                            ->label(null)->style('width: 500px;')->rows(4) !!}
                        </div>
                        <div role="tabpanel" class="tab-pane" id="terms">
                            {!! Former::textarea('terms')->data_bind("value:terms, placeholder: terms_placeholder, valueUpdate: 'afterkeydown'")
                            ->label(false)->style('width: 500px')->rows(4)
                            ->help('<div class="checkbox">
                                        <label>
                                            <input name="set_default_terms" type="checkbox" style="width: 24px" data-bind="checked: set_default_terms"/>'.trans('texts.save_as_default_terms').'
                                        </label>
                                        <div class="pull-right" data-bind="visible: showResetTerms()">
                                            <a href="#" onclick="return resetTerms()" title="'. trans('texts.reset_terms_help') .'">' . trans("texts.reset_terms") . '</a>
                                        </div>
                                    </div>') !!}
                        </div>
                        <div role="tabpanel" class="tab-pane" id="footer">
                            {!! Former::textarea('invoice_footer')->data_bind("value:invoice_footer, placeholder: footer_placeholder, valueUpdate: 'afterkeydown'")
                            ->label(false)->style('width: 500px')->rows(4)
                            ->help('<div class="checkbox">
                                        <label>
                                            <input name="set_default_footer" type="checkbox" style="width: 24px" data-bind="checked: set_default_footer"/>'.trans('texts.save_as_default_footer').'
                                        </label>
                                        <div class="pull-right" data-bind="visible: showResetFooter()">
                                            <a href="#" onclick="return resetFooter()" title="'. trans('texts.reset_footer_help') .'">' . trans("texts.reset_footer") . '</a>
                                        </div>
                                    </div>') !!}
                        </div>
						@if($entityType == "quote")
							<div role="tabpanel" class="tab-pane" id="addition-info">
								{!! Former::textarea('additional_info')->data_bind("value:additional_info, placeholder: 'Additional Info', valueUpdate: 'afterkeydown'")
                                ->label(false)->style('width: 500px')->rows(4)!!}
							</div>
						@endif
                        @if ($account->hasFeature(FEATURE_DOCUMENTS))
                        <div role="tabpanel" class="tab-pane" id="attached-documents" style="position:relative;z-index:9">
                            <div id="document-upload">
                                <div class="dropzone">
                                    <div data-bind="foreach: documents">
                                        <input type="hidden" name="document_ids[]" data-bind="value: public_id"/>
                                    </div>
                                </div>
                                @if ($invoice->hasExpenseDocuments())
                                    <h4>{{trans('texts.documents_from_expenses')}}</h4>
                                    @foreach($invoice->expenses as $expense)
                                        @foreach($expense->documents as $document)
                                            <div>{{$document->name}}</div>
                                        @endforeach
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

				</td>
				<td class="hide-border" style="display:none" data-bind="visible: $root.invoice_item_taxes.show"/>
				<td colspan="{{ $account->hide_quantity ? 1 : 2 }}">{{ trans('texts.subtotal') }}</td>
				<td style="text-align: right"><span data-bind="text: totals.subtotal"/></td>
			</tr>

			<tr style="display:none" data-bind="visible: discount() != 0">
				<td class="hide-border" colspan="3"/>
				<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
				<td colspan="{{ $account->hide_quantity ? 1 : 2 }}">{{ trans('texts.discount') }}</td>
				<td style="text-align: right"><span data-bind="text: totals.discounted"/></td>
			</tr>

            @if ($account->showCustomField('custom_invoice_label1', $invoice) && $invoice->custom_taxes1)
				<tr>
					<td class="hide-border" colspan="3"/>
					<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
					<td colspan="{{ $account->hide_quantity ? 1 : 2 }}">{{ $account->custom_invoice_label1 ?: trans('texts.surcharge') }}</td>
					<td style="text-align: right;padding-right: 28px" colspan="2"><input name="custom_value1" class="form-control" data-bind="value: custom_value1, valueUpdate: 'afterkeydown'"/></td>
				</tr>
			@endif

            @if ($account->showCustomField('custom_invoice_label2', $invoice) && $invoice->custom_taxes2)
				<tr>
					<td class="hide-border" colspan="3"/>
					<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
					<td colspan="{{ $account->hide_quantity ? 1 : 2 }}">{{ $account->custom_invoice_label2 ?: trans('texts.surcharge') }}</td>
					<td style="text-align: right;padding-right: 28px" colspan="2"><input name="custom_value2" class="form-control" data-bind="value: custom_value2, valueUpdate: 'afterkeydown'"/></td>
				</tr>
			@endif

            <tr style="display:none" data-bind="visible: $root.invoice_item_taxes.show &amp;&amp; totals.hasItemTaxes">
                <td class="hide-border" colspan="5"/>


                <td style="min-width:120px"><span data-bind="html: totals.itemTaxRates"/></td>
				<td>&nbsp;</td>
                <td style="text-align: right"><span data-bind="html: totals.itemTaxAmounts"/></td>
            </tr>

           

			<tr style="display:none" data-bind="visible: $root.invoice_taxes.show">
				<td class="hide-border" colspan="3"/>
				<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
				@if (!$account->hide_quantity)
					<td>{{ trans('texts.tax') }}</td>
				@endif
				<td style="min-width:120px">
                    {!! Former::select('')
                            ->id('taxRateSelect1')
                            ->addOption('', '')
                            ->options($taxRateOptions)
                            ->addClass($account->enable_second_tax_rate ? 'tax-select' : '')
                            ->data_bind('value: tax1, event:{change:onTax1Change}')
                            ->raw() !!}
                    <input type="text" name="tax_name1" data-bind="value: tax_name1" style="display:none">
                    <input type="text" name="tax_rate1" data-bind="value: tax_rate1" style="display:none">
                    <div data-bind="visible: $root.invoice().account.enable_second_tax_rate == '1'">
                    {!! Former::select('')
                            ->addOption('', '')
                            ->options($taxRateOptions)
                            ->addClass('tax-select')
                            ->data_bind('value: tax2, event:{change:onTax2Change}')
                            ->raw() !!}
                    </div>
                    <input type="text" name="tax_name2" data-bind="value: tax_name2" style="display:none">
                    <input type="text" name="tax_rate2" data-bind="value: tax_rate2" style="display:none">
                </td>
				<td style="text-align: right"><span data-bind="text: totals.taxAmount"/></td>
			</tr>

            @if ($account->showCustomField('custom_invoice_label1', $invoice) && !$invoice->custom_taxes1)
				<tr>
					<td class="hide-border" colspan="3"/>
					<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
					<td colspan="{{ $account->hide_quantity ? 1 : 2 }}">{{ $account->custom_invoice_label1 ?: trans('texts.surcharge') }}</td>
					<td style="text-align: right;padding-right: 28px" colspan="2"><input name="custom_value1" class="form-control" data-bind="value: custom_value1, valueUpdate: 'afterkeydown'"/></td>
				</tr>
			@endif

            @if ($account->showCustomField('custom_invoice_label2', $invoice) && !$invoice->custom_taxes2)
				<tr>
					<td class="hide-border" colspan="3"/>
					<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
					<td colspan="{{ $account->hide_quantity ? 1 : 2 }}">{{ $account->custom_invoice_label2 ?: trans('texts.surcharge') }}</td>
					<td style="text-align: right;padding-right: 28px" colspan="2"><input name="custom_value2" class="form-control" data-bind="value: custom_value2, valueUpdate: 'afterkeydown'"/></td>
				</tr>
			@endif

			@if (!$account->hide_paid_to_date)
				<tr>
					<td class="hide-border" colspan="3"/>
					<td style="display:none" class="hide-border" data-bind="visible: $root.invoice_item_taxes.show"/>
					<td colspan="{{ $account->hide_quantity ? 1 : 2 }}">{{ trans('texts.paid_to_date') }}</td>
					<td style="text-align: right" data-bind="text: totals.paidToDate"></td>
				</tr>
			@endif

             <tr style="display:none" data-bind="visible: interest() == 1">
                <td class="hide-border" colspan="5"/>


                <td style="min-width:120px"><span >Interest</span></td>
				<td>&nbsp;</td>
                <td style="text-align: right"><span data-bind="html: totals.interest"/></td>
            </tr>
			<tr data-bind="style: { 'font-weight': partial() ? 'normal' : 'bold', 'font-size': partial() ? '1em' : '1.05em' }">
				<td class="hide-border" colspan="3"/>
				<td class="hide-border" style="display:none" data-bind="visible: $root.invoice_item_taxes.show"/>
				<td class="hide-border" data-bind="css: {'hide-border': !partial()}" colspan="{{ $account->hide_quantity ? 1 : 2 }}">{{ $entityType == ENTITY_INVOICE ? $invoiceLabels['balance_due'] : trans('texts.total') }}</td>
				<td class="hide-border" data-bind="css: {'hide-border': !partial()}" style="text-align: right"><span data-bind="text: totals.total"></span></td>
			</tr>

			<tr style="font-size:1.05em; display:none; font-weight:bold" data-bind="visible: partial">
				<td class="hide-border" colspan="3"/>
				<td class="hide-border" style="display:none" data-bind="visible: $root.invoice_item_taxes.show"/>
				<td class="hide-border" colspan="{{ $account->hide_quantity ? 1 : 2 }}">{{ $invoiceLabels['partial_due'] }}</td>
				<td class="hide-border" style="text-align: right"><span data-bind="text: totals.partial"></span></td>
			</tr>

		</tfoot>


	</table>
	</div>
    </div>
    </div>
    @if(isset($email_queue) && count($email_queue))
    <p>&nbsp;</p>
    <div class="panel panel-default">
        <div class="panel-body">
            <div class="table-responsive" style="padding-top:4px">
                <table class="table invoice-table">
                    <thead>
                        <tr>
                            <th>Template</th>
                            <th>Send At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($email_queue as $queue)
                            <tr>
                                <td>{{$queue->template->name}}</td>
                                <td>{{$queue->send_at}}</td>
                                <td><a href="javascript:void()" onclick="javascript:deleteQueue({{$queue->id}},this)">Delete</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>  
    @endif      
	<p>&nbsp;</p>
    
	<div class="form-actions">
        @if(isset($prev) && $prev)
            <a class="btn btn-default" href="/invoices/{{$prev}}/edit"><span class="glyphicon glyphicon-chevron-left" style="padding-left: 0"></span></a>
        @endif
		<div style="display:none">
			{!! Former::populateField('entityType', $entityType) !!}

			{!! Former::text('entityType') !!}
			{!! Former::text('action') !!}
			{!! Former::text('public_id')->data_bind('value: public_id') !!}
			{!! Former::text('is_public')->data_bind('value: is_public') !!}
            {!! Former::text('is_recurring')->data_bind('value: is_recurring') !!}
            {!! Former::text('is_quote')->data_bind('value: is_quote') !!}
            {!! Former::text('has_tasks')->data_bind('value: has_tasks') !!}
            {!! Former::text('data')->data_bind('value: ko.mapping.toJSON(model)') !!}
			{!! Former::text('has_expenses')->data_bind('value: has_expenses') !!}
            {!! Former::text('pdfupload') !!}
		</div>

		@if (!Utils::hasFeature(FEATURE_MORE_INVOICE_DESIGNS) || \App\Models\InvoiceDesign::count() == COUNT_FREE_DESIGNS_SELF_HOST)
			{!! Former::select('invoice_design_id')->style('display:none;width:150px;background-color:white !important')->raw()->fromQuery($invoiceDesigns, 'name', 'id')->data_bind("value: invoice_design_id")->addOption(trans('texts.more_designs') . '...', '-1') !!}
		@else
			{!! Former::select('invoice_design_id')->style('display:none;width:150px;background-color:white !important')->raw()->fromQuery($invoiceDesigns, 'name', 'id')->data_bind("value: invoice_design_id") !!}
		@endif
        
        @if($invoice->invoice_status_id != INVOICE_STATUS_VOIDED)
            @if ( $invoice->exists && $invoice->id && ! $invoice->is_recurring)
                {!! Button::primary(trans('texts.download_pdf'))
                        ->withAttributes(['onclick' => 'onDownloadClick()', 'id' => 'downloadPdfButton'])
                        ->appendIcon(Icon::create('download-alt')) !!}
                {!! Button::primary('Print PDF')
                        ->withAttributes(['onclick' => 'printPDF()', 'id' => 'printPdfButton'])
                        ->appendIcon(Icon::create('print')) !!}        
            @endif

            @if (Auth::user()->canCreateOrEdit(ENTITY_INVOICE, $invoice) || $invoice->taggedUser())
                @if ($invoice->isClientTrashed())
                    <!-- do nothing -->
                @else
                    @if (!$invoice->is_deleted )
                        @if ($invoice->isSent())
                            {!! Button::success(trans("texts.save_{$entityType}"))->withAttributes(array('id' => 'saveButton', 'onclick' => 'onSaveClick()'))->appendIcon(Icon::create('floppy-disk')) !!}
                        @else
                            {!! Button::normal(trans("texts.save_draft"))->withAttributes(array('id' => 'draftButton', 'onclick' => 'onSaveDraftClick()'))->appendIcon(Icon::create('floppy-disk')) !!}
                            @if (! $invoice->trashed())
                                {!! Button::success(trans($invoice->is_recurring ? "texts.mark_ready" : "texts.mark_sent"))->withAttributes(array('id' => 'saveButton', 'onclick' => 'onMarkSentClick()'))->appendIcon(Icon::create('globe')) !!}
                            @endif
                        @endif
                        @if (! $invoice->trashed())
                            {!! Button::info(trans("texts.email_{$entityType}"))->withAttributes(array('id' => 'emailButton', 'onclick' => 'onEmailClick()'))->appendIcon(Icon::create('send')) !!}
                        @endif
                        @if ($invoice->id)
                            {!! DropdownButton::normal(trans('texts.more_actions'))->withContents($invoice->present()->moreActions())->dropup() !!}
                        @elseif ( ! $invoice->isQuote() && Request::is('*/clone'))
                            {!! Button::normal(trans($invoice->is_recurring ? 'texts.disable_recurring' : 'texts.enable_recurring'))->withAttributes(['id' => 'recurrButton', 'onclick' => 'onRecurrClick()'])->appendIcon(Icon::create('repeat')) !!}
                        @endif
                    @endif
                    @if ($invoice->trashed())
                        {!! Button::primary(trans('texts.restore'))->withAttributes(['onclick' => 'submitBulkAction("restore")'])->appendIcon(Icon::create('cloud-download')) !!}
                    @endif
                @endif
            @endif
        @endif

        @if(isset($next) && $next)
            <a class="btn btn-default " href="/invoices/{{$next}}/edit"><span class="glyphicon glyphicon-chevron-right" style="padding-left:0"></span></a>
        @endif    
	</div>
	<p>&nbsp;</p>

	@include('invoices.pdf', ['account' => Auth::user()->account, 'hide_pdf' => ! Auth::user()->account->live_preview])

	@if (!Auth::user()->account->isPro())
		<div style="font-size:larger">
			{!! trans('texts.pro_plan_remove_logo', ['link'=>'<a href="javascript:showUpgradeModal()">' . trans('texts.pro_plan_remove_logo_link') . '</a>']) !!}
		</div>
	@endif

	<div class="modal fade" id="clientModal" tabindex="-1" role="dialog" aria-labelledby="clientModalLabel" aria-hidden="true">
	  <div class="modal-dialog" data-bind="css: {'large-dialog': $root.showMore}">
	    <div class="modal-content" style="background-color: #f8f8f8">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title" id="clientModalLabel">{{ trans('texts.client') }}</h4>
	      </div>

       <div class="container" style="width: 100%; padding-bottom: 0px !important">
       <div class="panel panel-default">
        <div class="panel-body">

        <div class="row" data-bind="with: client" onkeypress="clientModalEnterClick(event)">
            <div style="margin-left:0px;margin-right:0px" data-bind="css: {'col-md-6': $root.showMore}">

                {!! Former::hidden('client_public_id')->data_bind("value: public_id, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[public_id]'}") !!}
                {!! Former::text('client[name]')
                    ->data_bind("value: name, valueUpdate: 'afterkeydown', attr { placeholder: name.placeholder }")
                    ->label('client_name') !!}

				@if ( ! $account->client_number_counter)
                <span data-bind="visible: $root.showMore">
				@endif

            	{!! Former::text('client[id_number]')
                            ->label('id_number')
							->placeholder($account->clientNumbersEnabled() ? $account->getNextNumber() : ' ')
                            ->data_bind("value: id_number, valueUpdate: 'afterkeydown'") !!}

				@if ( ! $account->client_number_counter)
				</span>
				@endif

				<span data-bind="visible: $root.showMore">
                    {!! Former::text('client[vat_number]')
                            ->label('vat_number')
                            ->data_bind("value: vat_number, valueUpdate: 'afterkeydown'") !!}

                    {!! Former::text('client[website]')
                            ->label('website')
                            ->data_bind("value: website, valueUpdate: 'afterkeydown'") !!}
                    {!! Former::text('client[work_phone]')
                            ->label('work_phone')
                            ->data_bind("value: work_phone, valueUpdate: 'afterkeydown'") !!}

                </span>

                @if (Auth::user()->hasFeature(FEATURE_INVOICE_SETTINGS))
                    @if ($account->custom_client_label1)
                        {!! Former::text('client[custom_value1]')
                            ->label($account->custom_client_label1)
                            ->data_bind("value: custom_value1, valueUpdate: 'afterkeydown'") !!}
                    @endif
                    @if ($account->custom_client_label2)
                        {!! Former::text('client[custom_value2]')
                            ->label($account->custom_client_label2)
                            ->data_bind("value: custom_value2, valueUpdate: 'afterkeydown'") !!}
                    @endif
                @endif

                <span data-bind="visible: $root.showMore">
                    &nbsp;

                    {!! Former::text('client[address1]')
                            ->label(trans('texts.address1'))
                            ->data_bind("value: address1, valueUpdate: 'afterkeydown'") !!}
                    {!! Former::text('client[address2]')
                            ->label(trans('texts.address2'))
                            ->data_bind("value: address2, valueUpdate: 'afterkeydown'") !!}
                    {!! Former::text('client[city]')
                            ->label(trans('texts.city'))
                            ->data_bind("value: city, valueUpdate: 'afterkeydown'") !!}
                    {!! Former::text('client[state]')
                            ->label(trans('texts.state'))
                            ->data_bind("value: state, valueUpdate: 'afterkeydown'") !!}
                    {!! Former::text('client[postal_code]')
                            ->label(trans('texts.postal_code'))
                            ->data_bind("value: postal_code, valueUpdate: 'afterkeydown'") !!}
                    {!! Former::select('client[country_id]')
                            ->label(trans('texts.country_id'))
                            ->addOption('','')->addGroupClass('country_select')
                            ->fromQuery(Cache::get('countries'), 'name', 'id')->data_bind("dropdown: country_id") !!}
                </span>

            </div>
            <div style="margin-left:0px;margin-right:0px" data-bind="css: {'col-md-6': $root.showMore}">

                <div data-bind='template: { foreach: contacts,
                                        beforeRemove: hideContact,
                                        afterAdd: showContact }'>

                    {!! Former::hidden('public_id')->data_bind("value: public_id, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][public_id]'}") !!}
                    {!! Former::text('first_name')->data_bind("value: first_name, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][first_name]'}") !!}
                    {!! Former::text('last_name')->data_bind("value: last_name, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][last_name]'}") !!}
                    {!! Former::text('email')->data_bind("value: email, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][email]', id:'email'+\$index()}")
                            ->addClass('client-email') !!}
                    {!! Former::text('phone')->data_bind("value: phone, valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][phone]'}") !!}
                    @if ($account->hasFeature(FEATURE_CLIENT_PORTAL_PASSWORD) && $account->enable_portal_password)
                        {!! Former::password('password')->data_bind("value: (typeof password=='function'?password():null)?'-%unchanged%-':'', valueUpdate: 'afterkeydown',
                            attr: {name: 'client[contacts][' + \$index() + '][password]'}")->autocomplete('new-password') !!}
                    @endif
                    <div class="form-group">
                        <div class="col-lg-8 col-lg-offset-4">
                            <span class="redlink bold" data-bind="visible: $parent.contacts().length > 1">
                                {!! link_to('#', trans('texts.remove_contact').' -', array('data-bind'=>'click: $parent.removeContact')) !!}
                            </span>
                            <span data-bind="visible: $index() === ($parent.contacts().length - 1)" class="pull-right greenlink bold">
                                {!! link_to('#', trans('texts.add_contact').' +', array('data-bind'=>'click: $parent.addContact')) !!}
                            </span>
                        </div>
                    </div>
                </div>

                <span data-bind="visible: $root.showMore">
                    &nbsp;
                </span>

                {!! Former::select('client[currency_id]')->addOption('','')
                        ->placeholder($account->currency ? $account->currency->name : '')
                        ->label(trans('texts.currency_id'))
                        ->data_bind('value: currency_id')
                        ->fromQuery($currencies, 'name', 'id') !!}

                <span data-bind="visible: $root.showMore">
                {!! Former::select('client[language_id]')->addOption('','')
                        ->placeholder($account->language ? $account->language->name : '')
                        ->label(trans('texts.language_id'))
                        ->data_bind('value: language_id')
                        ->fromQuery($languages, 'name', 'id') !!}
                {!! Former::select('client[payment_terms]')->addOption('','')->data_bind('value: payment_terms')
                        ->fromQuery(\App\Models\PaymentTerm::getSelectOptions(), 'name', 'num_days')
                        ->label(trans('texts.payment_terms'))
                        ->help(trans('texts.payment_terms_help')) !!}
                {!! Former::select('client[size_id]')->addOption('','')->data_bind('value: size_id')
                        ->label(trans('texts.size_id'))
                        ->fromQuery($sizes, 'name', 'id') !!}
                {!! Former::select('client[industry_id]')->addOption('','')->data_bind('value: industry_id')
                        ->label(trans('texts.industry_id'))
                        ->fromQuery($industries, 'name', 'id') !!}
                {!! Former::textarea('client_private_notes')
                        ->label(trans('texts.private_notes'))
                        ->data_bind("value: private_notes, attr:{ name: 'client[private_notes]'}") !!}
                </span>
            </div>
            </div>
        </div>
        </div>
        </div>

         <div class="modal-footer">
            <span class="error-block" id="emailError" style="display:none;float:left;font-weight:bold">{{ trans('texts.provide_name_or_email') }}</span><span>&nbsp;</span>
            <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('texts.cancel') }}</button>
            <button type="button" class="btn btn-default" data-bind="click: $root.showMoreFields, text: $root.showMore() ? '{{ trans('texts.less_fields') }}' : '{{ trans('texts.more_fields') }}'"></button>
            <button id="clientDoneButton" type="button" class="btn btn-primary" data-bind="click: $root.clientFormComplete">{{ trans('texts.done') }}</button>
         </div>

        </div>
      </div>
    </div>

	<div class="modal fade" id="recurringModal" tabindex="-1" role="dialog" aria-labelledby="recurringModalLabel" aria-hidden="true">
	  <div class="modal-dialog" style="min-width:150px">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title" id="recurringModalLabel">{{ trans('texts.recurring_invoices') }}</h4>
	      </div>

		  <div class="container" style="width: 100%; padding-bottom: 0px !important">
          <div class="panel panel-default">
			 <div class="panel-body">
				 {!! isset($recurringHelp) ? $recurringHelp : '' !!}
			 </div>
		  </div>
		  </div>

	     <div class="modal-footer" style="padding-top: 0px">
	      	<button type="button" class="btn btn-primary" data-dismiss="modal">{{ trans('texts.close') }}</button>
	     </div>

	    </div>
	  </div>
	</div>

    <div class="modal fade" id="signatureModal" tabindex="-1" role="dialog" aria-labelledby="signatureModalLabel" aria-hidden="true">
	  <div class="modal-dialog" style="min-width:150px">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title" id="signatureModalLabel">Signature</h4>
	      </div>

		  <div class="container" style="width: 100%; padding-bottom: 0px !important">
          <div class="panel panel-default">
			 <div class="panel-body">
				<div>
                    <input class="form-control" id="model-print-name" placeholder="Print Name">
                </div>
                <div>
						{{ trans('texts.sign_here') }}
					</div>
				 	<div id="signature" style="position:relative;z-index:1;"></div>
                    
                    <div style="background: #8c8c8c;width: 85%;height: 2px;top: -50px;position: relative;margin:auto"></div> 
			 </div>
		  </div>
		  </div>

	     <div class="modal-footer" style="padding-top: 0px">
	      	<button type="button" class="btn btn-primary" id="signatureDone">Done</button>
	     </div>

	    </div>
	  </div>
	</div>

    <div class="modal fade" id="recurringDueDateModal" tabindex="-1" role="dialog" aria-labelledby="recurringDueDateModalLabel" aria-hidden="true">
	  <div class="modal-dialog" style="min-width:150px">
	    <div class="modal-content">
	      <div class="modal-header">
	        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
	        <h4 class="modal-title" id="recurringDueDateModalLabel">{{ trans('texts.recurring_due_dates') }}</h4>
	      </div>

		  <div class="container" style="width: 100%; padding-bottom: 0px !important">
          <div class="panel panel-default">
			 <div class="panel-body">
				 {!! isset($recurringDueDateHelp) ? $recurringDueDateHelp : '' !!}
			</div>
		 </div>
		 </div>

	     <div class="modal-footer" style="padding-top: 0px">
	      	<button type="button" class="btn btn-primary" data-dismiss="modal">{{ trans('texts.close') }}</button>
	     </div>

	    </div>
	  </div>
	</div>

	@include('partials.email_templates')
	@include('invoices.email')

    {!! Former::close() !!}
    </form>

    {!! Former::open("{$entityType}s/bulk")->addClass('bulkForm') !!}
    {!! Former::populateField('bulk_public_id', $invoice->public_id) !!}
    <span style="display:none">
    {!! Former::text('bulk_public_id') !!}
    {!! Former::text('bulk_action') !!}
    </span>
    {!! Former::close() !!}

    </div>

    @include('invoices.knockout')

	<script type="text/javascript">
    Dropzone.autoDiscover = false;
    var firstLoad = false;
    var products = {!! $products !!};
    var clients = {!! $clients !!};

    //var contacts = ;
    var account = {!! Auth::user()->account !!};
    var dropzone;
    @if($invoice->client->public_id)
    var clientMap = {
        {{$invoice->client->public_id}}: {!! $invoice->client !!}
    };
    clientMap['{{$invoice->client->public_id}}'].contacts = clientMap['{{$invoice->client->public_id}}'].contactsNew
    @else
    var clientMap = {}
    @endif
    var _clientData = [];
    var contactMap = {};
    var _contactData = [];
    var $clientSelect = $('select#client');
    var invoiceDesigns = {!! $invoiceDesigns !!};
    var invoiceFonts = {!! $invoiceFonts !!};
	var refreshTimer;
    function deleteQueue(id,elm){
        $.get("/invoice-queue/"+id+"/delete",function(response){
            $(elm).closest("tr").remove();
        })
    }
	function LoadClients(clients, params){
	    var clientdata = [];

        for (var i=0; i<clients.length; i++) {
            var client = clients[i];
            clients[i].contacts = clients[i].contactsNew;
            clientMap[client.public_id] = client;
            var clientName = '';
			if(client.name){
                clientName = client.name;
			}
            else if(client.legal_business_name){
			    clientName = client.legal_business_name;
			}
			if(client.suffix){
			    clientName +=" ("+client.suffix + ")";
			}
            if (!clientName) {
                continue;
            }


            clientdata.push({id:client.public_id,text:clientName});

        }

        return {
            results: clientdata
        };
	}
    function getClientDisplayNameSuffix(client){
         var clientName = '';
			if(client.name){
                clientName = client.name;
			}
            else if(client.legal_business_name){
			    clientName = client.legal_business_name;
			}
			
			if(client.suffix){
			    clientName +=" ("+client.suffix + ")";
			}
            
            return clientName;
    }
	$(function() {
        // create client dictionary
        for (var i=0; i<clients.length; i++) {
            var client = clients[i];
            clients[i].contacts = clients[i].contactsNew;
            clientMap[client.public_id] = client;
            var clientName = getClientDisplayName(client);
            @if (! $invoice->id)
	            if (!clientName) {
	                continue;
	            }
            @endif
            /*for (var j=0; j<client.contacts.length; j++) {
                var contact = client.contacts[j];
                var contactName = getContactDisplayName(contact);
                if (contact.is_primary === '1') {
                    contact.send_invoice = true;
                }
                if (contactName && clientName != contactName) {
                    $clientSelect.append(new Option(contactName, client.public_id));
                }
            }*/
            _clientData.push({id:client.public_id,text:clientName});
           // $clientSelect.append(new Option(clientName, client.public_id));
        }

        /*for (var i=0; i<contacts.length; i++) {
            var contact = contacts[i];
            contactMap[contact.public_id] = contact;
            var contactName = getContactDisplayName(contact);
			@if (! $invoice->id)
            if (!contactName) {
                continue;
            }

			@endif
			/!*for (var j=0; j<client.contacts.length; j++) {
			 var contact = client.contacts[j];
			 var contactName = getContactDisplayName(contact);
			 if (contact.is_primary === '1') {
			 contact.send_invoice = true;
			 }
			 if (contactName && clientName != contactName) {
			 $clientSelect.append(new Option(contactName, client.public_id));
			 }
			 }*!/
            _contactData.push({id:contact.public_id,text:contactName});
            // $clientSelect.append(new Option(clientName, client.public_id));
        }*/
        
        @if ($data)
            // this means we failed so we'll reload the previous state
            window.model = new ViewModel({!! $data !!});
        @else
            // otherwise create blank model
            window.model = new ViewModel();

            var invoice = {!! $invoice !!};
            @if($invoice->invitations && !empty($invoice->invitations) && !empty($invoice->invitations[0]->signature_base64))
                invoice.signature = "data:image/png;base64,{{$invoice->invitations[0]->signature_base64}}";
                invoice.signature_date = "{{date("M d, Y",strtotime($invoice->invitations[0]->signature_date))}}";
            @endif
            invoice.client.contacts = invoice.client.contactsNew;
            ko.mapping.fromJS(invoice, model.invoice().mapping, model.invoice);
            model.invoice().is_recurring({{ $invoice->is_recurring ? '1' : '0' }});
            model.invoice().start_date_orig(model.invoice().start_date());

            @if ($invoice->id)
                var invitationContactIds = {!! json_encode($invitationContactIds) !!};
        			
                var client = invoice.client;
                if (client) { // in case it's deleted
                    for (var i=0; i<client.contacts.length; i++) {
                        var contact = client.contacts[i];
                        contact.send_invoice = invitationContactIds.indexOf(contact.public_id) >= 0;
                        model.invoice().client().contacts()[i].send_invoice(invitationContactIds.indexOf(contact.public_id) >= 0);
                    }
                }

                model.invoice().addItem(); // add blank item
            @else
                // set the default account tax rate
                @if ($account->invoice_taxes && ! empty($defaultTax))
                    var defaultTax = {!! $defaultTax->toJson() !!};
                    model.invoice().tax_rate1(defaultTax.rate);
                    model.invoice().tax_name1(defaultTax.name);
                @endif
            @endif

            @if (isset($tasks) && $tasks)
                // move the blank invoice line item to the end
                var blank = model.invoice().invoice_items.pop();
                var tasks = {!! $tasks !!};

                for (var i=0; i<tasks.length; i++) {
                    var task = tasks[i];
                    var item = model.invoice().addItem();
                    item.notes(task.description);
                    item.qty(task.duration);
                    item.task_public_id(task.publicId);
                    item.invoice_item_type_id = {{ INVOICE_ITEM_TYPE_TASK }};
                }
                model.invoice().invoice_items.push(blank);
                model.invoice().has_tasks(true);
            @endif

            @if (isset($expenses) && $expenses)
                model.expense_currency_id({{ isset($expenseCurrencyId) ? $expenseCurrencyId : 0 }});

                // move the blank invoice line item to the end
                var blank = model.invoice().invoice_items.pop();
                var expenses = {!! $expenses !!}

                for (var i=0; i<expenses.length; i++) {
                    var expense = expenses[i];
                    var item = model.invoice().addItem();
                    item.product_key(expense.expense_category ? expense.expense_category.name : '');
                    item.notes(expense.public_notes);
                    item.qty(1);
                    item.expense_public_id(expense.public_id);
					item.cost(expense.converted_amount);
                    item.tax_rate1(expense.tax_rate1);
                    item.tax_name1(expense.tax_name1);
                    item.tax_rate2(expense.tax_rate2);
                    item.tax_name2(expense.tax_name2);
                }
                model.invoice().invoice_items.push(blank);
                model.invoice().has_expenses(true);
            @endif

        @endif

        // display blank instead of '0'
        if (!NINJA.parseFloat(model.invoice().discount())) model.invoice().discount('');
        if (!NINJA.parseFloat(model.invoice().partial())) model.invoice().partial('');
        if (!model.invoice().custom_value1()) model.invoice().custom_value1('');
        if (!model.invoice().custom_value2()) model.invoice().custom_value2('');

        ko.applyBindings(model);
        onItemChange(true);


		$('#country_id').combobox().on('change', function(e) {
			var countryId = $('input[name=country_id]').val();
            var country = _.findWhere(countries, {id: countryId});
			if (country) {
                model.invoice().client().country = country;
                model.invoice().client().country_id(countryId);
            } else {
				model.invoice().client().country = false;
				model.invoice().client().country_id(0);
			}
		});

		$('[rel=tooltip]').tooltip({'trigger':'manual'});

		$('#invoice_date,  #start_date, #end_date, #last_sent_date').datepicker();

        @if($entityType == "quote")
			$("#invoice_date").trigger("change");
		@endif

		var $input = $('select#client');
        var firstLoad2 = true;
        $("select#contact").select2();
        $("select#custom_text_value2").select2();
        $("select#tags").select2();
		$input.on('change', function(e) {
            @if($invoice->id && Auth::user()->is_admin)
                if(firstLoad2) {
                    firstLoad2 = false;
                    return;
                }
            @endif
            var oldId = model.invoice().client().public_id();
            //var contactid = 0;

            var clientId = parseInt($(this).val(), 10) || 0;
            setTimeout(function(){
                if (clientId > 0) {
                    var selected = clientMap[clientId];
                    $("#custom_text_value1").val(selected.payments_terms).trigger("change");
                    var _contacts = selected.contacts;
                    _contactData = [];

                    for(var j=0; j<_contacts.length; j++){
                        var contact = selected.contacts[j];
                        selected.contacts[j].send_invoice = false;
                        var contactName = getContactDisplayName(contact);

                        _contactData.push({id:contact.public_id,text:contactName})
                    }
                    var reports = [{id:"",text:""}];
                    var reports_types = {
                        'kitchen-exhaust-hood-cleaning':'Kitchen Exhaust Hood Cleaning',
                        'portable-extinguishers-and-emergency-lights':'Portable Extinguishers & Emergency Lights',
                        'fire-suppression':'Fire Suppression',
                        'fire-alarm-system':'Annual Fire Alarm System Test',
                        'grease-interceptor': 'Grease Interceptor'
                    }
                    for(var k=0; k<selected.reports.length; k++){
                        var report = selected.reports[k];
                        var report_text = "";
                        if(reports_types[report.type]){
                            report_text = reports_types[report.type]+": "+ report.report_no;

                        }
                        else
                            report_text = report.type+": "+report.report_no;
                        reports.push({id:report.report_no,text:report_text})
                    }
                    $("select#custom_text_value2").select2('destroy').empty();
                    $("select#custom_text_value2").select2({ data: reports });;
                    model.loadClient(selected);
                    $("select#contact").select2('destroy').empty();
                    $("select#contact").select2({ data: _contactData });

                    // we enable searching by contact but the selection must be the client
                    //$('.client-input').val(getClientDisplayName(selected));
                    // if there's an invoice number pattern we'll apply it now
                    $('input[name=client]').val(clientId);
                    setInvoiceNumber(selected);
                    if(refreshTimer) clearTimeout(refreshTimer);
                    refreshTimer= setTimeout(function(){refreshPDF(true);},100);
                     $("select#contact").trigger("change");
                } else if (oldId) {
                    $('input[name=client]').val(oldId);
                    if(!clientMap[oldId]){
                        model.loadClient($.parseJSON(ko.toJSON(new ClientModel())));
                    }

                    model.invoice().client().country = false;
                    if(refreshTimer) clearTimeout(refreshTimer);
                    refreshTimer= setTimeout(function(){refreshPDF(true);},100);
                }
			},1);

		});

        @if($entityType == 'quote')
        	$("#invoice_date").on("change",function(){
				var days = 30;
				var date = new Date($(this).val());
				var res = date.setMonth(date.getMonth() +1);
            		date.setDate(date.getDate() -1);
            	$("#due_date").datepicker("setDate", date);
            	$("#due_date").trigger("change");
			});
            $("#invoice_date").trigger("change");
        @endif
		@if ($invoice->id || $invoice->client->public_id)
            @if(!$showBreadcrumbs && !Auth::user()->is_admin)
			    $input.off('change');
            @endif    
        setTimeout(function(){
            if (true) {
                var selected = invoice.client;
                @if(!$invoice->id && !$invoice->client->public_id)
                    $("#custom_text_value1").val(selected.payments_terms).trigger("change");
                @endif    
                var _contacts = selected.contacts;
                _contactData = [];
                var current_contact= 0;
                for(var j=0; j<_contacts.length; j++){
                    var contact = selected.contacts[j];
                    selected.contacts[j].send_invoice = false;
                    var contactName = getContactDisplayName(contact);
                    @if ($invoice->id )
                        if(invitationContactIds.length){
                            if(invitationContactIds[0] == contact.public_id)
                                current_contact = contact.public_id;
                        
                        
                        }
                    @endif

                    _contactData.push({id:contact.public_id,text:contactName})
                }
                var reports = [{id:"",text:""}];
                    var reports_types = {
                        'kitchen-exhaust-hood-cleaning':'Kitchen Exhaust Hood Cleaning',
                        'portable-extinguishers-and-emergency-lights':'Portable Extinguishers & Emergency Lights',
                        'fire-suppression':'Fire Suppression',
                        'fire-alarm-system':'Annual Fire Alarm System Test'
                    }
                    for(var k=0; k<selected.reports.length; k++){
                        var report = selected.reports[k];
                        var report_text = "";
                        if(reports_types[report.type]){
                            report_text = reports_types[report.type]+": "+ report.report_no;

                        }
                        else
                            report_text = report.type+": "+report.report_no;
                        reports.push({id:report.report_no,text:report_text})
                    }
                    $("select#custom_text_value2").select2('destroy').empty();
                    $("select#custom_text_value2").select2({ data: reports });;
                    $("select#custom_text_value2").val(invoice.custom_text_value2).change();
                model.loadClient(selected);
                $("select#contact").select2('destroy').empty();
                $("select#contact").select2({ data: _contactData });
                @if ($invoice->id )
                    if(invitationContactIds && invitationContactIds.length){
                        $("select#contact").val(current_contact);
                        
                        
                    }
                @endif
                // we enable searching by contact but the selection must be the client
                //$('.client-input').val(getClientDisplayName(selected));
                // if there's an invoice number pattern we'll apply it now
                //$('input[name=client]').val(clientId);
                setInvoiceNumber(selected);
                if(refreshTimer) clearTimeout(refreshTimer);
                refreshTimer= setTimeout(function(){refreshPDF(true);},100);
                $("select#contact").trigger("change");
            } else if (oldId) {
                $('input[name=client]').val(oldId);
                if(!clientMap[oldId]){
                    model.loadClient($.parseJSON(ko.toJSON(new ClientModel())));
                }

                model.invoice().client().country = false;
                if(refreshTimer) clearTimeout(refreshTimer);
                refreshTimer= setTimeout(function(){refreshPDF(true);},100);
            }
        },5);

		@endif

		// If no clients exists show the client form when clicking on the client select input
		if (clients.length === 0) {
			$('.client_select input.form-control').on('click', function() {
				model.showClientForm();
			});
		}
		var old_contact = 0;

        $("select#contact").on("change",function(){
            //var contactId = parseInt($(this).val(), 10) || 0;
           // model.invoice().client().contacts()[old_contact].send_invoice(false);
           // old_contact = contactId;
            var _contacts = model.invoice().client().contacts();
            for(var k = 0; k< _contacts.length; k++ ){
                _contacts[k].send_invoice(false);
                var contact = _contacts[k];
                if(contact.public_id() == $(this).val()){
                    _contacts[k].send_invoice(true);
                }
            }
            //model.invoice().client().contacts()[contactId].send_invoice(true);
            if(refreshTimer) clearTimeout(refreshTimer);
            refreshTimer= setTimeout(function(){refreshPDF(true);},100);

            if(!firstLoad){
                firstLoad = true;
                setTimeout(function(){NINJA.formIsChanged = false;},100);
            }
		});

				@if ($invoice->id || $invoice->client->public_id)
					//$("select#contact").off("change");
        if(refreshTimer) clearTimeout(refreshTimer);
        refreshTimer= setTimeout(function(){refreshPDF(true);},100);

        	@endif
$('#invoice_footer,#work_number, #terms, #public_notes, #invoice_number, #invoice_date, #due_date, #start_date, #po_number, #discount, #currency_id, #invoice_design_id, #recurring, #is_amount_discount, #partial,#interest, #custom_text_value1, #custom_text_value2').change(function() {
           // $('#downloadPdfButton').attr('disabled', true);
                if(refreshTimer) clearTimeout(refreshTimer);
                refreshTimer= setTimeout(function(){refreshPDF(true);},200);
		});


        $('.frequency_id .input-group-addon').click(function() {
            showLearnMore();
        });

        $('.recurring_due_date .input-group-addon').click(function() {
            showRecurringDueDateLearnMore();
        });

        var fields = ['invoice_date', 'due_date', 'start_date', 'end_date', 'last_sent_date'];
        for (var i=0; i<fields.length; i++) {
            var field = fields[i];
            (function (_field) {
                $('.' + _field + ' .input-group-addon').click(function() {
                    toggleDatePicker(_field);
                });
            })(field);
        }

        if (model.invoice().client().public_id() || {{ $invoice->id || count($clients) == 0 ? '1' : '0' }}) {
            // do nothing
        } else {
            $('.client_select input.form-control').focus();
        }

		$('#clientModal').on('shown.bs.modal', function () {
           // $('#client\\[name\\]').focus();
		}).on('hidden.bs.modal', function () {
			if (model.clientBackup) {
				model.loadClient(model.clientBackup);
                if(refreshTimer) clearTimeout(refreshTimer);
                refreshTimer= setTimeout(function(){refreshPDF(true);},100);
			}
		});

		$('#relatedActions > button:first').click(function() {
			onPaymentClick();
		});

		$('label.radio').addClass('radio-inline');

		@if ($invoice->client->id)
			$input.trigger('change');
		@else
        if(refreshTimer) clearTimeout(refreshTimer);
        	refreshTimer= setTimeout(function(){refreshPDF(true);},100);
		@endif

		var client = model.invoice().client();
		//setComboboxValue($('.client_select'),client.public_id(),client.name.display());

        @if (isset($tasks) && $tasks)
            NINJA.formIsChanged = true;
        @endif

        @if (isset($expenses) && $expenses)
            NINJA.formIsChanged = true;
        @endif

        applyComboboxListeners();

        @if (Auth::user()->account->hasFeature(FEATURE_DOCUMENTS))
        $('.main-form').submit(function(){
            if($('#document-upload .dropzone .fallback input').val())$(this).attr('enctype', 'multipart/form-data')
            else $(this).removeAttr('enctype')
        })

        // Initialize document upload
        window.dropzone = false;
        $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            if (window.dropzone) {
                return;
            }

            var target = $(e.target).attr('href') // activated tab
            if (target != '#attached-documents') {
                return;
            }

            window.dropzone = new Dropzone('#document-upload .dropzone', {
                url:{!! json_encode(url('documents')) !!},
                params:{
                    _token:"{{ Session::getToken() }}"
                },
                acceptedFiles:{!! json_encode(implode(',',\App\Models\Document::$allowedMimes)) !!},
                addRemoveLinks:true,
                dictRemoveFileConfirmation:"{{trans('texts.are_you_sure')}}",
                @foreach(['default_message', 'fallback_message', 'fallback_text', 'file_too_big', 'invalid_file_type', 'response_error', 'cancel_upload', 'cancel_upload_confirmation', 'remove_file'] as $key)
                    "dict{{Utils::toClassCase($key)}}":"{{trans('texts.dropzone_'.$key)}}",
                @endforeach
                maxFilesize:{{floatval(MAX_DOCUMENT_SIZE/1000)}},
				parallelUploads:1,
            });
            if(dropzone instanceof Dropzone){
                dropzone.on("addedfile",handleDocumentAdded);
                dropzone.on("removedfile",handleDocumentRemoved);
                dropzone.on("success",handleDocumentUploaded);
                dropzone.on("canceled",handleDocumentCanceled);
                dropzone.on("error",handleDocumentError);
                for (var i=0; i<model.invoice().documents().length; i++) {
                    var document = model.invoice().documents()[i];
                    var mockFile = {
                        name:document.name(),
                        size:document.size(),
                        type:document.type(),
                        public_id:document.public_id(),
                        status:Dropzone.SUCCESS,
                        accepted:true,
                        url:document.url(),
                        mock:true,
                        index:i
                    };

                    dropzone.emit('addedfile', mockFile);
                    dropzone.emit('complete', mockFile);
                    if(document.preview_url()){
                        dropzone.emit('thumbnail', mockFile, document.preview_url());
                    }
                    else if(document.type()=='jpeg' || document.type()=='png' || document.type()=='svg'){
                        dropzone.emit('thumbnail', mockFile, document.url());
                    }
                    dropzone.files.push(mockFile);
                }
            }
        });
        @endif
	});

    function onFrequencyChange(){
        var currentName = $('#frequency_id').find('option:selected').text()
        var currentDueDateNumber = $('#recurring_due_date').find('option:selected').attr('data-num');
        var optionClass = currentName && currentName.toLowerCase().indexOf('week') > -1 ? 'weekly' :  'monthly';
        var replacementOption = $('#recurring_due_date option[data-num=' + currentDueDateNumber + '].' + optionClass);

        $('#recurring_due_date option').hide();
        $('#recurring_due_date option.' + optionClass).show();

        // Switch to an equivalent option
        if(replacementOption.length){
            replacementOption.attr('selected','selected');
        }
        else{
            $('#recurring_due_date').val('');
        }
    }

	function applyComboboxListeners() {
        var selectorStr = '.invoice-table input, .invoice-table textarea';
		$(selectorStr).off('change').on('change', function(event) {
            if ($(event.target).hasClass('handled')) {
                return;
            }
           // $('#downloadPdfButton').attr('disabled', true);
            onItemChange();
            if(refreshTimer) clearTimeout(refreshTimer);
            refreshTimer= setTimeout(function(){refreshPDF(true);},100);
		});

        var selectorStr = '.invoice-table select';
        $(selectorStr).off('blur').on('blur', function(event) {
            onItemChange();
            if(refreshTimer) clearTimeout(refreshTimer);
            refreshTimer= setTimeout(function(){refreshPDF(true);},100);
        });

        $('textarea.word-wrap').on('keyup focus', function(e) {
            $(this).height(0).height(this.scrollHeight-18);
        });

	}

	function createInvoiceModel() {
        var model = ko.toJS(window.model);
        if(!model)return;
		var invoice = model.invoice;
		invoice.features = {
            customize_invoice_design:{{ Auth::user()->hasFeature(FEATURE_CUSTOMIZE_INVOICE_DESIGN) ? 'true' : 'false' }},
            remove_created_by:{{ Auth::user()->hasFeature(FEATURE_REMOVE_CREATED_BY) ? 'true' : 'false' }},
            invoice_settings:{{ Auth::user()->hasFeature(FEATURE_INVOICE_SETTINGS) ? 'true' : 'false' }}
        };
		invoice.is_quote = {{ $entityType == ENTITY_QUOTE ? 'true' : 'false' }};
		invoice.contact = _.findWhere(invoice.client.contacts, {send_invoice: true});

        if (invoice.is_recurring) {
            invoice.invoice_number = "{{ trans('texts.assigned_when_sent') }}";
            if (invoice.start_date) {
                invoice.invoice_date = invoice.start_date;
            }
        }

        @if (!$invoice->id || $invoice->is_recurring)
            if (!invoice.terms) {
                invoice.terms = account['{{ $entityType }}_terms'];
            }
            if (!invoice.invoice_footer) {
                invoice.invoice_footer = account['invoice_footer'];
            }
        @endif

		@if ($account->hasLogo())
			invoice.image = "{{ Form::image_data($account->getLogoRaw(), true) }}";
			invoice.imageWidth = {{ $account->getLogoWidth() }};
			invoice.imageHeight = {{ $account->getLogoHeight() }};
		@endif

        //invoiceLabels.item = invoice.has_tasks ? invoiceLabels.date : invoiceLabels.item_orig;
        invoiceLabels.quantity = invoice.has_tasks ? invoiceLabels.hours : invoiceLabels.quantity_orig;
        invoiceLabels.unit_cost = invoice.has_tasks ? invoiceLabels.rate : invoiceLabels.unit_cost_orig;

        return invoice;
	}

	function getPDFString(cb, force) {
		@if ( ! $account->live_preview)
			return;
		@endif
        var invoice = createInvoiceModel();
		var design  = getDesignJavascript();
		if (!design) return;
        generatePDF(invoice, design, force, cb);
	}

	function getDesignJavascript() {
		var id = $('#invoice_design_id').val();
		if (id == '-1') {
			showMoreDesigns();
			model.invoice().invoice_design_id(1);
			return invoiceDesigns[0].javascript;
		} else {
            var design = _.find(invoiceDesigns, function(design){ return design.id == id});
            return design ? design.javascript : '';
		}
	}

    function resetTerms() {
        sweetConfirm(function() {
            model.invoice().terms(model.invoice().default_terms());
            refreshPDF();
        });

        return false;
    }

    function resetFooter() {
        sweetConfirm(function() {
            model.invoice().invoice_footer(model.invoice().default_footer());
            refreshPDF();
        });

        return false;
    }

	function onDownloadClick() {
		trackEvent('/activity', '/download_pdf');
		var invoice = createInvoiceModel();
        var design  = getDesignJavascript();
		if (!design) return;
		var doc = generatePDF(invoice, design, true);
        var type = invoice.is_quote ? '{{ trans('texts.'.ENTITY_QUOTE) }}' : '{{ trans('texts.'.ENTITY_INVOICE) }}';
		doc.save(type +'-' + $('#invoice_number').val() + '.pdf');
	}

    function onRecurrClick() {
        var invoice = model.invoice();
        if (invoice.is_recurring()) {
            var recurring = false;
            var enableLabel = "{{ trans('texts.enable_recurring')}}";
			var actionLabel = "{{ trans('texts.mark_sent') }}";
        } else {
            var recurring = true;
            var enableLabel = "{{ trans('texts.disable_recurring')}}";
			var actionLabel = "{{ trans('texts.mark_active') }}";
        }
        invoice.is_recurring(recurring);
        $('#recurrButton').html(enableLabel + "<span class='glyphicon glyphicon-repeat'></span>");
		$('#saveButton').html(actionLabel + "<span class='glyphicon glyphicon-globe'></span>");
    }

	function onEmailClick() {
        if (!NINJA.isRegistered) {
            swal("{!! trans('texts.registration_required') !!}");
            return;
        }
		@if(!$invoice->id && !$invoice->client->public_id)
        var clientId = parseInt($('select#client').val(), 10) || 0;
        if (clientId == 0 ) {
            swal("{!! trans('texts.no_client_selected') !!}");
            return;
        }
		@endif
        if (!isContactSelected()) {
            swal("{!! trans('texts.no_contact_selected') !!}");
            return;
        }

        if (!isEmailValid()) {
            swal("{!! trans('texts.provide_email') !!}");
            return;
        }

		if (model.invoice().is_recurring()) {
			sweetConfirm(function() {
				onConfirmEmailClick();
			}, getSendToEmails());
		} else {
            if(model.invoice().attach_signature() == 1){
                $("#signatureModal").modal();
                $("div#signature").empty().jSignature({signatureLine:false, 'background-color':'transparent',height:150});
                $("#signatureDone").off().on("click",function(){
                    var signature = $("div#signature").jSignature('getData', 'image').join(",");
                    $("input#user_signature").val(signature);
                    
                    var print_name = $("#model-print-name").val();
                    $("#print_name").val(print_name);
                    $("#signatureModal").modal("hide");
                    showEmailModal();
                })
            }
            else
			    showEmailModal();
		}
	}

	function onConfirmEmailClick() {
		$('#emailModal div.modal-footer button').attr('disabled', true);
		model.invoice().is_public(true);
		submitAction('email');
	}
    function onConfirmSendLaterClick() {
        if (!$("#send_at").val()) {
            swal("{!! trans('texts.provide_send_at_date') !!}");
            return;
        }
		$('#emailModal div.modal-footer button').attr('disabled', true);
		//model.invoice().is_public(true);
		submitAction('send_later');
	}

	function onSaveDraftClick() {
		model.invoice().is_public(false);
		onSaveClick();
	}

	function onMarkSentClick() {
		if (model.invoice().is_recurring()) {
			if (!isSaveValid()) {
	            model.showClientForm();
	            return false;
	        }
            // warn invoice will be emailed when saving new recurring invoice
            var text = '\n' + getSendToEmails() + '\n\n' + "{!! trans("texts.confirm_recurring_timing") !!}";
            var title = "{!! trans("texts.confirm_recurring_email_$entityType") !!}";
            sweetConfirm(function() {
				model.invoice().is_public(true);
                submitAction('');
            }, text, title);
            return;
        } else {
			model.invoice().is_public(true);
			onSaveClick();
		}
	}

	function onSaveClick() {
		@if ($invoice->id)
			if (model.invoice().is_recurring()) {
	            if (model.invoice().start_date() != model.invoice().start_date_orig()) {
	                var text = "{!! trans("texts.original_start_date") !!}: " + model.invoice().start_date_orig() + '\n'
	                            + "{!! trans("texts.new_start_date") !!}: " + model.invoice().start_date();
	                var title = "{!! trans("texts.warn_start_date_changed") !!}";
	                sweetConfirm(function() {
	                    submitAction('');
	                }, text, title);
	                return;
	            }
	        }
		@endif

        @if (!empty($autoBillChangeWarning))
            var text = "{!! trans('texts.warn_change_auto_bill') !!}";
            sweetConfirm(function() {
                submitAction('');
            }, text);
            return;
        @endif

        submitAction('');
    }

    function getSendToEmails() {
        var client = model.invoice().client();
        var parts = [];

        for (var i=0; i<client.contacts().length; i++) {
            var contact = client.contacts()[i];
            if (contact.send_invoice()) {
                parts.push(getContactDisplayName(ko.toJS(contact)));
            }
        }

        return parts.join('\n');
    }

    function preparePdfData(action) {
        var invoice = createInvoiceModel();
        var design = getDesignJavascript();
        if (!design) return;

        doc = generatePDF(invoice, design, true);
        doc.getDataUrl( function(pdfString){
            $('#pdfupload').val(pdfString);
            submitAction(action);
        });
    }

	function submitAction(value) {
		if (!isSaveValid()) {
            model.showClientForm();
            return false;
        }

		$('#action').val(value);
		$('#submitButton').click();
	}

    function onFormSubmit(event) {
        if (window.countUploadingDocuments > 0) {
            swal("{!! trans('texts.wait_for_upload') !!}");
            return false;
        }

        @if ($invoice->is_deleted)
            if ($('#bulk_action').val() != 'restore') {
                return false;
            }
        @endif

        // check invoice number is unique
        if ($('.invoice-number').hasClass('has-error')) {
            return false;
        } else if ($('.partial').hasClass('has-error')) {
            return false;
        }

        if (!isSaveValid()) {
            model.showClientForm();
            return false;
        }

        // check currency matches for expenses
        var expenseCurrencyId = model.expense_currency_id();
        var clientCurrencyId = model.invoice().client().currency_id() || {{ $account->getCurrencyId() }};
        if (expenseCurrencyId && expenseCurrencyId != clientCurrencyId) {
            swal("{!! trans('texts.expense_error_mismatch_currencies') !!}");
            return false;
        }

        @if (Auth::user()->canCreateOrEdit(ENTITY_INVOICE, $invoice) || $invoice->taggedUser())
            if ($('#saveButton').is(':disabled')) {
                return false;
            }
            $('#saveButton, #emailButton, #draftButton').attr('disabled', true);
            // if save fails ensure user can try again
            $.post('{{ url($url) }}', $('.main-form').serialize(), function(data) {
				if (data && data.indexOf('http') === 0) {
					NINJA.formIsChanged = false;
					location.href = data;
				} else {
					handleSaveFailed();
				}
            }).fail(function(data) {
				handleSaveFailed(data);
            });
            return false;
        @else
            return false;
        @endif
    }

	function handleSaveFailed(data) {
		$('#saveButton, #emailButton, #draftButton').attr('disabled', false);
		$('#emailModal div.modal-footer button').attr('disabled', false);
		var error = '';
		if (data) {
			var error = firstJSONError(data.responseJSON) || data.statusText;
		}
		swal("{!! trans('texts.invoice_save_error') !!}", error);
	}

    function submitBulkAction(value) {
        $('#bulk_action').val(value);
        $('.bulkForm')[0].submit();
    }

	function isSaveValid() {
		var isValid = model.invoice().client().name() ? true : false;
		for (var i=0; i<model.invoice().client().contacts().length; i++) {
			var contact = model.invoice().client().contacts()[i];
			if (isValidEmailAddress(contact.email()) || contact.first_name() || contact.last_name()) {
				isValid = true;
				break;
			}
		}
		return isValid;
	}

    function isContactSelected() {
		var sendTo = false;
		var client = model.invoice().client();
		for (var i=0; i<client.contacts().length; i++) {
			var contact = client.contacts()[i];
            if (contact.send_invoice()) {
                return true;
            }
		}
		return false;
    }

	function isEmailValid() {
		var isValid = true;
		var client = model.invoice().client();
		for (var i=0; i<client.contacts().length; i++) {
			var contact = client.contacts()[i];
            if ( ! contact.send_invoice()) {
                continue;
            }
			if (isValidEmailAddress(contact.email())) {
				isValid = true;
			} else {
				isValid = false;
				break;
			}
		}
		return isValid;
	}

	function onCloneClick() {
		submitAction('clone');
	}

	function onConvertClick() {
		submitAction('convert');
	}

    @if ($invoice->id)
    	function onPaymentClick() {
            @if (!empty($autoBillChangeWarning))
                sweetConfirm(function() {
                    window.location = '{{ URL::to('payments/create/' . $invoice->client->public_id . '/' . $invoice->public_id ) }}';
                }, "{!! trans('texts.warn_change_auto_bill') !!}");
            @else
                window.location = '{{ URL::to('payments/create/' . $invoice->client->public_id . '/' . $invoice->public_id ) }}';
            @endif
    	}

    	function onCreditClick() {
    		window.location = '{{ URL::to('credits/create/' . $invoice->client->public_id . '/' . $invoice->public_id ) }}';
    	}
    @endif

	function onArchiveClick() {
		submitBulkAction('archive');
	}

	function onDeleteClick() {
        sweetConfirm(function() {
            submitBulkAction('delete');
        });
	}

	function formEnterClick(event) {
		if (event.keyCode === 13){
			if (event.target.type == 'textarea') {
				return;
			}
			event.preventDefault();

            @if($invoice->trashed())
                return;
            @endif
			submitAction('');
			return false;
		}
	}

	function clientModalEnterClick(event) {
		if (event.keyCode === 13){
			event.preventDefault();
            model.clientFormComplete();
            return false;
        }
	}

	function onItemChange(silent)
	{
		var hasEmpty = false;
		for(var i=0; i<model.invoice().invoice_items().length; i++) {
			var item = model.invoice().invoice_items()[i];
			if (item.isEmpty()) {
				hasEmpty = true;
			}
		}

		if (!hasEmpty) {
			model.invoice().addItem();
		}

		if (!silent) {
        	NINJA.formIsChanged = true;
		}
	}

    function onPartialChange()
    {
        var val = NINJA.parseFloat($('#partial').val());
        var oldVal = val;
        val = Math.max(Math.min(val, model.invoice().totals.rawTotal()), 0);

        if (val != oldVal) {
            if ($('.partial').hasClass('has-error')) {
                return;
            }
            $('.partial')
                .addClass('has-error')
                .find('div')
                .append('<span class="help-block">{{ trans('texts.partial_value') }}</span>');
        } else {
            $('.partial')
                .removeClass('has-error')
                .find('span')
                .hide();
        }

    }

    function printPDF(){
        $("body").addClass("pdf-print");
        window.print();
        $("body").removeClass("pdf-print");
    }
    function onRecurringEnabled()
    {
        if ($('#recurring').prop('checked')) {
            $('#emailButton').attr('disabled', true);
            model.invoice().partial('');
        } else {
            $('#emailButton').removeAttr('disabled');
        }
    }

    function showLearnMore() {
        $('#recurringModal').modal('show');
    }

    function showRecurringDueDateLearnMore() {
        $('#recurringDueDateModal').modal('show');
    }

    function setInvoiceNumber(client) {
		@if ($invoice->id || !$account->hasClientNumberPattern($invoice))
            return;
        @endif
        var number = '{{ $account->applyNumberPattern($invoice) }}';
        number = number.replace('{$clientCustom1}', client.custom_value1 ? client.custom_value1 : '');
        number = number.replace('{$clientCustom2}', client.custom_value2 ? client.custom_value1 : '');
        number = number.replace('{$clientIdNumber}', client.id_number ? client.id_number : '');
		@if ($invoice->isQuote() && ! $account->share_counter)
			number = number.replace('{$clientCounter}', pad(client.quote_number_counter, {{ $account->invoice_number_padding }}));
		@else
        	number = number.replace('{$clientCounter}', pad(client.invoice_number_counter, {{ $account->invoice_number_padding }}));
		@endif
		// backwards compatibility
		number = number.replace('{$custom1}', client.custom_value1 ? client.custom_value1 : '');
        number = number.replace('{$custom2}', client.custom_value2 ? client.custom_value1 : '');
        number = number.replace('{$idNumber}', client.id_number ? client.id_number : '');
        model.invoice().invoice_number(number);
    }

    window.countUploadingDocuments = 0;

    function handleDocumentAdded(file){
        // open document when clicked
        if (file.url) {
            file.previewElement.addEventListener("click", function() {
                window.open(file.url, '_blank');
            });
        }
        if(file.mock)return;
        file.index = model.invoice().documents().length;
        model.invoice().addDocument({name:file.name, size:file.size, type:file.type});
        window.countUploadingDocuments++;
    }

    function handleDocumentRemoved(file){
        model.invoice().removeDocument(file.public_id);
        refreshPDF(true);
        $.ajax({
            url: '{{ '/documents/' }}' + file.public_id,
            type: 'DELETE',
            success: function(result) {
                // Do something with the result
            }
        });
    }

    function handleDocumentUploaded(file, response){
        window.countUploadingDocuments--;
        file.public_id = response.document.public_id
        model.invoice().documents()[file.index].update(response.document);
        @if ($account->invoice_embed_documents)
            refreshPDF(true);
        @endif
        if(response.document.preview_url){
            dropzone.emit('thumbnail', file, response.document.preview_url);
        }
    }

    function handleDocumentCanceled() {
        window.countUploadingDocuments--;
    }

    function handleDocumentError() {
        window.countUploadingDocuments--;
    }
	@if($invoice->due_date_text)
		$("#due_date").val('{{$invoice->due_date}}');
	@endif

    function refreshPDF(force) {
        setTimeout(function(){
            return getPDFString(refreshPDFCB, force);
        },100)
        
    }
	</script>
    @if ($account->hasFeature(FEATURE_DOCUMENTS) && $account->invoice_embed_documents)
        @foreach ($invoice->documents as $document)
            @if($document->isPDFEmbeddable())
                <script src="{{ $document->getVFSJSUrl() }}" type="text/javascript" async></script>
            @endif
        @endforeach
        @foreach ($invoice->expenses as $expense)
            @foreach ($expense->documents as $document)
                @if($document->isPDFEmbeddable())
                    <script src="{{ $document->getVFSJSUrl() }}" type="text/javascript" async></script>
                @endif
            @endforeach
        @endforeach
    @endif

@stop
