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

        document.getElementById('msg-up').innerHTML = 'Tagging product ....';

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
                document.getElementById('msg-up').innerHTML = 'Image Tagged!';
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
     *  tag value 1 means tag
     *  tag value 0 means undo tag
     *
     */
    function update_image(tag) {

        var Node_imgs = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/file/image')
                }
            },
//            type: {
//                target_id: 'products'
//            },
            field_tag: {
                value:tag
            }
        };

        getCsrfTokenForTagImage(function (csrfToken) {
            var nid = document.getElementById('edit-identifier-nid').value;
            if (nid) {
                patchImageTag(csrfToken, Node_imgs, nid);
            }else{
                alert('Node product found, pls refresh the page.');
            }
        });
    }

    $("#studio-img-tag").click(function () {
        update_image(1);
    });

})(jQuery);


