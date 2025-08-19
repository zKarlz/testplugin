(function(){
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.llp-media-select').forEach(function(btn){
            btn.addEventListener('click', function(){
                var frame = wp.media({title:'Select image', multiple:false});
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    var target = document.getElementById(btn.dataset.target);
                    if(target){ target.value = attachment.id; }
                });
                frame.open();
            });
        });

        document.querySelectorAll('.llp-open-editor').forEach(function(btn){
            btn.addEventListener('click', function(){
                var loop = btn.dataset.loop;
                var baseField = document.getElementById('llp_base_image_id_'+loop);
                if(!baseField || !baseField.value){ alert('Select base image first'); return; }
                var url = wp.media.attachment(baseField.value).get('url');
                var maskField = document.getElementById('llp_mask_image_id_'+loop);
                var maskUrl = maskField && maskField.value ? wp.media.attachment(maskField.value).get('url') : '';
                var rotationField = document.getElementById('llp_bounds_rotation_'+loop);
                var rotation = parseFloat(rotationField && rotationField.value ? rotationField.value : 0);
                var bounds = {
                    x: parseInt(document.getElementById('llp_bounds_x_'+loop).value || 0,10),
                    y: parseInt(document.getElementById('llp_bounds_y_'+loop).value || 0,10),
                    w: parseInt(document.getElementById('llp_bounds_w_'+loop).value || 100,10),
                    h: parseInt(document.getElementById('llp_bounds_h_'+loop).value || 100,10)
                };
                var modal = document.createElement('div');
                modal.className = 'llp-editor-modal';
                modal.innerHTML = '<div class="llp-editor-backdrop"></div><div class="llp-editor-content"><div class="llp-editor-stage"><img src="'+url+'" class="llp-editor-base"/>'+(maskUrl ? '<img src="'+maskUrl+'" class="llp-editor-mask" style="position:absolute;top:0;left:0;opacity:0.5;pointer-events:none;"/>' : '')+'<div class="llp-rect" style="left:'+bounds.x+'px;top:'+bounds.y+'px;width:'+bounds.w+'px;height:'+bounds.h+'px;transform:rotate('+rotation+'deg);"></div></div><p><button type="button" class="button llp-editor-close">'+llpAdmin.closeText+'</button></p></div>';
                document.body.appendChild(modal);
                var rect = modal.querySelector('.llp-rect');
                var stage = modal.querySelector('.llp-editor-stage');
                var dragging=false, offsetX=0, offsetY=0;
                rect.addEventListener('mousedown', function(e){ dragging=true; offsetX=e.offsetX; offsetY=e.offsetY; });
                document.addEventListener('mouseup', function(){ dragging=false; });
                document.addEventListener('mousemove', function(e){
                    if(!dragging) return;
                    var rectStage = stage.getBoundingClientRect();
                    var x = e.pageX - rectStage.left - offsetX;
                    var y = e.pageY - rectStage.top - offsetY;
                    rect.style.left = x + 'px';
                    rect.style.top = y + 'px';
                    document.getElementById('llp_bounds_x_'+loop).value = Math.round(x);
                    document.getElementById('llp_bounds_y_'+loop).value = Math.round(y);
                });
                rect.addEventListener('dblclick', function(){
                    var w = parseInt(prompt(llpAdmin.widthText, rect.offsetWidth),10);
                    var h = parseInt(prompt(llpAdmin.heightText, rect.offsetHeight),10);
                    var r = parseFloat(prompt(llpAdmin.rotationText, rotation));
                    if(!isNaN(w)){ rect.style.width=w+'px'; document.getElementById('llp_bounds_w_'+loop).value=w; }
                    if(!isNaN(h)){ rect.style.height=h+'px'; document.getElementById('llp_bounds_h_'+loop).value=h; }
                    if(!isNaN(r)){ rotation=r; rect.style.transform='rotate('+r+'deg)'; rotationField.value=r; }
                });
                modal.querySelector('.llp-editor-close').addEventListener('click', function(){ modal.remove(); });
            });
        });
    });
})();
