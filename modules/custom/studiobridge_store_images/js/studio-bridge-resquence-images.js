(function ($) {

    function getCsrfToken(callback) {
        $
            .get(Drupal.url('rest/session/token'))
            .done(function (data) {
                var csrfToken = data;
                callback(csrfToken);
            });
    }

    function patchNode(csrfToken, node, nid) {
        $.ajax({
            url: 'http://studiobridge.local/node/' + nid + '?_format=hal_json',
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/hal+json',
                'X-CSRF-Token': csrfToken
            },
            data: JSON.stringify(node),
            success: function (node) {
                //console.log(node);
                document.getElementById('msg-up').innerHTML = 'Updated!';
                setTimeout(function(){
                    document.getElementById('msg-up').innerHTML = '';
                }, 3300);
            },
            error: function(){
                alert('Failed!');
            }

        });
    }

    function update_w() {
        document.getElementById('msg-up').innerHTML = 'Updating....';

        var p = document.getElementById('sortable');
        var a = p.childNodes;

        var imgs = [];

        //imgs.push({"target_id":"34"});
        //imgs.push({"target_id":"35"});

        console.log(imgs);
        for (var j in p.childNodes) {
            var k = j;
            if (a[j].childNodes) {
                var x = a[j].childNodes;
                if (x) {
                    imgs.push({"target_id": x[1].value});
                    //console.log(x[1].value);
                }
            }
        }
        //console.log(imgs);

        var Node1 = {
            _links: {
                type: {
                    href: 'http://studiobridge.local/rest/type/node/products'
                }
            },
            type: {
                target_id: 'test'
            },
            field_images: imgs
        };

        console.log(Node1);

        getCsrfToken(function (csrfToken) {
            var nid = document.getElementById('edit-identifier-nid').value;
            if (nid) {
                patchNode(csrfToken, Node1, nid);
            }
        });
    }

    $("#studio-resequence-bt").click(function () {
        update_w();
    });

})(jQuery);


