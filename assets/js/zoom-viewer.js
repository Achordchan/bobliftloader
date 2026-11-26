// A full-screen picture that zooms and moves inside its own frame, so a visitor can read a chart's small print without
// zooming the whole page: pinch, the mouse wheel, a double tap or the buttons zoom; a drag moves the zoomed picture; on a
// fitted picture a swipe or the arrow keys step to the next one (onStep). The page under it never scales or scrolls.
// Ported from the crane site's product and article viewer (Achordchan/bobliftcrane, assets/js/theme.js, bobliftZoomViewer),
// where it went through several rounds of review; the behaviour is kept as it was there.
//
// dialog: the <dialog>; stage: the frame the picture moves in (touch-action: none in news.css); image: the <img> in it;
// level, zoomIn, zoomOut, fitButton: the zoom readout and buttons; loadingStatus: a polite live region announcing a slow load.
// Returns show(event) to open, load() after changing the image's src, and endSwipe() before stepping to another picture.
export const zoomViewer = ({ dialog, stage, image, level, zoomIn, zoomOut, fitButton, nativeMax = 1.5, loadTimeout = 0, loadingStatus = null, onStep = null, onLoad = null, onError = null, onClose = null }) => {
  let fit = 1;
  let max = 2;
  let scale = 1;
  let x = 0;
  let y = 0;
  // Sideways offset of a fitted photo following a swiping finger.
  let swipe = 0;
  let ready = false;
  let previousOverflow = '';
  const pointers = new Map();
  let pinch = null;
  let drag = null;
  let lastTap = null;
  // Fingers that went down on a button on the picture (the photo arrows): tracked for a pinch, never captured or dragged.
  const buttonPointers = new Set();
  // A pinch that began with a finger on a button swallows that button's click.
  let swallowClick = false;
  let swallowTimer = 0;
  let tipTimer = 0;
  let loadToken = 0;
  let settleTimer = 0;
  // The picture after this one sits on the right, or on the left in Arabic.
  const forward = () => (document.documentElement.dir === 'rtl' ? -1 : 1);

  const size = () => [
    image.naturalWidth || Number(image.getAttribute('width')) || 0,
    image.naturalHeight || Number(image.getAttribute('height')) || 0,
  ];
  const local = (event) => {
    const rect = stage.getBoundingClientRect();
    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
  };
  const limit = (value) => Math.min(max, Math.max(fit, value));
  const atFit = () => scale <= fit * 1.001;
  // A button disabled while it has the focus would drop the focus to the page and out of reach of the shortcuts, so the
  // focus moves to the picture first.
  const setControl = (button, off) => {
    if (!button) return;
    if (off && document.activeElement === button) stage.focus({ preventScroll: true });
    button.disabled = off;
  };
  // While no picture is ready (loading, or failed) the zoom controls have nothing to act on: no level, all disabled.
  // The level is read out (aria-live), so it is written only when it changes, not on every move of a swipe or pan.
  const setLevel = (text) => {
    if (level && level.textContent !== text) level.textContent = text;
  };
  const lockControls = () => {
    setLevel('');
    [zoomIn, zoomOut, fitButton].forEach((button) => setControl(button, true));
  };
  // Keeps the picture on screen: centred along an axis where it is smaller than the stage, edge to edge where it is larger.
  const apply = () => {
    const [width, height] = size();
    const stageWidth = stage.clientWidth;
    const stageHeight = stage.clientHeight;
    const shownWidth = width * scale;
    const shownHeight = height * scale;
    x = shownWidth <= stageWidth ? (stageWidth - shownWidth) / 2 : Math.min(0, Math.max(stageWidth - shownWidth, x));
    y = shownHeight <= stageHeight ? (stageHeight - shownHeight) / 2 : Math.min(0, Math.max(stageHeight - shownHeight, y));
    image.style.transform = `translate(${x + swipe}px, ${y}px) scale(${scale})`;
    // Zoomed in, the photo arrows step aside (CSS hides [data-zoom-hide]) so a tap meant for the detail cannot change photos;
    // a focused one hands focus to the stage first. At fit, while loading and after a failure they are back.
    const zoomed = ready && !atFit();
    if (zoomed && document.activeElement?.matches?.('[data-zoom-hide]') && stage.contains(document.activeElement)) stage.focus({ preventScroll: true });
    stage.classList.toggle('is-zoomed', zoomed);
    // The controls follow a picture that is showing; while none is, lockControls() keeps them off.
    if (!ready) return;
    setLevel(`${Math.round((scale / fit) * 100)}%`);
    [[zoomIn, scale >= max * 0.999], [zoomOut, atFit()], [fitButton, false]].forEach(([button, off]) => setControl(button, off));
  };
  // Fit: the whole picture on screen, never blown up past its own pixels. Zoom goes to 4x the fit, and at least to
  // nativeMax times the picture's own size, so a phone can read the smallest figures.
  const measure = () => {
    const [width, height] = size();
    if (!width || !height || !stage.clientWidth || !stage.clientHeight) return false;
    image.style.width = `${width}px`;
    image.style.height = `${height}px`;
    fit = Math.min(stage.clientWidth / width, stage.clientHeight / height, 1);
    max = Math.max(fit * 4, nativeMax);
    return true;
  };
  const reset = () => {
    if (!measure()) return;
    scale = fit;
    swipe = 0;
    ready = true;
    apply();
    image.classList.add('is-ready');
  };
  const zoomAt = (next, px, py) => {
    if (!ready) return;
    const target = limit(next);
    // Zoomed in during a swipe (a wheel turn or + while the mouse is still down): the photo zooms from where it shows,
    // and the stroke goes on as a pan of the zoomed photo, so letting go never changes photos.
    const fromSwipe = Boolean(drag?.swipe) && target > fit * 1.001;
    if (fromSwipe) {
      clearTimeout(settleTimer);
      image.classList.remove('is-settling');
      x += swipe;
      swipe = 0;
    }
    const ratio = target / scale;
    x = px - (px - x) * ratio;
    y = py - (py - y) * ratio;
    scale = target;
    apply();
    if (fromSwipe) {
      const point = [...pointers.values()][0] || { x: drag.x + drag.dx, y: drag.y };
      drag = { ...drag, x: point.x, y: point.y, dx: 0, fromX: x, fromY: y, moved: true, swipe: false };
    }
  };
  const zoomCentre = (factor) => zoomAt(scale * factor, stage.clientWidth / 2, stage.clientHeight / 2);
  const startPinch = () => {
    const [a, b] = [...pointers.values()];
    const midX = (a.x + b.x) / 2;
    const midY = (a.y + b.y) / 2;
    // Begun before the picture was ready, it starts again from the fitted picture and the fingers where they are by then.
    pinch = { distance: Math.hypot(a.x - b.x, a.y - b.y) || 1, scale, contentX: (midX - x) / scale, contentY: (midY - y) / scale, pending: !ready };
    drag = null;
  };
  // A finger on a fitted photo swipes it toward the next one; anywhere else it drags the picture.
  const startDrag = (point) => {
    // dx: the finger's own sideways travel, kept apart from the picture's offset, which a load resets.
    // pending: put down before the picture was ready, so there was nothing yet to pan from.
    drag = { x: point.x, y: point.y, dx: 0, fromX: x, fromY: y, moved: false, swipe: Boolean(onStep) && (!ready || atFit()), pending: !ready };
  };
  const settle = () => {
    image.classList.add('is-settling');
    swipe = 0;
    apply();
    clearTimeout(settleTimer);
    settleTimer = setTimeout(() => image.classList.remove('is-settling'), 220);
  };

  // Shows the picture now in the image's src once it has loaded, fitted. A newer call (the next photo) wins.
  // The listeners of the load in progress; a newer load or the outcome removes both, so none pile up on the image.
  let detachLoad = () => {};
  // A picture that takes more than a moment is announced (loadingStatus, a polite live region holding its own text in
  // data-loading-text); the announcement is emptied when the load ends either way.
  // Whether the src has been set again since the picture last failed. A broken picture reports complete (and its old
  // address) even while a new request for it is under way, so the "failed already" shortcut below is only taken when
  // nothing has asked again since that failure (a broken drawing opened twice); otherwise the new request is waited for.
  let srcTouched = false;
  const srcWatch = typeof MutationObserver === 'function' ? new MutationObserver(() => { srcTouched = true; }) : null;
  srcWatch?.observe(image, { attributes: true, attributeFilter: ['src', 'srcset'] });
  const quiet = () => {
    if (loadingStatus && loadingStatus.textContent) loadingStatus.textContent = '';
  };
  const load = () => {
    const token = ++loadToken;
    // A src set just before this call is still in the observer's queue: take it now.
    if (srcWatch?.takeRecords().length) srcTouched = true;
    detachLoad();
    ready = false;
    swipe = 0;
    // A gesture under way carries on across the load (a fallback size, or a photo that finished while the finger was
    // down): a swipe keeps its travel and a pinch starts afresh once the picture is ready. Panning the old picture ends.
    if (drag && !drag.swipe) drag = null;
    if (pinch) pinch.pending = true;
    lastTap = null;
    lockControls();
    stage.classList.remove('is-zoomed');
    image.classList.remove('is-ready', 'is-settling');
    stage.classList.add('is-loading');
    image.loading = 'eager';
    // Removes only this load's own listeners, so a late callback never strips a newer load's.
    let stall = 0;
    let frame = 0;
    // This load's own announcement delay: cancelled with the load, so a photo swiped past within the delay never
    // announces itself over the next one.
    let announce = 0;
    // Ends this load's own waiting (listeners, frame, stall and announcement timers). The shared announcement is this
    // load's to clear only while it is still the current one, so a late callback of a replaced load never silences the
    // newer load's.
    const detach = () => {
      image.removeEventListener('load', done);
      image.removeEventListener('error', failed);
      cancelAnimationFrame(frame);
      clearTimeout(stall);
      clearTimeout(announce);
      if (token === loadToken) quiet();
      if (detachLoad === detach) detachLoad = () => {};
    };
    // An event from an earlier request (one finishing after the src changed) is not this load's: the picture asked for is
    // then not the one showing (currentSrc), or not finished yet (complete), depending on the browser. It is passed over
    // and the listeners wait for the picture asked for.
    // With a srcset or inside <picture> (a drawing could be) currentSrc is the browser's pick rather than src, and the viewer
    // never changes such a picture's source, so only completeness counts there.
    const asked = () => image.hasAttribute('srcset') || image.parentElement?.tagName === 'PICTURE' || image.currentSrc === image.src;
    const stale = (event) => event instanceof Event && (!asked() || !image.complete);
    const done = (event) => {
      // A late load event from the previous photo while the one asked for has already failed: its own error decides.
      if (stale(event) || (event instanceof Event && !image.naturalWidth)) return;
      detach();
      if (token !== loadToken || !dialog.open) return;
      stage.classList.remove('is-loading');
      reset();
      if (onLoad) onLoad();
    };
    const failed = (event) => {
      if (stale(event)) return;
      // A late error from the previous photo while the one asked for has already loaded: that is a success.
      if (event instanceof Event && image.naturalWidth) {
        done();
        return;
      }
      srcTouched = false;
      detach();
      // Closed meanwhile: no fallback download for a viewer nobody sees; opening again asks for the picture anew.
      if (token !== loadToken || !dialog.open) return;
      if (onError) onError();
      else stage.classList.remove('is-loading');
    };
    // complete and naturalWidth speak for the picture the image shows (currentSrc), which right after a new src can still be
    // the previous one: they count only when that is the picture asked for. Otherwise the load or error event decides.
    const settled = image.complete && asked();
    // The answer already in hand is given on the next frame; a newer load before then cancels it (detachLoad).
    if (settled && image.naturalWidth) {
      frame = requestAnimationFrame(() => done());
      detachLoad = detach;
    // Already failed (a broken picture opened again): no event will come, so report it now.
    } else if (settled && image.getAttribute('src') && !srcTouched) {
      frame = requestAnimationFrame(() => failed());
      detachLoad = detach;
    } else {
      image.addEventListener('load', done);
      image.addEventListener('error', failed);
      // A response that starts and then stalls fires neither event. Where the caller has a fallback (the photos: their
      // smaller size, then a message), loadTimeout ms of it count as failed; without one (the drawings) the picture is
      // waited for as long as it takes, so a slow one still shows when it arrives.
      if (loadTimeout > 0) stall = setTimeout(failed, loadTimeout);
      if (loadingStatus) {
        announce = setTimeout(() => {
          if (token === loadToken && dialog.open) loadingStatus.textContent = loadingStatus.dataset.loadingText || '';
        }, 600);
      }
      detachLoad = detach;
    }
  };
  // event: the click that opened it. A tap or a mouse click (detail > 0) gives the picture the focus without the keyboard
  // ring; Enter or Space on the button keeps the ring.
  const show = (event) => {
    previousOverflow = document.documentElement.style.overflow;
    document.documentElement.style.overflow = 'hidden';
    // The picture opens at the fit, where zoom out does nothing: disabled before showModal() so it never takes the focus
    // and drops it to the page when the fit disables it. The focus goes to the picture, ready for + - 0 and the arrows.
    if (zoomOut) zoomOut.disabled = true;
    if (zoomIn) zoomIn.disabled = false;
    dialog.showModal();
    stage.focus({ preventScroll: true, focusVisible: !(event?.detail > 0) });
    // The hint shows for a few seconds, or until the first touch, wheel turn or click on the picture.
    stage.classList.remove('is-touched');
    clearTimeout(tipTimer);
    tipTimer = setTimeout(() => stage.classList.add('is-touched'), 4000);
    load();
  };
  dialog.addEventListener('close', () => {
    document.documentElement.style.overflow = previousOverflow;
    // A drag cut short by Escape never sees its release: its grabbing cursor goes with the viewer.
    stage.classList.remove('is-dragging');
    // A load still running has nobody to show it to: its listeners and stall timer go; opening again starts a new one.
    detachLoad();
    pointers.clear();
    buttonPointers.clear();
    clearTimeout(swallowTimer);
    swallowClick = false;
    pinch = null;
    drag = null;
    lastTap = null;
    if (onClose) onClose();
  });
  zoomIn?.addEventListener('click', () => zoomCentre(1.5));
  zoomOut?.addEventListener('click', () => zoomCentre(1 / 1.5));
  // Back to the fit, for a picture that is showing; a load in progress fits its own picture when it arrives, and resetting
  // before that could show the previous picture's measure under the new one.
  const refit = () => {
    if (ready) reset();
  };
  fitButton?.addEventListener('click', refit);
  dialog.addEventListener('keydown', (event) => {
    if (event.ctrlKey || event.metaKey || event.altKey) return;
    if (event.key === '+' || event.key === '=') zoomCentre(1.5);
    else if (event.key === '-' || event.key === '_') zoomCentre(1 / 1.5);
    else if (event.key === '0') refit();
    else if (onStep && (!ready || atFit()) && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
      // A fitted photo, or one still loading or failed, has nothing to pan, so left and right step through the photos.
      onStep(event.key === 'ArrowRight' ? forward() : -forward());
    } else if (event.key.startsWith('Arrow') && ready) {
      // Arrow keys move the picture a fifth of the screen, so a keyboard can reach every edge once it is zoomed.
      const stepX = stage.clientWidth * 0.2;
      const stepY = stage.clientHeight * 0.2;
      if (event.key === 'ArrowLeft') x += stepX;
      else if (event.key === 'ArrowRight') x -= stepX;
      else if (event.key === 'ArrowUp') y += stepY;
      else if (event.key === 'ArrowDown') y -= stepY;
      else return;
      apply();
    } else return;
    event.preventDefault();
  });
  window.addEventListener('resize', () => {
    if (!dialog.open || !ready) return;
    const wasFit = atFit();
    if (!measure()) return;
    if (wasFit) scale = fit;
    scale = limit(scale);
    apply();
  });

  stage.addEventListener('wheel', (event) => {
    event.preventDefault();
    stage.classList.add('is-touched');
    const point = local(event);
    // A trackpad pinch arrives as a wheel event with ctrlKey and small deltas; a mouse wheel notch is about 100 px.
    const delta = event.deltaY * (event.deltaMode === 1 ? 16 : 1);
    zoomAt(scale * Math.exp(-delta * (event.ctrlKey ? 0.01 : 0.002)), point.x, point.y);
  }, { passive: false });
  stage.addEventListener('pointerdown', (event) => {
    // Fingers are tracked from the start, even while the picture loads: a swipe can leave a photo still loading, and a pinch
    // or pan begun on the spinner carries on once the picture shows (zoom and pan themselves wait for it).
    if (!pointers.size) {
      // A new press of any kind (a mouse on an arrow included): the last pinch's click window ends here, so its timer cannot
      // clear this gesture's guard and a fresh click is never swallowed.
      clearTimeout(swallowTimer);
      swallowClick = false;
    }
    if (event.pointerType === 'mouse' && event.button !== 0) return;
    // Buttons on the picture (the photo arrows) keep their own clicks, so a finger on one is never captured or dragged;
    // it still counts toward a pinch with a finger on the picture. A mouse on a button is left alone.
    const onButton = Boolean(event.target.closest('button'));
    if (onButton && event.pointerType === 'mouse') return;
    if (onButton) buttonPointers.add(event.pointerId);
    else stage.setPointerCapture?.(event.pointerId);
    stage.classList.add('is-touched');
    pointers.set(event.pointerId, local(event));
    if (pointers.size === 2) {
      if (swipe) {
        // A fitted photo already pulled sideways: the pinch starts from where it shows, with no spring-back under the fingers.
        clearTimeout(settleTimer);
        image.classList.remove('is-settling');
        x += swipe;
        swipe = 0;
      }
      startPinch();
      if (buttonPointers.size) swallowClick = true;
    } else if (pointers.size === 1 && !onButton) startDrag(local(event));
  });
  stage.addEventListener('click', (event) => {
    // Only a pointer's click (detail > 0) can be the pinch's leftover; Enter or Space on an arrow (detail 0) always steps.
    if (!swallowClick || !event.detail || !event.target.closest('button')) return;
    swallowClick = false;
    event.preventDefault();
    event.stopImmediatePropagation();
  }, true);
  stage.addEventListener('pointermove', (event) => {
    if (!pointers.has(event.pointerId)) return;
    const point = local(event);
    pointers.set(event.pointerId, point);
    if (pinch && pointers.size >= 2) {
      if (!ready) return;
      if (pinch.pending) {
        startPinch();
        return;
      }
      const [a, b] = [...pointers.values()];
      scale = limit(pinch.scale * (Math.hypot(a.x - b.x, a.y - b.y) / pinch.distance));
      // The point of the picture that was under the fingers stays under them, so a pinch can also pan.
      x = (a.x + b.x) / 2 - pinch.contentX * scale;
      y = (a.y + b.y) / 2 - pinch.contentY * scale;
      apply();
    } else if (drag) {
      if (!drag.swipe && !ready) return;
      if (!drag.swipe && drag.pending) {
        // The picture has just shown under a finger that was already down: the pan starts here, from the fitted picture.
        drag = { ...drag, x: point.x, y: point.y, dx: 0, fromX: x, fromY: y, moved: true, pending: false };
        stage.classList.add('is-dragging');
        return;
      }
      const dx = point.x - drag.x;
      const dy = point.y - drag.y;
      drag.dx = dx;
      if (!drag.moved && Math.hypot(dx, dy) > 6) {
        drag.moved = true;
        stage.classList.add('is-dragging');
      }
      if (drag.moved) {
        // The stroke's overall direction so far decides, not its first few pixels: while it is mostly upright the photo
        // stays put, and once it runs sideways it follows (the release judges the whole stroke the same way).
        if (drag.swipe) swipe = Math.abs(dx) >= Math.abs(dy) ? dx : 0;
        else {
          x = drag.fromX + dx;
          y = drag.fromY + dy;
        }
        apply();
      }
    }
  });
  const release = (event) => {
    if (!pointers.has(event.pointerId)) return;
    const point = pointers.get(event.pointerId);
    const wasPinch = Boolean(pinch);
    const fromButton = buttonPointers.delete(event.pointerId);
    pointers.delete(event.pointerId);
    // The click that follows the last finger of a pinch is swallowed only then; a later keyboard press on an arrow still works.
    if (!pointers.size && swallowClick) swallowTimer = setTimeout(() => { swallowClick = false; }, 400);
    stage.classList.remove('is-dragging');
    if (pointers.size >= 2) {
      startPinch();
      return;
    }
    pinch = null;
    if (wasPinch) {
      // The pinch is over: the picture goes back inside its bounds (centred at fit), which also undoes a sideways pull the
      // pinch took over and never moved. Smoothly, unless a finger left on the picture drags on from here.
      if (pointers.size === 1 && !buttonPointers.has([...pointers.keys()][0])) apply();
      else settle();
    }
    if (pointers.size === 1) {
      lastTap = null;
      // The finger left on a button does nothing more; one left on the picture carries on dragging from where it is.
      if (buttonPointers.has([...pointers.keys()][0])) {
        drag = null;
        return;
      }
      startDrag([...pointers.values()][0]);
      drag.moved = true;
      drag.swipe = false;
      lastTap = null;
      return;
    }
    // A plain press on a button: its own click does the work.
    if (fromButton) {
      drag = null;
      lastTap = null;
      return;
    }
    // The stroke ends where the finger lifts: a fast flick can pass the move threshold, or turn upright, after its last move.
    if (drag && event.type === 'pointerup') {
      const end = local(event);
      const dx = end.x - drag.x;
      const dy = end.y - drag.y;
      if (!drag.moved && Math.hypot(dx, dy) > 6) drag.moved = true;
      if (drag.swipe && drag.moved && Math.abs(dy) > Math.abs(dx)) {
        // Mostly upright overall: no photo change, the picture springs back.
        drag = null;
        lastTap = null;
        settle();
        return;
      }
      drag.dx = dx;
      if (drag.moved && !drag.swipe && ready && !drag.pending) {
        x = drag.fromX + dx;
        y = drag.fromY + dy;
        apply();
      }
    }
    if (drag && drag.swipe && drag.moved) {
      // A swipe past a fifth of the screen (at most 80 px) moves to the neighbouring photo; a shorter one springs back.
      const distance = drag.dx;
      drag = null;
      lastTap = null;
      if (event.type === 'pointerup' && Math.abs(distance) > Math.min(80, stage.clientWidth * 0.2)) onStep(distance < 0 ? forward() : -forward());
      else settle();
      return;
    }
    const tapped = event.type === 'pointerup' && !wasPinch && drag && !drag.moved && ready;
    drag = null;
    if (!tapped) {
      lastTap = null;
      return;
    }
    // Double tap (or double click): zoom in 2.5x on that spot, or back to the fit when already zoomed.
    const now = Date.now();
    if (lastTap && now - lastTap.time < 350 && Math.hypot(point.x - lastTap.x, point.y - lastTap.y) < 30) {
      lastTap = null;
      if (scale > fit * 1.001) reset();
      else zoomAt(fit * 2.5, point.x, point.y);
    } else {
      lastTap = { time: now, x: point.x, y: point.y };
    }
  };
  stage.addEventListener('pointerup', release);
  stage.addEventListener('pointercancel', release);
  // A change of photo ends a swipe still under way (an arrow key pressed mid-drag), so letting go does not step again.
  const endSwipe = () => {
    if (drag?.swipe) {
      drag = null;
      stage.classList.remove('is-dragging');
    }
    swipe = 0;
  };
  return { show, load, endSwipe };
};
