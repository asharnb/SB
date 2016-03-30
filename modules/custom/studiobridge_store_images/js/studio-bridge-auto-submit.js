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
            console.log(a);
            if(a){
//                a.forEach(function(img) {
//                    //console.log(img);
//                    append_img(img);
//                });

                for(var i in a){
                    //console.log(i + '----' + a[i]);
                    //alert(a[i]);
                    append_img(a[i],i)
                }
            }

            //console.log(data.content);
        });

    }

    function append_img(img,fid) {

        var container, inputs, index;

        // Get the container element
        container = document.getElementById('sortable');

        // Find its child `input` elements
        inputs = container.getElementsByTagName('input');
        var seq = inputs.length + 1;



        var ul = document.getElementById("sortable");
        var li = document.createElement("div");
        //li.appendChild(document.createTextNode(100));
        li.setAttribute("class", "bulkviewfiles imagefile ui-sortable-handle"); // added line

        var block = '<div class="bulkviewfiles imagefile">';
        //var block = '';
        block += '<div class="box" style="max-width: 250px;">';

        block +=  '<div class="ribbon"><span id="seq-'+ fid +'">'+ seq +'</span></div>';

        block +=  '<div class="scancontainer">';
        block +=  '<img src="'+ img.uri +'" class="scanpicture">';
        block +=  '</div>';
        block +=  "<input name='image[" + fid + "]' type='hidden' value='" + fid + "'/>";
        block +=  '<div class="file-name">';
        block +=  '<span class="bkname"><i class="fa fa-camera"></i><b id="seq-img-' + fid + '">'+img.name+'</b></span>';
        block +=  '<hr class="simple">';

        block += '<div class="row">';
        block += '<div class="col col-sm-6">';
        block += '<span><a class=" dropdown-toggle label label-default dropdown mr-5" data-toggle="dropdown" ><i class="fa fa-cog"></i> <i class="fa fa-caret-down"></i></a>';
        block += '<ul class="dropdown-menu pull-right"><li><a class="label label-default no-margin" onclick="return false;">Use this as full shot</a></li></ul>';
        block += '<span ><a target ="_blank" href="#" class="label label-info"><i class="glyphicon glyphicon-fullscreen"></i></a>';
        block += '</div>';
        block += '<div class="col col-sm-6">';
        block += '<span><a onclick="return false;" class="label label-danger mr5 pull-right">Delete</a>';
        block += '</div>';
        block += '</div>';

        block += '</div>';
        block += '</div>';
        block += '</div>';

        //li.innerHTML = "<input name='image[" + fid + "]' type='hidden' value='" + fid + "'/>";
        //li.innerHTML = "<img src='"+ img +"' /><input name='image[" + fid + "]' type='hidden' value='" + fid + "'/>";
        li.innerHTML = block;
        ul.appendChild(li);
    }

    $(function() {
        $("#sortable").sortable({
            tolerance: 'pointer',
            start: function(event, ui){
                ui.placeholder.html("<div class='bulkviewfiles file gray-bkground' style='width: 250px; height: 250px; background: #D2D2D2;'></div>");
            },
            stop: function(event, ui){
                ui.placeholder.html("");
            }
        });


        $( "#sortable" ).disableSelection();
    });



})(jQuery);


