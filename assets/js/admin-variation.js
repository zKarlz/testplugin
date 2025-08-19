jQuery(function($){
    function initVariationEditor($container){
        var $img   = $container.find('img.variation-preview');
        var $mask  = $container.find('.llp-mask');
        var state  = {
            x: parseFloat($container.data('x')) || 0,
            y: parseFloat($container.data('y')) || 0,
            width: parseFloat($container.data('width')) || ($img.length?$img.width():0),
            height: parseFloat($container.data('height')) || ($img.length?$img.height():0),
            rotation: parseFloat($container.data('rotation')) || 0,
            dpi: parseFloat($container.data('dpi')) || 0
        };

        function apply(){
            $img.css({
                transform: 'translate('+state.x+'px,'+state.y+'px) rotate('+state.rotation+'deg)',
                width: state.width,
                height: state.height
            });
            $container.find('[data-field="x"]').val(state.x.toFixed(2));
            $container.find('[data-field="y"]').val(state.y.toFixed(2));
            $container.find('[data-field="width"]').val(state.width.toFixed(2));
            $container.find('[data-field="height"]').val(state.height.toFixed(2));
            $container.find('[data-field="rotation"]').val(state.rotation.toFixed(2));
            $container.find('[data-field="dpi"]').val(state.dpi);
        }

        // draggable
        if ($img.draggable) {
            $img.draggable({
                stop: function(e,ui){
                    state.x = ui.position.left;
                    state.y = ui.position.top;
                    apply();
                }
            });
        }

        // resizable
        if ($img.resizable) {
            $img.resizable({
                handles: 'ne, se, sw, nw',
                stop: function(e,ui){
                    state.width  = ui.size.width;
                    state.height = ui.size.height;
                    apply();
                }
            });
        }

        // rotation handle
        var $rotHandle = $('<div class="llp-rotation-handle"></div>').appendTo($container);
        $rotHandle.on('mousedown', function(e){
            e.preventDefault();
            var center = $img.offset();
            center.left += $img.outerWidth()/2;
            center.top  += $img.outerHeight()/2;
            $(document).on('mousemove.llprotate', function(ev){
                var angle = Math.atan2(ev.pageY - center.top, ev.pageX - center.left) * 180 / Math.PI;
                state.rotation = angle;
                apply();
            }).on('mouseup.llprotate', function(){
                $(document).off('.llprotate');
            });
        });

        // numeric inputs -> update preview
        $container.on('input', '.llp-variation-field', function(){
            var key = $(this).data('field');
            state[key] = parseFloat($(this).val()) || 0;
            apply();
        });

        // mask toggle
        $container.on('change', '.toggle-mask', function(){
            if ($mask.length) {
                $mask.toggle(this.checked);
            }
        });

        apply();
    }

    $('.llp-variation-editor').each(function(){
        initVariationEditor($(this));
    });
});
