(function ($) {

    function getCsrfTokenForDelete(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function patchNodeDeleteX(csrfToken, node, nid) {

        $.ajax({
            url: Drupal.url('studio/product/test/post?_format=json'),
            method: 'POST',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (node) {
                //console.log(node);
                swal({
                    title: "Images Deleted",
                    text: "Your selected images have been deleted",
                    type: "success",
                    showConfirmButton: false,
                    timer: 1000
                });

            },
            error: function(){
                alert('Failed!');
            }

        });
    }

    function update_deleteX() {
        alert(11111);
        var Node_imgs = {
            "title": [
                {
                    "value": "Krishna kanth *123"
                }
            ],
            "body": [
                {
                    "value": "Example node title"
                }
            ],
            "type": [
                {
                    "target_id": "test"
                }
            ]
        };

        getCsrfTokenForDelete(function (csrfToken) {
            patchNodeDeleteX(csrfToken, Node_imgs, 1);
        });

    }

//    $("#studio-delete-bt").click(function () {
//        update_deleteX();
//        //alert(1234)
//    });

    $(window).bind('beforeunload', function() {
        if(/Firefox[\/\s](\d+)/.test(navigator.userAgent) && new Number(RegExp.$1) >= 4) {
            if(confirm("Are you Sure do you want to leave?")) {
                alert('hellow inside');
                update_deleteX();
                history.go();
            } else {
                alert('hellow insidessss');
                window.setTimeout(function() {
                    window.stop();
                }, 1);
            }
        } else {
            alert('hellow insideddddddd');
            return "Are you Sure do you want to leave?";
        }
    });



})(jQuery);
