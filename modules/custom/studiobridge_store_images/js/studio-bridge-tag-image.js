(function ($) {

    function getCsrfTokenForTagImage(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function patchImageTag(csrfToken, file, fid, tag) {

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

                if(tag){
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
                    var r = '<i class="fa fa-lg fa-barcode txt-color-white"></i>';
                    document.getElementById('seq-' + fid).className += ' tag';
                    document.getElementById('seq-'+ fid).innerHTML = r;

                    //todo
                    updateTagClasses(fid, 'tag');
                }else{
                    swal({
                        title: "Undo Tag Shot",
                        text: "Tag shot has been cancelled",
                        type: "success",
                        showConfirmButton: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "OK",
                        closeOnConfirm: true,
                        timer:1500
                    });
                    updateTagClasses(fid, 'untag');
                    updateSequence();

                    $('#seq-'+fid).removeClass('tag');
                }

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
                patchImageTag(csrfToken, img, fid, tag);
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


    $(document).on("click",".studio-img-un-tag",function(){
        var id = $(this).parents('span').attr('id');
        console.log('fullshot'+id);
        update_image(0,id);
    });

    function updateTagClasses(fid, tag_status){

        if(tag_status == 'tag'){
            var tagItem = document.getElementById(fid);
            var s = '#'+fid+ ' .studio-img-tag';
            // alert(s);
            $(s).addClass('studio-img-un-tag');
            $(s).removeClass('studio-img-tag');
            $('.studio-img-tag').addClass('studio-img-tag-nutral');
            $('.studio-img-tag').removeClass('studio-img-tag');
        }else{

            var tagItem = document.getElementById(fid);
            var s = '#'+fid+ ' .studio-img-un-tag';
            // alert(s);
            $(s).addClass('studio-img-tag');
            $(s).removeClass('studio-img-un-tag');
            $('.studio-img-tag-nutral').addClass('studio-img-tag');
            $('.studio-img-tag-nutral').removeClass('studio-img-tag-nutral');

        }

    }

    function updateSequence(){
        // Update image wrappers.
        container = document.getElementById('imagecontainer');
        var inputs2 = container.getElementsByClassName("form-checkbox");
        for (index2 = 0; index2 < inputs2.length; ++index2) {
            // deal with inputs[index] element.
            document.getElementById('seq-'+inputs2[index2].value).innerHTML = index2 + 1;
        }
    }

})(jQuery);
