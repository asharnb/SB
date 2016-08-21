
(function($) {
    'use strict';
    //attach jquery once here to ensure it runs once on load
    var ProductList = $('[data-product="list"]');
    var emailOpened = $('[data-product="opened"]');


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
        e.stopPropagation();
        var id = $(this).attr('data-email-id');
        var email = null;
        $.ajax({
            dataType: "json",
            url: "http://revox.io/json/emails.json",
            success: function(data) {
                $.each(data.emails, function(i) {
                    var obj = data.emails[i];
                    var list = obj.list;
                    $.each(list, function(j) {
                        if (list[j].id == 1) {
                            email = list[j];
                            return;
                        }
                    });
                    if (email != null) return;
                });
                emailOpened.find('.sender .name').text(email.from);
                emailOpened.find('.sender .datetime').text(email.datetime);
                emailOpened.find('.subject').text(email.subject);
                emailOpened.find('.email-content-body').html(email.body);
                //emailOpened.find('.thumbnail-wrapper').html(thumbnailWrapper.html()).attr('class', thumbnailClasses);
                $('.no-result').hide();
                $('.actions-dropdown').toggle();
                $('.actions, .email-content-wrapper').show();
                $('.email-reply').data('wysihtml5') && $('.email-reply').wysihtml5(editorOptions);
                $(".email-content-wrapper").scrollTop(0);
                $('.menuclipper').menuclipper({
                    bufferWidth: 20
                });
            }
        });
        $('.item').removeClass('active');
        $(this).addClass('active');
    });


    $('.secondary-sidebar').click(function(e) {
        e.stopPropagation();
    })


    $(document).ready(function() {
        $(".list-view-wrapper").scrollbar();
    });


})(window.jQuery);
