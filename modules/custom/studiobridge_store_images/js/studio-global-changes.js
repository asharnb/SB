// Temporary solution for changing node form title label.

(function ($) {
    $( "#node-container-form > #edit-title-wrapper > div.js-form-item-title-0-value label" )
        .html( "Contanier Id <span class=\"form-required\">*</span>" );

    $( "#edit-field-vm-0-target-id" ).blur(function() {
        var v = document.getElementById('edit-field-vm-0-target-id').value;
        v = v.replace(/\((.+?)\)/, '');
        v= v.trim();
        document.getElementById('edit-field-vm-0-target-id').value = v;
    });

    $( "#edit-field-stylish-0-target-id" ).blur(function() {
        var s = document.getElementById('edit-field-stylish-0-target-id').value;
        s = s.replace(/\((.+?)\)/, '');
        s= s.trim();
        document.getElementById('edit-field-stylish-0-target-id').value = s;
    });

})(jQuery);
///var/www/html/tmp/studiobridge-drupal/modules/custom/studiobridge_store_images/js/studio-global-changes.js