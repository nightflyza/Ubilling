
$.widget("custom.catcomplete", $.ui.autocomplete, {
    _create: function () {
        this._super();
        this.widget().menu("option", "items", "> :not(.ui-autocomplete-category)");
    },
    _renderMenu: function (ul, items) {
        var that = this,
                currentCategory = "";
        $.each(items, function (index, item) {
            var li;
            if (item.category != currentCategory) {
                ul.append("<li class=\'ui-autocomplete-category\'>" + item.category + "</li>");
                currentCategory = item.category;
            }
            li = that._renderItemData(ul, item);
            if (item.category) {
                li.attr("aria-label", item.category + " : " + item.label);
            }
        });
    }
});

$(function () {
    var cache = {};
    $("#globalsearch").catcomplete({
        minLength: 2,
        focus: function (event, ui) {
            $("#globalsearch_type").val(ui.item.type);
        },
        source: function (request, response) {
            var term = request.term;
            if (term in cache) {
                response(cache[ term ]);
                return;
            }

            $.getJSON("?module=usersearch&glosearch=true", request, function (data, status, xhr) {
                cache[ term ] = data;
                response(data);
            });
        }
    });

});
