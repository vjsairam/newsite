/**
 * @copyright   Copyright (C) 2015 icotheme.com. All Rights Reserved.
 */
;
'use strict';

var WidgetChooser = Class.create();
WidgetChooser.prototype = {
    initialize: function (url) {
        this.url = url;
    },
    hideEntityChooser: function (container) {
        if ($(container)) {
            $(container).addClassName('no-display').hide();
        }
    },
    displayEntityChooser: function (container) {
        var params = {};
        params.url = this.url;
        if (container && params.url) {
            container = $(container);
            params.data = {
                id: container.id,
                selected: container.up('td.value').down('input[type="text"].entities').value
            };
            this.displayChooser(params, container);
        }
    },
    displayChooser: function (params, container) {
        container = $(container);
        if (params.url && container) {
            if (container.innerHTML == '') {
                new Ajax.Request(params.url, {
                    method: 'post',
                    parameters: params.data,
                    onSuccess: function (transport) {
                        try {
                            if (transport.responseText) {
                                Element.insert(container, transport.responseText);
                                container.removeClassName('no-display').show();
                            }
                        } catch (e) {
                            alert('Error occurs during loading chooser.');
                        }
                    }
                });
            } else {
                container.removeClassName('no-display').show();
            }
        }
    },
    checkCategory: function (event) {
        var node = event.memo.node;
        var container = event.target.up('td.value');
        var elm = container.down('input[type="text"].entities');
        this.updateEntityValue(node.id, elm, node.attributes.checked);
    },
    checkProduct: function (event) {
        var input = event.memo.element,
            container = event.target.up('td.value'),
            elm = container.down('input[type="text"].entities');
        if (!isNaN(input.value)) this.updateEntityValue(input.value, elm, input.checked);
    },
    updateEntityValue: function (value, elm, isAdd) {
        var values = $(elm).value.strip();
        if (values) values = values.split(',');
        else values = [];
        if (isAdd) {
            if (-1 === values.indexOf(value)) {
                values.push(value);
                $(elm).value = values.join(',');
            }
        } else {
            if (-1 != values.indexOf(value)) {
                values.splice(values.indexOf(value), 1);
                $(elm).value = values.join(',');
            }
        }
    },
    clearEntityValue: function (container) {
        var elm = $(container).up('td.value').down('input[type="text"].entities');
        if (elm) elm.value = '';
        var hidden = $(container).down('input[type="hidden"]');
        if (hidden) hidden.value = '';
        $(container).select('input[type="checkbox"]').each(function (checkbox) {
            checkbox.checked = false;
        });
    }
};