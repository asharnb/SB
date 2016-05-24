/**
* Created by asharbabar on 4/22/16.
*/
(function ($) {

  //TO DO: disable delete button by default, only enable when something is checked to delete

  'use strict';

  Drupal.behaviors.sessiondatatable = {
    attach: function(context) {
      $('#dt_viewsessions').DataTable( {
        "bSort": false,
        "bDestroy": true,
        "iDisplayLength": 100,
        "order": [[1 , 'asc']],
        "autoWidth" : true
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

  Drupal.behaviors.importproducts = {
    attach: function(context) {
      $("#import-products", context).click(function () {
        var productcount = $("#import-products").attr('data-id');
        console.log(productcount);
        if(productcount==='0'){

          swal({
            title: "White Balance Check",
            text: "Please make sure that you have white balanced your camera before shooting any products.",
            //type: "info",
            imageUrl: '/sbtest/themes/studiobridge/images/whitebalance.png',
            imageWidth: 400,
            imageHeight: 200,
            showCancelButton: false,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "I agree",
            closeOnConfirm: true
          }, function () {
            window.location = "/sbtest/live-shooting-page1";
          });


        } else{
          window.location = "/sbtest/live-shooting-page1";

        }

      });
    }
  };


}(jQuery));
