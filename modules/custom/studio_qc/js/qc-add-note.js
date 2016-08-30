(function ($) {

    function getCsrfToken(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                callback(data);
            });
    }

    /*
     *  Send note to the server.
     */
    function qcOperation(csrfToken, node, init) {
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
            error: function () {
                alert('Failed!');
            }

        });
    }

    /*
     *  Process approve or reject request before sending it to server.
     */
    function processAddNoteRequest(product, session, note) {
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
                        "note": note

                    },
                    "format": null,
                    "summary": null
                }
            ],
            "type": [
                {"target_id": "test"}
            ]
        };

        getCsrfToken(function (csrfToken) {
            qcOperation(csrfToken, data, 'notes');
        });

    }

    $(document).on("click", ".add-note", function () {

        swal({
            title: 'Add Note',
            html: '<div id="warehouse-flag-form">' +
                '<textarea class="js-text-full text-full form-textarea form-control resize-vertical" data-drupal-selector="edit-field-notes-0-value" title="" data-toggle="tooltip" id="edit-field-notes-0-value" name="field_notes[0][value]" rows="4" cols="60" placeholder="Add notes about this product..." data-original-title=""></textarea>' +
                '</div>',
            showCloseButton: true,
            showCancelButton: true,
            confirmButtonText: 'Add',
            cancelButtonText: 'Cancel'
        }, function () {
            //var option = document.getElementById('flag-option').value;
            var note = document.getElementById('edit-field-notes-0-value').value;
            var pid = 999;
            var sid = 999;
            console.log(note);
            processAddNoteRequest(pid, sid, note);

        });

    });

})(window.jQuery);