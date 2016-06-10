(function ($) {

    function getCsrfTokenForTagImage(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function patchImageTag(csrfToken, file, fid) {

        //document.getElementById('msg-up').innerHTML = 'Tagging product ....';

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
                //document.getElementById('msg-up').innerHTML = 'Image Tagged!';
                swal({
                    title: "Tag Shot",
                    text: "Tag shot has been selected",
                    type: "success",
                    showConfirmButton: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "OK",
                    closeOnConfirm: true,
                    timer:1500
                });


            },
            error: function(){
                swal({
                    title: "Tag Shot",
                    text: "There was an error, please try again.",
                    type: "error",
                    showCancelButton: false,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "OK",
                    closeOnConfirm: true
                });
            }

        });

        setTimeout(function(){
            document.getElementById('msg-up').innerHTML = '';
        }, 3300);

    }

    /*
     *  tag value 1 means tag
     *  tag value 0 means undo tag
     *
     */
    function update_image(tag,fidinput) {
        // todo get file name here
        var fid = fidinput;

        // var identifier = document.getElementById('edit-identifier-hidden').value;


        var img = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/file/image')
                }
            },
            field_tag: {
                value: tag
            },
            filename: {

                value: "Tag.jpg"

            }
        };

        getCsrfTokenForTagImage(function (csrfToken) {
            if (fid) {
                patchImageTag(csrfToken, img, fid);
            }else{
                alert('Node product found, pls refresh the page.');
            }
        });
    }

$(document).on("click",".studio-img-tag",function(){
    // $(".studio-img-tag").click(function () {

        var id = $(this).parents('span').attr('id');
        console.log('fullshot'+id);
        update_image(1,id);
    });

})(jQuery);
