(function ($) {

  //TO DO: disable delete button by default, only enable when something is checked to delete

  'use strict';

  Drupal.behaviors.tagfordelete = {
    attach: function(context, settings) {
      $('input:checkbox').change(function(){
        var closeelement = document.getElementById( "ribboncontainer" );
        if($(this).is(":checked")) {
          var id = $(this).parents('span').attr('id');
          $('#seq-'+id).addClass('tag-deleted');
          $('#seq-'+id).html("<i class='fa fa-trash'></i>")
          console.log($('#tag-img-'+id).html)
        } else {
          var id = $(this).parents('span').attr('id')
          $('#seq-'+id).removeClass('tag-deleted');
          $('#seq-'+id).html($('#seq-'+id).attr('name'))
        }
      });
    }
  };

  Drupal.behaviors.tagfordelete2 = {
    attach: function(context, settings) {
      // $(".scanpicture").ondblclick(function(){
      //   // var closeelement = var id = $(this).parents('span').attr('data-imageid');
      //   console.log('yes');
      // });

      $(".scanpicture", context).ondblclick = function() { console.log("ondblclick event detected!"); };
    }
  };




}(jQuery));
