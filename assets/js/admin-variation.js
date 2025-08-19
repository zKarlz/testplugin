(function($){
  'use strict';

  // Initialize WordPress media picker with thumbnail preview
  function initMediaPicker(button, input, preview){
    var frame;
    $(button).on('click', function(e){
      e.preventDefault();
      if(frame){
        frame.open();
        return;
      }
      frame = wp.media({
        title: 'Select image',
        button: { text: 'Use image' },
        multiple: false
      });
      frame.on('select', function(){
        var attachment = frame.state().get('selection').first().toJSON();
        $(input).val(attachment.id).trigger('change');
        if(attachment.sizes && attachment.sizes.thumbnail){
          $(preview).html('<img src="'+attachment.sizes.thumbnail.url+'" />');
        } else {
          $(preview).html('<img src="'+attachment.url+'" />');
        }
      });
      frame.open();
    });
  }

  // Initialize editor interactions
  function initEditor(canvas){
    var $canvas = $(canvas);
    var $selection = $canvas.find('.llp-selection');
    var $width = $canvas.find('.llp-width');
    var $height = $canvas.find('.llp-height');
    var $rotation = $canvas.find('.llp-rotation');
    var $mask = $canvas.find('.llp-mask-overlay');
    var $toggleMask = $canvas.find('.llp-toggle-mask');

    // jQuery UI draggable & resizable
    $selection.draggable({
      containment: 'parent',
      drag: function(e, ui){
        // live position updates optional
      }
    }).resizable({
      handles: 'n, e, s, w, ne, se, sw, nw',
      containment: 'parent',
      resize: function(event, ui){
        $width.val(Math.round(ui.size.width));
        $height.val(Math.round(ui.size.height));
      }
    });

    // Simple rotation handle
    var rotating = false;
    var startAngle = 0;
    var currentAngle = parseFloat($rotation.val()) || 0;
    var center = {x:0,y:0};

    var $handle = $('<div class="llp-rotate-handle">\u21bb</div>').appendTo($selection);
    $handle.on('mousedown', function(e){
      rotating = true;
      var offset = $selection.offset();
      center = { x: offset.left + $selection.width()/2, y: offset.top + $selection.height()/2 };
      startAngle = Math.atan2(e.pageY-center.y, e.pageX-center.x) - currentAngle*Math.PI/180;
      e.preventDefault();
    });
    $(document).on('mousemove', function(e){
      if(!rotating) return;
      var angle = Math.atan2(e.pageY-center.y, e.pageX-center.x) - startAngle;
      currentAngle = angle*180/Math.PI;
      $selection.css('transform', 'rotate('+currentAngle+'deg)');
      $rotation.val(Math.round(currentAngle));
    }).on('mouseup', function(){ rotating=false; });

    // Toggle mask overlay preview
    $toggleMask.on('change', function(){
      if($(this).is(':checked')){
        $mask.show();
      } else {
        $mask.hide();
      }
    });
  }

  $(function(){
    // Base and mask media pickers
    initMediaPicker('.llp-base-image-upload', '.llp-base-image-id', '.llp-base-image-preview');
    initMediaPicker('.llp-mask-image-upload', '.llp-mask-image-id', '.llp-mask-image-preview');

    // Editor setup
    $('.llp-editor-canvas').each(function(){
      initEditor(this);
    });
  });

})(jQuery);
