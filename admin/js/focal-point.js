/**
 * TKA Site Utilities - Media Focal Point Admin Interaction Handler
 * Supports mouse click/drag, touch drag, presets, and keyboard navigation
 */
(function () {
  'use strict';

  let activePicker = null;
  const sessionFocalPoints = new Map();

  /**
   * Clamp value between min and max
   */
  const clamp = (val, min, max) => Math.max(min, Math.min(max, val));

  /**
   * Apply coordinates visually to a picker wrapper without triggering a save.
   */
  const applyVisualCoordinates = (wrapper, x, y) => {
    const picker = wrapper.querySelector('.focal-point-picker');
    const reticle = wrapper.querySelector('.focal-point-reticle');
    const inputX = wrapper.querySelector('.focal-point-input-x');
    const inputY = wrapper.querySelector('.focal-point-input-y');
    const badgeX = wrapper.querySelector('.coord-x');
    const badgeY = wrapper.querySelector('.coord-y');

    if (reticle) {
      reticle.style.left = `${x}%`;
      reticle.style.top = `${y}%`;
    }
    if (badgeX) badgeX.textContent = `${x}%`;
    if (badgeY) badgeY.textContent = `${y}%`;
    if (inputX) {
      inputX.value = x;
      inputX.setAttribute('value', String(x));
    }
    if (inputY) {
      inputY.value = y;
      inputY.setAttribute('value', String(y));
    }
    if (picker) {
      picker.setAttribute('aria-valuenow', `${x}, ${y}`);
    }
  };

  /**
   * Sync updated focal point with WordPress Backbone Attachment model cache
   * and persist immediately via dedicated AJAX endpoint.
   */
  const persistFocalPoint = (picker, x, y) => {
    const wrapper = picker.closest('.focal-point-field-wrapper');
    if (!wrapper) return;

    const attachmentId = parseInt(wrapper.dataset.attachmentId, 10);
    const nonce = wrapper.dataset.nonce || '';
    if (!attachmentId) return;

    sessionFocalPoints.set(attachmentId, { x, y });

    // 1. Immediately update in-memory Backbone attachment model so switching away
    // and back in the Media Modal never re-renders stale compat.item HTML.
    const updateBackboneModel = (serverFocal, serverCompat) => {
      if (!window.wp || !window.wp.media || !window.wp.media.model) {
        return;
      }
      const models = [];
      if (window.wp.media.model.Attachments && window.wp.media.model.Attachments.all) {
        const m1 = window.wp.media.model.Attachments.all.get(attachmentId);
        if (m1) models.push(m1);
      }
      if (window.wp.media.model.Attachment && typeof window.wp.media.model.Attachment.get === 'function') {
        const m2 = window.wp.media.model.Attachment.get(attachmentId);
        if (m2 && !models.includes(m2)) models.push(m2);
      }

      const focalObj = serverFocal || {
        x,
        y,
        css: `${x}% ${y}%`,
        style: `object-position: ${x}% ${y}%; --focal-x: ${x}%; --focal-y: ${y}%;`,
        has_focal_point: true,
      };

      const compatForm = wrapper.closest('form.compat-item') || wrapper.closest('.compat-item');

      models.forEach((model) => {
        model.set('focal_point', focalObj);

        if (serverCompat && serverCompat.item) {
          model.set('compat', serverCompat);
        } else if (compatForm) {
          const currentCompat = model.get('compat');
          if (currentCompat && typeof currentCompat === 'object') {
            model.set('compat', Object.assign({}, currentCompat, { item: compatForm.innerHTML }));
          }
        }
      });
    };

    applyVisualCoordinates(wrapper, x, y);
    updateBackboneModel();

    // 2. Persist to database via dedicated AJAX endpoint
    const ajaxUrl = window.ajaxurl || '/wp-admin/admin-ajax.php';
    if (nonce && window.fetch) {
      const formData = new FormData();
      formData.append('action', 'tka_save_focal_point');
      formData.append('attachment_id', String(attachmentId));
      formData.append('nonce', nonce);
      formData.append('x', String(x));
      formData.append('y', String(y));

      window.fetch(ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
      })
        .then((res) => res.json())
        .then((resp) => {
          if (resp && resp.success && resp.data) {
            updateBackboneModel(resp.data.focal_point, resp.data.compat);
          }
        })
        .catch(() => {});
    }
  };

  /**
   * Update coordinates and sync with DOM inputs and reticle
   */
  const setCoordinates = (picker, x, y, triggerChange = true) => {
    x = clamp(Math.round(x), 0, 100);
    y = clamp(Math.round(y), 0, 100);

    const reticle = picker.querySelector('.focal-point-reticle');
    const inputX = picker.parentElement.querySelector('.focal-point-input-x');
    const inputY = picker.parentElement.querySelector('.focal-point-input-y');
    const badgeX = picker.parentElement.querySelector('.coord-x');
    const badgeY = picker.parentElement.querySelector('.coord-y');

    if (reticle) {
      reticle.style.left = `${x}%`;
      reticle.style.top = `${y}%`;
    }

    if (badgeX) badgeX.textContent = `${x}%`;
    if (badgeY) badgeY.textContent = `${y}%`;

    if (inputX) {
      inputX.value = x;
      inputX.setAttribute('value', String(x));
    }
    if (inputY) {
      inputY.value = y;
      inputY.setAttribute('value', String(y));
    }

    if (triggerChange) {
      persistFocalPoint(picker, x, y);
    }
  };

  /**
   * Calculate point from client coordinates relative to the picker element
   */
  const updateFromPointer = (e, picker, triggerChange = false) => {
    const rect = picker.getBoundingClientRect();
    if (rect.width === 0 || rect.height === 0) return;

    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;

    const x = ((clientX - rect.left) / rect.width) * 100;
    const y = ((clientY - rect.top) / rect.height) * 100;

    setCoordinates(picker, x, y, triggerChange);
  };

  // Pointer start (Mouse & Touch)
  const onPointerStart = (e) => {
    const picker = e.target.closest('.focal-point-picker');
    if (!picker) return;

    e.preventDefault();
    activePicker = picker;
    picker.classList.add('is-dragging');
    picker.focus();

    updateFromPointer(e, picker, false);
  };

  // Pointer move
  const onPointerMove = (e) => {
    if (!activePicker) return;
    e.preventDefault();
    updateFromPointer(e, activePicker, false);
  };

  // Pointer end
  const onPointerEnd = (e) => {
    if (!activePicker) return;
    const picker = activePicker;
    activePicker = null;
    picker.classList.remove('is-dragging');

    // Trigger change event to persist on release
    const clientX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
    const clientY = e.changedTouches ? e.changedTouches[0].clientY : e.clientY;
    if (clientX !== undefined && clientY !== undefined) {
      updateFromPointer(e, picker, true);
    } else {
      const inputX = picker.parentElement.querySelector('.focal-point-input-x');
      const inputY = picker.parentElement.querySelector('.focal-point-input-y');
      const x = inputX ? parseFloat(inputX.value) || 50 : 50;
      const y = inputY ? parseFloat(inputY.value) || 50 : 50;
      persistFocalPoint(picker, x, y);
    }
  };

  // Click on presets / reset buttons
  const onButtonClick = (e) => {
    const btn = e.target.closest('.focal-point-btn');
    if (!btn) return;

    e.preventDefault();
    const container = btn.closest('.focal-point-field-wrapper');
    if (!container) return;

    const picker = container.querySelector('.focal-point-picker');
    if (!picker) return;

    const targetX = parseFloat(btn.dataset.presetX ?? 50);
    const targetY = parseFloat(btn.dataset.presetY ?? 50);

    setCoordinates(picker, targetX, targetY, true);
  };

  // Keyboard accessibility
  const onKeyDown = (e) => {
    const picker = e.target.closest('.focal-point-picker');
    if (!picker) return;

    const step = e.shiftKey ? 5 : 1;
    const inputX = picker.parentElement.querySelector('.focal-point-input-x');
    const inputY = picker.parentElement.querySelector('.focal-point-input-y');
    if (!inputX || !inputY) return;

    let currentX = parseFloat(inputX.value) || 50;
    let currentY = parseFloat(inputY.value) || 50;
    let handled = false;

    switch (e.key) {
      case 'ArrowLeft':
        currentX -= step;
        handled = true;
        break;
      case 'ArrowRight':
        currentX += step;
        handled = true;
        break;
      case 'ArrowUp':
        currentY -= step;
        handled = true;
        break;
      case 'ArrowDown':
        currentY += step;
        handled = true;
        break;
      case 'Home':
        currentX = 50;
        currentY = 50;
        handled = true;
        break;
    }

    if (handled) {
      e.preventDefault();
      setCoordinates(picker, currentX, currentY, true);
    }
  };

  // Global listeners with delegation
  document.addEventListener('mousedown', onPointerStart, { passive: false });
  document.addEventListener('mousemove', onPointerMove, { passive: false });
  document.addEventListener('mouseup', onPointerEnd);

  document.addEventListener('touchstart', onPointerStart, { passive: false });
  document.addEventListener('touchmove', onPointerMove, { passive: false });
  document.addEventListener('touchend', onPointerEnd);

  document.addEventListener('click', onButtonClick);
  document.addEventListener('keydown', onKeyDown);

  // Observe dynamically rendered Media Modal attachment details to restore
  // in-session focal point coordinates even if Backbone re-renders cached HTML.
  if (window.MutationObserver && document.body) {
    const observer = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        for (const node of mutation.addedNodes) {
          if (node.nodeType !== 1) continue;
          const wrappers = node.matches && node.matches('.focal-point-field-wrapper')
            ? [node]
            : (node.querySelectorAll ? Array.from(node.querySelectorAll('.focal-point-field-wrapper')) : []);
          wrappers.forEach((wrapper) => {
            const id = parseInt(wrapper.dataset.attachmentId, 10);
            if (id && sessionFocalPoints.has(id)) {
              const coords = sessionFocalPoints.get(id);
              applyVisualCoordinates(wrapper, coords.x, coords.y);
            }
          });
        }
      }
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }
})();
