/**
 * Created by asharbabar on 4/22/16.
 */
(function ($) {

    //TO DO: disable delete button by default, only enable when something is checked to delete

    'use strict';

    Drupal.behaviors.datatables = {
        attach: function(context, settings) {
            $('#dt_viewsessions').DataTable( {
                "bSort": true,
                "bDestroy": true,
                "iDisplayLength": 15,
                "order": [[0, 'desc']],

            } );


        }

    };

    Drupal.behaviors.closesession = {
        attach: function(context) {
            $("#close-session", context).click(function () {
                var id = $("#close-session").attr('data-id');
                swal({
                    title: "Close Session?",
                    text: "Are you sure you want to close this session?",
                    type: "question",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Close Session",
                    closeOnConfirm: true
                }, function () {
                    window.location = "/sbtest/close-session/"+id+"/1";
                });
            });
        }
    };


}(jQuery));
