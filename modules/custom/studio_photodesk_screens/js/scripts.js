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


}(jQuery));