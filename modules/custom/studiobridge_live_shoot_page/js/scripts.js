(function ($) {
    'use strict';

    Drupal.behaviors.awesome = {
        attach: function(context, settings) {

            $('input:checkbox').change(function(){
                var closeelement = document.getElementById( "ribboncontainer" );
                if($(this).is(":checked")) {
                    var id = $(this).parents('span').attr('id')
                    $('#seq-'+id).addClass('tag-deleted');
                    console.log($('#tag-img-'+id))
                } else {
                    var id = $(this).parents('span').attr('id')
                    $('#seq-'+id).removeClass('tag-deleted');
                }
            });
    }
    };

}(jQuery));