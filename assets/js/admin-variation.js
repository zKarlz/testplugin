// Interactive editor for variation bounds and mask
// Displays base image with optional mask overlay and draggable/rotatable bounds.
// Syncs DPI, aspect ratio and min resolution values with corresponding inputs.

document.addEventListener('DOMContentLoaded', () => {
    const baseImage = document.getElementById('llp-base-image');
    const maskImage = document.getElementById('llp-mask');
    const bounds = document.getElementById('llp-bounds');

    if (!baseImage || !bounds) {
        return;
    }

    // Optional mask overlay
    if (maskImage && maskImage.src) {
        maskImage.style.position = 'absolute';
        maskImage.style.top = '0';
        maskImage.style.left = '0';
        maskImage.style.pointerEvents = 'none';
    }

    // Ensure container is positioned
    const container = bounds.parentElement;
    container.style.position = 'relative';

    // Basic styles
    baseImage.style.display = 'block';
    bounds.style.position = 'absolute';
    bounds.style.border = '2px dashed #0073aa';
    bounds.style.cursor = 'move';
    bounds.style.top = '0px';
    bounds.style.left = '0px';
    bounds.style.width = baseImage.clientWidth + 'px';
    bounds.style.height = baseImage.clientHeight + 'px';

    // Rotation handle
    const rotateHandle = document.createElement('div');
    rotateHandle.style.position = 'absolute';
    rotateHandle.style.width = '12px';
    rotateHandle.style.height = '12px';
    rotateHandle.style.background = '#0073aa';
    rotateHandle.style.borderRadius = '50%';
    rotateHandle.style.right = '-16px';
    rotateHandle.style.top = '-16px';
    rotateHandle.style.cursor = 'grab';
    bounds.appendChild(rotateHandle);

    const dpiInput = document.getElementById('llp_dpi');
    const aspectInput = document.getElementById('llp_aspect_ratio');
    const minResInput = document.getElementById('llp_min_resolution');

    const xInput = document.getElementById('llp_bound_x');
    const yInput = document.getElementById('llp_bound_y');
    const wInput = document.getElementById('llp_bound_width');
    const hInput = document.getElementById('llp_bound_height');
    const rInput = document.getElementById('llp_bound_rotation');

    let dragStart = null;
    let rotateStart = null;

    function updateHiddenInputs() {
        const rect = bounds.getBoundingClientRect();
        const containerRect = container.getBoundingClientRect();
        const dpi = parseInt(dpiInput && dpiInput.value, 10) || 72;

        const width = rect.width;
        const height = rect.height;
        const aspect = width && height ? (width / height).toFixed(2) : '';
        const minRes = Math.round(width * dpi) + 'x' + Math.round(height * dpi);

        if (xInput) xInput.value = rect.left - containerRect.left;
        if (yInput) yInput.value = rect.top - containerRect.top;
        if (wInput) wInput.value = width;
        if (hInput) hInput.value = height;

        const transform = bounds.style.transform;
        const match = /rotate\(([-0-9.]+)deg\)/.exec(transform);
        if (match && rInput) {
            rInput.value = parseFloat(match[1]);
        }

        if (aspectInput) aspectInput.value = aspect;
        if (minResInput) minResInput.value = minRes;
    }

    function parseAspect(val) {
        if (!val) return null;
        const parts = val.split(':');
        if (parts.length === 2) {
            const w = parseFloat(parts[0]);
            const h = parseFloat(parts[1]);
            if (w && h) return w / h;
        }
        const num = parseFloat(val);
        return isNaN(num) ? null : num;
    }

    function applyInputsToBounds() {
        const dpi = parseInt(dpiInput && dpiInput.value, 10) || 72;
        const aspect = parseAspect(aspectInput && aspectInput.value);
        const minRes = (minResInput && minResInput.value ? minResInput.value.split('x').map(v => parseInt(v, 10)) : null);

        if (minRes && minRes[0] && minRes[1]) {
            const width = minRes[0] / dpi;
            const height = minRes[1] / dpi;
            bounds.style.width = width + 'px';
            bounds.style.height = height + 'px';
        }

        if (aspect && bounds.clientHeight) {
            bounds.style.width = (bounds.clientHeight * aspect) + 'px';
        }

        updateHiddenInputs();
    }

    // Dragging
    bounds.addEventListener('mousedown', e => {
        if (e.target === rotateHandle) return;
        dragStart = {
            x: e.clientX,
            y: e.clientY,
            left: parseFloat(bounds.style.left),
            top: parseFloat(bounds.style.top)
        };
        document.addEventListener('mousemove', onDrag);
        document.addEventListener('mouseup', endDrag);
    });

    function onDrag(e) {
        if (!dragStart) return;
        const dx = e.clientX - dragStart.x;
        const dy = e.clientY - dragStart.y;
        bounds.style.left = dragStart.left + dx + 'px';
        bounds.style.top = dragStart.top + dy + 'px';
        updateHiddenInputs();
    }

    function endDrag() {
        dragStart = null;
        document.removeEventListener('mousemove', onDrag);
        document.removeEventListener('mouseup', endDrag);
    }

    // Rotating
    rotateHandle.addEventListener('mousedown', e => {
        rotateStart = {
            x: e.clientX,
            y: e.clientY,
            angle: getCurrentRotation()
        };
        document.addEventListener('mousemove', onRotate);
        document.addEventListener('mouseup', endRotate);
    });

    function getCurrentRotation() {
        const match = /rotate\(([-0-9.]+)deg\)/.exec(bounds.style.transform);
        return match ? parseFloat(match[1]) : 0;
    }

    function onRotate(e) {
        if (!rotateStart) return;
        const dx = e.clientX - rotateStart.x;
        const dy = e.clientY - rotateStart.y;
        const angle = rotateStart.angle + dx * 0.5 + dy * 0.5; // simple calc
        bounds.style.transform = `rotate(${angle}deg)`;
        updateHiddenInputs();
    }

    function endRotate() {
        rotateStart = null;
        document.removeEventListener('mousemove', onRotate);
        document.removeEventListener('mouseup', endRotate);
    }

    // Sync when inputs change
    [dpiInput, aspectInput, minResInput].forEach(input => {
        if (input) {
            input.addEventListener('change', applyInputsToBounds);
        }
    });

    applyInputsToBounds();
    updateHiddenInputs();
});

