(function ($) {
    'use strict';

    Drupal.behaviors.awesome = {
        attach: function(context, settings) {
            console.log('hiiii');

            if ($('#del-img-636').prop("checked")==true){
                console.log('checked');
            }
    }
    };

}(jQuery));