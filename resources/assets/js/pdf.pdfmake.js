var NINJA = NINJA || {};

NINJA.TEMPLATES = {
    CLEAN: "1",
    BOLD: "2",
    MODERN: "3",
    NORMAL: "4",
    BUSINESS: "5",
    CREATIVE: "6",
    ELEGANT: "7",
    HIPSTER: "8",
    PLAYFUL: "9",
    PHOTO: "10"
};

function GetPdfMake(invoice, javascript, callback) {

    javascript = NINJA.decodeJavascript(invoice, javascript);

    function jsonCallBack(key, val) {

        // handle custom functions
        if (typeof val === 'string') {
            if (val.indexOf('$firstAndLast') === 0) {
                var parts = val.split(':');
                return function(i, node) {
                    return (i === 0 || i === node.table.body.length) ? parseFloat(parts[1]) : 0;
                };
            } else if (val.indexOf('$none') === 0) {
                return function(i, node) {
                    return 0;
                };
            } else if (val.indexOf('$notFirstAndLastColumn') === 0) {
                var parts = val.split(':');
                return function(i, node) {
                    return (i === 0 || i === node.table.widths.length) ? 0 : parseFloat(parts[1]);
                };
            } else if (val.indexOf('$LastColumn') === 0) {
                var parts = val.split(':');
                return function(i, node) {
                    return (i === node.table.widths.length) ? parseFloat(parts[1]) : 0;
                };
            } else if (val.indexOf('$FirstColumn') === 0) {
                var parts = val.split(':');
                return function(i, node) {
                    return (i === node.table.widths.length) ? parseFloat(parts[1]) : 0;
                };
            } else if (val.indexOf('$notFirst') === 0) {
                var parts = val.split(':');
                return function(i, node) {
                    return i === 0 ? 0 : parseFloat(parts[1]);
                };
            } else if (val.indexOf('$amount') === 0) {
                var parts = val.split(':');
                return function(i, node) {
                    return parseFloat(parts[1]);
                };
            } else if (val.indexOf('$primaryColor') === 0) {
                var parts = val.split(':');
                return NINJA.primaryColor || parts[1];
            } else if (val.indexOf('$secondaryColor') === 0) {
                var parts = val.split(':');
                return NINJA.secondaryColor || parts[1];
            }
        }

        // determine whether or not to show the header/footer
        if (invoice.features.customize_invoice_design) {
            if (key === 'header') {
                return function(page, pages) {
                    if (page === 1 || invoice.account.all_pages_header == '1') {
                        if (invoice.features.remove_created_by) {
                            return NINJA.updatePageCount(JSON.parse(JSON.stringify(val)), page, pages);
                        } else {
                            return val;
                        }
                    } else {
                        return '';
                    }
                }
            } else if (key === 'footer') {
                return function(page, pages) {
                    if (page === pages || invoice.account.all_pages_footer == '1') {
                        if (invoice.features.remove_created_by) {
                            return NINJA.updatePageCount(JSON.parse(JSON.stringify(val)), page, pages);
                        } else {
                            return val;
                        }
                    } else {
                        return '';
                    }
                }
            }
        }

        // check for markdown
        if (key === 'text') {
            val = NINJA.parseMarkdownText(val, true);
        }

        /*
        if (key === 'stack') {
            val = NINJA.parseMarkdownStack(val);
            val = NINJA.parseMarkdownText(val, false);
        }
        */

        return val;
    }

    // Add ninja logo to the footer
    var dd = JSON.parse(javascript, jsonCallBack);
    var designId = invoice.invoice_design_id;
    if (!invoice.features.remove_created_by && !isEdge) {
        var footer = (typeof dd.footer === 'function') ? dd.footer() : dd.footer;
        if (footer) {
            if (footer.hasOwnProperty('columns')) {
                footer.columns.push({ image: logoImages.imageLogo1, alignment: 'right', width: 130, margin: [0, 0, 0, 0] })
            } else {
                var foundColumns;
                for (var i = 0; i < footer.length; i++) {
                    var item = footer[i];
                    if (item.hasOwnProperty('columns')) {
                        foundColumns = true;
                        var columns = item.columns;
                        if (columns[0].hasOwnProperty('stack')) {
                            columns[0].stack.push({ image: logoImages.imageLogo3, alignment: 'left', width: 130, margin: [40, 6, 0, 0] });
                        } else {
                            columns.push({ image: logoImages.imageLogo1, alignment: 'right', width: 130, margin: [0, -40, 20, 0] })
                        }
                    }
                }
                if (!foundColumns) {
                    footer.push({ image: logoImages.imageLogo1, alignment: 'right', width: 130, margin: [0, 0, 10, 10] })
                }
            }
        }
    }

    // set page size
    dd.pageSize = invoice.account.page_size;

    //dd.watermark = {text: 'PAID', color: 'blue', opacity: 0.3};

    pdfMake.fonts = {}
    fonts = window.invoiceFonts || invoice.invoice_fonts;

    // Add only the loaded fonts
    $.each(fonts, function(i, font) {
        addFont(font);
    });


    function addFont(font) {
        if (window.ninjaFontVfs[font.folder]) {
            folder = 'fonts/' + font.folder;
            pdfMake.fonts[font.name] = {
                normal: folder + '/' + font.normal,
                italics: folder + '/' + font.italics,
                bold: folder + '/' + font.bold,
                bolditalics: folder + '/' + font.bolditalics
            }
        }
    }

    if (!dd.defaultStyle) dd.defaultStyle = { font: NINJA.bodyFont };
    else if (!dd.defaultStyle.font) dd.defaultStyle.font = NINJA.bodyFont;

    doc = pdfMake.createPdf(dd);
    doc.save = function(fileName) {
        this.download(fileName);
    };

    return doc;
}

NINJA.updatePageCount = function(obj, pageNumber, pageCount) {
    var pageNumberRegExp = new RegExp('\\$pageNumber', 'g');
    var pageCountRegExp = new RegExp('\\$pageCount', 'g');

    for (key in obj) {
        if (!obj.hasOwnProperty(key)) {
            continue;
        }
        var val = obj[key];
        if (typeof val === 'string') {
            val = val.replace(pageNumberRegExp, pageNumber);
            val = val.replace(pageCountRegExp, pageCount);
            obj[key] = val;
        } else if (typeof val === 'object') {
            obj[key] = NINJA.updatePageCount(val, pageNumber, pageCount);
        }
    }

    return obj;
}

NINJA.decodeJavascript = function(invoice, javascript) {
    var signature_image = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAlgAAACWCAYAAAACG/YxAAAHgElEQVR4Xu3WMREAAAgDMerfNCZ+DAI65Bh+5wgQIECAAAECBFKBpWvGCBAgQIAAAQIETmB5AgIECBAgQIBALCCwYlBzBAgQIECAAAGB5QcIECBAgAABArGAwIpBzREgQIAAAQIEBJYfIECAAAECBAjEAgIrBjVHgAABAgQIEBBYfoAAAQIECBAgEAsIrBjUHAECBAgQIEBAYPkBAgQIECBAgEAsILBiUHMECBAgQIAAAYHlBwgQIECAAAECsYDAikHNESBAgAABAgQElh8gQIAAAQIECMQCAisGNUeAAAECBAgQEFh+gAABAgQIECAQCwisGNQcAQIECBAgQEBg+QECBAgQIECAQCwgsGJQcwQIECBAgAABgeUHCBAgQIAAAQKxgMCKQc0RIECAAAECBASWHyBAgAABAgQIxAICKwY1R4AAAQIECBAQWH6AAAECBAgQIBALCKwY1BwBAgQIECBAQGD5AQIECBAgQIBALCCwYlBzBAgQIECAAAGB5QcIECBAgAABArGAwIpBzREgQIAAAQIEBJYfIECAAAECBAjEAgIrBjVHgAABAgQIEBBYfoAAAQIECBAgEAsIrBjUHAECBAgQIEBAYPkBAgQIECBAgEAsILBiUHMECBAgQIAAAYHlBwgQIECAAAECsYDAikHNESBAgAABAgQElh8gQIAAAQIECMQCAisGNUeAAAECBAgQEFh+gAABAgQIECAQCwisGNQcAQIECBAgQEBg+QECBAgQIECAQCwgsGJQcwQIECBAgAABgeUHCBAgQIAAAQKxgMCKQc0RIECAAAECBASWHyBAgAABAgQIxAICKwY1R4AAAQIECBAQWH6AAAECBAgQIBALCKwY1BwBAgQIECBAQGD5AQIECBAgQIBALCCwYlBzBAgQIECAAAGB5QcIECBAgAABArGAwIpBzREgQIAAAQIEBJYfIECAAAECBAjEAgIrBjVHgAABAgQIEBBYfoAAAQIECBAgEAsIrBjUHAECBAgQIEBAYPkBAgQIECBAgEAsILBiUHMECBAgQIAAAYHlBwgQIECAAAECsYDAikHNESBAgAABAgQElh8gQIAAAQIECMQCAisGNUeAAAECBAgQEFh+gAABAgQIECAQCwisGNQcAQIECBAgQEBg+QECBAgQIECAQCwgsGJQcwQIECBAgAABgeUHCBAgQIAAAQKxgMCKQc0RIECAAAECBASWHyBAgAABAgQIxAICKwY1R4AAAQIECBAQWH6AAAECBAgQIBALCKwY1BwBAgQIECBAQGD5AQIECBAgQIBALCCwYlBzBAgQIECAAAGB5QcIECBAgAABArGAwIpBzREgQIAAAQIEBJYfIECAAAECBAjEAgIrBjVHgAABAgQIEBBYfoAAAQIECBAgEAsIrBjUHAECBAgQIEBAYPkBAgQIECBAgEAsILBiUHMECBAgQIAAAYHlBwgQIECAAAECsYDAikHNESBAgAABAgQElh8gQIAAAQIECMQCAisGNUeAAAECBAgQEFh+gAABAgQIECAQCwisGNQcAQIECBAgQEBg+QECBAgQIECAQCwgsGJQcwQIECBAgAABgeUHCBAgQIAAAQKxgMCKQc0RIECAAAECBASWHyBAgAABAgQIxAICKwY1R4AAAQIECBAQWH6AAAECBAgQIBALCKwY1BwBAgQIECBAQGD5AQIECBAgQIBALCCwYlBzBAgQIECAAAGB5QcIECBAgAABArGAwIpBzREgQIAAAQIEBJYfIECAAAECBAjEAgIrBjVHgAABAgQIEBBYfoAAAQIECBAgEAsIrBjUHAECBAgQIEBAYPkBAgQIECBAgEAsILBiUHMECBAgQIAAAYHlBwgQIECAAAECsYDAikHNESBAgAABAgQElh8gQIAAAQIECMQCAisGNUeAAAECBAgQEFh+gAABAgQIECAQCwisGNQcAQIECBAgQEBg+QECBAgQIECAQCwgsGJQcwQIECBAgAABgeUHCBAgQIAAAQKxgMCKQc0RIECAAAECBASWHyBAgAABAgQIxAICKwY1R4AAAQIECBAQWH6AAAECBAgQIBALCKwY1BwBAgQIECBAQGD5AQIECBAgQIBALCCwYlBzBAgQIECAAAGB5QcIECBAgAABArGAwIpBzREgQIAAAQIEBJYfIECAAAECBAjEAgIrBjVHgAABAgQIEBBYfoAAAQIECBAgEAsIrBjUHAECBAgQIEBAYPkBAgQIECBAgEAsILBiUHMECBAgQIAAAYHlBwgQIECAAAECsYDAikHNESBAgAABAgQElh8gQIAAAQIECMQCAisGNUeAAAECBAgQEFh+gAABAgQIECAQCwisGNQcAQIECBAgQEBg+QECBAgQIECAQCwgsGJQcwQIECBAgAABgeUHCBAgQIAAAQKxgMCKQc0RIECAAAECBASWHyBAgAABAgQIxAICKwY1R4AAAQIECBAQWH6AAAECBAgQIBALCKwY1BwBAgQIECBAQGD5AQIECBAgQIBALCCwYlBzBAgQIECAAAGB5QcIECBAgAABArGAwIpBzREgQIAAAQIEHn+PAJfBzgmrAAAAAElFTkSuQmCC";
    if (!invoice.signature || !invoice.signature.length)
        invoice.signature = signature_image;
    if (!invoice.signature_date)
        invoice.signature_date = " ";

    var paidImage = "data:image/png;base64,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";
    var pastDue = "data:image/png;base64,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";

    var additional_info = [
        [{
            "text": "Additional Info",
            "fillColor": "#163e8d",
            "color": "#ffffff",
            "style": "tableHeaderStyle",
            "alignment": "left",
            "margin": [
                5,
                0,
                0,
                0
            ]
        }]

    ];
    var i = 0;
    if (invoice.additional_info) {
        var info = invoice.additional_info.split("\n");
        for (var i = 0; i < info.length; i++) {
            additional_info.push(
                [{
                    "text": info[i],
                    "style": (i % 2) ? "even" : "odd"
                }]
            );
        }
    }

    for (; i < 4; i++) {
        additional_info.push(
            [{
                "text": " ",
                "style": (i % 2) ? "even" : "odd"

            }]
        );
    }

    invoice.additional_info_int = JSON.stringify(additional_info);
    var breake_page = false;
    var footer_counter = 1;
    if (invoice.due_date_text) {
        invoice.due_date = invoice.due_date_text;
    }
    if (!invoice.user_quote_signature) {
        invoice.user_quote_signature = signature_image;
    } else {
        invoice.user_quote_signature = "data:" + invoice.user_quote_signature;
    }
    if (!invoice.is_quote && invoice.user_signature && invoice.user_signature.length) {
        invoice.print_user_signature_int =
            '{ "image":"data:' + invoice.user_signature + '", "width":150, "absolutePosition": { "x": 200, "y": 76 } },\
         { "canvas": [ { "type": "line", "x1": 0, "y1": 5, "x2": 150,  "y2": 5, "lineWidth": 0.5 } ] }, \
         { "text":"' + invoice.print_name + '" }';
    }
    if (invoice.is_quote) {
        invoice.left_footer_margin_bottom_int = 10;
        invoice.footer_margin_top_int = -20;
        tax_keys = Object.keys(invoice.item_taxes).length;
        if (tax_keys > 0) {
            //tax_keys -= 1;
            invoice.left_footer_margin_bottom_int += tax_keys * 18;
            invoice.footer_margin_top_int += tax_keys * 3;
        }
        footer_counter += tax_keys;
        if (invoice.discount && invoice.discount != '0.00') {
            invoice.left_footer_margin_bottom_int += 18;
            invoice.footer_margin_top_int += 3;
            footer_counter++;
        }
        if (invoice.partial && invoice.partial != '0.00') {
            invoice.left_footer_margin_bottom_int += 18;
            invoice.footer_margin_top_int += 3;
            footer_counter++;
        }
        if (invoice.footer_margin_top_int > -1) {
            invoice.footer_margin_top_int = -1;
        }
        if (footer_counter > 3 && invoice.invoice_items.length > 10 && invoice.invoice_items.length < 19) {
            breake_page = true;
        }
        if (invoice.invoice_items.length > 12 && invoice.invoice_items.length < 19)
            breake_page = true;
        invoice.breake_page = breake_page;
        invoice.footer_counter = footer_counter;
        if (breake_page) {
            invoice.havepage_break = "pageBreak";
            invoice.footer_margin_top_int = -20;
        }
    } else {
        invoice.left_footer_margin_bottom_int = 35;
        invoice.footer_margin_top_int = -20;
        tax_keys = Object.keys(invoice.item_taxes).length;
        if (tax_keys > 0) {
            //tax_keys -= 1;
            invoice.left_footer_margin_bottom_int += tax_keys * 28;
            invoice.footer_margin_top_int += tax_keys * 10;
        }
        footer_counter += tax_keys;
        if (invoice.discount && invoice.discount != '0.00') {
            invoice.left_footer_margin_bottom_int += 28;
            invoice.footer_margin_top_int += 10;
            footer_counter++;
        }
        if (invoice.interest_rate) {
            invoice.left_footer_margin_bottom_int += 28;
            invoice.footer_margin_top_int += 10;
            footer_counter++;
        }
        if (invoice.partial && invoice.partial != '0.00') {
            invoice.left_footer_margin_bottom_int += 32;
            invoice.footer_margin_top_int += 10;
            footer_counter++;
        }
        if (invoice.footer_margin_top_int > -1) {
            invoice.footer_margin_top_int = -1;
        }
        var total_rows = invoice.invoice_items.length;
        if (invoice.public_notes && invoice.public_notes.length) total_rows + 2;
        if (footer_counter > 3 && total_rows > 10 && total_rows < 19) {
            breake_page = true;
        }
        if (total_rows > 12 && total_rows < 19)
            breake_page = true;
        invoice.breake_page = breake_page;
        invoice.footer_counter = footer_counter;
        if (breake_page) {
            invoice.havepage_break = "pageBreak";
            invoice.footer_margin_top_int = -20;
        }
    }

    if (invoice.invoice_status_id === 6) {
        invoice.paid_overdue_int = JSON.stringify({ "image": paidImage, "width": 100 })
    }
    if (invoice.is_over_due && invoice.invoice_status_id >= 2) {
        invoice.paid_overdue_int = JSON.stringify({ "image": pastDue, "width": 100 })
    }

    isEdge = false;
    var account = invoice.account;
    var blankImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=';

    // search/replace variables
    var json = {
        'accountName': account.name || ' ',
        'accountLogo': (!isEdge && window.accountLogo) ? window.accountLogo : blankImage,
        'accountDetails': NINJA.accountDetails(invoice),
        'contactDetails': NINJA.contactDetails(invoice),
        'accountAddress': NINJA.accountAddress(invoice),
        'invoiceDetails': NINJA.invoiceDetails(invoice),
        'invoiceDetailsHeight': (NINJA.invoiceDetails(invoice).length * 16) + 16,
        'invoiceLineItems': invoice.is_statement ? NINJA.statementLines(invoice) : NINJA.invoiceLines(invoice),
        'invoiceLineItemColumns': invoice.is_statement ? NINJA.statementColumns(invoice) : NINJA.invoiceColumns(invoice),
        'invoiceDocuments': isEdge ? [] : NINJA.invoiceDocuments(invoice),
        'quantityWidth': NINJA.quantityWidth(invoice),
        'taxWidth': NINJA.taxWidth(invoice),
        'clientDetails': NINJA.clientDetails(invoice),
        'notesAndTerms': NINJA.notesAndTerms(invoice),
        'subtotals': invoice.is_statement ? NINJA.statementSubtotals(invoice) : NINJA.subtotals(invoice),
        'subtotalsHeight': (NINJA.subtotals(invoice).length * 16) + 16,
        'subtotalsWithoutBalance': invoice.is_statement ? [
            []
        ] : NINJA.subtotals(invoice, true),
        'subtotalsBalance': NINJA.subtotalsBalance(invoice),
        'balanceDue': formatMoneyInvoice(invoice.balance_amount, invoice),
        'invoiceFooter': NINJA.invoiceFooter(invoice),
        'invoiceNumber': invoice.invoice_number || ' ',
        'entityType': invoice.is_statement ? invoiceLabels.statement : invoice.is_quote ? invoiceLabels.quote : invoice.balance_amount < 0 ? invoiceLabels.credit_note : invoiceLabels.invoice,
        'entityTypeUC': (invoice.is_statement ? invoiceLabels.statement : invoice.is_quote ? invoiceLabels.quote : invoice.balance_amount < 0 ? invoiceLabels.credit_note : invoiceLabels.invoice).toUpperCase(),
        'entityTaxType': invoice.is_statement ? invoiceLabels.statement : invoice.is_quote ? invoiceLabels.tax_quote : invoiceLabels.tax_invoice,
        'fontSize': NINJA.fontSize,
        'fontSizeLarger': NINJA.fontSize + 1,
        'fontSizeLargest': NINJA.fontSize + 2,
        'fontSizeSmaller': NINJA.fontSize - 1,
        'bodyFont': NINJA.bodyFont,
        'headerFont': NINJA.headerFont,
    }

    for (var key in json) {
        // remove trailing commas for these fields
        if (['quantityWidth', 'taxWidth'].indexOf(key) >= 0) {
            var regExp = new RegExp('"\\$' + key + '",', 'g');
            val = json[key];
        } else {
            var regExp = new RegExp('"\\$' + key + '"', 'g');
            var val = JSON.stringify(json[key]);
            val = doubleDollarSign(val);
        }
        javascript = javascript.replace(regExp, val);
    }

    // search/replace labels
    var regExp = new RegExp('"\\$\\\w*?Label(UC)?(:)?(\\\?)?"', 'g');
    var matches = javascript.match(regExp);

    if (matches) {
        for (var i = 0; i < matches.length; i++) {
            var match = matches[i];
            field = match.substring(2, match.indexOf('Label'));
            field = toSnakeCase(field);
            var value = getDescendantProp(invoice, field);
            if (match.indexOf('?') < 0 || value) {
                if (invoice.partial > 0 && field == 'balance_due') {
                    field = 'partial_due';
                } else if (invoice.is_quote) {
                    if (field == 'due_date') {
                        field = 'valid_until';
                    } else {
                        field = field.replace('invoice', 'quote');
                    }
                }
                if (invoice.is_statement) {
                    if (field == 'your_invoice') {
                        field = 'your_statement';
                    } else if (field == 'invoice_issued_to') {
                        field = 'statement_issued_to';
                    } else if (field == 'invoice_to') {
                        field = 'statement_to';
                    }
                } else if (invoice.balance_amount < 0) {
                    if (field == 'your_invoice') {
                        field = 'your_credit';
                    } else if (field == 'invoice_issued_to') {
                        field = 'credit_issued_to';
                    } else if (field == 'invoice_to') {
                        field = 'credit_to';
                    }
                }

                var label = invoiceLabels[field];
                if (match.indexOf('UC') >= 0) {
                    label = label.toUpperCase();
                }
                if (match.indexOf(':') >= 0) {
                    label = label + ':';
                }
            } else {
                label = ' ';
            }
            javascript = javascript.replace(match, '"' + label + '"');
        }
    }

    // search/replace values
    var regExp = new RegExp('"\\$[a-z][\\\w\\\.]*?[Value]?"|\\s\\$[a-z][\\\w\\\.]*?[Value]?"', 'g');
    var matches = javascript.match(regExp);

    if (matches) {
        for (var i = 0; i < matches.length; i++) {
            var match = matches[i];

            // reserved words
            if ([
                    '"$none"',
                    '"$firstAndLast"',
                    '"$notFirstAndLastColumn"',
                    '"$LastColumn"',
                    '"$FirstColumn"',
                    '"$notFirst"',
                    '"$amount"',
                    '"$primaryColor"',
                    '"$secondaryColor"',
                ].indexOf(match) >= 0) {
                continue;
            }

            // legacy style had 'Value' at the end
            if (endsWith(match, 'Value"')) {
                field = match.substring(2, match.indexOf('Value'));
            } else {
                field = match.substring(2, match.length - 1);
            }
            field = toSnakeCase(field);

            var value = getDescendantProp(invoice, field) || ' ';
            value = doubleDollarSign(value);
            if (endsWith(match, '_int"')) {
                javascript = javascript.replace("\"$" + field + '"', '' + value + '');
            } else {
                javascript = javascript.replace("$" + field, '' + value + '');
            }
            // javascript = javascript.replace("$"+field, ''+value+'');
        }
    }

    return javascript;
}


NINJA.notesAndTerms = function(invoice) {
    var data = [];

    if (invoice.public_notes) {
        data.push({ stack: [{ text: invoice.is_recurring ? processVariables(invoice.public_notes) : invoice.public_notes, style: ['notes'] }] });
        data.push({ text: ' ' });
    }

    if (invoice.terms) {
        data.push({ text: invoiceLabels.terms, style: ['termsLabel'] });
        data.push({ stack: [{ text: invoice.is_recurring ? processVariables(invoice.terms) : invoice.terms, style: ['terms'] }] });
    }

    return NINJA.prepareDataList(data, 'notesAndTerms');
}

NINJA.statementColumns = function(invoice) {
    return ["22%", "22%", "22%", "17%", "17%"];
}

NINJA.statementLines = function(invoice) {
    var grid = [
        []
    ];
    grid[0].push({ text: invoiceLabels.invoice_number, style: ['tableHeader', 'itemTableHeader'] });
    grid[0].push({ text: invoiceLabels.invoice_date, style: ['tableHeader', 'invoiceDateTableHeader'] });
    grid[0].push({ text: invoiceLabels.due_date, style: ['tableHeader', 'dueDateTableHeader'] });
    grid[0].push({ text: invoiceLabels.total, style: ['tableHeader', 'totalTableHeader'] });
    grid[0].push({ text: invoiceLabels.balance, style: ['tableHeader', 'balanceTableHeader'] });

    for (var i = 0; i < invoice.invoice_items.length; i++) {
        var item = invoice.invoice_items[i];
        var row = [];
        var rowStyle = (i % 2 == 0) ? 'odd' : 'even';

        grid.push([
            { text: item.invoice_number, style: ['invoiceNumber', 'productKey', rowStyle] },
            { text: item.invoice_date && item.invoice_date != '0000-00-00' ? moment(item.invoice_date).format(invoice.date_format) : ' ', style: ['invoiceDate', rowStyle] },
            { text: item.due_date && item.due_date != '0000-00-00' ? moment(item.due_date).format(invoice.date_format) : ' ', style: ['dueDate', rowStyle] },
            { text: formatMoneyInvoice(item.amount, invoice), style: ['subtotals', rowStyle] },
            { text: formatMoneyInvoice(item.balance, invoice), style: ['lineTotal', rowStyle] },
        ]);
    }

    return NINJA.prepareDataTable(grid, 'invoiceItems');
}

NINJA.invoiceColumns = function(invoice) {
    var account = invoice.account;
    var columns = [];

    columns.push("5%");
    columns.push("11%");
    columns.push("10%");

    columns.push("45%");

    if (invoice.features.invoice_settings && account.custom_invoice_item_label1) {
        columns.push("15%");
    }
    if (invoice.features.invoice_settings && account.custom_invoice_item_label2) {
        columns.push("10%");
    }

    var count = 1;
    if (account.hide_quantity == '1') {
        count -= 2;
    }
    if (account.show_item_taxes == '1') {
        count++;
    }
    for (var i = 0; i < count; i++) {
        columns.push("14%");
    }

    return columns;
}

NINJA.invoiceFooter = function(invoice) {
    var footer = invoice.invoice_footer;

    if (invoice.is_recurring) {
        footer = processVariables(footer);
    }

    if (!invoice.features.invoice_settings && invoice.invoice_design_id == 3) {
        return footer ? footer.substring(0, 200) : ' ';
    } else {
        return footer || ' ';
    }
}

NINJA.quantityWidth = function(invoice) {
    return invoice.account.hide_quantity == '1' ? '' : '"14%", ';
}

NINJA.taxWidth = function(invoice) {
    return invoice.account.show_item_taxes == '1' ? '"14%", ' : '';
}

NINJA.invoiceLines = function(invoice) {
    var account = invoice.account;
    var total = 0;
    var shownItem = false;
    var hideQuantity = invoice.account.hide_quantity == '1';
    var showItemTaxes = invoice.account.show_item_taxes == '1';

    var grid = [
        []
    ];

    grid[0].push({ text: "Qty", style: ['tableHeaderStyle'] });
    grid[0].push({ text: "Rate", style: ['tableHeaderStyle'] });
    grid[0].push({ text: "Item", style: ['tableHeaderStyle'] });
    grid[0].push({ text: "Description", style: ['tableHeaderStyle'] });

    // if (invoice.has_product_key) {
    //     grid[0].push({text: invoiceLabels.item, style: ['tableHeader', 'itemTableHeader']});
    // }

    // grid[0].push({text: invoiceLabels.description, style: ['tableHeader', 'descriptionTableHeader']});

    if (invoice.features.invoice_settings && account.custom_invoice_item_label1) {
        grid[0].push({ text: account.custom_invoice_item_label1, style: ['tableHeaderStyle'] });
    }
    if (invoice.features.invoice_settings && account.custom_invoice_item_label2) {
        grid[0].push({ text: account.custom_invoice_item_label2, style: ['tableHeaderStyle'] });
    }

    // if (!hideQuantity) {
    //     grid[0].push({text: invoiceLabels.unit_cost, style: ['tableHeader', 'costTableHeader']});
    //     grid[0].push({text: invoiceLabels.quantity, style: ['tableHeader', 'qtyTableHeader']});
    // }
    if (showItemTaxes) {
        grid[0].push({ text: invoiceLabels.tax, style: ['tableHeaderStyle'] });
    }

    grid[0].push({ text: invoiceLabels.line_total, style: ['tableHeaderStyle'] });
    var counter1 = 0;
    var paritial_total = 0;
    for (var i = 0; i < invoice.invoice_items.length; i++) {

        var row = [];
        var item = invoice.invoice_items[i];
        var cost = item.cost;
        var org_cost = cost;
        var qty = NINJA.parseFloat(item.qty) ? roundToTwo(NINJA.parseFloat(item.qty)) + '' : '';
        var notes = item.notes;
        var sku = item.sku;

        var productKey = item.product_key;
        var tax1 = '';
        var tax2 = '';
        var custom_value1 = item.custom_value1;
        var custom_value2 = item.custom_value2;

        if (showItemTaxes) {
            if (item.tax_name1) {
                tax1 = parseFloat(item.tax_rate1);
            }
            if (item.tax_name2) {
                tax2 = parseFloat(item.tax_rate2);
            }
        }

        // show at most one blank line
        if (shownItem && !notes && !productKey && !sku && (!cost || cost == '0' || cost == '0.00' || cost == '0,00')) {
            continue;
        }

        counter1 += 1;
        shownItem = true;
        if (cost !== '0' && cost != '0.00' && cost != '0,00' && cost != "")
            cost = formatMoneyInvoice(cost, invoice);
        else
            cost = "";
        // process date variables
        if (invoice.is_recurring) {
            notes = processVariables(notes);
            sku = processVariables(sku);
            productKey = processVariables(productKey);
            custom_value1 = processVariables(item.custom_value1);
            custom_value2 = processVariables(item.custom_value2);
        }

        var lineTotal = roundToTwo(NINJA.parseFloat(item.cost)) * roundToTwo(NINJA.parseFloat(item.qty));
        if (NINJA.parseFloat(org_cost) < 0) {
            if (org_cost && org_cost.indexOf("%") !== -1) {
                cost = NINJA.parseFloat(org_cost);
                var discount = cost / 100 * paritial_total;
                discount = roundToTwo(discount);
                // item.totals._rawTotal(-discount);
                lineTotal = discount;
                paritial_total = 0;
                cost = org_cost;

            } else {

                lineTotal = NINJA.parseFloat(org_cost);
            }
        } else {
            paritial_total = lineTotal;
        }

        if (account.include_item_taxes_inline == '1') {
            if (tax1) {
                lineTotal += lineTotal * tax1 / 100;
                lineTotal = roundToTwo(lineTotal);
            }
            if (tax2) {
                lineTotal += lineTotal * tax2 / 100;
                lineTotal = roundToTwo(lineTotal);
            }
        }
        if (lineTotal != '0.00' && lineTotal != '0' && lineTotal != '0,00')
            lineTotal = formatMoneyInvoice(lineTotal, invoice);
        else
            lineTotal = "";
        rowStyle = (i % 2 == 0) ? 'odd' : 'even';

        row.push({ style: ["quantity", rowStyle], text: formatAmount(qty, invoice.client.currency_id) || ' ', alignment: "center", margin: [0, 0, 0, 0] }); // product key can be blank when selecting from a datalist
        row.push({ style: ["cost", rowStyle], text: cost, alignment: "right", margin: [0, 0, 0, 0] });
        row.push({ style: ["sku", rowStyle], text: sku || ' ', alignment: "center", margin: [0, 0, 0, 0] }); // product key can be blank when selecting from a datalist
        // row.push({style:["productKey", rowStyle], text:productKey || ' '}); // product key can be blank when selecting from a datalist
        // if (invoice.has_product_key) {
        //     row.push({style:["productKey", rowStyle], text:productKey || ' '}); // product key can be blank when selecting from a datalist
        // }
        row.push({ style: ["productKey", rowStyle], stack: [{ text: productKey || ' ' }], alignment: "left", margin: [0, 0, 0, 0] });
        if (invoice.features.invoice_settings && account.custom_invoice_item_label1) {
            row.push({ style: ["customValue1", rowStyle], text: custom_value1 || ' ', alignment: "center", margin: [0, 0, 0, 0] });
        }
        if (invoice.features.invoice_settings && account.custom_invoice_item_label2) {

            row.push({ style: ["customValue2", rowStyle], text: custom_value2 || ' ', alignment: "center", margin: [0, 0, 0, 0] });
        }
        // if (!hideQuantity) {
        //     row.push({style:["cost", rowStyle], text:cost});
        //     row.push({style:["quantity", rowStyle], text:formatAmount(qty, invoice.client.currency_id) || ' '});
        // }
        if (showItemTaxes) {
            var str = ' ';
            if (item.tax_name1) {
                str += tax1.toString() + '%';
            }
            if (item.tax_name2) {
                if (item.tax_name1) {
                    str += '  ';
                }
                str += tax2.toString() + '%';
            }
            row.push({ style: ["tax", rowStyle], text: str, alignment: "right", margin: [0, 0, 0, 0] });
        }
        row.push({ style: ["lineTotal", rowStyle], text: lineTotal || ' ', alignment: "right", margin: [0, 0, 0, 0] });

        grid.push(row);
    }
    if (invoice.interest_rate && false) {
        var row = [];
        rowStyle = (counter1 % 2 == 0) ? 'odd' : 'even';

        row.push({ style: ["quantity", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] }); // product key can be blank when selecting from a datalist
        row.push({ style: ["cost", rowStyle], text: "", alignment: "right", margin: [0, 5, 0, 4] });
        row.push({ style: ["sku", rowStyle], text: "Interest", alignment: "center", margin: [0, 5, 0, 4] }); // product key can be blank when selecting from a datalist

        row.push({ style: ["productKey", rowStyle], stack: [{ text: "3% Per Month" }], alignment: "left", margin: [0, 5, 0, 4] });
        if (invoice.features.invoice_settings && account.custom_invoice_item_label1) {
            row.push({ style: ["customValue1", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] });
        }
        if (invoice.features.invoice_settings && account.custom_invoice_item_label2) {
            row.push({ style: ["customValue2", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] });
        }
        // if (!hideQuantity) {
        //     row.push({style:["cost", rowStyle], text:cost});
        //     row.push({style:["quantity", rowStyle], text:formatAmount(qty, invoice.client.currency_id) || ' '});
        // }
        if (showItemTaxes) {
            var str = ' ';

            row.push({ style: ["tax", rowStyle], text: str, alignment: "right", margin: [0, 5, 0, 6] });
        }
        row.push({ style: ["lineTotal", rowStyle], text: formatMoneyInvoice(invoice.interest_rate, invoice), alignment: "right", margin: [0, 5, 0, 6] });

        grid.push(row);
    }
    if (invoice.public_notes && invoice.public_notes.length) {
        var row = [];
        rowStyle = (counter1 % 2 == 0) ? 'odd' : 'even';

        row.push({ style: ["quantity", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] }); // product key can be blank when selecting from a datalist
        row.push({ style: ["cost", rowStyle], text: "", alignment: "right", margin: [0, 5, 0, 4] });
        row.push({ style: ["sku", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] }); // product key can be blank when selecting from a datalist

        row.push({ style: ["productKey", rowStyle], stack: [{ text: "" }], alignment: "left", margin: [0, 5, 0, 4] });
        if (invoice.features.invoice_settings && account.custom_invoice_item_label1) {
            row.push({ style: ["customValue1", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] });
        }
        if (invoice.features.invoice_settings && account.custom_invoice_item_label2) {
            row.push({ style: ["customValue2", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] });
        }
        // if (!hideQuantity) {
        //     row.push({style:["cost", rowStyle], text:cost});
        //     row.push({style:["quantity", rowStyle], text:formatAmount(qty, invoice.client.currency_id) || ' '});
        // }
        if (showItemTaxes) {
            var str = ' ';

            row.push({ style: ["tax", rowStyle], text: str, alignment: "right", margin: [0, 5, 0, 6] });
        }
        row.push({ style: ["lineTotal", rowStyle], text: "", alignment: "right", margin: [0, 5, 0, 6] });

        grid.push(row);
        counter1++;
        rowStyle = (counter1 % 2 == 0) ? 'odd' : 'even';
        row = [];
        row.push({ style: ["clientNote", rowStyle], text: invoice.public_notes, alignment: "center", margin: [0, 5, 0, 4], "colSpan": 6 }); // product key can be blank when selecting
        grid.push(row);
        counter1++;
    }
    max_row = 12;
    if (invoice.is_quote) max_row = 9;
    if (invoice.footer_counter > 2 && counter1 < 11) {
        if (invoice.is_quote) max_row = 8;
        else
            max_row = 12;
    }
    if (invoice.footer_counter > 4 && counter1 < 11) {
        if (invoice.is_quote) max_row = 6;
        else
            max_row = 10;
    }

    if (invoice.footer_counter > 2 && counter1 >= 11) {
        if (invoice.is_quote) max_row = 11;
        else
            max_row = 17;
    }
    if (invoice.footer_counter > 2 && counter1 == 11 && invoice.is_quote) {
        max_row = 11;

    }
    if (counter1 >= 19)
        max_row = 28;


    for (; counter1 < max_row; counter1++) {
        var row = [];
        rowStyle = (counter1 % 2 == 0) ? 'odd' : 'even';

        row.push({ style: ["quantity", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] }); // product key can be blank when selecting from a datalist
        row.push({ style: ["cost", rowStyle], text: "", alignment: "right", margin: [0, 5, 0, 4] });
        row.push({ style: ["sku", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] }); // product key can be blank when selecting from a datalist

        row.push({ style: ["productKey", rowStyle], stack: [{ text: "" }], alignment: "left", margin: [0, 5, 0, 4] });
        if (invoice.features.invoice_settings && account.custom_invoice_item_label1) {
            row.push({ style: ["customValue1", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] });
        }
        if (invoice.features.invoice_settings && account.custom_invoice_item_label2) {
            row.push({ style: ["customValue2", rowStyle], text: "", alignment: "center", margin: [0, 5, 0, 4] });
        }
        // if (!hideQuantity) {
        //     row.push({style:["cost", rowStyle], text:cost});
        //     row.push({style:["quantity", rowStyle], text:formatAmount(qty, invoice.client.currency_id) || ' '});
        // }
        if (showItemTaxes) {
            var str = ' ';

            row.push({ style: ["tax", rowStyle], text: str, alignment: "right", margin: [0, 5, 0, 6] });
        }
        row.push({ style: ["lineTotal", rowStyle], text: "", alignment: "right", margin: [0, 5, 0, 6] });

        grid.push(row);
    }
    return NINJA.prepareDataTable(grid, 'invoiceItems');
};

NINJA.invoiceDocuments = function(invoice) {
    if (invoice.account.invoice_embed_documents != '1') {
        return [];
    }

    var j = 0;
    var stack = [];
    var stackItem = null;

    if (invoice.documents) {
        for (var i = 0; i < invoice.documents.length; i++) {
            addDoc(invoice.documents[i]);
        }
    }

    if (invoice.expenses) {
        for (var i = 0; i < invoice.expenses.length; i++) {
            var expense = invoice.expenses[i];
            for (var j = 0; j < expense.documents.length; j++) {
                addDoc(expense.documents[j]);
            }
        }
    }

    function addDoc(document) {
        var path = document.base64;

        if (!path) path = 'docs/' + document.public_id + '/' + document.name;
        if (path && (window.pdfMake.vfs[path] || document.base64)) {
            // Only embed if we actually have an image for it
            if (j % 3 == 0) {
                stackItem = { columns: [] };
                stack.push(stackItem);
            }
            stackItem.columns.push({ stack: [{ image: path, style: 'invoiceDocument', fit: [150, 150] }], width: 175 })
            j++;
        }
    }

    return stack.length ? { stack: stack } : [];
}

NINJA.statementSubtotals = function(invoice) {
    var data = [
        [
            { text: invoiceLabels.balance_due, style: ['subtotalsLabel', 'balanceDueLabel'] },
            { text: formatMoneyInvoice(invoice.balance_amount, invoice), style: ['subtotals', 'balanceDue'] }
        ]
    ];

    return NINJA.prepareDataPairs(data, 'subtotals');
}

NINJA.subtotals = function(invoice, hideBalance) {
    if (!invoice) {
        return;
    }

    var account = invoice.account;
    var data = [];
    data.push([{ text: invoiceLabels.subtotal, style: ['subtotalsLabel', 'subtotalLabel'] }, { text: formatMoneyInvoice(invoice.subtotal_amount, invoice), style: ['subtotals', 'subtotal'] }]);

    if (invoice.discount_amount != 0) {
        data.push([{ text: invoiceLabels.discount, style: ['subtotalsLabel', 'discountLabel'] }, { text: formatMoneyInvoice(invoice.discount_amount, invoice), style: ['subtotals', 'discount'] }]);
    }

    var customValue1 = NINJA.parseFloat(invoice.custom_value1);
    var customValue1Label = customValue1 >= 0 ? (account.custom_invoice_label1 || invoiceLabels.surcharge) : invoiceLabels.discount;

    var customValue2 = NINJA.parseFloat(invoice.custom_value2);
    var customValue2Label = customValue2 >= 0 ? (account.custom_invoice_label2 || invoiceLabels.surcharge) : invoiceLabels.discount;

    if (customValue1 && invoice.custom_taxes1 == '1') {
        data.push([{ text: customValue1Label, style: ['subtotalsLabel', 'customTax1Label'] }, { text: formatMoneyInvoice(invoice.custom_value1, invoice), style: ['subtotals', 'customTax1'] }]);
    }
    if (customValue2 && invoice.custom_taxes2 == '1') {
        data.push([{ text: customValue2Label, style: ['subtotalsLabel', 'customTax2Label'] }, { text: formatMoneyInvoice(invoice.custom_value2, invoice), style: ['subtotals', 'customTax2'] }]);
    }

    for (var key in invoice.item_taxes) {
        if (invoice.item_taxes.hasOwnProperty(key)) {
            var taxRate = invoice.item_taxes[key];
            var taxStr = taxRate.name + ' ' + (taxRate.rate * 1).toString() + '%';
            data.push([{ text: taxStr, style: ['subtotalsLabel', 'taxLabel'] }, { text: formatMoneyInvoice(taxRate.amount, invoice), style: ['subtotals', 'tax'] }]);
        }
    }

    if (invoice.tax_name1) {
        var taxStr = invoice.tax_name1 + ' ' + (invoice.tax_rate1 * 1).toString() + '%';
        data.push([{ text: taxStr, style: ['subtotalsLabel', 'tax1Label'] }, { text: formatMoneyInvoice(invoice.tax_amount1, invoice), style: ['subtotals', 'tax1'] }]);
    }
    if (invoice.tax_name2) {
        var taxStr = invoice.tax_name2 + ' ' + (invoice.tax_rate2 * 1).toString() + '%';
        data.push([{ text: taxStr, style: ['subtotalsLabel', 'tax2Label'] }, { text: formatMoneyInvoice(invoice.tax_amount2, invoice), style: ['subtotals', 'tax2'] }]);
    }

    if (customValue1 && invoice.custom_taxes1 != '1') {
        data.push([{ text: customValue1Label, style: ['subtotalsLabel', 'custom1Label'] }, { text: formatMoneyInvoice(invoice.custom_value1, invoice), style: ['subtotals', 'custom1'] }]);
    }
    if (customValue2 && invoice.custom_taxes2 != '1') {
        data.push([{ text: customValue2Label, style: ['subtotalsLabel', 'custom2Label'] }, { text: formatMoneyInvoice(invoice.custom_value2, invoice), style: ['subtotals', 'custom2'] }]);
    }

    if (invoice.interest_rate) {
        data.push([
            { text: "Interest", style: ['subtotalsLabel', 'custom2Label'] },
            { text: formatMoneyInvoice(invoice.interest_rate, invoice), style: ['subtotals'] }
        ]);
    }

    var paid = invoice.amount - invoice.balance;
    if (!invoice.is_quote && invoice.balance_amount >= 0 && (invoice.account.hide_paid_to_date != '1' || paid)) {
        data.push([{ text: invoiceLabels.paid_to_date, style: ['subtotalsLabel', 'paidToDateLabel'] }, { text: formatMoneyInvoice(paid, invoice), style: ['subtotals', 'paidToDate'] }]);
    }

    var isPartial = NINJA.parseFloat(invoice.partial);

    if (!hideBalance || isPartial) {
        data.push([
            { text: invoice.is_quote || invoice.balance_amount < 0 ? invoiceLabels.total : invoiceLabels.balance_due, style: ['subtotalsLabel', isPartial ? '' : 'balanceDueLabel'] },
            { text: formatMoneyInvoice(invoice.total_amount, invoice), style: ['subtotals', isPartial ? '' : 'balanceDue'] }
        ]);
    }



    if (!hideBalance) {
        if (isPartial) {
            data.push([
                { text: invoiceLabels.partial_due, style: ['subtotalsLabel', 'balanceDueLabel'] },
                { text: formatMoneyInvoice(invoice.balance_amount, invoice), style: ['subtotals', 'balanceDue'] }
            ]);
        }
    }



    return NINJA.prepareDataPairs(data, 'subtotals');
}

NINJA.subtotalsBalance = function(invoice) {
    var isPartial = NINJA.parseFloat(invoice.partial);
    return [
        [
            { text: isPartial ? invoiceLabels.partial_due : (invoice.is_quote || invoice.balance_amount < 0 ? invoiceLabels.total : invoiceLabels.balance_due), style: ['subtotalsLabel', 'balanceDueLabel'] },
            { text: formatMoneyInvoice(invoice.balance_amount, invoice), style: ['subtotals', 'balanceDue'] }
        ]
    ];
}

NINJA.accountDetails = function(invoice) {
    var account = invoice.account;
    if (invoice.features.invoice_settings && account.invoice_fields) {
        var fields = JSON.parse(account.invoice_fields).account_fields1;
    } else {
        var fields = [
            'account.company_name',
            'account.id_number',
            'account.vat_number',
            'account.website',
            'account.email',
            'account.phone',
        ];
    }

    var data = [];

    for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        var value = NINJA.renderClientOrAccountField(invoice, field);
        if (value) {
            data.push(value);
        }
    }

    return NINJA.prepareDataList(data, 'accountDetails');
}

NINJA.contactDetails = function(invoice) {
    var account = invoice.account;

    var fields = [
        'client.client_name',
        'client.contact_position',
        'client.contact_name_2'
    ];


    var data = [];

    for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        var value = NINJA.renderClientOrAccountField(invoice, field);
        if (value) {
            data.push(value);
        }
    }

    return NINJA.prepareDataList(data, 'contactDetails');
}

NINJA.accountAddress = function(invoice) {
    var account = invoice.account;
    if (invoice.features.invoice_settings && account.invoice_fields) {
        var fields = JSON.parse(account.invoice_fields).account_fields2;
    } else {
        var fields = [
            'account.address1',
            'account.address2',
            'account.city_state_postal',
            'account.country',
            'account.custom_value1',
            'account.custom_value2',
        ]
    }

    var data = [];

    for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        var value = NINJA.renderClientOrAccountField(invoice, field);
        if (value) {
            data.push(value);
        }
    }

    return NINJA.prepareDataList(data, 'accountAddress');
}

NINJA.renderInvoiceField = function(invoice, field) {

    var account = invoice.account;

    if (field == 'invoice.invoice_number') {
        if (invoice.is_statement) {
            return false;
        } else {
            return [
                { text: (invoice.is_quote ? invoiceLabels.quote_number : invoice.balance_amount < 0 ? invoiceLabels.credit_number : invoiceLabels.invoice_number), style: ['invoiceNumberLabel'] },
                { text: invoice.invoice_number, style: ['invoiceNumber'] }
            ];
        }
    } else if (field == 'invoice.po_number') {
        return [
            { text: invoiceLabels.po_number },
            { text: invoice.po_number }
        ];
    } else if (field == 'invoice.invoice_date') {
        return [
            { text: (invoice.is_statement ? invoiceLabels.statement_date : invoice.is_quote ? invoiceLabels.quote_date : invoice.balance_amount < 0 ? invoiceLabels.credit_date : invoiceLabels.invoice_date) },
            { text: invoice.invoice_date }
        ];
    } else if (field == 'invoice.due_date') {
        return [
            { text: (invoice.is_quote ? invoiceLabels.valid_until : invoiceLabels.due_date) },
            { text: invoice.is_recurring ? false : invoice.due_date }
        ];
    } else if (field == 'invoice.custom_text_value1') {
        if (invoice.custom_text_value1 && account.custom_invoice_text_label1) {
            return [
                { text: invoice.account.custom_invoice_text_label1 },
                { text: invoice.is_recurring ? processVariables(invoice.custom_text_value1) : invoice.custom_text_value1 }
            ];
        } else {
            return false;
        }
    } else if (field == 'invoice.custom_text_value2') {
        if (invoice.custom_text_value2 && account.custom_invoice_text_label2) {
            return [
                { text: invoice.account.custom_invoice_text_label2 },
                { text: invoice.is_recurring ? processVariables(invoice.custom_text_value2) : invoice.custom_text_value2 }
            ];
        } else {
            return false;
        }
    } else if (field == 'invoice.balance_due') {
        return [
            { text: invoice.is_quote || invoice.balance_amount < 0 ? invoiceLabels.total : invoiceLabels.balance_due, style: ['invoiceDetailBalanceDueLabel'] },
            { text: formatMoneyInvoice(invoice.total_amount, invoice), style: ['invoiceDetailBalanceDue'] }
        ];
    } else if (field == invoice.partial_due) {
        if (NINJA.parseFloat(invoice.partial)) {
            return [
                { text: invoiceLabels.partial_due, style: ['invoiceDetailBalanceDueLabel'] },
                { text: formatMoneyInvoice(invoice.balance_amount, invoice), style: ['invoiceDetailBalanceDue'] }
            ];
        } else {
            return false;
        }
    } else if (field == '.blank') {
        return [{ text: ' ' }, { text: ' ' }];
    }
}

NINJA.invoiceDetails = function(invoice) {

    var account = invoice.account;
    if (invoice.features.invoice_settings && account.invoice_fields) {
        var fields = JSON.parse(account.invoice_fields).invoice_fields;
    } else {
        var fields = [
            'invoice.invoice_number',
            'invoice.po_number',
            'invoice.invoice_date',
            'invoice.due_date',
            'invoice.balance_due',
            'invoice.partial_due',
            'invoice.custom_text_value1',
            'invoice.custom_text_value2',
        ];
    }
    var data = [];

    for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        var value = NINJA.renderInvoiceField(invoice, field);
        if (value) {
            data.push(value);
        }
    }

    return NINJA.prepareDataPairs(data, 'invoiceDetails');
}


NINJA.renderClientOrAccountField = function(invoice, field) {
    var client = invoice.client;
    if (!client) {
        return false;
    }
    var account = invoice.account;
    if (client.contacts && client.contacts.length)
        var contact = client.contacts[0];
    else {
        var contact = {};
        client.contacts = [];
    }

    if (invoice.contact)
        contact = invoice.contact;
    for (j = 0; j < client.contacts.length; j++) {
        var _contact = client.contacts[j];
        if (_contact.send_invoice) {
            contact = _contact;
            break;
        }
    }
    var clientName = client.name;

    var contact_name = (contact.first_name + ' ' + contact.last_name);



    if (field == 'client.client_name') {
        return { text: clientName || ' ', style: ['clientName'] };
    } else if (field == 'client.contact_name') {
        return (contact.first_name || contact.last_name) ? { text: "c/o:  " + contact.first_name + ' ' + contact.last_name } : false;
    } else if (field == 'client.contact_name_2') {
        return (contact.first_name || contact.last_name) ? { text: contact.first_name + ' ' + contact.last_name } : false;
    } else if (field == 'client.id_number') {
        return { text: client.id_number };
    } else if (field == 'client.vat_number') {
        return { text: client.vat_number };
    } else if (field == 'client.address1') {
        if (contact.billing_address && contact.billing_address.address_1) {
            return { text: contact.billing_address.address_1 };
        }
        return { text: client.address1 };
    } else if (field == 'client.address2') {
        if (contact.billing_address && contact.billing_address.address_2) {
            return { text: contact.billing_address.address_2 };
        }
        return { text: client.address2 };
    } else if (field == 'client.city_state_postal') {
        var cityStatePostal = '';
        if (contact.billing_address && contact.billing_address.zip) {
            var swap = client.country && client.country.swap_postal_code;
            cityStatePostal = formatAddress(contact.billing_address.city, contact.billing_address.state, contact.billing_address.zip, swap);
        } else
        if (client.city || client.state || client.postal_code) {
            var swap = client.country && client.country.swap_postal_code;
            cityStatePostal = formatAddress(client.city, client.state, client.postal_code, swap);
        }
        return { text: cityStatePostal };
    } else if (field == 'client.postal_city_state') {
        var postalCityState = '';
        if (contact.city || contact.state || contact.postal_code) {
            postalCityState = formatAddress(client.city, client.state, client.postal_code, true);
        }
        return { text: postalCityState };
    } else if (field == 'client.country') {
        return { text: client.country ? client.country.name : '' };
    } else if (field == 'client.email') {
        var clientEmail = contact.email == clientName ? '' : contact.email;
        return { text: clientEmail };
    } else if (field == 'client.phone') {
        return { text: contact.phone };
    } else if (field == 'client.custom_value1') {
        return { text: account.custom_client_label1 && client.custom_value1 ? account.custom_client_label1 + ' ' + client.custom_value1 : false };
    } else if (field == 'client.custom_value2') {
        return { text: account.custom_client_label2 && client.custom_value2 ? account.custom_client_label2 + ' ' + client.custom_value2 : false };
    } else if (field == 'client.contact_position') {
        return { text: contact.position }
    }

    if (field == 'account.company_name') {
        return { text: account.name, style: ['accountName'] };
    } else if (field == 'account.id_number') {
        return { text: account.id_number, style: ['idNumber'] };
    } else if (field == 'account.vat_number') {
        return { text: account.vat_number, style: ['vatNumber'] };
    } else if (field == 'account.website') {
        return { text: account.website, style: ['website'] };
    } else if (field == 'account.email') {
        return { text: account.work_email, style: ['email'] };
    } else if (field == 'account.phone') {
        return { text: account.work_phone, style: ['phone'] };
    } else if (field == 'account.address1') {
        return { text: account.address1 };
    } else if (field == 'account.address2') {
        return { text: account.address2 };
    } else if (field == 'account.city_state_postal') {
        var cityStatePostal = '';
        if (account.city || account.state || account.postal_code) {
            var swap = account.country && account.country.swap_postal_code;
            cityStatePostal = formatAddress(account.city, account.state, account.postal_code, swap);
        }
        return { text: cityStatePostal };
    } else if (field == 'account.postal_city_state') {
        var postalCityState = '';
        if (account.city || account.state || account.postal_code) {
            postalCityState = formatAddress(account.city, account.state, account.postal_code, true);
        }
        return { text: postalCityState };
    } else if (field == 'account.country') {
        return account.country ? { text: account.country.name } : false;
    } else if (field == 'account.custom_value1') {
        if (invoice.features.invoice_settings) {
            return invoice.account.custom_label1 && invoice.account.custom_value1 ? { text: invoice.account.custom_label1 + ' ' + invoice.account.custom_value1 } : false;
        }
    } else if (field == 'account.custom_value2') {
        if (invoice.features.invoice_settings) {
            return invoice.account.custom_label2 && invoice.account.custom_value2 ? { text: invoice.account.custom_label2 + ' ' + invoice.account.custom_value2 } : false;
        }
    } else if (field == '.blank') {
        return { text: ' ' };
    }

    return false;
}

NINJA.clientDetails = function(invoice) {
    var account = invoice.account;
    if (invoice.features.invoice_settings && account.invoice_fields) {
        var fields = JSON.parse(account.invoice_fields).client_fields;
    } else {
        var fields = [
            'client.client_name',
            // 'client.id_number',
            // 'client.vat_number',
            'client.contact_name',
            'client.address1',
            'client.address2',
            'client.city_state_postal',
            // 'client.country',
            //'client.email',
            'client.custom_value1',
            'client.custom_value2',
        ];
    }
    var data = [];

    for (var i = 0; i < fields.length; i++) {
        var field = fields[i];
        var value = NINJA.renderClientOrAccountField(invoice, field);
        if (value) {
            data.push(value);
        }
    }

    return NINJA.prepareDataList(data, 'clientDetails');
}

NINJA.getPrimaryColor = function(defaultColor) {
    return NINJA.primaryColor ? NINJA.primaryColor : defaultColor;
}

NINJA.getSecondaryColor = function(defaultColor) {
    return NINJA.primaryColor ? NINJA.secondaryColor : defaultColor;
}

// remove blanks and add section style to all elements
NINJA.prepareDataList = function(oldData, section) {
    var newData = [];
    for (var i = 0; i < oldData.length; i++) {
        var item = NINJA.processItem(oldData[i], section);
        if (item.text || item.stack) {
            newData.push(item);
        }
    }
    return newData;
}

NINJA.prepareDataTable = function(oldData, section) {
    var newData = [];
    for (var i = 0; i < oldData.length; i++) {
        var row = oldData[i];
        var newRow = [];
        for (var j = 0; j < row.length; j++) {
            var item = NINJA.processItem(row[j], section);
            newRow.push(item);

        }
        if (newRow.length) {
            newData.push(newRow);
        }
    }
    return newData;
}

NINJA.prepareDataPairs = function(oldData, section) {
    var newData = [];
    for (var i = 0; i < oldData.length; i++) {
        var row = oldData[i];
        var isBlank = false;
        for (var j = 0; j < row.length; j++) {
            var item = NINJA.processItem(row[j], section);
            if (!item.text) {
                isBlank = true;
            }
            if (j == 1) {
                NINJA.processItem(row[j], section + "Value");
            }
        }
        if (!isBlank) {
            newData.push(oldData[i]);
        }
    }
    return newData;
}

NINJA.processItem = function(item, section) {
    if (item.style && item.style instanceof Array) {
        item.style.push(section);
    } else {
        item.style = [section];
    }
    return item;
}


NINJA.parseMarkdownText = function(val, groupText) {
    var rules = [
        ['\\\*\\\*(\\\w.+?)\\\*\\\*', { 'bold': true }], // **value**
        ['\\\*(\\\w.+?)\\\*', { 'italics': true }], // *value*
        ['^###(.*)', { 'style': 'help' }], // ### Small/gray help
        ['^##(.*)', { 'style': 'subheader' }], // ## Header
        ['^#(.*)', { 'style': 'header' }] // # Subheader
    ];

    var parts = typeof val === 'string' ? [val] : val;
    for (var i = 0; i < rules.length; i++) {
        var rule = rules[i];
        var formatter = function(data) {
            return $.extend(data, rule[1]);
        }
        parts = NINJA.parseRegExp(parts, rule[0], formatter, true);
    }

    return parts.length > 1 ? parts : val;
}

/*
NINJA.parseMarkdownStack = function(val)
{
    if (val.length == 1) {
        var item = val[0];
        var line = item.hasOwnProperty('text') ? item.text : item;

        if (typeof line === 'string') {
            line = [line];
        }

        var regExp = '^\\\* (.*[\r\n|\n|\r]?)';
        var formatter = function(data) {
            return {"ul": [data.text]};
        }

        val = NINJA.parseRegExp(line, regExp, formatter, false);
    }

    return val;
}
*/

NINJA.parseRegExp = function(val, regExpStr, formatter, groupText) {
    var regExp = new RegExp(regExpStr, 'gm');
    var parts = [];

    for (var i = 0; i < val.length; i++) {
        var line = val[i];
        parts = parts.concat(NINJA.parseRegExpLine(line, regExp, formatter, groupText));
    }

    return parts.length > 1 ? parts : val;
}

NINJA.parseRegExpLine = function(line, regExp, formatter, groupText) {
    var parts = [];
    var lastIndex = 0;

    while (match = regExp.exec(line)) {
        if (match.index > lastIndex) {
            parts.push(line.substring(lastIndex, match.index));
        }
        var data = {};
        data.text = match[1];
        data = formatter(data);
        parts.push(data);
        lastIndex = match.index + match[0].length;
    }

    if (parts.length) {
        if (lastIndex < line.length) {
            parts.push(line.substring(lastIndex));
        }
        return parts;
    }

    return line;
}