(function ($) {

    function getCsrfTokenForFullShotImage(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function patchImageFullShot(csrfToken, file, fid) {

        //document.getElementById('msg-up').innerHTML = 'Image marked as fullshot ....';

        $.ajax({
            url: Drupal.url('file/' + fid + '?_format=hal_json'),
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(file),
            success: function (file) {
                //console.log(node);
                //document.getElementById('msg-up').innerHTML = 'Image Fullshot started!';
                swal({
                    title: "Full Shot",
                    text: "Image has been selected as full shot. Scan next ID",
                    type: "success",
                    showCancelButton: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "OK",
                    closeOnConfirm: true
                });
            },
            error: function(){
                swal({
                    title: "Full Shot",
                    text: "There was an error, please try again.",
                    type: "error",
                    showCancelButton: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "OK",
                    closeOnConfirm: true
                });
            }

        });

//        setTimeout(function(){
//            document.getElementById('msg-up').innerHTML = '';
//        }, 3300);

    }

    /*
     *  tag value 1 means tag
     *  tag value 0 means undo tag
     *
     */
    function update_image_fullshot(tag,fidinput) {
        var nid = fidinput;
        var Node_imgs = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/file/image')
                }
            },
//            type: {
//                target_id: 'products'
//            },
            field_full_shoot: {
                value:tag
            }
        };

        getCsrfTokenForFullShotImage(function (csrfToken) {
            if (nid) {
                patchImageFullShot(csrfToken, Node_imgs, nid);
            }else{
                alert('Node product found, pls refresh the page.');
            }
        });
    }

    $(".studio-img-fullshot").click(function () {

        var id = $(this).parents('span').attr('id');
        console.log('fullshot');
        update_image_fullshot(1,id);
    });

    $(document).on("click",".studio-img-fullshot",function(){

        var id = $(this).parents('span').attr('id');
        console.log('fullshot');
        update_image_fullshot(1,id);
    });


})(jQuery);


