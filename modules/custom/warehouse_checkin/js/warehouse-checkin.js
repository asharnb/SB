(function ($) {

    function getCsrfTokenForWarehouse(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    // When user hits enter.
    $('#warehouse-checkin-product-scan').keypress(function (e) {
        var key = e.which;
        if(key == 13)  // the enter key code
        {
            $('#warehouse-checkin-product-status-wrapper').html('Processing product...');
            var container = document.getElementById('warehouse-container-id').value;
            var container_nid = document.getElementById('warehouse-container-nid').value;
            //check for scanning same product
            if(this.value.length){
                process_product(this.value,container, container_nid, false);
            }else{
                $('#warehouse-checkin-product-status-wrapper').html('Please enter product value.');
            }
        }
    });

    /*
     *  Process to warehouse rest resource.
     */
    function wareHouseScanProduct(csrfToken, node, init, onload) {
        $('#warehouse-checkin-product-status-wrapper').html('Checking server ...');
        $.ajax({
            url: Drupal.url('warehouse/operation/' + init + '/post?_format=json'),
            method: 'POST',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (response) {
                updateProductInformationBlock(response);
                $('#warehouse-checkin-product-status-wrapper').html('Processed successfully.');
                console.log(response);

                if(!onload){
                    if(response.duplicate){
                        swal({
                            title: 'Duplicate Product',
                            text: 'Duplicate product, already found in another container.',
                            type: 'warning', //error
                            showCancelButton: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "OK",
                            closeOnConfirm: true,
                            timer: 5000
                        });
                    }
                    if(response.already_scanned){
                        swal({
                            title: 'Product already scanned',
                            text: 'This product already scanned in this container.',
                            type: 'warning', //error
                            showCancelButton: false,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: "OK",
                            closeOnConfirm: true,
                            timer: 5000
                        });
                    }

                    setTimeout(function(){
                        $('#warehouse-checkin-product-status-wrapper').html('&nbsp');
                    }, 3300);
                }

            },
            error: function(){
                alert('Failed! **');
                setTimeout(function(){
                    $('#warehouse-checkin-product-status-wrapper').html('&nbsp');
                }, 3300);
            }

        });
    }


    /*
     *  Process product in the container.
     */
    function process_product(product,container, container_nid, onload){
        var data = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/node/test')
                }
            },
            "body": [
                {
                    "value": {
                        "product": product,
                        "container": container,
                        "container_nid": container_nid
                    },
                    "format": null,
                    "summary": null
                }
            ],
            "type":[{"target_id":"test"}]
        };

        getCsrfTokenForWarehouse(function (csrfToken) {
            wareHouseScanProduct(csrfToken, data, 'import', onload);
        });

    }

    function onLoadProductBlock(){
        var container = document.getElementById('warehouse-container-id').value;
        var container_nid = document.getElementById('warehouse-container-nid').value;
    }


    function updateProductInformationBlock(data){
        //document.getElementById('dd-identifier').innerHTML = data.product.concept;
        $('#dd-identifier').html(data.product.identifier);
        $('#dd-description').html(data.product.description);
        $('#dd-colorvariant').html(data.product.colorvariant);
        $('#dd-gender').html(data.product.gender);
        $('#dd-color').html(data.product.color);
        $('#dd-size').html(data.product.size);
        $('#dd-styleno').html(data.product.styleno);
        $('#pid').val(data.product.pid);

        var a = data.images;
        document.getElementById('filmroll').innerHTML = '';
        var tag = [];
        //console.log(a);
        if(a){
            for(var i in a){
                if ($('#warpper-img-'+ i).length == 0) {
                    // div not found,
                    attachImages(a[i],i);
                }
            }
        }
    }


    $("#container-finish").click(function () {
        var container_nid = document.getElementById('warehouse-container-nid').value;

        swal({
            title: "Confirm Finish",
            text: "Are you sure you want to finish this container?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Finish It",
            closeOnConfirm: false
        },function () {
            window.location = Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'warehouse/checkout/' + container_nid);
        });

//        swal({
//            title: 'jQuery HTML example',
//            html: $('<input type="text">')
//                .addClass('some-class')
//                .text('jQuery is everywhere.') +
//                $('<input type="text">')
//                    .addClass('some-class1')
//                    .text('jQuery is everywhere1.')
//        })

    });

    function attachImages(img,fid) {
        if ($('#warpper-img-'+ fid).length > 0) {
            return;
        }

        var container, inputs, index;

        // Get the container element
        container = document.getElementById('filmroll');

        // Find its child `input` elements
        //inputs = container.getElementsByTagName('input');
        inputs = container.getElementsByClassName("form-checkbox");
        var seq = inputs.length + 1;



        var ul = document.getElementById('filmroll');
        var li = document.createElement("div");
        //li.appendChild(document.createTextNode(100));
        li.setAttribute("class", "bulkviewfiles imagefile ui-sortable-handle"); // added line
        li.setAttribute('id','warpper-img-' + fid);


        var block = '';

        if(img.tag==1){
            block += '<div class="ribbon" id="ribboncontainer"><span data-id="'+fid+'" class="for-tag tag" id="seq-' + fid +'" name="' + seq +'"><i class="fa fa-lg fa-barcode txt-color-white"></i></span></div>';
        } else{
            block += '<div class="ribbon" id="ribboncontainer"><span class="for-tag" id="seq-' + fid +'" name="' + seq +'">' + seq +'</span></div>';
        }

        block +=  '<div class="scancontainer"><div class="hovereffect">';
        block += '<div class="hidden" id="src-'+fid+'" data-src="'+img.uri+'"></div>'
        block +=  '<img src="'+ img.uri +'" class="scanpicture" data-imageid="'+ fid +'" >';
        block += '<div class="overlay"><input type="checkbox" class="form-checkbox" id="del-img-'+ fid +'" hidden value="'+ fid +'"><a class="info select-delete" data-id="'+ fid +'" data-click="no">Select image</a></div>';

        block +=  '</div>';

        block +=  '<div class="file-name">';

        block +=  '<div id="tag-seq-img-'+fid+'" type="hidden"></div>';

        block += '<div class="row">';

        block += '<div class="col col-sm-12"><span id= "warehouse-tags-container-'+fid+'" data-value="'+fid+'">';
        block += '<a class="col-sm-4 text-info " id= "warehouse-fullview-'+fid+'"><i class="fa fa-lg fa-fw fa-search"></i></a>';
        block += '<a class="col-sm-4 text-info warehouse-tag" id= "warehouse-tag-'+fid+'"><i class="fa fa-lg fa-fw fa-barcode"></i></a>';
        block += '<a class="col-sm-4 text-info" id= "warehouse-fullshot-'+fid+'"><i class="fa fa-lg fa-fw fa-trash"></i></a>';
        block += '</span></div>';

        block += '</div>';
        block += '</div>';
        block += '</div>';
        block += '<div class="studio-img-weight"><input type="hidden" value="'+fid+'"></div>';
        block += '</div>';

        li.innerHTML = block;
        ul.appendChild(li);

        var tag = document.getElementById('warehouse-tag-'+fid);
        tag.addEventListener("click", function(){
            // tag image todo:
            update_image(1,fid, 0);

        }, false);


    }

    $( document ).ready(function() {
        var w = $('#warehouse-checkin-product-scan');
        var wx = w.val();
        var container = document.getElementById('warehouse-container-id').value;
        var container_nid = document.getElementById('warehouse-container-nid').value;
        //check for scanning same product
        if(wx.length){
            process_product(wx,container, container_nid, true);
        }
    });


    /*
     *  tag value 1 means tag
     *  tag value 0 means undo tag
     *
     */
    function update_image(tag,fidinput, ref) {

        var tags_lenght = $('.tag').length;
        var is_tagged = $('#warpper-img-'+ fidinput +' .tag').length;

        if(is_tagged >=1){
            swal({
                title: "Tag Image",
                text: "This image already tagged!, Delete it to tag a new image.",
                type: "error",
                showCancelButton: false,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "OK",
                closeOnConfirm: true
            });
            return;
        }

        if(tags_lenght >=1){

            swal({
                title: "Tag Image",
                text: "Tag image already found, please delete them to tag a new image.",
                type: "error",
                showCancelButton: false,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "OK",
                closeOnConfirm: true
            });
            return;
        }


        // todo get file name here
        var fid = fidinput;

        // var identifier = document.getElementById('edit-identifier-hidden').value;


        var img = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/file/image')
                }
            },
            field_tag: {
                value: tag
            },
            field_reference: [
                {
                    "value": ref
                }
            ],
            filename: {

                value: "Tag.jpg"

            }
        };

        getCsrfTokenForWarehouse(function (csrfToken) {
            if (fid) {
                patchImageTag(csrfToken, img, fid, tag);
            }else{
                alert('No product found, pls refresh the page.');
            }
        });
    }


    function patchImageTag(csrfToken, file, fid, tag) {

        //document.getElementById('msg-up').innerHTML = 'Tagging product ....';

        $.ajax({
            url: Drupal.url('file/' + fid + '?_format=hal_json'),
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(file),
            success: function (file) {

                if(tag){
                    //console.log(node);
                    //document.getElementById('msg-up').innerHTML = 'Image Tagged!';
                    swal({
                        title: "Tag Shot",
                        text: "Tag shot has been selected",
                        type: "success",
                        showConfirmButton: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "OK",
                        closeOnConfirm: true,
                        timer:1500
                    });
                    var r = '<i class="fa fa-lg fa-barcode txt-color-white"></i>';
                    document.getElementById('seq-' + fid).className += ' tag';
                    document.getElementById('seq-'+ fid).innerHTML = r;

                    //todo
                    //updateTagClasses(fid, 'tag');
                }else{
                    swal({
                        title: "Undo Tag Shot",
                        text: "Tag shot has been cancelled",
                        type: "success",
                        showConfirmButton: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "OK",
                        closeOnConfirm: true,
                        timer:1500
                    });
                    //updateTagClasses(fid, 'untag');
                    //updateSequence();

                    $('#seq-'+fid).removeClass('tag');
                }

            },
            error: function(){
                swal({
                    title: "Tag Shot",
                    text: "There was an error, please try again.",
                    type: "error",
                    showCancelButton: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "OK",
                    closeOnConfirm: true
                });
            }

        });


    }



})(jQuery);
