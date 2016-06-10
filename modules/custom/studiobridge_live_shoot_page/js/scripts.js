(function ($) {

  //TO DO: disable delete button by default, only enable when something is checked to delete

  'use strict';

  var selected = 0;

  $(document).on("click",".select-delete",function(){
    //alert('no');
    console.log($(this));
    var id = $(this).attr("data-id")
    var clicked = $(this).attr("data-click")
    var container = document.getElementById( "warpper-img-"+id );

    if(clicked==='yes'){
      console.log(id);
      $("#warpper-img-"+id).removeClass('border-selected');
      $(this).attr("data-click","no")
      $(this).html("Select Image")
      $('#del-img-'+id).prop('checked', false);
      selected--
    }else{
      $("#warpper-img-"+id).addClass('border-selected');
      console.log(id);
      $(this).attr("data-click","yes")
      $(this).html("Unselect Image")
      $('#del-img-'+id).prop('checked', true);
      selected++
    }

    if(selected === 0){
      $('#studio-delete-bt').attr('disabled', 'disabled');
      $('#studio-delete-bt').removeClass('border-blue text-complete');

    }else{
      $('#studio-delete-bt').removeAttr("disabled");
      $('#studio-delete-bt').addClass('border-blue text-complete');
    }




  });
  
  Drupal.behaviors.liveshootpage = {
    attach: function(context, settings) {


    }
  };


  Drupal.behaviors.sortable = {
    attach: function(context, settings) {

      $("#imagecontainer").sortable({
          tolerance: 'pointer',
          start: function(event, ui){
              ui.placeholder.html("<div class='bulkviewfiles file gray-bkground' style='width: 200px; height: 200px; background: #D2D2D2;'></div>");
          },
          change: function (event, ui){

          },
          stop: function(event, ui){
              ui.placeholder.html("");
              $('#studio-resequence-bt').removeAttr("disabled");
              $('#studio-resequence-bt').addClass('border-blue text-complete');
          }
      });


      $( "#imagecontainer" ).disableSelection();
    }


  };

}(jQuery));
