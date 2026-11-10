export function initClassroomOutline({ documentRef = globalThis.document,
  matchMedia = globalThis.matchMedia?.bind(globalThis) } = {}) {
  const toggle = documentRef?.querySelector('#jlms-classroom-outline-toggle');
  const outline = documentRef?.querySelector('#jlms-classroom-outline');
  if (!toggle || !outline || !matchMedia) return;

  const mobile = matchMedia('(max-width: 48rem)');
  const setExpanded = expanded => {
    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    toggle.textContent = toggle.dataset[expanded ? 'hideLabel' : 'showLabel'];
    outline.hidden = !expanded;
  };
  const syncViewport = () => {
    toggle.hidden = !mobile.matches;
    setExpanded(!mobile.matches);
  };

  toggle.addEventListener('click', () => {
    if (mobile.matches) setExpanded(toggle.getAttribute('aria-expanded') !== 'true');
  });
  syncViewport();
  if (typeof mobile.addEventListener === 'function') mobile.addEventListener('change', syncViewport);
  else mobile.addListener?.(syncViewport);
}

initClassroomOutline();
