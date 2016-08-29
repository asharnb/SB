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
                //updateProductInformationBlock(response);
                alert('success');
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

    // click event for approve image.
    // todo change selector based on what is there in template.
    $("#qc-approve-img").click(function () {

        // todo get following information from specific tags saved or updated from ajax.
        var product = '';
        var session = '';
        var imgs = '';
        var state = 'approve_img';

        swal({
            title: "Confirm Approve",
            text: "Are you sure you want to approve this images in this product ?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Approve",
            closeOnConfirm: false
        },function () {
            approveOrRejectRequestProcess(product,session, imgs, state)
        });
    });

    // click event for reject image.
    // todo change selector based on what is there in template.
    $("#qc-reject-img").click(function () {

        // todo get following information from specific tags saved or updated from ajax.
        var product = '';
        var session = '';
        var imgs = '';
        var state = 'reject_img';

        swal({
            title: "Confirm Reject",
            text: "Are you sure you want to reject this images in this product ?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Reject",
            closeOnConfirm: false
        },function () {
            approveOrRejectRequestProcess(product,session, imgs, state)
        });
    });



})(jQuery);
