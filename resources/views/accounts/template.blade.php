<div role="tabpanel" class="tab-pane {{ isset($active) && $active ? 'active' : '' }}" id="{{ $field }}">
    <div class="panel-body" style="padding-bottom: 0px">
        @if (isset($isReminder) && $isReminder)

            {!! Former::populateField('enable_' . $field, intval($account->{'enable_' . $field})) !!}
            {!! Former::populateField('enable_' . $field, intval($account->{'enable_' . $field})) !!}
            @if (floatval($fee = $account->account_email_settings->{"late_fee{$number}_amount"}))
                {!! Former::populateField('late_fee' . $number . '_amount', $fee) !!}
            @endif
            @if (floatval($fee = $account->account_email_settings->{"late_fee{$number}_percent"}))
                {!! Former::populateField('late_fee' . $number . '_percent', $fee) !!}
            @endif
            
            <div class="well" style="padding-bottom:20px">
                <div class="row">
                    <div class="col-md-6">
                        {!! Former::plaintext('schedule')
                                ->value(
                                    Former::input('num_days_' . $field)
                                        ->style('float:left;width:20%')
                                        ->raw() .
                                    Former::select('direction_' . $field)
                                        ->addOption(trans('texts.days_before'), REMINDER_DIRECTION_BEFORE)
                                        ->addOption(trans('texts.days_after'), REMINDER_DIRECTION_AFTER)
                                        ->style('float:left;width:40%')
                                        ->raw() .
                                    '<div id="days_after_'. $field .'" style="float:left;width:40%;display:none;padding-top:8px;padding-left:16px;font-size:16px;">' . trans('texts.days_after') . '</div>' .
                                    Former::select('field_' . $field)
                                        ->addOption(trans('texts.field_due_date'), REMINDER_FIELD_DUE_DATE)
                                        ->addOption(trans('texts.field_invoice_date'), REMINDER_FIELD_INVOICE_DATE)
                                        ->style('float:left;width:40%')
                                        ->raw()
                                ) !!}
                    </div>
                    <div class="col-md-6">

                        {!! Former::checkbox('enable_' . $field)
                                ->text('enable')
                                ->label('send_email')
                                ->value(1) !!}

                    </div>
                </div>
                <div class="row" style="padding-top:30px">
                    <div class="col-md-6">
                        {!! Former::text('late_fee' . $number . '_amount')
                                        ->label('late_fee_amount')
                                        ->type('number')
                                        ->step('any') !!}
                    </div>
                    <div class="col-md-6">
                        {!! Former::text('late_fee' . $number . '_percent')
                                        ->label('late_fee_percent')
                                        ->type('number')
                                        ->step('any')
                                        ->append('%') !!}
                    </div>
                </div>
            </div>
            <br/>
        @endif

        {!! Former::populateField('email_template_' . $field,${'templates_'.$field}) !!}
        <div class="row">
            <div class="col-md-12">
                {!! Former::select('email_template_' . $field)
                            ->label('template')
                            ->fromQuery($templates, function($model) { return $model->name; }, 'id') !!}
            </div>
        </div>
        <div class="row">
           {{--  <div class="col-md-6">
                <div class="pull-right"><a href="#" onclick="return resetText('{{ 'subject' }}', '{{ $field }}')">{{ trans("texts.reset") }}</a></div>
                {!! Former::text('email_subject_' . $field)
                        ->label(trans('texts.subject'))
                        ->appendIcon('question-sign')
                        ->addGroupClass('email-subject') !!}
            </div> --}}
        <div class="col-md-12">
            <p>&nbsp;<p/>
                <div id="{{ $field }}_subject_preview"></div>
        </div>
        <div class="col-md-12">
                <p>&nbsp;<p/>
                <div id="{{ $field }}_template_preview" class="template-preview"><iframe></iframe></div>
            </div>
        </div>
        <p>&nbsp;<p/>
        
    </div>
</div>

<script type="text/javascript">
    $(function() {
        

        

        $("#email_template_{{$field}}").on("change",function(){
            $.getJSON("/api/templates/"+$(this).val()+"/json",function(res){
               
            $('#{{ $field }}_template_preview iframe').contents().find('body').html(renderEmailTemplate(res['content']));
            $("#{{ $field }}_subject_preview").html(renderEmailTemplate(res['subject']));

                //refreshPreview();
        });
        });
        $("#email_template_{{$field}}").trigger("change");
    });



</script>
