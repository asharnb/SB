/**
 * Created by asharbabar on 4/22/16.
 */
(function ($) {

    //TO DO: disable delete button by default, only enable when something is checked to delete

    'use strict';

    Drupal.behaviors.datatables = {
        attach: function(context, settings) {
            $('#dt_unmapped').DataTable( {
                "bSort": false,
                "bDestroy": true,
                "iDisplayLength": 15,
                "order": [[1, 'asc']],

            } );
            $('#dt_mapped').DataTable( {
                "bSort": false,
                "bDestroy": true,
                "iDisplayLength": 15,
                "order": [[1, 'asc']],

            } );
            $('#dt_viewsessions').DataTable( {
                "bSort": false,
                "bDestroy": true,
                "iDisplayLength": 15,
                "order": [[1, 'asc']],

            } );


        }

    };

    Drupal.behaviors.closesession = {
        attach: function(context) {
            $("#close-session", context).click(function () {

                swal({
                    title: "Close Session?",
                    text: "Are you sure you want to close this session?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Close Session",
                    closeOnConfirm: true
                }, function () {
                    console.log('dropped');
                });
            });
        }
    };


}(jQuery));