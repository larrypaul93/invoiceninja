@extends('header')

@section('onReady')
	$('input#name').focus();
@stop

@section('content')

@if ($errors->first('contacts'))
    <div class="alert alert-danger">{{ trans($errors->first('contacts')) }}</div>
@endif

<div class="row">

	{!! Former::open($url)
            ->autocomplete('off')
            ->rules(
                ['email' => 'email']
            )->addClass('col-md-12 warn-on-exit')
            ->method($method) !!}

    @include('partials.autocomplete_fix')

	@if ($client)
		{!! Former::populate($client) !!}
		{!! Former::populateField('task_rate', floatval($client->task_rate) ? Utils::roundSignificant($client->task_rate) : '') !!}
		{!! Former::populateField('show_tasks_in_portal', intval($client->show_tasks_in_portal)) !!}
		{!! Former::populateField('send_reminders', intval($client->send_reminders)) !!}
        
        {!! Former::hidden('public_id') !!}
	@else
		{!! Former::populateField('invoice_number_counter', 1) !!}
		{!! Former::populateField('quote_number_counter', 1) !!}
		@if ($account->client_number_counter)
			{!! Former::populateField('id_number', $account->getNextNumber()) !!}
		@endif
	@endif

	<div class="row">
		<div class="col-md-6">


			<div class="panel panel-default" style="min-height: 380px">
				<div class="panel-heading">
					<h3 class="panel-title">{!! trans('texts.organization') !!}</h3>
				</div>
				<div class="panel-body">
					@if(!$client || $client && !$client->public_id)
						{{Former::populateField("type","Client")}}
						{{Former::populateField("user_id",Auth::user()->id)}}
					@endif
					{!! Former::text('name') !!}
					{!! Former::text('suffix') !!}
					{!! Former::text('legal_business_name') !!}
					@if(Auth::user()->hasPermission("edit_all"))
						{!! Former::select('user_id')->label("Account Manger")->fromQuery($accountManger,function($model){return $model->name;},"id") !!}
					@endif
					{!! Former::hidden('type') !!}
					{!! Former::checkbox('is_supplier')->value(1) !!}
					{!! Former::select('region')->options(["GVRD","FVRD","RMOW","CRD","SCRD","SLRD","RDOS","GTA","SF"]) !!}
					{!! Former::select('status')->options(["Active","Closed","Inactive","Suspended"]) !!}
					
					{!! Former::text('work_phone') !!}
					{!! Former::text('phone_home') !!}
					{!! Former::text('phone_main') !!}
					{!! Former::text('phone_fax') !!}
					{!! Former::text('phone_toll_free') !!}
					{!! Former::text('phone_cell') !!}
					{!! Former::text('email') !!}
					{!! Former::text('website') !!}
					{!! Former::text('keywords') !!}
					{!! Former::checkbox('personal') !!}
					{{-- {!! Former::file('site_image') !!} --}}
					
					
					{!! Former::text('id_number')->placeholder($account->clientNumbersEnabled() ? $account->getNextNumber() : ' ') !!}
					{!! Former::text('vat_number') !!}
					
					@if (Auth::user()->hasFeature(FEATURE_INVOICE_SETTINGS))
						@if ($customLabel1)
							{!! Former::text('custom_value1')->label($customLabel1) !!}
						@endif
						@if ($customLabel2)
							{!! Former::text('custom_value2')->label($customLabel2) !!}
						@endif
					@endif

					@if ($account->usesClientInvoiceCounter())
						{!! Former::text('invoice_number_counter')->label('invoice_counter') !!}

						@if (! $account->share_counter)
							{!! Former::text('quote_number_counter')->label('quote_counter') !!}
						@endif
					@endif
				</div>
			</div>

			<div class="panel panel-default" style="min-height: 500px">
				<div class="panel-heading">
					<h3 class="panel-title">{!! trans('texts.additional_info') !!}</h3>
				</div>
				<div class="panel-body">
					{!! Former::select('timezone_id')->addOption("","")->fromQuery($timezones,function($item){return $item->name;},"id") !!}
					{!! Former::select('currency_id')->addOption('','')
						->placeholder($account->currency ? $account->currency->name : '')
						->fromQuery($currencies, 'name', 'id') !!}
					{!! Former::select('language_id')->addOption('','')
						->placeholder($account->language ? trans('texts.lang_'.$account->language->name) : '')
						->fromQuery($languages, 'name', 'id') !!}
					{!! Former::select('payment_type')->options(["Cash","Cheque","MasterCard","Visa","PO"]) !!}	
					{!! Former::select('payment_terms')->addOption('','')->options(["50 / 50","50 / 40 / 10","Due on Completion","Due on Receipt","Net 15", "Net 30"])
						->help(trans('texts.payment_terms_help')) !!}
						@if ($account->isModuleEnabled(ENTITY_TASK))
							{!! Former::text('task_rate')
									->placeholder($account->present()->taskRate)
									->help('task_rate_help') !!}
							{!! Former::checkbox('show_tasks_in_portal')
						        ->text(trans('texts.show_tasks_in_portal'))
								->label('client_portal')
						        ->value(1) !!}
						@endif
						@if ($account->hasReminders())
							{!! Former::checkbox('send_reminders')
								->text('send_client_reminders')
								->label('reminders')
								->value(1) !!}
						@endif
					{!! Former::select('size_id')->addOption('','')
						->fromQuery($sizes, 'name', 'id') !!}
					{!! Former::select('industry_id')->addOption('','')
						->fromQuery($industries, 'name', 'id') !!}
					{!! Former::textarea('private_notes') !!}


					@if (Auth::user()->account->isNinjaAccount())
						@if (isset($planDetails))
							{!! Former::populateField('plan', $planDetails['plan']) !!}
							{!! Former::populateField('plan_term', $planDetails['term']) !!}
							@if (!empty($planDetails['paid']))
								{!! Former::populateField('plan_paid', $planDetails['paid']->format('Y-m-d')) !!}
							@endif
							@if (!empty($planDetails['expires']))
								{!! Former::populateField('plan_expires', $planDetails['expires']->format('Y-m-d')) !!}
							@endif
							@if (!empty($planDetails['started']))
								{!! Former::populateField('plan_started', $planDetails['started']->format('Y-m-d')) !!}
							@endif
						@endif
						{!! Former::select('plan')
									->addOption(trans('texts.plan_free'), PLAN_FREE)
									->addOption(trans('texts.plan_pro'), PLAN_PRO)
									->addOption(trans('texts.plan_enterprise'), PLAN_ENTERPRISE)!!}
						{!! Former::select('plan_term')
									->addOption()
									->addOption(trans('texts.plan_term_yearly'), PLAN_TERM_YEARLY)
									->addOption(trans('texts.plan_term_monthly'), PLAN_TERM_MONTHLY)!!}
						{!! Former::text('plan_started')
									->data_date_format('yyyy-mm-dd')
									->addGroupClass('plan_start_date')
									->append('<i class="glyphicon glyphicon-calendar"></i>') !!}
						{!! Former::text('plan_paid')
									->data_date_format('yyyy-mm-dd')
									->addGroupClass('plan_paid_date')
									->append('<i class="glyphicon glyphicon-calendar"></i>') !!}
						{!! Former::text('plan_expires')
									->data_date_format('yyyy-mm-dd')
									->addGroupClass('plan_expire_date')
									->append('<i class="glyphicon glyphicon-calendar"></i>') !!}
						<script type="text/javascript">
							$(function() {
								$('#plan_started, #plan_paid, #plan_expires').datepicker();
							});
						</script>
					@endif

				</div>
			</div>
        
        </div>
	
	
		<div class="col-md-6">
			<div  data-bind='template: { foreach: addresses, beforeRemove: hideAddress, afterAdd: showAddress }'>
				<div class="panel panel-default" >
				<div class="panel-heading">
					<h3 class="panel-title" data-bind="text: type()+ ' {!! trans('texts.address') !!}'">{!! trans('texts.address') !!}</h3>
				</div>
				<div class="panel-body">
					<div >
						{!! Former::hidden('id')->data_bind("value: id, valueUpdate: 'afterkeydown', attr: {name: 'address[' + \$index() + '][id]'}") !!}
						{!! Former::hidden('type')->data_bind("value:type, valueUpdate:'afterkeydown', attr:{name: 'address[' + \$index() + '][type]'}") !!}
						
						{!! Former::select('country')->addClass("country")->addOption('','')->fromQuery($countries, 'name', 'id')->data_bind("value:country, valueUpdate:'afterkeydown', attr:{name: 'address[' + \$index() + '][country]'}") !!}
						<div data-bind="visible: states() && states().length">
							{!! Former::select('state_dropdown')->label("State")->data_bind("optionsCaption: 'Select State',optionsValue: 'code',optionsText: function(item) {return item.name; }, options: states, value:state_dropdown, valueUpdate:'afterkeydown'") !!}
						
						</div>
						<div data-bind="visible: !states() || !states().length">
							{!! Former::text('state_text')->label("State")->addClass("combobox-state")->data_bind("visible: !states() || !states().length, value:state_text, valueUpdate:'afterkeydown'") !!}
						
						</div>
						{!! Former::hidden('state')->data_bind("value:state, valueUpdate:'afterkeydown', attr:{name: 'address[' + \$index() + '][state]'}") !!}
						{!! Former::text('city')->data_bind("value:city, valueUpdate:'afterkeydown', attr:{name: 'address[' + \$index() + '][city]'}") !!}
						{!! Former::text('address_1')->data_bind("value:address_1, valueUpdate:'afterkeydown', attr:{name: 'address[' + \$index() + '][address_1]'}") !!}
						{!! Former::text('address_2')->data_bind("value:address_2, valueUpdate:'afterkeydown', attr:{name: 'address[' + \$index() + '][address_2]'}") !!}
						{!! Former::text('zip')->data_bind("value:zip, valueUpdate:'afterkeydown', attr:{name: 'address[' + \$index() + '][zip]'}") !!}
						{{-- <div class="form-group">
							<div class="col-lg-8 col-lg-offset-4 bold">
								<span class="redlink bold" data-bind="visible: $parent.addresses().length > 4">
									{!! link_to('#', trans('texts.remove_address').' -', array('data-bind'=>'click: $parent.removeAddress')) !!}
								</span>
								<span  class="pull-right greenlink bold">
									{!! link_to('#', trans('texts.add_address').' +', array('onclick'=>'return addAddress()')) !!}
								</span>
							</div>
						</div> --}}
					</div>

				</div>
			</div>
			</div>
			

			

		</div>
	</div>


	{!! Former::hidden('data')->data_bind("value: ko.toJSON(model)") !!}

	<script type="text/javascript">

	

	var countriesStates = {!! json_encode($countriesStates) !!}
	function AddressModel(data) {
		var self = this;
		self.id = ko.observable('');
		self.country = ko.observable('');
		self.state = ko.observable('');
		self.state_text = ko.observable('');
		self.state_dropdown = ko.observable('');
		self.states= ko.observableArray([]);
		self.city = ko.observable('');
		self.address_1 = ko.observable('');
		self.address_2 = ko.observable('');
		self.zip = ko.observable('');
		self.type = ko.observable('');
		self.country.subscribe(function(newValue) {
			self.states(countriesStates[newValue]);
			
		});
		self.state_text.subscribe(function(state){
			if(!self.states() || !self.states().length)
				self.state(state);
			
		})
		self.state_dropdown.subscribe(function(state){
			if(self.states() && self.states().length)
			self.state(state);
			
		})
		self.state.subscribe(function(state){
			self.state_text(state);
			self.state_dropdown(state);
			
		})
		if (data) {
			
			ko.mapping.fromJS(data, {
				'country': {
					create:function(options){
						console.log("Country");
						var country_id = options.data;
						if(countriesStates[country_id])
							self.states(countriesStates[country_id]);
						else
							self.states([])
						self.state_dropdown(self.state());
						self.state_text(self.state());
						return options.data;
					}
				} 
			}, self);
		}
		
	}

	function ClientModel(data) {
		var self = this;

        self.addresses = ko.observableArray();

		self.mapping = {
		    'addresses': {
		    	create: function(options) {
		    		return new AddressModel(options.data);
		    	}
		    }
		}

		if (data) {
			var addresses = data.addresses;
			data.addresses = []; 
			ko.mapping.fromJS(data, self.mapping, this);
			var is_found = false;
			for(var i=0; i< addresses.length; i++){
				if(addresses[i].type == "Main"){
					is_found = true;
					self.addresses.push(new AddressModel(addresses[i]));

				}
			}
			if(!is_found){
				self.addresses.push(new AddressModel({type:"Main",country:"38",state:"BC"}));
			}
			is_found = false;
			for(var i=0; i< addresses.length; i++){
				if(addresses[i].type == "Billing"){
					is_found = true;
					self.addresses.push(new AddressModel(addresses[i]));
				}
			}
			if(!is_found){
				self.addresses.push(new AddressModel({type:"Billing",country:"38",state:"BC"}));
			}
			is_found = false;
			for(var i=0; i< addresses.length; i++){
				if(addresses[i].type == "Home"){
					is_found = true;
					self.addresses.push(new AddressModel(addresses[i]));
				}
			}
			if(!is_found){
				self.addresses.push(new AddressModel({type:"Home",country:"38",state:"BC"}));
			}
			is_found = false;
			for(var i=0; i< addresses.length; i++){
				if(addresses[i].type == "Head Office"){
					is_found = true;
					self.addresses.push(new AddressModel(addresses[i]));
				}
			}
			if(!is_found){
				self.addresses.push(new AddressModel({type:"Head Office",country:"38",state:"BC"}));
			}
		} else {
			self.addresses.push(new AddressModel({type:"Main",country:"38",state:"BC"}));
			self.addresses.push(new AddressModel({type:"Billing",country:"38",state:"BC"}));
			self.addresses.push(new AddressModel({type:"Home",country:"38",state:"BC"}));
			self.addresses.push(new AddressModel({type:"Head Office",country:"38",state:"BC"}));
			
		}

		
	}
	var dd = {!! $client !!}
    @if ($data)
        window.model = new ClientModel({!! $data !!});
    @else
	    window.model = new ClientModel({!! $client !!});
    @endif

	
	model.showAddress = function(elem) { if (elem.nodeType === 1) $(elem).hide().slideDown(); $('select',$(elem)).combobox(); }
	model.hideAddress = function(elem) { if (elem.nodeType === 1) $(elem).slideUp(function() { $(elem).remove(); }) }

	function addAddress() {
		model.addresses.push(new AddressModel());
		return false;
	}
	model.removeAddress = function() {
		model.addresses.remove(this);
	}

	ko.applyBindings(model);

	
	$(function() {
	//	$("select.country, #language_id, #currency_id, #timezone_id").combobox();
	});


	</script>

	<center class="buttons">
    	{!! Button::normal(trans('texts.cancel'))->large()->asLinkTo(URL::to('/clients/' . ($client ? $client->public_id : '')))->appendIcon(Icon::create('remove-circle')) !!}
        {!! Button::success(trans('texts.save'))->submit()->large()->appendIcon(Icon::create('floppy-disk')) !!}
	</center>

	{!! Former::close() !!}
</div>
@stop
