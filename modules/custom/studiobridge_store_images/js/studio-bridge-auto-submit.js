(function ($) {
    $(function() {
        setInterval(function() {

            var rand = Math.floor((Math.random() * 1000000) + 1);
            $.get("http://studiobridge.local/live-shoot-image-container/" + rand + "?_format=json", function(data, status){
                //alert("Data: " + data + "\nStatus: " + status);
                document.getElementById('studio-img-container').innerHTML = data.content;
                console.log(data.content);
            });

            document.getElementById('views-exposed-form-individual-project-view-page-1').submit();
        }, 10000);
    });
})(jQuery);


