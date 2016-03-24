(function ($) {
    $(function() {
     var identifier = document.getElementById('edit-identifier-hidden').value;
        //alert(identifier)
     if(identifier != ''){
        setInterval(function() {
            var rand = Math.floor((Math.random() * 1000000) + 1);
            var identifier = document.getElementById('edit-identifier-hidden').value;
            triggerit(identifier, rand);
            //document.getElementById('views-exposed-form-individual-project-view-page-1').submit();

        }, 1000);
         var rand = Math.floor((Math.random() * 1000000) + 1);
         triggerit(identifier, rand);

     }else{
         document.getElementById('studio-img-container').innerHTML = '--------No product scanned or no recent open product in this session :( ----------';
         setInterval(function() {

             var identifier = document.getElementById('edit-identifier-hidden').value;
             if(identifier != ''){
                var rand = Math.floor((Math.random() * 1000000) + 1);
                triggerit(identifier, rand);
             }

         }, 1000);
     }
    });

    function triggerit(identifier, rand){
        var url = "/live-shoot-image-container/";
        if(window.location.hostname == "staging.dreamcms.me"){
            url = "/studiobridge/live-shoot-image-container/";
        }
        $.get(url + identifier + "/"+ rand +"?_format=json", function(data, status){
            //alert("Data: " + data + "\nStatus: " + status);
            //document.getElementById('studio-img-container').innerHTML = data.content;
            document.getElementById('block-currentsessionviewblock').innerHTML = data.block1;
            document.getElementById('studio-bridge-product-details').innerHTML = data.block2;

            var a = data.block3;
            if(a){
                a.forEach(function(img) {
                    //console.log(img);
                    append_img(img);
                });
            }

            //console.log(data.content);
        });
    }

    function append_img(img) {
        var ul = document.getElementById("sortable");
        var li = document.createElement("li");
        //li.appendChild(document.createTextNode(100));
        li.setAttribute("class", "ui-state-default ui-sortable-handle"); // added line
        li.innerHTML = "<img src='"+ img +"' />";
        ul.appendChild(li);
    }

    $(function() {
        $( "#sortable" ).sortable();
        $( "#sortable" ).disableSelection();
    });

})(jQuery);


