(function ($) {

    function getCsrfTokenForDelete(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function getFileNameDelete(callback, fid) {

        $.get(Drupal.url('filename/'+ fid +'/'+ Math.floor((Math.random() * 1000000) + 1) +'?_format=json'))
            .done(function (data) {
                document.getElementById('seq-img-'+ fid).innerHTML = data.filename;
                callback(data.filename);
            });
    }

    function patchNodeDelete(csrfToken, node, nid) {

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
                document.getElementById('msg-up').innerHTML = 'Deleted!';

                // update whole img container
                var container, inputs, index, index2;

                // Get the container element
                container = document.getElementById('sortable');

                // Find its child `input` elements
                inputs = container.getElementsByClassName("form-checkbox");

                var del = [];
                // Delete checked images.
                for (index = 0; index < inputs.length; ++index) {
                    // deal with inputs[index] element.
                    if(inputs[index].checked){
                        //document.getElementById("warpper-img-"+inputs[index].value).remove();
                        //alert(inputs[index].value);
                        del.push("warpper-img-"+inputs[index].value);
                    }
                }
                del.forEach(function(entry) {
                    //console.log(entry);
                    document.getElementById(entry).remove();
                });

                // Update image wrappers.
                container = document.getElementById('sortable');
                var inputs2 = container.getElementsByClassName("form-checkbox");
                for (index2 = 0; index2 < inputs2.length; ++index2) {
                    // deal with inputs[index] element.
                    document.getElementById('seq-'+inputs2[index2].value).innerHTML = index2 + 1;
                    //document.getElementById("warpper-img-453").remove();

                    // todo : get img file name
                    var rand = Math.floor((Math.random() * 1000000) + 1);
                    var fid =  inputs2[index2].value;
                    getFileNameDelete(function (filename) {
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

    function update_delete() {

        var container, inputs, index;
        var imgs = [];

        // Get the container element
        container = document.getElementById('sortable');

        // Find its child `input` elements
        inputs = container.getElementsByClassName("form-checkbox");
        var unchecked = 0;
        for (index = 0; index < inputs.length; ++index) {
            // deal with inputs[index] element.
            //console.log(inputs[index].value);
            if(!(inputs[index].checked)){
                imgs.push({"target_id": inputs[index].value});
            }else{
                ++unchecked;
            }
        }
        console.log(imgs);
        //var y = document.getElementsByClassName("form-checkbox");
        //alert(y[1].checked +'--'+ y[1].value);

        var Node_imgs = {
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

        console.log(Node_imgs);

        if(unchecked){
            document.getElementById('msg-up').innerHTML = 'Deleting selected images....';
            getCsrfTokenForDelete(function (csrfToken) {
                var nid = document.getElementById('edit-identifier-nid').value;
                if (nid) {
                    patchNodeDelete(csrfToken, Node_imgs, nid);
                }
            });
        }
        else{
            alert('No images selected to delete');

        }
    }

    $("#studio-delete-bt").click(function () {
        update_delete();
        //alert(1234)
    });

})(jQuery);


