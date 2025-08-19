(function(){
  const canvas = document.getElementById('llp-canvas');
  if (!canvas) {
    return;
  }
  const ctx = canvas.getContext('2d');
  const fileInput = document.getElementById('llp-upload');
  const finalizeBtn = document.getElementById('llp-finalize');
  const addToCartBtn = document.getElementById('add-to-cart');
  const outputField = document.getElementById('llp_transform');

  let img = new Image();
  let dragging = false;
  let lastX = 0;
  let lastY = 0;
  let imgX = canvas.width / 2;
  let imgY = canvas.height / 2;
  let scale = 1;
  let rotation = 0;

  function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    if (!img.src) return;
    ctx.save();
    ctx.translate(imgX, imgY);
    ctx.rotate(rotation);
    ctx.scale(scale, scale);
    ctx.drawImage(img, -img.width / 2, -img.height / 2);
    ctx.restore();
  }

  fileInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(evt) {
      img = new Image();
      img.onload = draw;
      img.src = evt.target.result;
    };
    reader.readAsDataURL(file);
  });

  canvas.addEventListener('mousedown', function(e) {
    dragging = true;
    lastX = e.offsetX;
    lastY = e.offsetY;
  });

  canvas.addEventListener('mousemove', function(e) {
    if (!dragging) return;
    const dx = e.offsetX - lastX;
    const dy = e.offsetY - lastY;
    imgX += dx;
    imgY += dy;
    lastX = e.offsetX;
    lastY = e.offsetY;
    draw();
  });

  canvas.addEventListener('mouseup', function() { dragging = false; });
  canvas.addEventListener('mouseleave', function() { dragging = false; });

  canvas.addEventListener('wheel', function(e) {
    e.preventDefault();
    if (e.shiftKey) {
      rotation += e.deltaY * 0.01;
    } else {
      const delta = e.deltaY > 0 ? -0.05 : 0.05;
      scale = Math.max(0.1, scale + delta);
    }
    draw();
  });

  finalizeBtn.addEventListener('click', function(e) {
    e.preventDefault();
    const transform = {
      x: imgX,
      y: imgY,
      scale: scale,
      rotation: rotation
    };
    const json = JSON.stringify(transform);
    if (outputField) {
      outputField.value = json;
    }
    if (addToCartBtn) {
      addToCartBtn.disabled = false;
    }
    document.dispatchEvent(new CustomEvent('llp:transform', { detail: json }));
  });
})();
