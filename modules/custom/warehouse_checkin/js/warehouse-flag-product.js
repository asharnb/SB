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
            field_flag_option: {
                value:option
            },
            field_flag_message: {
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

    $(".studio-product-drop").click(function () {
        swal({
            title: 'Flag Product',
            type: 'info',
            html:
                'options, textarea will come here',
            showCloseButton: true,
            showCancelButton: true,
            confirmButtonText:
                '<i class="fa fa-thumbs-up"></i> Submit!',
            cancelButtonText:
                '<i class="fa fa-thumbs-down"></i>'
        },function () {
            update_product('option','message');
            });
    });

})(jQuery);
