<script type="text/javascript">

function ViewModel(data) {
    var self = this;
    self.showMore = ko.observable(false);

    //self.invoice = data ? false : new InvoiceModel();
    self.invoice = ko.observable(data ? false : new InvoiceModel());
    self.expense_currency_id = ko.observable();
    self.products = {!! $products !!};

    self.loadClient = function(client) {
        ko.mapping.fromJS(client, model.invoice().client().mapping, model.invoice().client);
        @if (!$invoice->id)
            self.setDueDate();
        @endif
    }

    self.showMoreFields = function() {
        self.showMore(!self.showMore());
    }

    self.setDueDate = function() {
        @if ($entityType == ENTITY_INVOICE)
            var paymentTerms = parseInt(self.invoice().client().payment_terms());
            if (paymentTerms && paymentTerms != 0 && !self.invoice().due_date())
            {
                if (paymentTerms == -1) paymentTerms = 0;
                var dueDate = $('#invoice_date').datepicker('getDate');
                dueDate.setDate(dueDate.getDate() + paymentTerms);
                self.invoice().due_date(dueDate);
                // We're using the datepicker to handle the date formatting
                self.invoice().due_date($('#due_date').val());
            }
        @endif
    }

    self.clearBlankContacts = function() {
        var client = self.invoice().client();
        var contacts = client.contacts();
        $(contacts).each(function(index, contact) {
            if (index > 0 && contact.isBlank()) {
                client.contacts.remove(contact);
            }
        });
    }

    self.invoice_taxes = ko.observable({{ Auth::user()->account->invoice_taxes ? 'true' : 'false' }});
    self.invoice_item_taxes = ko.observable({{ Auth::user()->account->invoice_item_taxes ? 'true' : 'false' }});
    self.show_item_taxes = ko.observable({{ Auth::user()->account->show_item_taxes ? 'true' : 'false' }});

    self.mapping = {
        'invoice': {
            create: function(options) {
                return new InvoiceModel(options.data);
            }
        },
    }

    if (data) {
        ko.mapping.fromJS(data, self.mapping, self);
    }

    self.invoice_taxes.show = ko.computed(function() {
        if (self.invoice().tax_name1() || self.invoice().tax_name2()) {
            return true;
        }

        return self.invoice_taxes() && {{ count($taxRateOptions) ? 'true' : 'false' }};
    });

    self.invoice_item_taxes.show = ko.computed(function() {
        if (self.invoice_item_taxes() && {{ count($taxRateOptions) ? 'true' : 'false' }}) {
            return true;
        }
        for (var i=0; i<self.invoice().invoice_items().length; i++) {
            var item = self.invoice().invoice_items()[i];
            if (item.tax_name1() || item.tax_name2()) {
                return true;
            }
        }
        return false;
    });

    self.showClientForm = function() {
        trackEvent('/activity', '/view_client_form');
        self.clientBackup = ko.mapping.toJS(self.invoice().client);

        $('#emailError').css( "display", "none" );
        $('#clientModal').modal('show');
    }

    self.clientFormComplete = function() {
        trackEvent('/activity', '/save_client_form');

        var email = $("[name='client[contacts][0][email]']").val();
        var firstName = $("[name='client[contacts][0][first_name]']").val();
        var lastName = $("[name='client[contacts][0][last_name]']").val();
        var name = $("[name='client[name]']").val();

        if (name) {
            //
        } else if (firstName || lastName) {
            name = firstName + ' ' + lastName;
        } else {
            name = email;
        }

        var isValid = name ? true : false;
        var contacts = self.invoice().client().contacts();
        $(contacts).each(function(item, value) {
            if (value.isValid()) {
                isValid = true;
            }
        });
        if (!isValid) {
            $('#emailError').css( "display", "inline" );
            return;
        }

        if (self.invoice().client().public_id() == 0) {
            self.invoice().client().public_id(-1);
            self.invoice().client().invoice_number_counter = 1;
            self.invoice().client().quote_number_counter = 1;
        }

        model.setDueDate();
        model.clearBlankContacts();

        setComboboxValue($('.client_select'), -1, name);

        var client = $.parseJSON(ko.toJSON(self.invoice().client()));
        setInvoiceNumber(client);

        //$('.client_select select').combobox('setSelected');
        //$('.client_select input.form-control').val(name);
        //$('.client_select .combobox-container').addClass('combobox-selected');

        $('#emailError').css( "display", "none" );

        refreshPDF(true);
        model.clientBackup = false;
        $('#clientModal').modal('hide');
    }

    self.clientLinkText = ko.computed(function() {
        if (self.invoice().client().public_id())
        {
            return "{{ trans('texts.edit_client') }}";
        }
        else
        {
            if (clients.length > {{ Auth::user()->getMaxNumClients() }})
            {
                return '';
            }
            else
            {
                return "{{ trans('texts.create_new_client') }}";
            }
        }
    });


}

function InvoiceModel(data) {
    if (data) {
        var clientModel = false;
    } else {
        var clientModel = new ClientModel();
        clientModel.id_number("{{ $account->getNextNumber() }}");
    }

    var self = this;
    this.client = ko.observable(clientModel);
    this.is_public = ko.observable(0);
    self.account = {!! $account !!};
    self.id = ko.observable('');

    self.rep = ko.observable("{{ Auth::user()->getRep()}}");
    self.discount = ko.observable('');
    self.is_amount_discount = ko.observable(0);
    self.frequency_id = ko.observable(4); // default to monthly
    self.terms = ko.observable('');
    self.work_number = ko.observable('');
    self.attach_signature = ko.observable('');
    self.user_signature = ko.observable('');
    self.user_company = ko.observable('');
    self.user_position = ko.observable('');
    self.user_name = ko.observable('');
    self.print_name=ko.observable('');
    self.additional_info = ko.observable("");
    self.default_terms = ko.observable(account.{{ $entityType }}_terms);
    self.terms_placeholder = ko.observable({{ (!$invoice->id || $invoice->is_recurring) && $account->{"{$entityType}_terms"} ? "account.{$entityType}_terms" : false}});
    self.set_default_terms = ko.observable(false);
    self.invoice_footer = ko.observable('');
    self.default_footer = ko.observable(account.invoice_footer);
    self.footer_placeholder = ko.observable({{ (!$invoice->id || $invoice->is_recurring) && $account->invoice_footer ? 'account.invoice_footer' : false}});
    self.set_default_footer = ko.observable(false);
    self.public_notes = ko.observable('');
    self.po_number = ko.observable('');
    self.invoice_date = ko.observable('');
    self.invoice_number = ko.observable('');
    self.due_date_text = ko.observable('');
    self.due_date = ko.observable('');
    self.recurring_due_date = ko.observable('');
    self.start_date = ko.observable('');
    self.start_date_orig = ko.observable('');
    self.end_date = ko.observable('');
    self.last_sent_date = ko.observable('');
    self.tax_name1 = ko.observable();
    self.tax_rate1 = ko.observable();
    self.tax_rate1IsInclusive = ko.observable(0);
    self.tax_name2 = ko.observable();
    self.tax_rate2 = ko.observable();
    self.tax_rate2IsInclusive = ko.observable(0);
    self.is_recurring = ko.observable(0);
    self.is_quote = ko.observable({{ $entityType == ENTITY_QUOTE ? '1' : '0' }});
    self.auto_bill = ko.observable(0);
    self.client_enable_auto_bill = ko.observable(false);
    self.invoice_status_id = ko.observable(0);
    self.invoice_items = ko.observableArray();
    self.documents = ko.observableArray();
    self.expenses = ko.observableArray();
    self.amount = ko.observable(0);
    self.interest_rate = ko.observable(0);
    self.interest_paid = ko.observable(0);
   
    
    self.balance = ko.observable(0);
    self.invoice_design_id = ko.observable(1);
    self.partial = ko.observable(0);
    self.has_tasks = ko.observable();
    self.has_expenses = ko.observable();
    self.signature = "";
    self.signature_date = " ";
    self.custom_value1 = ko.observable(0);
    self.custom_value2 = ko.observable(0);
    self.custom_taxes1 = ko.observable(false);
    self.custom_taxes2 = ko.observable(false);
    self.custom_text_value1 = ko.observable();
    self.custom_text_value2 = ko.observable();
    self.interest = ko.observable(0);

    self.mapping = {
        'client': {
            create: function(options) {
                return new ClientModel(options.data);
            }
        },
        'due_date_text': {
            create: function(options){
                if(options.data){
                    options.parent.due_date(options.data);
                    return options.data;
                }
            }
        },
        'invoice_items': {
            create: function(options) {
                return new ItemModel(options.data);
            }
        },
        'documents': {
            create: function(options) {
                return new DocumentModel(options.data);
            }
        },
        'expenses': {
            create: function(options) {
                return new ExpenseModel(options.data);
            }
        },
    }

    self.addItem = function() {
        if (self.invoice_items().length >= {{ MAX_INVOICE_ITEMS }}) {
            return false;
        }
        var itemModel = new ItemModel();
        @if ($account->hide_quantity)
            itemModel.qty(1);
        @endif
        self.invoice_items.push(itemModel);
        applyComboboxListeners();
        return itemModel;
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

    if (data) {
        ko.mapping.fromJS(data, self.mapping, self);
    } else {
        self.addItem();
    }

    self.qtyLabel = ko.computed(function() {
        return self.has_tasks() ? invoiceLabels['hours'] : invoiceLabels['quantity'];
    }, this);

    self.costLabel = ko.computed(function() {
        return self.has_tasks() ? invoiceLabels['rate'] : invoiceLabels['unit_cost'];
    }, this);

    this.tax1 = ko.computed({
        read: function () {
            return self.tax_rate1IsInclusive() + ' ' + self.tax_rate1() + ' ' + self.tax_name1();
        },
        write: function(value) {
            var parts = value.split(' ');
            self.tax_rate1IsInclusive(parts.shift());
            self.tax_rate1(parts.shift());
            self.tax_name1(parts.join(' '));
        }
    })

    this.tax2 = ko.computed({
        read: function () {
            return self.tax_rate2IsInclusive() + ' ' + self.tax_rate2() + ' ' + self.tax_name2();
        },
        write: function(value) {
            var parts = value.split(' ');
            self.tax_rate2IsInclusive(parts.shift());
            self.tax_rate2(parts.shift());
            self.tax_name2(parts.join(' '));
        }
    })

    self.removeItem = function(item) {
        self.invoice_items.remove(item);
        refreshPDF(true);
    }

    self.formatMoney = function(amount) {
        var client = $.parseJSON(ko.toJSON(self.client()));
        return formatMoneyAccount(amount, self.account, client);
    }

    self.totals = ko.observable();

    self.totals.rawSubtotal = ko.computed(function() {
        var total = 0;
        var paritial_total = 0;
        for(var p=0; p < self.invoice_items().length; ++p) {
            var item = self.invoice_items()[p];
            var cost = item.cost();
            var _cost = NINJA.parseFloat(cost);
            if(_cost < 0){

                if(cost && cost.indexOf("%") !== -1){
                    cost = NINJA.parseFloat(cost);
                    var discount = cost/100 * paritial_total;
                    discount = roundToTwo(discount);
                   // item.totals._rawTotal(-discount);
                    item.totals.total(discount);
                    paritial_total = 0;

                }
                else {

                    paritial_total = item.totals.rawTotal();
                }
            }
            else {
                paritial_total = item.totals.rawTotal();
            }
            total += item.totals.rawTotal();
            total = roundToTwo(total);


        }
        return total;
    });

    self.totals.subtotal = ko.computed(function() {
        var total = self.totals.rawSubtotal();
        return self.formatMoney(total);
    });

   

    

    
    
    self.totals.rawDiscounted = ko.computed(function() {
        if (parseInt(self.is_amount_discount())) {
            return roundToTwo(self.discount());
        } else {
            return roundToTwo(self.totals.rawSubtotal() * (self.discount()/100));
        }
    });

    self.totals.discounted = ko.computed(function() {
        return self.formatMoney(self.totals.rawDiscounted());
    });

    self.totals.taxAmount = ko.computed(function() {
        var total = self.totals.rawSubtotal();
        var discount = self.totals.rawDiscounted();
        total -= discount;

        var customValue1 = roundToTwo(self.custom_value1());
        var customValue2 = roundToTwo(self.custom_value2());
        var customTaxes1 = self.custom_taxes1() == 1;
        var customTaxes2 = self.custom_taxes2() == 1;

        if (customValue1 && customTaxes1) {
            total = NINJA.parseFloat(total) + customValue1;
        }
        if (customValue2 && customTaxes2) {
            total = NINJA.parseFloat(total) + customValue2;
        }

        var taxRate1 = parseFloat(self.tax_rate1());
        var tax1 = roundToTwo(total * (taxRate1/100));

        var taxRate2 = parseFloat(self.tax_rate2());
        var tax2 = roundToTwo(total * (taxRate2/100));

        return self.formatMoney(tax1 + tax2);
    });

    self.totals.itemTaxes = ko.computed(function() {
        var taxes = {};
        var total = self.totals.rawSubtotal();
        for(var i=0; i<self.invoice_items().length; i++) {
            var item = self.invoice_items()[i];
            var lineTotal = item.totals.rawTotal();
            if (self.discount()) {
                if (parseInt(self.is_amount_discount())) {
                    lineTotal -= roundToTwo((lineTotal/total) * self.discount());
                } else {
                    lineTotal -= roundToTwo(lineTotal * (self.discount()/100));
                }
            }

            var taxAmount = roundToTwo(lineTotal * item.tax_rate1() / 100);
            if (taxAmount) {
                var key = item.tax_name1() + item.tax_rate1();
                if (taxes.hasOwnProperty(key)) {
                    taxes[key].amount += taxAmount;
                } else {
                    taxes[key] = {name:item.tax_name1(), rate:item.tax_rate1(), amount:taxAmount};
                }
            }

            var taxAmount = roundToTwo(lineTotal * item.tax_rate2() / 100);
            if (taxAmount) {
                var key = item.tax_name2() + item.tax_rate2();
                if (taxes.hasOwnProperty(key)) {
                    taxes[key].amount += taxAmount;
                } else {
                    taxes[key] = {name:item.tax_name2(), rate:item.tax_rate2(), amount:taxAmount};
                }
            }
        }
        return taxes;
    });

    self.totals.hasItemTaxes = ko.computed(function() {
        var count = 0;
        var taxes = self.totals.itemTaxes();
        for (var key in taxes) {
            if (taxes.hasOwnProperty(key)) {
                count++;
            }
        }
        return count > 0;
    });

    self.totals.itemTaxRates = ko.computed(function() {
        var taxes = self.totals.itemTaxes();
        var parts = [];
        for (var key in taxes) {
            if (taxes.hasOwnProperty(key)) {
                parts.push(taxes[key].name + ' ' + (taxes[key].rate*1) + '%');
            }
        }
        return parts.join('<br/>');
    });

    self.totals.itemTaxAmounts = ko.computed(function() {
        var taxes = self.totals.itemTaxes();
        var parts = [];
        for (var key in taxes) {
            if (taxes.hasOwnProperty(key)) {
                parts.push(self.formatMoney(taxes[key].amount));
            }
        }
        return parts.join('<br/>');
    });

    self.totals.rawPaidToDate = ko.computed(function() {
        return roundToTwo(accounting.toFixed(self.amount(),2) - accounting.toFixed(self.balance(),2));
    });

    self.totals.paidToDate = ko.computed(function() {
        var total = self.totals.rawPaidToDate();
        return self.formatMoney(total);
    });

    self.totals.rawTotal = ko.computed(function() {
        var total = accounting.toFixed(self.totals.rawSubtotal(),2);
        var discount = self.totals.rawDiscounted();
        total -= discount;

        var customValue1 = roundToTwo(self.custom_value1());
        var customValue2 = roundToTwo(self.custom_value2());
        var customTaxes1 = self.custom_taxes1() == 1;
        var customTaxes2 = self.custom_taxes2() == 1;

        if (customValue1 && customTaxes1) {
            total = NINJA.parseFloat(total) + customValue1;
        }
        if (customValue2 && customTaxes2) {
            total = NINJA.parseFloat(total) + customValue2;
        }

        var taxAmount1 = roundToTwo(total * (parseFloat(self.tax_rate1())/100));
        var taxAmount2 = roundToTwo(total * (parseFloat(self.tax_rate2())/100));
        total = NINJA.parseFloat(total) + taxAmount1 + taxAmount2;
        total = roundToTwo(total);

        var taxes = self.totals.itemTaxes();
        for (var key in taxes) {
            if (taxes.hasOwnProperty(key)) {
                total += taxes[key].amount;
                total = roundToTwo(total);
            }
        }

        if (customValue1 && !customTaxes1) {
            total = NINJA.parseFloat(total) + customValue1;
        }
        if (customValue2 && !customTaxes2) {
            total = NINJA.parseFloat(total) + customValue2;
        }

        var paid = self.totals.rawPaidToDate();
        if (paid > 0) {
            total -= paid;
        }

        return total;
    });

     self.totals.rawInterest = ko.computed(function() {
        var total = self.totals.rawTotal();
        if(self.interest() == 1 && TODAY_DATE && self.balance() > 0){
            var invoice_date = new Date(self.invoice_date());
            var due_date = new Date(self.due_date());
            var today_date = new Date(TODAY_DATE);
            var days = (today_date - invoice_date) / (1000 * 60 * 60 * 24);
            var interest = 0;
            if (days > 30) {
                    self.interest_rate(roundToTwo(0.1 * (total ) / 100 * days))
                }
             else if (self.interest_paid()) {
                self.interest_rate(self.interest_paid());
            }
            
        }
        else {
            self.interest_rate(0);
        }
        return self.interest_rate();
        
    });
    self.totals.interest = ko.computed(function() {
        
        return self.formatMoney(self.totals.rawInterest());
    });
    self.totals.total = ko.computed(function() {
        
        return self.formatMoney(self.totals.rawTotal()+self.totals.rawInterest());
    });

    

    self.totals.partial = ko.computed(function() {
        return self.formatMoney(self.partial());
    });

    self.onDragged = function(item) {
        refreshPDF(true);
    }

    self.showResetTerms = function() {
        return self.default_terms() && self.terms() != self.default_terms();
    }

    self.showResetFooter = function() {
        return self.default_footer() && self.invoice_footer() != self.default_footer();
    }

    self.applyInclusivTax = function(taxRate) {
        for (var i=0; i<self.invoice_items().length; i++) {
            var item = self.invoice_items()[i];
            item.applyInclusivTax(taxRate);
        }
    }

    self.onTax1Change = function(obj, event) {
        if ( ! event.originalEvent) {
            return;
        }
        var taxKey = $(event.currentTarget).val();
        var taxRate = parseFloat(self.tax_rate1());
        if (taxKey.substr(0, 1) != 1) {
            return;
        }
        self.applyInclusivTax(taxRate);
    }

    self.onTax2Change = function(obj, event) {
        if ( ! event.originalEvent) {
            return;
        }
        var taxKey = $(event.currentTarget).val();
        var taxRate = parseFloat(self.tax_rate2());
        if (taxKey.substr(0, 1) != 1) {
            return;
        }
        self.applyInclusivTax(taxRate);
    }
}

function ClientModel(data) {
    var self = this;
    self.public_id = ko.observable(0);
    self.name = ko.observable('');
    self.id_number = ko.observable('');
    self.vat_number = ko.observable('');
    self.work_phone = ko.observable('');
    self.custom_value1 = ko.observable('');
    self.custom_value2 = ko.observable('');
    self.private_notes = ko.observable('');
    self.address1 = ko.observable('');
    self.main_address = ko.observable('');
    self.address2 = ko.observable('');
    self.city = ko.observable('');
    self.state = ko.observable('');
    self.postal_code = ko.observable('');
    self.country_id = ko.observable('');
    self.size_id = ko.observable('');
    self.industry_id = ko.observable('');
    self.currency_id = ko.observable('');
    self.language_id = ko.observable('');
    self.website = ko.observable('');
    self.payment_terms = ko.observable(0);
    self.contacts = ko.observableArray();

    self.mapping = {
        'contacts': {
            create: function(options) {
                var model = new ContactModel(options.data);
                model.send_invoice(options.data.send_invoice == '1');
                return model;
            }
        },
        'main_address': {
            create: function(options){
                options.parent.address1(options.data.address_1);
                options.parent.address2(options.data.address_2);
                options.parent.state(options.data.state);
                options.parent.city(options.data.city);
                options.parent.city(options.data.city);
                options.parent.postal_code(options.data.zip);
                options.parent.country_id(options.data.country);
                return options.data;
            },
            update: function(options){
                options.parent.address1(options.data?options.data.address_1:"");
                options.parent.address2(options.data?options.data.address_2:"");
                options.parent.state(options.data?options.data.state:"");
                options.parent.city(options.data?options.data.city:"");
                options.parent.city(options.data?options.data.city:"");
                options.parent.postal_code(options.data?options.data.zip:"");
                options.parent.country_id(options.data?options.data.country:"");
                return options.data;
            }
        },
        'ignore': ["address1","address2", "state", 'postal_code', 'country_id']

    };

    self.showContact = function(elem) { if (elem.nodeType === 1) $(elem).hide().slideDown() }
    self.hideContact = function(elem) { if (elem.nodeType === 1) $(elem).slideUp(function() { $(elem).remove(); }) }

    self.addContact = function() {
        var contact = new ContactModel();
        contact.send_invoice(true);
        self.contacts.push(contact);
        return false;
    }

    self.removeContact = function() {
        self.contacts.remove(this);
    }

    self.name.display = ko.computed(function() {
        if (self.name()) {
            return self.name();
        }
        if (!self.contacts() || self.contacts().length == 0) return;
        var contact = self.contacts()[0];
        if (contact.first_name() || contact.last_name()) {
            return contact.first_name() + ' ' + contact.last_name();
        } else {
            return contact.email();
        }
    });

    self.name.placeholder = ko.computed(function() {
        if (!self.contacts() || self.contacts().length == 0) return '';
        var contact = self.contacts()[0];
        if (contact.first_name() || contact.last_name()) {
            return contact.first_name() + ' ' + contact.last_name();
        } else {
            return contact.email();
        }
    });

    if (data) {
        ko.mapping.fromJS(data, {}, this);
    } else {
        self.addContact();
    }
}

function ContactModel(data) {
    var self = this;
    self.public_id = ko.observable('');
    self.first_name = ko.observable('');
    self.last_name = ko.observable('');
    self.email = ko.observable('');
    self.phone = ko.observable('');
    self.send_invoice = ko.observable(false);
    self.invitation_link = ko.observable('');
    self.invitation_status = ko.observable('');
    self.invitation_openend = ko.observable(false);
    self.invitation_viewed = ko.observable(false);
    self.email_error = ko.observable('');
    self.signature = ko.observable('');
    self.invitation_signature_svg = ko.observable('');
    self.invitation_signature_date = ko.observable('');

    if (data) {
        ko.mapping.fromJS(data, {}, this);
    }

    self.isBlank = ko.computed(function() {
        return ! self.first_name() && ! self.last_name() && ! self.email() && ! self.phone();
    });

    self.displayName = ko.computed(function() {
        var str = '';
        if (self.first_name() || self.last_name()) {
            str += (self.first_name() || '') + ' ' + (self.last_name() || '') + ' ';
        }
        if (self.email()) {
            if (str) {
                str += '&lt;' + self.email() + '&gt;';
            } else {
                str += self.email();
            }
        }

        return str + '<br/>';
    });

    self.email.display = ko.computed(function() {
        var str = '';

        if (self.first_name() || self.last_name()) {
            str += (self.first_name() || '') + ' ' + (self.last_name() || '') + '<br/>';
        }
        if (self.email()) {
            str += self.email() + '<br/>';
        }
        return str;
    });

    self.view_as_recipient = ko.computed(function() {
        var str = '';
        @if (Utils::isConfirmed())
        if (self.invitation_link()) {
            // clicking adds 'silent=true' however it's removed when copying the link
            str += '<a href="' + self.invitation_link() + '" onclick="window.open(\'' + self.invitation_link()
                    + '?silent=true\', \'_blank\');return false;">{{ trans('texts.view_as_recipient') }}</a>';
        }
        @endif

        return str;
    });

    self.info_color = ko.computed(function() {
        if (self.invitation_viewed()) {
            return '#57D172';
        } else if (self.invitation_openend()) {
            return '#FFCC00';
        } else {
            return '#B1B5BA';
        }
    });

    self.isValid = function() {
        var email = (self.email() || '').trim();
        var emailValid = isValidEmailAddress(email);

        // if the email is set it must be valid
        if (email && ! emailValid) {
            return false;
        } else {
            return self.first_name() || email;
        }
    }
}

function ItemModel(data) {
    var self = this;
    self.product_key = ko.observable('');
    self.notes = ko.observable('');
    self.cost = ko.observable('');
    self.qty = ko.observable(0);
    self.sku = ko.observable(0);
    self.custom_value1 = ko.observable('');
    self.custom_value2 = ko.observable('');
    self.tax_name1 = ko.observable('');
    self.tax_rate1 = ko.observable(0);
    self.tax_rate1IsInclusive = ko.observable(0);
    self.tax_name2 = ko.observable('');
    self.tax_rate2 = ko.observable(0);
    self.tax_rate2IsInclusive = ko.observable(0);
    self.task_public_id = ko.observable('');
    self.expense_public_id = ko.observable('');
    self.invoice_item_type_id = ko.observable({{ INVOICE_ITEM_TYPE_STANDARD }});
    self.actionsVisible = ko.observable(false);

    this.tax1 = ko.computed({
        read: function () {
            return self.tax_rate1IsInclusive() + ' ' + self.tax_rate1() + ' ' + self.tax_name1();
        },
        write: function(value) {
            var parts = value.split(' ');
            self.tax_rate1IsInclusive(parts.shift());
            self.tax_rate1(parts.shift());
            self.tax_name1(parts.join(' '));
        }
    })

    this.tax2 = ko.computed({
        read: function () {
            return self.tax_rate2IsInclusive() + ' ' + self.tax_rate2() + ' ' + self.tax_name2();
        },
        write: function(value) {
            var parts = value.split(' ');
            self.tax_rate2IsInclusive(parts.shift());
            self.tax_rate2(parts.shift());
            self.tax_name2(parts.join(' '));
        }
    })

    this.prettyQty = ko.computed({
        read: function () {
            return NINJA.parseFloat(this.qty()) ? NINJA.parseFloat(this.qty()) : '';
        },
        write: function (value) {
            this.qty(value);
        },
        owner: this
    });

    this.prettyCost = ko.computed({
        read: function () {

            return this.cost() ? this.cost() : '';
        },
        write: function (value) {
             console.log("setting");
            value += '';
            have_mins = false;
            have_prcent = false;
            if(value.indexOf("-") !== -1){
                    value = value.replace(/\-/g,"");
                    have_mins = true;
            }
            if(value.indexOf("%") !== -1){
               value = value.replace(/\%/g,"");
                    have_prcent = true;
            }
            if(value.length >= 1 && value.indexOf(".") == -1){
                //var last_value = value[value.length-1];
               // value = value.substr(0,value.length-1)+".";
                //value += last_value+"0";
                //value +=".00";
            }
            else if(value.length == 0){
                value = "00"
            }
            value = value.replace(/\./g,"");
            
            
            
            value = Number((value/100)+"").toFixed(2);
            console.log(value);
            if(have_mins){
                value = "-"+value;
            }
            if(have_prcent){
                value = value+"%"
            }
            this.cost(value+"");
        },
        owner: this
    });

    if (data) {
        ko.mapping.fromJS(data, {}, this);
    }

    this.totals = ko.observable();
    this.totals._rawTotal = ko.observable(0);
    this.totals.rawTotal = ko.computed(function() {
        if(self.totals._rawTotal()){
            return roundToTwo(self.totals._rawTotal());
        }
        var cost = roundToTwo(NINJA.parseFloat(self.cost()));
        var qty = roundToTwo(NINJA.parseFloat(self.qty()));
        var value = cost * qty;
        return value ? roundToTwo(value) : 0;
    });

    this.totals.total = ko.computed({
        read: function(){
            var total = self.totals.rawTotal();
            return window.hasOwnProperty('model') && total ? model.invoice().formatMoney(total) : '';
        },
        write: function(total){
            self.totals._rawTotal(total);
        },
        owner: this
    });



    this.hideActions = function() {
        this.actionsVisible(false);
    }

    this.showActions = function() {
        this.actionsVisible(true);
    }

    this.isEmpty = function() {
        return !self.product_key() && !self.notes() && !self.cost() && (!self.qty() || {{ $account->hide_quantity ? 'true' : 'false' }});
    }

    this.onSelect = function() {}

    self.applyInclusivTax = function(taxRate) {
        
        if ( ! taxRate) {
            return;
        }
        console.log("over writting");
        var cost = self.cost() / (100 + taxRate) * 100;
        self.cost(roundToTwo(cost));
    }

    self.onTax1Change = function (obj, event) {
        if (event.originalEvent) {
            var taxKey = $(event.currentTarget).val();
            var taxRate = parseFloat(self.tax_rate1());
            if (taxKey.substr(0, 1) == 1) {
                self.applyInclusivTax(taxRate);
            }
        }
    }

    self.onTax2Change = function (obj, event) {
        if (event.originalEvent) {
            var taxKey = $(event.currentTarget).val();
            var taxRate = parseFloat(self.tax_rate2());
            if (taxKey.substr(0, 1) == 1) {
                self.applyInclusivTax(taxRate);
            }
        }
    }
}

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

function CategoryModel(data) {
    var self = this;
    self.name = ko.observable('')

    self.update = function(data){
        ko.mapping.fromJS(data, {}, this);
    }

    if (data) {
        self.update(data);
    }
}

var ExpenseModel = function(data) {
    var self = this;

    self.mapping = {
        'documents': {
            create: function(options) {
                return new DocumentModel(options.data);
            }
        },
        'expense_category': {
            create: function(options) {
                return new CategoryModel(options.data);
            }
        }
    }

    self.description = ko.observable('');
    self.qty = ko.observable(0);
    self.public_id = ko.observable(0);
    self.amount = ko.observable();
    self.converted_amount = ko.observable();

    if (data) {
        ko.mapping.fromJS(data, self.mapping, this);
    }
};

/* Custom binding for product key typeahead */
ko.bindingHandlers.productTypeahead = {
    init: function (element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
        var $element = $(element);
        var allBindings = allBindingsAccessor();

        $element.typeahead({
            highlight: true,
            minLength: 0,
        },
        {
            name: 'data',
            display: allBindings.key,
            limit: 50,
            templates: {
                suggestion: function(item) { return '<div title="' + item[allBindings.key] + '">' + item[allBindings.key] + '</div>' }
            },
            source: searchData(allBindings.items, allBindings.key)
        }).on('typeahead:select', function(element, datum, name) {
            @if (Auth::user()->account->fill_products)
                var model = ko.dataFor(this);
                if (model.expense_public_id()) {
                    return;
                }
                if (datum.product_key && (!model.product_key() || !model.task_public_id())) {
                    model.product_key(datum.product_key);
                }
                if (datum.notes && (!model.notes() || !model.task_public_id())) {
                    model.notes(datum.notes);
                }
            if (datum.sku && (!model.sku() || !model.task_public_id())) {
                model.sku(datum.sku);
            }
                if (datum.cost) {
                    model.cost(accounting.toFixed(datum.cost, 2));
                }
                if (!model.qty()) {
                    model.qty(1);
                }
                @if ($account->invoice_item_taxes)
                    if (datum.tax_name1) {
                        var $select = $(this).parentsUntil('tbody').find('select').first();
                        $select.val('0 ' + datum.tax_rate1 + ' ' + datum.tax_name1).trigger('change');
                    }
                    if (datum.tax_name2) {
                        var $select = $(this).parentsUntil('tbody').find('select').last();
                        $select.val('0 ' + datum.tax_rate2 + ' ' + datum.tax_name2).trigger('change');
                    }
                @endif
                @if (Auth::user()->isPro() && $account->custom_invoice_item_label1)
                    if (datum.custom_value1) {
                        model.custom_value1(datum.custom_value1);
                    }
                @endif
                @if (Auth::user()->isPro() && $account->custom_invoice_item_label2)
                    if (datum.custom_value2) {
                        model.custom_value2(datum.custom_value2);
                    }
                @endif
            @endif
            onItemChange();
        }).on('typeahead:change', function(element, datum, name) {
            var value = valueAccessor();
            value(datum);
            onItemChange();
            refreshPDF(true);
        });
    },

    update: function (element, valueAccessor) {
        var value = ko.utils.unwrapObservable(valueAccessor());
        if (value) {
            $(element).typeahead('val', value);
        }
    }
};

ko.bindingHandlers.datePicker1 = {
    init: function(element, valueAccessor, allBindingsAccessor) {
        //initialize datepicker with some optional options
        var options = allBindingsAccessor().datepickerOptions || {},
            $el = $(element);

        $el.datepicker(options);

        //handle the field changing by registering datepicker's changeDate event
        ko.utils.registerEventHandler(element, "changeDate", function () {
            var observable = valueAccessor();
            observable($el.val());
        });

        //handle disposal (if KO removes by the template binding)
        ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
            $el.datepicker("destroy");
        });

    },
    update: function(element, valueAccessor) {
        var value = ko.utils.unwrapObservable(valueAccessor()),
            $el = $(element);

        //handle date data coming via json from Microsoft
        if (String(value).indexOf('/Date(') == 0) {
            value = new Date(parseInt(value.replace(/\/Date\((.*?)\)\//gi, "$1")));
        }

        var current = $el.datepicker("getDate");

        if (value - current !== 0) {
            $el.datepicker("setDate", value);
        }
    }
};

ko.bindingHandlers.datePicker2 = {
    init: function(element, valueAccessor, allBindingsAccessor) {
        //initialize datepicker with some optional options
        var options = allBindingsAccessor().datepickerOptions || {},
            $el = $(element);
        options = $.extend({forceParse: false,allowInputToggle: false},options);
        $el.datepicker(options);

        //handle the field changing by registering datepicker's changeDate event
        ko.utils.registerEventHandler(element, "blur", function () {
            var observable = valueAccessor();
            console.log($el.val())
            observable($el.val());
        });
        ko.utils.registerEventHandler(element, "changeDate", function () {
            var observable = valueAccessor();
            observable($el.val());
        });


        //handle disposal (if KO removes by the template binding)
        ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
            $el.datepicker("destroy");
        });

    },
    update: function(element, valueAccessor) {
        var value = ko.utils.unwrapObservable(valueAccessor()),
            $el = $(element);

        //handle date data coming via json from Microsoft
        if (String(value).indexOf('/Date(') == 0) {
            value = new Date(parseInt(value.replace(/\/Date\((.*?)\)\//gi, "$1")));
        }

        var current = $el.datepicker("getDate");

        if (!current) {
            $el.datepicker("setDate", value);
        }


    }
};


ko.bindingHandlers.select2 = {
    init: function(el, valueAccessor, allBindingsAccessor, viewModel) {
        ko.utils.domNodeDisposal.addDisposeCallback(el, function() {
            $(el).select2('destroy');
        });

        var allBindings = allBindingsAccessor(),
            select2 = ko.utils.unwrapObservable(allBindings.select2);

        $(el).select2(select2);
    },
    update: function (el, valueAccessor, allBindingsAccessor, viewModel) {
        var allBindings = allBindingsAccessor();

        if ("value" in allBindings) {
            if ((allBindings.select2.multiple || el.multiple) && allBindings.value().constructor != Array) {
                $(el).val(allBindings.value().split(',')).trigger('change');
            }
            else {
                $(el).val(allBindings.value()).trigger('change');
            }
        }
        else if ("selectedOptions" in allBindings) {
            var converted = [];
            var textAccessor = function(value) { return value; };
            if ("optionsText" in allBindings) {
                textAccessor = function(value) {
                    var valueAccessor = function (item) { return item; }
                    if ("optionsValue" in allBindings) {
                        valueAccessor = function (item) { return item[allBindings.optionsValue]; }
                    }
                    var items = $.grep(allBindings.options(), function (e) { return valueAccessor(e) == value});
                    if (items.length == 0 || items.length > 1) {
                        return "UNKNOWN";
                    }
                    return items[0][allBindings.optionsText];
                }
            }
            $.each(allBindings.selectedOptions(), function (key, value) {
                converted.push({id: value, text: textAccessor(value)});
            });
            $(el).select2("data", converted);
        }
        $(el).trigger("change");
    }
};

function checkInvoiceNumber() {
    var url = '{{ url('check_invoice_number') }}/{{ $invoice->exists ? $invoice->public_id : '' }}?invoice_number=' + encodeURIComponent($('#invoice_number').val());
    $.get(url, function(data) {
        var isValid = data == '{{ RESULT_SUCCESS }}' ? true : false;
        if (isValid) {
            $('.invoice-number')
                .removeClass('has-error')
                .find('span')
                .hide();
        } else {
            if ($('.invoice-number').hasClass('has-error')) {
                return;
            }
            $('.invoice-number')
                .addClass('has-error')
                .find('div')
                .append('<span class="help-block">{{ trans('validation.unique', ['attribute' => trans('texts.invoice_number')]) }}</span>');
        }
    });
}


ko.bindingHandlers.rateFormat = {
    init: function (element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
        ko.utils.registerEventHandler(element, 'keyup', function (event) {
            var observable = valueAccessor();
            observable(formatInput(element.value));
            observable.notifySubscribers(1);
        });
    },
    update: function (element, valueAccessor, allBindingsAccessor) {
        var value = ko.utils.unwrapObservable(valueAccessor());
        $(element).val(value);
    }
};

function formatInput(value) {
    if(!value) return;
    value += '';

    value = value.replace(/\./g,"");


    return (value/100)+"";
}

</script>
