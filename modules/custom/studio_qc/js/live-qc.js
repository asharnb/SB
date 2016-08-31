
(function($) {
    'use strict';
    //attach jquery once here to ensure it runs once on load


    $('.full-height-scroll').slimscroll({
        height: '100%'
    })

    var ProductList = $('[data-product="list"]');
    var ProductOpened = $('[data-product="opened"]');

    //$("#content").LoadingOverlay("show");
    ProductList.length && $.ajax({
        dataType: "json",
        url: "screens/productsQC?_format=json",
        success: function(data) {
            var groupedlist = data[0].list;
            $.each(groupedlist, function(i) {

                var group = groupedlist[i];
                var list = groupedlist[i];
                var listViewGroupCont = $('<div/>', {
                    "class": "list-view-group-container"
                });
                listViewGroupCont.append('<div class="list-view-group-header"><span>' + i + '</span></div>');
                var ul = $('<ul/>', {
                    "class": "no-padding"
                });
                $.each(list, function(j) {
                    var $this = list[j];
                    var id = $this.id;

                    var concept = $this.concept_img_url;
                    if(concept==undefined){
                      var concept = 'Unmapped';
                    }

                    var title = $this.title;
                    var session = $this.id;
                    var totalimages = $this.totalimages;
                    var sessioninfo = $this.sessions;
                    var cv = '';
                    if($this.colorvariant!==undefined) { cv = $this.colorvariant };

                    // if(sessioninfo!==null || sessioninfo!==undefined)
                    // $.each(sessioninfo, function(k) {
                    //   var photographer = sessioninfo[k];
                    //   var photographerpath = '/themes/studiobridge/users/'+ photographer + '.jpg';
                    // });



                    //{{session_users.photographer|lower|replace({".": "_" , " " : "_"})}}.jpg
                    var li = '<li class="item padding-15" data-session-id="' + i + '" data-product-id="' + id + '"> \
                                <div class="checkbox  no-margin p-l-10"> \
                                    <input type="checkbox" value="1" id="emailcheckbox-' + i + "-" + j + '"> \
                                    <label for="emailcheckbox-' + i + "-" + j + '"></label> \
                                </div> \
                                <div class="inline m-l-15"> \
                                    <p class="recipients no-margin hint-text small">' + concept + '</p> \
                                    <p class="subject no-margin">' + title + '</p> \
                                    <p class="subject no-margin">' + cv + '</p> \
                                </div> \
                                <h4 class="text-navy datetime bold">' + totalimages + '</h1> \
                                <div class="clearfix"></div> \
                            </li>';
                    ul.append(li);
                });
                listViewGroupCont.append(ul);
                ProductList.append(listViewGroupCont);
            });
            ProductList.ioslist();
            $("#content").LoadingOverlay("hide", true)
        },
        // error: function(i) {
        //   console.log(i);
        //
        //
        // },
    });
    $('body').on('click', '.item .checkbox', function(e) {
        e.stopPropagation();
    });
    $('body').on('click', '.item', function(e) {
        $(".split-details").LoadingOverlay("show");

        e.stopPropagation();
        var id = $(this).attr('data-product-id');
        var session = $(this).attr('data-session-id');
        var product = null;
        ProductOpened.find('#imagecontainer').empty();
        ProductOpened.find('.product').empty();

        $.ajax({
            dataType: "json",
            url: "screens/productsQC?_format=json&search="+id,
            //get product
            //check if new or reshoot
            success: function(data) {


                var allproduct = data[0].list;


                $.each(allproduct, function(j) {
                  if (j == session){
                      var product = allproduct[j][0];
                      //start displaying product information
                      ProductOpened.find('.product-concept').html(product.concept_img_url);
                      ProductOpened.find('.product-identifier').text(product.title);
                      ProductOpened.find('.product-cv').text(product.field_color_variant);

                      //append all product images
                      if(product.images!==false){
                        $.each(product.images, function(j) {
                          console.log(product.images[j]);
                          append_img(product.images[j],j)
                        });
                        $(".fancybox-thumb").fancybox({
                          prevEffect	: 'none',
                          nextEffect	: 'none',
                          type : 'image',

                          helpers	: {
                            title	: {
                              type: 'outside'
                            },
                            thumbs	: {
                              width	: 50,
                              height	: 50
                            }
                          }
                        });
                      }

                  }

                })


                $('.no-result').hide();
                $('.actions, .email-content-wrapper').show();
                $(".email-content-wrapper").scrollTop(0);
                $('.menuclipper').menuclipper({
                    bufferWidth: 20
                });
                $(".split-details").LoadingOverlay("hide", true);
            }
        });
        $('.item').removeClass('active');
        $(this).addClass('active');




    });


    $('.secondary-sidebar').click(function(e) {
        e.stopPropagation();
    })


    $(document).ready(function() {
        $(".list-view-wrapper").scrollbar({ignoreOverlay: false});
        //$(".email-content-wrapper").scrollbar({ignoreOverlay: false});

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


    function append_img(img,fid) {
        if ($('#warpper-img-'+ fid).length > 0) {
            return;
        }

        var container, inputs, index;

        // Get the container element
        container = document.getElementById('imagecontainer');

        // Find its child `input` elements
        //inputs = container.getElementsByTagName('input');
        inputs = container.getElementsByClassName("form-checkbox");
        var seq = inputs.length + 1;



        var ul = document.getElementById('imagecontainer');
        var li = document.createElement("div");
        //li.appendChild(document.createTextNode(100));
        li.setAttribute("class", "bulkviewfiles imagefile ui-sortable-handle"); // added line
        li.setAttribute('id','warpper-img-' + fid);


        var block = '';
        if(img.tag==1){
          block += '<div class="ribbon" id="ribboncontainer"><span class="for-tag tag" id="seq-' + fid +'" name="' + seq +'"><i class="fa fa-lg fa-barcode txt-color-white"></i></span></div>';
        } else{
          block += '<div class="ribbon" id="ribboncontainer"><span class="for-tag" id="seq-' + fid +'" name="' + seq +'">' + seq +'</span></div>';
        }

        block +=  '<div class="scancontainer"><div class="hovereffect">';
        block +=  '<img src="'+ img.uri +'" class="scanpicture" data-imageid="'+ fid +'">';
        block += '<div class="overlay"><input type="checkbox" class="form-checkbox" id="del-img-'+ fid +'" hidden value="'+ fid +'"><a class="info select-delete" data-id="'+ fid +'" data-click="no">Select image</a></div>';

        block +=  '</div>';

        block +=  '<div class="file-name">';

        block +=  '<div id="tag-seq-img-'+fid+'" type="hidden"></div>';

        block += '<div class="row">';


        block += '<div class="col col-sm-12"><span id= "'+fid+'">\
        <a rel="fancybox-thumb" href="' + img.uri + '" class="fancybox-thumb col-sm-4 text-info"><i class="fa fa-lg fa-fw fa-search"></i></a> \
        <a class=" col-sm-4 text-info" ><i class="fa fa-lg fa-fw fa-check text-success"></i></a>\
        <a class=" col-sm-4 text-info" ><i class="fa fa-lg fa-fw fa-times text-danger"></i></a>\
        </span></div>';

        block += '</div>';
        block += '</div>';
        block += '</div>';
        block += '<div class="studio-img-weight"><input type="hidden" value="'+fid+'"></div>';
        block += '</div>';

        li.innerHTML = block;
        ul.appendChild(li);


    }




})(window.jQuery);
