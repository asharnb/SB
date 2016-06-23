(function ($) {

    function getCsrfToken(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function checkFilesExist(callback, fid, camma) {

        $.get(Drupal.url('filename/'+ fid +'/'+ Math.floor((Math.random() * 1000000) + 1) +'?_format=json&fids='+camma))
            .done(function (data) {
                callback(data);
            });
    }


    function patchNode(csrfToken, node, nid, unwanted) {
        $.ajax({
            url: Drupal.url('node/' + nid + '?_format=hal_json'),
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (node) {
                swal({
                    title: "Resequence Successful",
                    text: "Images have have been resequenced",
                    type: "success",
                    showConfirmButton: false,
                    closeOnConfirm: true,
                    timer: 1000
                });

                deleteUnWanted(unwanted);
                reSeq();

            },
            error: function(){
                alert('Failed!, Reload the page & try again.');
            }

        });
    }

    function update_w() {

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
            if(inputs[index].type == 'hidden'){
                if(dup_holder.indexOf(inputs[index].value) == '-1'){
                    if(inputs[index].value){
                        var fid =  inputs[index].value;
                        imgsOriginal.push(fid);
                    }
                }
                dup_holder.push(inputs[index].value);
            }
        }

        if(imgsOriginal.length){


            var fids_comma = imgsOriginal.join();
            var rand = Math.floor((Math.random() * 1000000) + 1);

            checkFilesExist(function (fileObj) {

                var imgs = [];
                var fids = fileObj.fids;
                var unwanted = fileObj.unwanted;


                for(var index in fids) {
                    if (fids.hasOwnProperty(index)) {
                        //var attr = object[index];
                        var tmp =  fids[index];
                        imgs.push({"target_id": tmp});
                    }
                }

                if(imgs.length > 1){
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


                    post_now(Node1, unwanted);

                }
                else{
                    swal({
                        title: "Resequence Error",
                        text: "No images were selected to be resequenced OR only 1 image found",
                        type: "error",
                        showCancelButton: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "OK",
                        closeOnConfirm: true
                    });

                    deleteUnWanted(unwanted);
                    reSeq();
                }

            }, rand, fids_comma);

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

    function post_now(Node1, unwanted){
        //console.log(Node1);
        getCsrfToken(function (csrfToken) {
            var nid = document.getElementById('edit-identifier-nid').value;
            if (nid) {
                patchNode(csrfToken, Node1, nid, unwanted);
            }
        });
    }

    function deleteUnWanted(unwanted){
        var del = [];
        unwanted.forEach(function(entry) {
            document.getElementById("warpper-img-"+entry).remove();
        });
    }

    function reSeq(){
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

                if(dup_holder.indexOf(inputs[index].value) == '-1'){
                    var seq = document.getElementById('seq-'+inputs[index].value);
                    if(seq){
                        document.getElementById('seq-'+inputs[index].value).innerHTML = inc;
                        ++inc;

                        // todo : get img file name
                        var rand = Math.floor((Math.random() * 1000000) + 1);
                        var fid =  inputs[index].value;
                    }

                }
                dup_holder.push(inputs[index].value);
            }
        }
    }


})(jQuery);
