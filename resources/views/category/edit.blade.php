@extends('header')

@section('content')
    @parent

    {!! Former::open($url)->method($method)
        ->rules(['name' => 'required|max:255'])
        ->addClass('col-md-10 col-md-offset-1 warn-on-exit') !!}


    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">{!! $title !!}</h3>
        </div>
        <div class="panel-body form-padding-right">

            @if ($category)
                {{ Former::populate($category) }}

            @endif

            {!! Former::text('name')->label('texts.category') !!}


                {!! Former::select('parent_id')
                          ->addOption('Select Parent Category', '')
                          ->label(trans('texts.parent_category'))
                          ->fromQuery($categories, function($model) { return $model->name; }, 'id') !!}

        </div>
    </div>

    {!! Former::actions(
        Button::normal(trans('texts.cancel'))->large()->asLinkTo(URL::to('/category'))->appendIcon(Icon::create('remove-circle')),
        Button::success(trans('texts.save'))->submit()->large()->appendIcon(Icon::create('floppy-disk'))
    ) !!}

    {!! Former::close() !!}

    <script type="text/javascript">

        $(function() {
            $('#name').focus();
        });

    </script>

@stop
