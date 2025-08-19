(function(){
    const cfg = window.llp_frontend || {};
    document.addEventListener('DOMContentLoaded', () => {
        const uploadBtn = document.getElementById('llp-upload-btn');
        const fileInput = document.getElementById('llp-upload');
        const assetField = document.getElementById('llp_asset_id');
        const transformField = document.getElementById('llp_transform');
        const editor = document.getElementById('llp-editor');
        const canvas = document.getElementById('llp-canvas');
        const baseLayer = document.getElementById('llp-base');
        const maskLayer = document.getElementById('llp-mask');
        const rotateL = document.getElementById('llp-rotate-left');
        const rotateR = document.getElementById('llp-rotate-right');
        const finalizeBtn = document.getElementById('llp-finalize-btn');
        const preview = document.getElementById('llp-preview');
        const addBtn = document.querySelector('form.cart button[type="submit"]');

        if (addBtn) { addBtn.disabled = true; }
        if (!uploadBtn || !fileInput || !canvas) { return; }

        const ctx = canvas.getContext('2d');
        const state = {img:null, iw:0, ih:0, scale:1, rotation:0, tx:0, ty:0, bounds:null, dragging:false, lastX:0, lastY:0};

        function draw(){
            if(!state.img || !state.bounds) return;
            ctx.clearRect(0,0,canvas.width,canvas.height);
            const b = state.bounds;
            const cx = b.x + b.w/2 + state.tx;
            const cy = b.y + b.h/2 + state.ty;
            ctx.save();
            ctx.translate(cx, cy);
            ctx.rotate(state.rotation * Math.PI/180);
            const w = b.w * state.scale;
            const h = b.h * state.scale;
            ctx.drawImage(state.img, -w/2, -h/2, w, h);
            ctx.restore();
        }

        function clamp(){
            const b = state.bounds;
            const maxX = (state.iw * state.scale - b.w)/2;
            const maxY = (state.ih * state.scale - b.h)/2;
            if (maxX < 0) { state.tx = 0; } else { state.tx = Math.max(Math.min(state.tx, maxX), -maxX); }
            if (maxY < 0) { state.ty = 0; } else { state.ty = Math.max(Math.min(state.ty, maxY), -maxY); }
        }

        function clearFit(){
            assetField.value = '';
            transformField.value = '';
            preview.innerHTML = '';
            if (addBtn) { addBtn.disabled = true; }
            editor.style.display = 'none';
            state.img = null;
            state.bounds = null;
            fileInput.value = '';
            ctx.clearRect(0,0,canvas.width,canvas.height);
        }

        const variationForm = document.querySelector('form.variations_form');
        if (variationForm && window.jQuery) {
            const $form = window.jQuery(variationForm);
            let lastVariationId = 0;
            let lastAttrs = {};
            $form.on('found_variation', function(event, variation){
                const newId = variation && variation.variation_id ? parseInt(variation.variation_id) : 0;
                if (assetField.value && lastVariationId && newId !== lastVariationId) {
                    if (!window.confirm('Changing the variation will discard your current fit. Continue?')) {
                        for (const name in lastAttrs) {
                            const sel = $form.find('select[name="' + name + '"]');
                            if (sel.length) { sel.val(lastAttrs[name]); }
                        }
                        $form.trigger('check_variations');
                        return;
                    }
                    clearFit();
                }
                lastVariationId = newId;
                lastAttrs = {};
                $form.find('select').each(function(){ lastAttrs[this.name] = this.value; });
            });
        }

        canvas.addEventListener('mousedown', e => {
            state.dragging = true;
            state.lastX = e.offsetX;
            state.lastY = e.offsetY;
        });
        document.addEventListener('mouseup', () => { state.dragging = false; });
        canvas.addEventListener('mousemove', e => {
            if (!state.dragging) return;
            state.tx += e.offsetX - state.lastX;
            state.ty += e.offsetY - state.lastY;
            state.lastX = e.offsetX;
            state.lastY = e.offsetY;
            clamp();
            draw();
        });
        canvas.addEventListener('wheel', e => {
            e.preventDefault();
            const delta = e.deltaY < 0 ? 1.1 : 0.9;
            state.scale *= delta;
            clamp();
            draw();
        });

        if (rotateL) { rotateL.addEventListener('click', () => { state.rotation -= 5; draw(); }); }
        if (rotateR) { rotateR.addEventListener('click', () => { state.rotation += 5; draw(); }); }

        function setupEditor(vdata){
            editor.style.display = 'block';
            baseLayer.src = vdata.base;
            maskLayer.src = vdata.mask;
            baseLayer.onload = () => {
                canvas.width = vdata.base_w || baseLayer.naturalWidth;
                canvas.height = vdata.base_h || baseLayer.naturalHeight;
                draw();
            };
        }

        uploadBtn.addEventListener('click', () => {
            if (!fileInput.files.length) return;
            const variationInput = document.querySelector('input[name="variation_id"]');
            const variationId = variationInput ? parseInt(variationInput.value) : 0;
            const vdata = cfg.variations ? cfg.variations[variationId] : null;
            if (!vdata) return;

            const data = new FormData();
            data.append('file', fileInput.files[0]);
            data.append('variation_id', variationId);
            data.append('nonce', cfg.nonce);
            fetch(cfg.upload_url, {method:'POST', credentials:'same-origin', body:data})
                .then(r => r.json())
                .then(res => {
                    if (!res.asset_id) return;
                    assetField.value = res.asset_id;
                    state.iw = res.width;
                    state.ih = res.height;
                    state.scale = 1; state.rotation = 0; state.tx = 0; state.ty = 0;
                    state.bounds = vdata.bounds;
                    const img = new Image();
                    img.onload = () => { state.img = img; setupEditor(vdata); draw(); };
                    img.src = URL.createObjectURL(fileInput.files[0]);
                });
        });

        if (finalizeBtn) {
            finalizeBtn.addEventListener('click', () => {
                if (!state.img) return;
                const b = state.bounds;
                const cropW = b.w / state.scale;
                const cropH = b.h / state.scale;
                const cropX = state.iw / 2 - cropW / 2 - state.tx / state.scale;
                const cropY = state.ih / 2 - cropH / 2 - state.ty / state.scale;
                const transform = {
                    scale: state.scale,
                    rotation: state.rotation,
                    tx: state.tx,
                    ty: state.ty,
                    crop: {
                        x: Math.round(cropX),
                        y: Math.round(cropY),
                        w: Math.round(cropW),
                        h: Math.round(cropH)
                    }
                };
                transformField.value = JSON.stringify(transform);
                const variationInput = document.querySelector('input[name="variation_id"]');
                const variationId = variationInput ? parseInt(variationInput.value) : 0;
                fetch(cfg.finalize_url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({
                        nonce: cfg.nonce,
                        asset_id: assetField.value,
                        variation_id: variationId,
                        transform: transformField.value
                    })
                })
                .then(r => r.json())
                .then(res => {
                    if (res && res.thumb_url) {
                        preview.innerHTML = '<img src="' + res.thumb_url + '" alt="" />';
                        if (addBtn) { addBtn.disabled = false; }
                    }
                });
            });
        }
    });
})();

