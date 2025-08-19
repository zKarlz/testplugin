(function(){
  class CanvasEditor {
    constructor(wrapper) {
      this.wrapper = wrapper;
      this.canvas = wrapper.querySelector('canvas');
      this.ctx = this.canvas.getContext('2d');
      this.fileInput = wrapper.querySelector('#llp-upload');
      this.finalizeButton = wrapper.querySelector('.llp-finalize');
      this.output = wrapper.querySelector('#llp-composite');
      this.addToCart = document.querySelector('form.cart button[type="submit"]');
      this.boundsWidth = parseInt(wrapper.dataset.boundsWidth, 10);
      this.boundsHeight = parseInt(wrapper.dataset.boundsHeight, 10);
      this.aspect = parseFloat(wrapper.dataset.aspect);
      if (this.addToCart) {
        this.addToCart.disabled = true;
      }
      this.bind();
    }

    bind() {
      if (this.fileInput) {
        this.fileInput.addEventListener('change', e => this.loadImage(e));
      }
      if (this.finalizeButton) {
        this.finalizeButton.addEventListener('click', () => this.finalize());
      }
    }

    loadImage(e) {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = evt => {
        const img = new Image();
        img.onload = () => {
          this.drawImageToCanvas(img);
        };
        img.src = evt.target.result;
      };
      reader.readAsDataURL(file);
    }

    drawImageToCanvas(img) {
      this.canvas.width = this.boundsWidth;
      this.canvas.height = this.boundsHeight;
      const targetRatio = this.boundsWidth / this.boundsHeight;
      let sx = 0, sy = 0, sw = img.width, sh = img.height;
      const srcRatio = img.width / img.height;
      if (srcRatio > targetRatio) {
        sw = img.height * targetRatio;
        sx = (img.width - sw) / 2;
      } else {
        sh = img.width / targetRatio;
        sy = (img.height - sh) / 2;
      }
      this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
      this.ctx.drawImage(img, sx, sy, sw, sh, 0, 0, this.canvas.width, this.canvas.height);
    }

    finalize() {
      if (!this.output) return;
      const tempCanvas = document.createElement('canvas');
      tempCanvas.width = this.canvas.width;
      tempCanvas.height = this.canvas.height;
      const tctx = tempCanvas.getContext('2d');
      const base = this.wrapper.querySelector('.llp-base');
      const mockup = this.wrapper.querySelector('.llp-mockup');
      const mask = this.wrapper.querySelector('.llp-mask');
      if (base && base.complete) {
        tctx.drawImage(base, 0, 0, tempCanvas.width, tempCanvas.height);
      }
      tctx.drawImage(this.canvas, 0, 0);
      if (mask && mask.complete) {
        tctx.globalCompositeOperation = 'destination-in';
        tctx.drawImage(mask, 0, 0, tempCanvas.width, tempCanvas.height);
        tctx.globalCompositeOperation = 'source-over';
      }
      if (mockup && mockup.complete) {
        tctx.drawImage(mockup, 0, 0, tempCanvas.width, tempCanvas.height);
      }
      this.output.value = tempCanvas.toDataURL('image/png');
      if (this.addToCart) {
        this.addToCart.disabled = false;
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('llp-customizer');
    if (wrapper) {
      new CanvasEditor(wrapper);
    }
  });
})();
