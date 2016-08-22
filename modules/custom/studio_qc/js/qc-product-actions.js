(function ($) {

    function getCsrfTokenForWarehouse(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }


    /*
     *  Process to warehouse rest resource.
     */
    function qcOperation(csrfToken, node, init) {
        $('#warehouse-checkin-product-status-wrapper').html('Checking server ...');
        $.ajax({
            url: Drupal.url('qc/operation/' + init + '/post?_format=json'),
            method: 'POST',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (response) {
                //updateProductInformationBlock(response);
                alert('success');
            },
            error: function(){
                alert('Failed!');
            }

        });
    }


    /*
     *  Process product in the container.
     */
    function process_product(product,session, imgs, state){
        var data = {
            _links: {
                type: {
                    href: Drupal.url.toAbsolute(drupalSettings.path.baseUrl + 'rest/type/node/test')
                }
            },
            "body": [
                {
                    "value": {
                        "pid": product,
                        "sid": session,
                        "images": imgs,
                        "state": state

                    },
                    "format": null,
                    "summary": null
                }
            ],
            "type":[{"target_id":"test"}]
        };

        getCsrfTokenForWarehouse(function (csrfToken) {
            qcOperation(csrfToken, data, 'reject_all');
        });

    }



    /*
     *  tag value 1 means tag
     *  tag value 0 means undo tag
     *
     */
    function update_image(tag,fidinput, ref) {

        var tags_lenght = $('.tag').length;
        var is_tagged = $('#warpper-img-'+ fidinput +' .tag').length;


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
            field_reference: [
                {
                    "value": ref
                }
            ],
            filename: {

                value: filename

            }
        };

        getCsrfTokenForWarehouse(function (csrfToken) {
            if (fid) {
                patchImageTag(csrfToken, img, fid, tag);
            }else{
                alert('No product found, pls refresh the page.');
            }
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

            },
            error: function(){

            }

        });


    }


    $(document).on("click",".approve-all",function(){
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

        });




})(jQuery);
