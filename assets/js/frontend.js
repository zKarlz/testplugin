(function($){
    function CanvasEditor($el){
        var settings = $el.data('settings');
        var bounds = settings.bounds || {x:0,y:0,width:300,height:300};
        var canvas = $el.find('canvas')[0];
        var ctx = canvas.getContext('2d');
        var fileInput = $el.find('input[type="file"]');
        var finalizeBtn = $el.find('#llp-finalize');
        var addToCart = $el.find('.single_add_to_cart_button');
        var finalizedField = $el.find('#llp_finalized');

        function drawImage(img){
            var min = settings.min_resolution || {width:0,height:0};
            if(img.width < min.width || img.height < min.height){
                alert('Image does not meet minimum resolution.');
                return;
            }
            var expected = settings.aspect_ratio;
            var ratio = img.width / img.height;
            var w = bounds.width;
            var h = bounds.height;
            canvas.width = w;
            canvas.height = h;
            ctx.clearRect(0,0,w,h);
            // fit image within bounds maintaining aspect ratio
            var scale = Math.max(w / img.width, h / img.height);
            var dw = img.width * scale;
            var dh = img.height * scale;
            var dx = (w - dw) / 2;
            var dy = (h - dh) / 2;
            ctx.drawImage(img, dx, dy, dw, dh);
        }

        fileInput.on('change', function(e){
            var file = this.files[0];
            if(!file) return;
            var reader = new FileReader();
            reader.onload = function(evt){
                var img = new Image();
                img.onload = function(){
                    drawImage(img);
                };
                img.src = evt.target.result;
            };
            reader.readAsDataURL(file);
        });

        finalizeBtn.on('click', function(){
            finalizedField.val('1');
            addToCart.prop('disabled', false);
        });
    }

    $(function(){
        $('#llp-customizer').each(function(){
            new CanvasEditor($(this));
        });
    });
})(jQuery);
