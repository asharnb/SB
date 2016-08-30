/**
 * Created by Krishna on 29/8/16.
 */

(function ($) {
    'use strict';
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
                console.log(response);
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

    // click event for approve all.
    // todo change selector based on what is there in template.
    $(".approve-all").click(function () {

        // todo get following information from specific tags saved or updated from ajax.
        var product = '';
        var session = '';
        var imgs = [1,2];
        var state = 'approve_all';
        var pid = document.getElementById('selected-pid').value;

        swal({
            title: "Approve All?",
            text: "Are you sure you want to approve all images?",
            type: "success",
            showCancelButton: true,
            confirmButtonColor: "#00b9e5",
            confirmButtonText: "Approve",
            closeOnConfirm: true
        },function () {
            approveOrRejectRequestProcess(pid,session, imgs, state)
        });
    });

    // click event for reject all.
    // todo change selector based on what is there in template.
    $(".reject-all").click(function () {

        // todo get following information from specific tags saved or updated from ajax.
        var product = '';
        var session = '';
        var imgs = [1,2];
        var state = 'reject_all';

        swal({
            title: "Reject All?",
            text: "Are you sure you want to reject all images?",
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Reject",
            closeOnConfirm: true
        },function () {
            approveOrRejectRequestProcess(product,session, imgs, state)
        });
    });



})(jQuery);
