
const toggleChevron = (element,chevron) => $(`${element} .chevron:first`).replaceWith(chevron)
$.widget("custom.bookAutocomplete", $.ui.autocomplete, {
    _renderItem: function (ul, item) {
        return $(item.htm)
            .attr("data-value", item.value)
            .appendTo(ul)
    },
});

$.widget("custom.bookSelect", $.ui.selectmenu, {
    _renderItem: function( ul, item ) {
        var li = $( "<li>" ),
            wrapper = $( "<div>", {title: item.element.attr( "title" )});

        if ( item.disabled ) {
            this._addClass( li, null, "ui-state-disabled" );
        }
        this._setText( wrapper, `${item.label}` );
        wrapper.addClass(`ui-dropdown-item`)
        wrapper.prepend($(checkedIcon))

        return li.append( wrapper ).appendTo( ul );
    }
});


const iniSelect = function (){
    $(".book-select").each(function (index, item) {
        $(item).bookSelect({
            classes:{
                'ui-selectmenu-menu':'ui-dropdown ui-dropdown-container',
                'ui-selectmenu-button':'ui-select-item-option',
            },
        }).bookSelect( "menuWidget" );
    });
};
const initAutocomplete = function (params) {
    let {container = null, onRequestHandler = null, options} = params

    if (!container) {
        return;
    }

    const form = $(container).parents(`form:has(${container})`);
    const session_data = {
        "_session_key": form.children('input[name=_session_key]').val(),
        "_token": form.children('input[name=_token]').val()
    };


    $(`${container} .books-autocomplete:first`).bookAutocomplete({
        ...{
            _create: function () {
                this._super();
                this.widget()
                    .menu("option", "items", "> :not(._disabled)");
            },
            minLength: 2,
            source: function (req, res) {
                if (onRequestHandler) {
                    oc.ajax(onRequestHandler, {
                        data: {...session_data, ...{term: req.term}},
                        success: (data) => res(data)
                    })
                }
            },
            classes: {
                'ui-autocomplete': 'ui-dropdown ui-dropdown-container'
            },
            open: () => $(container).addClass('ui-menu-opened'),
            close: () => $(container).removeClass('ui-menu-opened'),
            select: function (event, ui) {
                oc.ajax(ui.item.handler, {
                    data: {...session_data, ...ui},
                })
            },
        },
        ...options
    });
    $(`${container} .should-focus:first`).focus()

};
