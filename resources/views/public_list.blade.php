@extends('public.header')

@section('content')

	<style type="text/css">
        table.dataTable thead > tr > th, table.invoice-table thead > tr > th {
            background-color: {{ $color }} !important;
        }

        .pagination>.active>a,
        .pagination>.active>span,
        .pagination>.active>a:hover,
        .pagination>.active>span:hover,
        .pagination>.active>a:focus,
        .pagination>.active>span:focus {
            background-color: {{ $color }};
            border-color: {{ $color }};
        }

        table.table thead .sorting:after { content: '' !important }
        table.table thead .sorting_asc:after { content: '' !important }
        table.table thead .sorting_desc:after { content: '' !important }
        table.table thead .sorting_asc_disabled:after { content: '' !important }
        table.table thead .sorting_desc_disabled:after { content: '' !important }

	</style>

	<div class="container" id="main-container" style="min-height:800px">

		<p>&nbsp;</p>

		<!--
		<div id="top_right_buttons" class="pull-right">
			<input id="tableFilter" type="text" style="width:140px;margin-right:17px" class="form-control pull-left" placeholder="{{ trans('texts.filter') }}"/>
		</div>
		-->

        @if($entityType == ENTITY_INVOICE && $client->hasRecurringInvoices())
            <div class="pull-right" style="margin-top:5px">
                {!! Button::primary(trans("texts.recurring_invoices"))->asLinkTo(URL::to('/client/invoices/recurring')) !!}
            </div>
        @endif
        <h3>{{ $title }}</h3>

		{!! Datatable::table()
	    	->addColumn($columns)
	    	->setUrl(route('api.client.' . $entityType . 's'))
	    	->setOptions('sPaginationType', 'bootstrap')
	    	->render('datatable') !!}
	</div>

    @if ($entityType == ENTITY_RECURRING_INVOICE)
        {!! Former::open(URL::to('/client/invoices/auto_bill'))->id('auto_bill_form')  !!}
        <input type="hidden" name="public_id" id="auto_bill_public_id">
        <input type="hidden" name="enable" id="auto_bill_enable">
        {!! Former::close() !!}

        <script type="text/javascript">
            function setAutoBill(publicId, enable){
                $('#auto_bill_public_id').val(publicId);
                $('#auto_bill_enable').val(enable?'1':'0');
                $('#auto_bill_form').submit();
            }
        </script>
    @endif

    <div id="client-email" class="modal fade" role="dialog">
    <div class="modal-dialog">

        <!-- Modal content-->
        <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">Email <span id="email-type-label">Invoice</span></h4>
        </div>
        {!! Former::open("/send-email/")->name("send-client-email")->method("POST") !!}
            <div class="modal-body">
                
                {!! Former::hidden("id") !!}
                {!! Former::hidden("type") !!}
                {!! Former::input("name")->label("Receiver Name") !!}
                {!! Former::email("email")->label("Receiver Email") !!}
                {!! Former::textarea("message")->label("Message") !!}
                
                
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Send</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        {!! Former::close() !!}    
        </div>

    </div>
    </div>
    <script>
        String.prototype.ucwords = function() {
            str = this.toLowerCase();
            return str.replace(/(^([a-zA-Z\p{M}]))|([ -][a-zA-Z\p{M}])/g,
                function(s){
                return s.toUpperCase();
                });
        };
        function sendClientEmail(type,id){
            $("#email-type-label").text(type.ucwords());
            $("#client-email input[name='type']").val(type);
            $("#client-email input[name='id']").val(id);
            $("#client-email").modal();
        }
    </script>

	<p>&nbsp;</p>

@stop
