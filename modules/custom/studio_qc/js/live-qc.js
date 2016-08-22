
(function($) {
    'use strict';
    //attach jquery once here to ensure it runs once on load
    var ProductList = $('[data-product="list"]');
    var ProductOpened = $('[data-product="opened"]');


    ProductList.length && $.ajax({
        dataType: "json",
        url: "screens/productsQC?_format=json",
        success: function(data) {
            $.each(data, function(i) {
                var obj = data[i];
                var group = data.group;
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
                    var li = '<li class="item padding-15" data-product-id="' + id + '"> \
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
        $("#email-content-wrapper").LoadingOverlay("show", {
            image       : "modules/custom/studio_qc/img/CC_smooth.gif",
            //fontawesome : "fa fa-spinner fa-spin"
        });

        e.stopPropagation();
        var id = $(this).attr('data-product-id');
        var product = null;
        $.ajax({
            dataType: "json",
            url: "screens/productsQC?_format=json&search="+id,
            success: function(data) {


                var product = data.list[0];
                ProductOpened.find('.product-concept').html(product.concept);
                ProductOpened.find('.product-identifier').text(product.title);
                ProductOpened.find('.product-cv').text(product.colorvariant);
                ProductOpened.find('.email-content-body').html(product.title);

                $('.no-result').hide();
                $('.actions-dropdown').toggle();
                $('.actions, .email-content-wrapper').show();
                $('.email-reply').data('wysihtml5') && $('.email-reply').wysihtml5(editorOptions);
                $(".email-content-wrapper").scrollTop(0);
                $('.menuclipper').menuclipper({
                    bufferWidth: 20
                });
                $("#email-content-wrapper").LoadingOverlay("hide", true)
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


    $(document).on("click",".approve-all",function(){
      swal({
          title: "Approve All?",
          text: "Are you sure you want to approve all images?",
          type: "success",
          showCancelButton: true,
          confirmButtonColor: "#00b9e5",
          confirmButtonText: "Approve",
          closeOnConfirm: true
      },function () {
          //update_product(1);
          //alert('approved');
          });

    });

    $(document).on("click",".reject-all",function(){
      swal({
          title: "Reject All?",
          text: "Are you sure you want to reject all images?",
          type: "warning",
          showCancelButton: true,
          confirmButtonColor: "#DD6B55",
          confirmButtonText: "Reject",
          closeOnConfirm: true
      },function () {
          //update_product(1);
          //alert('rejected');
          });

    });

    $(document).on("click",".add-note",function(){

      swal({
          title: 'Add Note',
          html:
              '<div id="warehouse-flag-form">' +
              '<textarea class="js-text-full text-full form-textarea form-control resize-vertical" data-drupal-selector="edit-field-notes-0-value" title="" data-toggle="tooltip" id="edit-field-notes-0-value" name="field_notes[0][value]" rows="4" cols="60" placeholder="Add notes about this product..." data-original-title=""></textarea>'+
              '</div>',
          showCloseButton: true,
          showCancelButton: true,
          confirmButtonText:
              'Add',
          cancelButtonText:
              'Cancel'
      },function () {
          var option = document.getElementById('flag-option').value;
          var reason = document.getElementById('flag-reason').value;

          });

    });



})(window.jQuery);
