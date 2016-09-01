(function ($) {

    function getCsrfToken(callback) {
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
                //document.getElementById('msg-up').innerHTML = 'Updated!';
                swal({
                    title: "Resequence Successful",
                    text: "Images have have been resequenced",
                    type: "success",
                    showConfirmButton: false,
                    closeOnConfirm: true,
                    timer: 1000
                });
                $("#sequence-check").removeClass('fa-square-o');
                $("#sequence-check").addClass('fa-square-check-o');
                $("#sequence-check").addClass('text-success');
                // update whole img container
                var container, inputs, index;
                var dup_holder = [];

                // Get the container element
                container = document.getElementById('imagecontainer');

                // Find its child `input` elements
                inputs = container.getElementsByTagName('input');

                var inc = 1;
                for (index = 0; index < inputs.length; ++index) {
                    // deal with inputs[index] element.
                    if(inputs[index].type == 'hidden'){
                        //alert(inc + '---' + inputs[index].value);

                        if(dup_holder.indexOf(inputs[index].value) == '-1'){
                            document.getElementById('seq-'+inputs[index].value).innerHTML = inc;
                            ++inc;

                            // todo : get img file name
                            var rand = Math.floor((Math.random() * 1000000) + 1);
                            var fid =  inputs[index].value;
                            getFileName(function (filename) {
                                //console.log(csrfToken);
                                //document.getElementById('seq-img-'+ fid).innerHTML = filename;
                            }, fid);
                        }
                        dup_holder.push(inputs[index].value);
                    }
                }

            },
            error: function(){
                alert('Failed!');
            }

        });
    }

    function update_w() {
        //document.getElementById('msg-up').innerHTML = 'Updating....';

        var container, inputs, index;
        var imgs = [];
        var dup_holder = [];

        // Get the container element
        container = document.getElementById('imagecontainer');

        // Find its child `input` elements
        inputs = container.getElementsByTagName('input');
        for (index = 0; index < inputs.length; ++index) {
            // deal with inputs[index] element.
            //console.log(inputs[index].value);
            if(inputs[index].type == 'hidden'){
                if(dup_holder.indexOf(inputs[index].value) == '-1'){
                    imgs.push({"target_id": inputs[index].value});
                }
                dup_holder.push(inputs[index].value);
            }
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
            getCsrfToken(function (csrfToken) {
                var nid = document.getElementById('edit-identifier-nid').value;
                if (nid) {
                    patchNode(csrfToken, Node1, nid);
                }
            });
        }
        else{
            swal({
                title: "Resequence Error",
                text: "No images were selected to be resequenced",
                type: "error",
                showCancelButton: false,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "OK",
                closeOnConfirm: true
            });

        }
    }

    $("#studio-resequence-bt").click(function () {
        update_w();
    });

})(jQuery);
