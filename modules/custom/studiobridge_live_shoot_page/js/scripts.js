(function ($) {

  //TO DO: disable delete button by default, only enable when something is checked to delete

  'use strict';

  Drupal.behaviors.tagfordelete = {
    attach: function(context, settings) {
      $( ".select-delete" ).click(function() {
        var id = $(this).attr("data-id")
        var clicked = $(this).attr("data-click")
        var container = document.getElementById( "warpper-img-"+id );

        if(clicked==='yes'){
          $("#warpper-img-"+id).removeClass('border-selected');
          $(this).attr("data-click","no")
          $(this).html("Select Image")
          $('#del-img-'+id).prop('checked', false);
        }else{
          $("#warpper-img-"+id).addClass('border-selected');
          $(this).attr("data-click","yes")
          $(this).html("Unselect Image")
          $('#del-img-'+id).prop('checked', true);
        }

        console.log( container);
      });

    }
  };

  Drupal.behaviors.tagfordelete2 = {
    attach: function(context, settings) {
      // $(".scanpicture").ondblclick(function(){
      //   // var closeelement = var id = $(this).parents('span').attr('data-imageid');
      //   console.log('yes');
      // });

      $(".select-delete", context).click = function() { console.log("ondblclick event detected!"); };
    }
  };




}(jQuery));
