(function ($) {

    function getCsrfTokenForWarehouse(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    $('#warehouse-checkin-product-scan').keypress(function (e) {
        var key = e.which;
        if(key == 13)  // the enter key code
        {
            document.getElementById('warehouse-checkin-product-status-wrapper').innerHTML = 'Processing product...';
            //check for scanning same product
            if(this.value.length){
                process_product(this.value);
            }else{
                document.getElementById('warehouse-checkin-product-status-wrapper').innerHTML = 'Please enter product value.';
            }
        }
    });

    function wareHouseScanProduct(csrfToken, node, init) {
        document.getElementById('warehouse-checkin-product-status-wrapper').innerHTML = 'Checking server ...';
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
                document.getElementById('warehouse-checkin-product-status-wrapper').innerHTML = 'Processed successfully.';
                console.log(response);
            },
            error: function(){
                alert('Failed! **');
            }

        });
    }


    /*

     */
    function process_product(product){

        var data = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/node/test')
                }
            },
            "body": [
                {
                    "value": {
                        "product": product
                        //"sid": 2
                    },
                    "format": null,
                    "summary": null
                }
            ],
            "type":[{"target_id":"test"}]
        };

        getCsrfTokenForWarehouse(function (csrfToken) {
            wareHouseScanProduct(csrfToken, data, 'import');
        });

    }

    function updateProductInformationBlock(data){
        document.getElementById('dd-identifier').innerHTML = 1234;
    }

})(jQuery);