(function ($) {

    function getCsrfTokenForProductDrop(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function patchNodeDrop(csrfToken, node, nid) {

        //document.getElementById('msg-up').innerHTML = 'Dropping product ....';

        $.ajax({
            url: Drupal.url('node/' + nid + '?_format=hal_json'),
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (node) {
                swal({
                    title: "Product Flagged",
                    text: "This product has been flagged!",
                    type: "success",
                    showCancelButton: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "OK",
                    closeOnConfirm: true,
                    //timer: 1500
                });
                var dcount = document.getElementById('liveshoot-drop').innerHTML;
                dcount++;
                document.getElementById('liveshoot-drop').innerHTML = dcount;
                document.getElementById('product-state').innerHTML = 'Dropped';
            },
            error: function(){
                alert('Failed! Try after sometime or Refresh the page.');
            }

        });


    }

    /*
     *  draft value 1 means draft
     *  draft value 0 means undo draft
     *
     */
    function update_product(option, message) {
        var Node_imgs = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/node/products')
                }
            },
            type: {
                target_id: 'products'
            },
            field_container_flag_options: {
                value:option
            },
            field_container_flag_message: {
                value:message
            }
        };

        getCsrfTokenForProductDrop(function (csrfToken) {
            var nid = document.getElementById('pid').value;
            console.log(nid);
            if (nid) {
                patchNodeDrop(csrfToken, Node_imgs, nid);
            }else{
                alert('No product found, pls refresh the page.');
            }
        });
    }

    $("#warehouse-flag-product").click(function () {
        swal({
            title: 'Flag Product',
            type: 'info',
            html:
                '<div id="warehouse-flag-form">' +
                'Option: <select id="flag-option" required="required">' +
                    '<option value="op1">Option 1</option>' +
                    '<option value="op2">Option 2</option> ' +
                    '<option value="op3">Option 3</option>' +
                '</select>'+
                '<br />' +
                'Reason: <textarea rows="2" id="flag-reason" required="required"></textarea>'+
                '</div>',
            showCloseButton: true,
            showCancelButton: true,
            confirmButtonText:
                'Flag',
            cancelButtonText:
                'Cancel'
        },function () {
            var option = document.getElementById('flag-option').value;
            var reason = document.getElementById('flag-reason').value;
                if(option && reason.length){
                    update_product(option,reason);
                }else{
                    alert('Missing input.');
                }
            });
    });

})(jQuery);
