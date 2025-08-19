(function(){
    const cfg = window.llp_frontend || {};
    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('llp-upload-btn');
        const file = document.getElementById('llp-upload');
        const assetField = document.getElementById('llp_asset_id');
        const transformField = document.getElementById('llp_transform');
        const preview = document.getElementById('llp-preview');
        const addBtn = document.querySelector('form.cart button[type="submit"]');
        if (addBtn) { addBtn.disabled = true; }
        if (!btn || !file) return;
        btn.addEventListener('click', () => {
            if (!file.files.length) return;
            const data = new FormData();
            data.append('file', file.files[0]);
            const variationInput = document.querySelector('input[name="variation_id"]');
            data.append('variation_id', variationInput ? variationInput.value : 0);
            data.append('nonce', cfg.nonce);
            fetch(cfg.upload_url, {method:'POST', credentials:'same-origin', body:data})
                .then(r => r.json())
                .then(res => {
                    if (!res.asset_id) return;
                    assetField.value = res.asset_id;
                    const transform = {scale:1, rotation:0, tx:0, ty:0, crop:{x:0,y:0,w:res.width,h:res.height}};
                    transformField.value = JSON.stringify(transform);
                    return fetch(cfg.finalize_url, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type':'application/json'},
                        body: JSON.stringify({nonce:cfg.nonce, asset_id:res.asset_id, variation_id: variationInput ? variationInput.value : 0, transform: transformField.value})
                    });
                })
                .then(r => r ? r.json() : null)
                .then(res => {
                    if (res && res.thumb_url) {
                        preview.innerHTML = '<img src="' + res.thumb_url + '" style="max-width:150px" />';
                        if (addBtn) { addBtn.disabled = false; }
                    }
                });
        });
    });
})();
