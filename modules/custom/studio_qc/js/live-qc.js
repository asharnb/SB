
(function($) {
    'use strict';
    var ProductList = $('[data-product="list"]');
    var ProductOpened = $('[data-product="opened"]');


    ProductList.length && $.ajax({
        dataType: "json",
        url: "screens/productsQC?_format=json",
        success: function(data) {
            $.each(data, function(i) {
                var obj = data[i];
                var group = obj.group;
                var list = data.list;
                var listViewGroupCont = $('<div/>', {
                    "class": "list-view-group-container"
                });
                listViewGroupCont.append('<div class="list-view-group-header"><span>' + group + '</span></div>');
                var ul = $('<ul/>', {
                    "class": "no-padding"
                });
                $.each(list, function(j) {
                    var $this = list[j];
                    var id = $this.id;
                    var to = $this.concept;
                    var title = $this.title;
                    var session = $this.id;
                    var totalimages = $this.totalimages;
                    var li = '<li class="item padding-15" data-email-id="' + id + '"> \
                                <div class="checkbox  no-margin p-l-10"> \
                                    <input type="checkbox" value="1" id="emailcheckbox-' + i + "-" + j + '"> \
                                    <label for="emailcheckbox-' + i + "-" + j + '"></label> \
                                </div> \
                                <div class="inline m-l-15"> \
                                    <p class="recipients no-margin hint-text small">' + to + '</p> \
                                    <p class="subject no-margin">' + title + '</p> \
                                    <p class="body no-margin"> \
                                     Session: ' + session + ' \
                                    </p> \
                                </div> \
                                <div class="datetime">' + totalimages + '</div> \
                                <div class="clearfix"></div> \
                            </li>';
                    ul.append(li);
                });
                listViewGroupCont.append(ul);
                ProductList.append(listViewGroupCont);
            });
              ProductList.ioslist();
        }
    });
    $('body').on('click', '.item .checkbox', function(e) {
        e.stopPropagation();
    });
    $('body').on('click', '.item', function(e) {

        //getproductbynid service to fetch data and images

        e.stopPropagation();

        $('.item').removeClass('active');
        $(this).addClass('active');
    });


    $('.secondary-sidebar').click(function(e) {
        e.stopPropagation();
    })

}(jQuery));
