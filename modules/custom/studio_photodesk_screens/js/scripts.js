/**
* Created by asharbabar on 4/22/16.
*/
(function ($) {

  //TO DO: disable delete button by default, only enable when something is checked to delete

  //'use strict';

  Drupal.behaviors.sessiondatatable = {
    attach: function(context) {
       var oTable = $('#dt_viewsessions').DataTable( {
        "bSort": true,
        "bDestroy": true,
        "iDisplayLength": 30,
        "order": [[0 , 'desc']],
        "autoWidth" : true
      } );
      $('#dt_search_box').keyup(function(){
            oTable.search($(this).val()).draw() ;
      })

      $('#btn_search_reset').click(function(){
            document.getElementById('dt_search_box').value = "";
              oTable.search('').draw() ;
      })

      $('#dt_viewsessions_wrapper').addClass('m-t-n');
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

  var equalheight = function(container){

    var currentTallest = 0,
    currentRowStart = 0,
    rowDivs = new Array(),
    topPosition = 0;
    $(container).each(function() {

      var el = jQuery(this);
      console.log(el.height());
      el.height('auto')
      topPosition = el.position().top;

      if (currentRowStart != topPosition) {
        for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
          rowDivs[currentDiv].height(currentTallest);
        }
        rowDivs.length = 0; // empty the array
        currentRowStart = topPosition;
        currentTallest = el.height();
        rowDivs.push(el);
      } else {
        rowDivs.push(el);
        currentTallest = (currentTallest < el.height()) ? (el.height()) : (currentTallest);
      }
      for (currentDiv = 0 ; currentDiv < rowDivs.length ; currentDiv++) {
        rowDivs[currentDiv].height(currentTallest);
      }
    });
  }

  $(window).load(function() {
    equalheight('.equalheight');
  });


  $(window).resize(function(){
    equalheight('.equalheight');
  });

}(jQuery));
