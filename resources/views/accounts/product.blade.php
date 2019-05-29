@extends('header')


@section('head')
    @parent

    <link href="{{ asset('css/select2.css') }}" rel="stylesheet" type="text/css"/>
    <script src="{{ asset('js/select2.min.js ') }}" type="text/javascript"></script>
@endsection
@section('content')
  @parent

  {!! Former::open($url)->method($method)
      ->rules(['product_key' => 'required|max:255'])
      ->addClass('col-md-10 col-md-offset-1 warn-on-exit') !!}


  <div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title">{!! $title !!}</h3>
  </div>
  <div class="panel-body form-padding-right">

  @if ($product)
    {{ Former::populate($product) }}
    {{ Former::populateField('cost', number_format($product->cost, 2, '.', '')) }}
  @endif

  {!! Former::text('product_key')->label('texts.product') !!}
    {!! Former::select('status')
            ->addOption('Select Stats', '')
            ->label("Status")
            ->fromQuery([["id"=>"active","name"=>"Active"],["id"=>"disabled","name"=>"Disabled"]], function($model) { return $model->name ; }, 'id') !!}
    {!! Former::text('sku') !!}
    {!! Former::text('on_hand') !!}
    {!! Former::select('category_id')
           ->addOption('Select Category', '')
           ->label("Category")
           ->fromQuery($categories, function($model) { return $model->name ; }, 'id') !!}
    {!! Former::select('sub_category_id')
           ->addOption('Select Sub Category', '')
           ->label("Sub Category")
           ->fromQuery($subCats, function($model) { return $model->name ; }, 'id') !!}

    {!! Former::text('part_no') !!}
    
    {!! Former::text('upc') !!}
    {!! Former::textarea('notes')->rows(6) !!}


  @if ($account->hasFeature(FEATURE_INVOICE_SETTINGS))
      @if ($account->custom_invoice_item_label1 && false)
          {!! Former::text('custom_value1')->label($account->custom_invoice_item_label1) !!}
      @endif
      @if ($account->custom_invoice_item_label2 && false)
          {!! Former::text('custom_value2')->label($account->custom_invoice_item_label2) !!}
      @endif
  @endif



  {!! Former::text('cost') !!}

  {!! Former::text('purchase_price') !!}
      {!! Former::select('supplier_id')
                 ->addOption('Select Supplier', '')
                 ->label("Supplier")
                 ->fromQuery($suppliers, function($model) { return $model->name ; }, 'id') !!}
  @if ($account->invoice_item_taxes)
      @include('partials.tax_rates')
  @endif

  </div>
  </div>

  {!! Former::actions(
      Button::normal(trans('texts.cancel'))->large()->asLinkTo(URL::to('/products'))->appendIcon(Icon::create('remove-circle')),
      Button::success(trans('texts.save'))->submit()->large()->appendIcon(Icon::create('floppy-disk'))
  ) !!}

  {!! Former::close() !!}

  <script type="text/javascript">

  $(function() {
    $('#product_key').focus();
    $("#category_id").on("change",function(){
        $("#sub_category_id").html("<option value=''>Select Sub Category</option>");
        $.get("/category/sub/"+$(this).val(),function(data){
           $.each(data,function(index,elm){
               $("#sub_category_id").append("<option value='"+this.id+"'>"+this.name+"</option>")
            })
        });
    })

    $("#supplier_id").select2();
  });

  </script>

@stop
