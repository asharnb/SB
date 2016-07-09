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
        $('#dd-color-variant').html(data.product.colorvariant);
        $('#dd-gender').html(data.product.gender);
        $('#dd-color').html(data.product.color);
        $('#dd-size').html(data.product.size);
        $('#dd-styleno').html(data.product.styleno);
        $('#pid').val(data.product.pid);

        var a = data.images;
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

//        swal({
//            title: "Confirm Finish",
//            text: "Are you sure you want to finish this container?",
//            type: "warning",
//            showCancelButton: true,
//            confirmButtonColor: "#DD6B55",
//            confirmButtonText: "Finish It",
//            closeOnConfirm: false
//        },function () {
//            window.location = Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'warehouse/checkout/' + container_nid);
//        });

        swal({
            title: 'jQuery HTML example',
            html: $('<input type="text">')
                .addClass('some-class')
                .text('jQuery is everywhere.') +
                $('<input type="text">')
                    .addClass('some-class1')
                    .text('jQuery is everywhere1.')
        })

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


        block += '<div class="col col-sm-12"><span id= "'+fid+'"><a class="col-sm-4 text-info" href= "/file/'+fid+'" target="_blank" ><i class="fa fa-lg fa-fw fa-search"></i></a><a class="col-sm-4 studio-img-fullshot text-info"><i class="fa fa-lg fa-fw fa-copy"></i></a><a class=" col-sm-4 studio-img-tag text-info" ><i class="fa fa-lg fa-fw fa-barcode"></i></a></span></div>';

        block += '</div>';
        block += '</div>';
        block += '</div>';
        block += '<div class="studio-img-weight"><input type="hidden" value="'+fid+'"></div>';
        block += '</div>';

        li.innerHTML = block;
        ul.appendChild(li);

//        var dcount = document.getElementById('product-img-count').innerHTML;
//        dcount++;
//        document.getElementById('product-img-count').innerHTML = dcount;
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

})(jQuery);
