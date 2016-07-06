(function ($) {

    function getCsrfTokenForWarehouse(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
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
    }

    /*
     *  Process product in the container.
     */
    function drop_product(product,container, container_nid){
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
            wareHouseDropProduct(csrfToken, data, 'drop-product');
        });

    }

    /*
     *  Process to warehouse rest resource.
     */
    function wareHouseDropProduct(csrfToken, node, init) {
        //$('#warehouse-checkin-product-status-wrapper').html('Checking product in the backend....');
        $.ajax({
            url: Drupal.url('warehouse/operation/' + init + '/post?_format=json'),
            method: 'POST',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (response) {
                var type = 'success';
                if(response.status  ==  false){
                    type = 'error';
                }
                swal({
                    title: response.message,
                    //text: response.status,
                    type: type, //error
                    showCancelButton: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "OK",
                    closeOnConfirm: true
                    //timer: 1500
                });

                console.log(response.status);

            },
            error: function(){
                alert('Failed! there was an issue in server, try to reload the page. Please contact administrator if it fails again.');
            }

        });
    }

    /*
     *
     */
    $("#warehouse-drop-product").click(function () {
        var product = document.getElementById('warehouse-checkin-product-scan').value;
        var container = document.getElementById('warehouse-container-id').value;
        var container_nid = document.getElementById('warehouse-container-nid').value;

        swal({
            title: "Confirm Drop",
            text: "Are you sure you want to drop this product from this container?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Drop It",
            closeOnConfirm: false
        },function () {
            drop_product(product,container,container_nid);
        });

    });

})(jQuery);
