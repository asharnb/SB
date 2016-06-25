(function ($) {

    function getCsrfToken(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function getFileName(callback, fid, camma) {

        $.get(Drupal.url('filename/'+ fid +'/'+ Math.floor((Math.random() * 1000000) + 1) +'?_format=json&fids='+camma))
            .done(function (data) {
                //document.getElementById('seq-img-'+ fid).innerHTML = data.filename;
                callback(data);
            });
    }

    function checkFileExist(callback, fid) {

        $.get(Drupal.url('file/'+ fid +'?_format=json'))
            .done(function (data) {
                //console.log(data);
                //alert(data.fid.value);
                //document.getElementById('seq-img-'+ fid).innerHTML = data.fid.value;
                callback(data,fid);
            });
    }

    function patchNode(csrfToken, node, nid) {
        console.log(node);
        $.ajax({
            url: Drupal.url('node/' + nid + '?_format=hal_json'),
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (node) {
                console.log('------------------');
                console.log(node);
                //document.getElementById('msg-up').innerHTML = 'Updated!';
                swal({
                    title: "Resequence Successful",
                    text: "Images have have been resequenced",
                    type: "success",
                    showConfirmButton: false,
                    closeOnConfirm: true,
                    timer: 1000
                });
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
//                            getFileName(function (filename) {
//                                //console.log(csrfToken);
//                                //document.getElementById('seq-img-'+ fid).innerHTML = filename;
//                            }, fid);
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
        var imgsOriginal = [];
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
                    if(inputs[index].value){
                        var fid =  inputs[index].value;
                        //imgs.push({"target_id": fid});
                        imgsOriginal.push(fid);
                    }
                }
                dup_holder.push(inputs[index].value);
            }
        }

        if(imgsOriginal.length){


            var fids_comma = imgsOriginal.join();

                console.log(fileObj);
                //document.getElementById('seq-img-'+ fid).innerHTML = filename;

                for (index = 0; index < fileObj.fids.length; ++index) {
                    var fid =  fileObj.fids[index];
                    imgs.push({"target_id": fid});
                }

                if(imgs.length){

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


                    post_now(Node1);

                }


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
        //update_w();
        foo(myCallback);
    });

    function post_now(Node1){
        getCsrfToken(function (csrfToken) {
            var nid = document.getElementById('edit-identifier-nid').value;
            if (nid) {
                console.log(Node1);
                patchNode(csrfToken, Node1, nid);

            }
        });
    }



    var result = foo();
    function myCallback(result) {
        var imgs = [];
        for (index = 0; index < result.fids.length; ++index) {
            var fid =  result.fids[index];
            imgs.push({"target_id": fid});
        }

        if(imgs.length){

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


            //post_now(Node1);

        }
    }

    //foo(myCallback);

    function foo(myCallback) {

        var container, inputs, index;
        var imgs = [];
        var imgsOriginal = [];
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
                    if(inputs[index].value){
                        var fid =  inputs[index].value;
                        //imgs.push({"target_id": fid});
                        imgsOriginal.push(fid);
                    }
                }
                dup_holder.push(inputs[index].value);
            }
        }

        if(imgsOriginal.length){
            var fids_comma = imgsOriginal.join();
            var nid = document.getElementById('edit-identifier-nid').value;
            $.ajax({
                url: Drupal.url('filename/'+ nid +'/'+ Math.floor((Math.random() * 1000000) + 1) +'?_format=json&fids='+fids_comma),
                method: 'GET',
                headers: {
                    'Content-Type': 'application/hal+json'
                },
                //data: JSON.stringify(node),
                success: myCallback,
                error: function(){
                    alert('Failed!');
                }

            });
        }

    }




})(jQuery);
