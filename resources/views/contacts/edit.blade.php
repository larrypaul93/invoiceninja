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

	@if ($contact)
		{!! Former::populate($contact) !!}
        {!! Former::hidden('public_id') !!}
	
	@endif

	<div class="row">
		<div class="col-md-6">


			<div class="panel panel-default" style="min-height: 380px">
				<div class="panel-heading">
					<h3 class="panel-title">{!! trans('texts.info') !!}</h3>
				</div>
				<div class="panel-body">
					@if(!$contact || $contact && !$contact->public_id)
						
						{{Former::populateField("user_id",Auth::user()->id)}}
					@endif
					{!! Former::text('first_name') !!}
					{!! Former::text('last_name') !!}
                    
						{!! Former::select('client_id')->label("Account")->addOption("","")->fromQuery($clients,function($model){return ($model->suffix?$model->name."(".$model->suffix.")":$model->name);},'id') !!}
                    
					{!! Former::text('username') !!}
                    {!! Former::password('password') !!}
                    
                    {!! Former::text('position') !!}
					@if(Auth::user()->hasPermission("edit_all"))
						{!! Former::select('user_id')->label("Account Manger")->fromQuery($accountManger,function($model){return $model->name;},"id") !!}
					@endif
					{!! Former::select('type')->addOptions("","")->fromQuery($roles,function($model){return $model->name;},"id") !!}
					{!! Former::select('region')->options(["GVRD","FVRD","RMOW","CRD","SCRD","SLRD","RDOS","GTA","SF"]) !!}
					{!! Former::select('status')->options(["Active","Closed","Inactive","Suspended"]) !!}
					
					{!! Former::text('phone_business') !!}
					{!! Former::text('phone_home') !!}
					{!! Former::text('phone_main') !!}
					{!! Former::text('phone_fax') !!}
					{!! Former::text('phone_toll_free') !!}
					{!! Former::text('phone_cell') !!}
					{!! Former::text('email') !!}
					{!! Former::text('webmail_email') !!}
					{!! Former::text('webmail_password') !!}
					{!! Former::select('hear_about_us')->options(["Direct Contact (Sales Rep)","Magazine","N/A","Newspaper","Referral","Sales Representative","Search Engine (Google Yahoo Bing etc.)","Trade Show"]) !!}

					{!! Former::text('website') !!}
					{!! Former::text('keywords') !!}
					{!! Former::checkbox('personal') !!}
					{{-- {!! Former::file('contact_image') !!}
					{!! Former::file('avatar') !!} --}}
					
					
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
        self.first = false;
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
            if(!self.first){
                self.state_text(state);
			    self.state_dropdown(state);
                self.first = true;
            }
			
			
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
                        if(self.sate && self.sate()){
                            self.state_dropdown(self.state);
						    self.state_text(self.state);
                        }    
						
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
	
    @if ($data)
        window.model = new ClientModel({!! $data !!});
    @else
	    window.model = new ClientModel({!! $contact !!});
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
		$("#client_id").combobox();
	});


	</script>

	<center class="buttons">
    	{!! Button::normal(trans('texts.cancel'))->large()->asLinkTo(URL::to('/clients/' . ($client ? $client->public_id : '')))->appendIcon(Icon::create('remove-circle')) !!}
        {!! Button::success(trans('texts.save'))->submit()->large()->appendIcon(Icon::create('floppy-disk')) !!}
	</center>

	{!! Former::close() !!}
</div>
@stop
