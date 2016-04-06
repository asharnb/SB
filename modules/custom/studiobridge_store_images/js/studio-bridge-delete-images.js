(function ($) {

    function getCsrfTokenForDelete(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function getFileName(callback, fid) {

        $.get(Drupal.url('filename/'+ fid +'/'+ Math.floor((Math.random() * 1000000) + 1) +'?_format=json'))
            .done(function (data) {
                document.getElementById('seq-img-'+ fid).innerHTML = data.filename;
                callback(data.filename);
            });
    }

    function patchNode(csrfToken, node, nid) {

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
                document.getElementById('msg-up').innerHTML = 'Updated!';

                // update whole img container
                var container, inputs, index;

                // Get the container element
                container = document.getElementById('sortable');

                // Find its child `input` elements
                inputs = container.getElementsByTagName('input');
                for (index = 0; index < inputs.length; ++index) {
                    // deal with inputs[index] element.
                    document.getElementById('seq-'+inputs[index].value).innerHTML = index + 1;
                    // todo : get img file name
                    var rand = Math.floor((Math.random() * 1000000) + 1);
                    var fid =  inputs[index].value;
                    getFileName(function (filename) {
                        //console.log(csrfToken);
                        //document.getElementById('seq-img-'+ fid).innerHTML = filename;
                    }, fid);

                }

                setTimeout(function(){
                    document.getElementById('msg-up').innerHTML = '';
                }, 3300);
            },
            error: function(){
                alert('Failed!');
            }

        });
    }

    function update_w() {
        document.getElementById('msg-up').innerHTML = 'Updating....';

        var container, inputs, index;
        var imgs = [];

        // Get the container element
        container = document.getElementById('sortable');

        // Find its child `input` elements
        inputs = container.getElementsByTagName('input');
        for (index = 0; index < inputs.length; ++index) {
            // deal with inputs[index] element.
            //console.log(inputs[index].value);
            imgs.push({"target_id": inputs[index].value});
        }

        var Node1 = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/node/products')
                }
            },
            type: {
                target_id: 'products'
            },
            field_images: imgs
        };

        console.log(Node1);

        if(imgs.length){
            getCsrfTokenForDelete(function (csrfToken) {
                var nid = document.getElementById('edit-identifier-nid').value;
                if (nid) {
                    patchNode(csrfToken, Node1, nid);
                }
            });
        }
        else{
            alert('No images found');

        }
    }

    $("#studio-resequence-bt").click(function () {
        update_w();
    });

})(jQuery);


