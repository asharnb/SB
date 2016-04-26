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

        document.getElementById('msg-up').innerHTML = 'Dropping product ....';

        $.ajax({
            url: Drupal.url('node/' + nid + '?_format=hal_json'),
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (node) {
                //console.log(node);
                swal({
                    title: "Drop Product",
                    text: "This product has been marked as dropped, it will be dropped when the session is closed.",
                    type: "success",
                    showCancelButton: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "OK",
                    closeOnConfirm: true
                });
            },
            error: function(){
                alert('Failed! Try after sometime or Refresh the page.');
            }

        });

        setTimeout(function(){
            document.getElementById('msg-up').innerHTML = '';
        }, 3300);

    }

    /*
     *  draft value 1 means draft
     *  draft value 0 means undo draft
     *
     */
    function update_product(draft) {
        var Node_imgs = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/node/products')
                }
            },
            type: {
                target_id: 'products'
            },
            field_draft: {
                value:draft
            }
        };

        getCsrfTokenForProductDrop(function (csrfToken) {
            var nid = document.getElementById('edit-identifier-nid').value;
            console.log(nid);
            if (nid) {
                patchNodeDrop(csrfToken, Node_imgs, nid);
            }else{
                alert('Node product found, pls refresh the page.');
            }
        });
    }

    $(".studio-product-drop").click(function () {
        update_product(1);
    });

})(jQuery);


