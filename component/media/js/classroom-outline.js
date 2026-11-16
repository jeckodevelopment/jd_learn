export function initClassroomOutline({ documentRef = globalThis.document,
  matchMedia = globalThis.matchMedia?.bind(globalThis),
  ResizeObserverClass = globalThis.ResizeObserver } = {}) {
  const toggle = documentRef?.querySelector('#jlms-classroom-outline-toggle');
  const outline = documentRef?.querySelector('#jlms-classroom-outline');
  const classroom = documentRef?.querySelector('#jlms-classroom');
  if (!toggle || !outline || !classroom) return;

  const mobile = matchMedia?.('(max-width: 48rem)');
  const threshold = 768;
  let compact = false;
  const setExpanded = expanded => {
    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    toggle.textContent = toggle.dataset[expanded ? 'hideLabel' : 'showLabel'];
    outline.hidden = !expanded;
  };
  const syncCompact = narrow => {
    if (compact === narrow && !toggle.hidden) return;
    compact = narrow;
    toggle.hidden = !compact;
    // With a wide container the syllabus remains visible. On narrow layouts
    // it starts collapsed; never use a storage-backed preference.
    setExpanded(!compact);
  };
  toggle.addEventListener('click', () => {
    if (compact) setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
  });

  if (typeof ResizeObserverClass === 'function') {
    const observer = new ResizeObserverClass(entries => {
      const width = entries[0]?.contentRect?.width;
      if (Number.isFinite(width)) syncCompact(width <= threshold);
    });
    observer.observe(classroom);
    // The initial state is safe even before the first ResizeObserver callback.
    syncCompact(classroom.getBoundingClientRect?.().width <= threshold);
  } else if (mobile) {
    const syncViewport = () => syncCompact(mobile.matches);
    syncViewport();
    if (typeof mobile.addEventListener === 'function') mobile.addEventListener('change', syncViewport);
    else mobile.addListener?.(syncViewport);
  }
}

initClassroomOutline();
