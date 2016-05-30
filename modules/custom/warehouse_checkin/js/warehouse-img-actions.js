(function ($) {

    function getCsrfTokenForImgActions(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                //var csrfToken = data;
                callback(data);
            });
    }

    function postImage(csrfToken, node) {

        $.ajax({
            url: Drupal.url('entity/file?_format=hal_json'),
            method: 'POST',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (node) {
                console.log(node);
//                swal({
//                    title: "Session paused",
//                    text: "Your selected session to pause",
//                    type: "success",
//                    showConfirmButton: false,
//                    timer: 1000
//                });
                //window.location = Drupal.url('view-session/' + sid);
            },
            error: function(){
                alert('Failed! **');
            }

        });
    }

    function SendImageToServer(cid,tag, ref, src) {
        //        alert(11111);
        var img = {
            "_links": {
                "type": {
                    "href": Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/file/image')
                }
            },
            "filename": [
                {    "value": "Tag.jpg"   }
            ],
            "filemime": [
                {    "value": "image/jpeg"   }
            ],
            "field_container": [
                {     "target_id": cid   }
            ],
//            "filesize": [
//                {    "value": "488"   }
//            ],
            "type": [
                {    "target_id": "image"   }
            ],
            "data": [
                {    "value": src   }
            ],
            "status": [
                {     "value": "1"   }
            ],
            "field_reference": [
                {
                    "value": ref
                }
            ],
            "field_tag": [
                {
                    "value": tag
                }
            ]
        };

        getCsrfTokenForImgActions(function (csrfToken) {
            postImage(csrfToken, img);
        });
    }

    $("#warehouse-img").click(function () {
        var container = document.getElementById('warehouse-container-id').value;
        var container_nid = document.getElementById('warehouse-container-nid').value;
        var tag = 0;
        var ref = 0;
        SendImageToServer(container_nid,tag,ref,src);
    });

    //.scancontainer > img

})(jQuery);
