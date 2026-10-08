/**
 * TKA Site Utilities - Media Focal Point Admin Interaction Handler
 * Supports mouse click/drag, touch drag, presets, and keyboard navigation
 */
(function () {
  'use strict';

  let activePicker = null;

  /**
   * Clamp value between min and max
   */
  const clamp = (val, min, max) => Math.max(min, Math.min(max, val));

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

    let changed = false;
    if (inputX && String(inputX.value) !== String(x)) {
      inputX.value = x;
      changed = true;
    }
    if (inputY && String(inputY.value) !== String(y)) {
      inputY.value = y;
      changed = true;
    }

    if (triggerChange && changed) {
      if (inputX) {
        inputX.dispatchEvent(new Event('input', { bubbles: true }));
        inputX.dispatchEvent(new Event('change', { bubbles: true }));
      }
      if (inputY) {
        inputY.dispatchEvent(new Event('input', { bubbles: true }));
        inputY.dispatchEvent(new Event('change', { bubbles: true }));
      }
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
      if (inputX) inputX.dispatchEvent(new Event('change', { bubbles: true }));
      if (inputY) inputY.dispatchEvent(new Event('change', { bubbles: true }));
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
})();
