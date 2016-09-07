/**
 * Created by Krishna on 29/8/16.
 */

(function ($) {

    // function to get csrf token
    function getCsrfToken(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                callback(data);
            });
    }


    /*
     *  Send approve/reject request to the server.
     */
    function qcOperation(csrfToken, node, init) {
        $.ajax({
            url: Drupal.url('qc/operation/' + init + '/post?_format=json'),
            method: 'POST',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (response) {
                swal({
                    title: "Response",
                    text: response.message,
                    type: response.type,
                    showConfirmButton: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "OK",
                    closeOnConfirm: true,
                    timer:3000
                });
            },
            error: function(){
                alert('Failed!');
            }

        });
    }

    /*
     *  Process approve or reject request before sending it to server.
     */
    function approveOrRejectRequestProcess(product,session, imgs, state){
        var data = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/node/test')
                }
            },
            "body": [
                {
                    "value": {
                        "pid": product,
                        "sid": session,
                        "images": imgs

                    },
                    "format": null,
                    "summary": null
                }
            ],
            "type":[{"target_id":"test"}]
        };

        getCsrfToken(function (csrfToken) {
            qcOperation(csrfToken, data, state);
        });

    }

    // click event for reject image.
    // todo change selector based on what is there in template.
    $(document).on("click",".qc-img-reject",function(){
        // todo get following information from specific tags saved or updated from ajax.
        var product = document.getElementById('selected-pid').value;
        var session = '';
        var imgs = $(this).attr('data-img-id');
        var state = 'reject_img';

        swal({
            title: "Confirm Reject",
            text: "Are you sure you want to reject this image from this product ?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Reject",
            closeOnConfirm: true
        },function () {
            approveOrRejectRequestProcess(product,session, imgs, state)
        });
    });



    $(document).on("click",".qc-img-approve",function(){
        var product = document.getElementById('selected-pid').value;
        var session = '';
        var imgs = $(this).attr('data-img-id');
        var state = 'approve_img';

        swal({
            title: "Confirm Approve",
            text: "Are you sure you want to approve this image to this product ?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Approve",
            closeOnConfirm: true
        },function () {
            approveOrRejectRequestProcess(product,session, imgs, state)
        });

    });


})(jQuery);
