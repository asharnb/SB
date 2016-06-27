(function ($) {

    function getCsrfTokenForDelete(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function patchNodeDelete(csrfToken, node, nid, unwanted) {

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
                swal({
                    title: "Images Deleted",
                    text: "Your selected images have been deleted",
                    type: "success",
                    showConfirmButton: false,
                    timer: 1000
                });

                // update whole img container
                var container, inputs, index, index2;

                // Get the container element
                container = document.getElementById('imagecontainer');

                // Find its child `input` elements
                inputs = container.getElementsByClassName("form-checkbox");

                var del = [];
                // Delete checked images.
                for (index = 0; index < inputs.length; ++index) {
                    // deal with inputs[index] element.
                    if(inputs[index].checked){
                        //document.getElementById("warpper-img-"+inputs[index].value).remove();
                        console.log(inputs[index].value);
                        del.push("warpper-img-"+inputs[index].value);
                    }
                }
                del.forEach(function(entry) {
                    console.log(entry);
                    document.getElementById(entry).remove();
                });

                deleteUnWanted(unwanted);

                // Update image wrappers.
                container = document.getElementById('imagecontainer');
                var inputs2 = container.getElementsByClassName("form-checkbox");
                for (index2 = 0; index2 < inputs2.length; ++index2) {
                    // deal with inputs[index] element.
                    document.getElementById('seq-'+inputs2[index2].value).innerHTML = index2 + 1;
                    //document.getElementById("warpper-img-453").remove();

                    var dcount = document.getElementById('product-img-count-deleted').innerHTML;
                    dcount++;
                    document.getElementById('product-img-count-deleted').innerHTML = dcount;

                    // todo : get img file name
                    var rand = Math.floor((Math.random() * 1000000) + 1);
                    var fid =  inputs2[index2].value;

                }

            },
            error: function(xhr, status, error) {
              var err = eval("(" + xhr.responseText + ")");
              alert(err.Message);
              // swal({
              //     title: "Delete Failed",
              //     text: "There was an error deleting, please try again.",
              //     type: "error",
              //     showCancelButton: false,
              //     confirmButtonColor: "#DD6B55",
              //     confirmButtonText: "OK",
              //     closeOnConfirm: true
              // });
            }

        });
    }

    function update_delete() {

        var container, inputs, index;
        var imgs = [];
        var imgsOriginal = [];

        // Get the container element
        container = document.getElementById('imagecontainer');

        // Find its child `input` elements
        inputs = container.getElementsByClassName("form-checkbox");
        var unchecked = 0;
        for (index = 0; index < inputs.length; ++index) {
            // deal with inputs[index] element.
            //console.log(inputs[index].value);
            if(!(inputs[index].checked)){
                //imgs.push({"target_id": inputs[index].value});
                var fid =  inputs[index].value;
                if(fid){
                    imgsOriginal.push(fid);
                }
            }else{
                ++unchecked;
            }
        }


        if(unchecked){
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

                        //document.getElementById('msg-up').innerHTML = 'Deleting selected images....';
                        getCsrfTokenForDelete(function (csrfToken) {
                            var nid = document.getElementById('edit-identifier-nid').value;
                            if (nid) {
                                patchNodeDelete(csrfToken, Node_imgs, nid, unwanted);
                            }
                        });


                }, rand, fids_comma);

            }
            else{

                var Node_imgs = {
                    _links: {
                        type: {
                            href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/node/products')
                        }
                    },
                    type: {
                        target_id: 'products'
                    },
                    field_images: []
                };

                //document.getElementById('msg-up').innerHTML = 'Deleting selected images....';
                getCsrfTokenForDelete(function (csrfToken) {
                    var nid = document.getElementById('edit-identifier-nid').value;
                    if (nid) {
                        patchNodeDelete(csrfToken, Node_imgs, nid, []);
                    }
                });

            }

        }
        else{
            swal({
                title: "Delete Error",
                text: "No images were selected to be deleted",
                type: "error",
                showCancelButton: false,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "OK",
                closeOnConfirm: true
            });

        }
    }

    $("#studio-delete-bt").click(function () {
        update_delete();
        //alert(1234)
    });


    function checkFilesExist(callback, fid, camma) {

        $.get(Drupal.url('filename/'+ fid +'/'+ Math.floor((Math.random() * 1000000) + 1) +'?_format=json&fids='+camma))
            .done(function (data) {
                callback(data);
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


    function deleteUnWanted(unwanted){
        var del = [];
        unwanted.forEach(function(entry) {
            document.getElementById("warpper-img-"+entry).remove();
        });
    }

})(jQuery);
