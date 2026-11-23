(() => {
  let visitor;
  try {
    visitor = localStorage.getItem('saimed_visitor_id');
    if (!visitor) {
      visitor = crypto.randomUUID
        ? crypto.randomUUID()
        : `visitor-${Date.now()}-${Math.random()}`;
      localStorage.setItem('saimed_visitor_id', visitor);
    }
  } catch {
    visitor = 'anonymous';
  }
  function track(eventType, extra = {}) {
    const body = JSON.stringify({
      eventType,
      visitorId: visitor,
      path: location.pathname,
      referrer: document.referrer,
      ...extra,
    });
    if (
      !navigator.sendBeacon ||
      !navigator.sendBeacon(
        '?api=track',
        new Blob([body], { type: 'application/json' }),
      )
    )
      fetch('?api=track', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body,
        keepalive: true,
      }).catch(() => {});
  }
  try {
    const last = Number(sessionStorage.getItem('saimed_last_view') || 0);
    if (Date.now() - last > 1800000) {
      track('view');
      sessionStorage.setItem('saimed_last_view', Date.now());
    }
  } catch {
    track('view');
  }
  document
    .querySelectorAll('.track-social')
    .forEach((link) =>
      link.addEventListener('click', () =>
        track('social_click', {
          linkKey: link.dataset.key,
          targetLabel: link.dataset.title,
        }),
      ),
    );
  let timer;
  function toast(message) {
    const el = document.querySelector('.public-toast');
    el.textContent = message;
    el.classList.add('public-toast--visible');
    clearTimeout(timer);
    timer = setTimeout(
      () => el.classList.remove('public-toast--visible'),
      2400,
    );
  }
  function closeMenus() {
    document.querySelectorAll('.link-card').forEach((card) => {
      card.classList.remove('link-card--menu-open');
      card.querySelector('.card-menu').setAttribute('aria-expanded', 'false');
      card.querySelector('.card-dropdown').hidden = true;
    });
  }
  document.querySelectorAll('.link-card').forEach((card) => {
    const data = { linkKey: card.dataset.key, targetLabel: card.dataset.title };
    card
      .querySelector('.card-link')
      .addEventListener('click', () => track('link_click', data));
    card.querySelector('.card-menu').addEventListener('click', (e) => {
      e.stopPropagation();
      const open = card.querySelector('.card-dropdown').hidden;
      closeMenus();
      card.querySelector('.card-dropdown').hidden = !open;
      card.classList.toggle('link-card--menu-open', open);
      e.currentTarget.setAttribute('aria-expanded', String(open));
    });
    card.querySelectorAll('[data-action]').forEach((button) =>
      button.addEventListener('click', async () => {
        const url = new URL(card.dataset.url, location.href).href,
          action = button.dataset.action;
        if (action === 'open') {
          track('link_click', data);
          window.open(url, '_blank', 'noopener,noreferrer');
        } else {
          try {
            if (action === 'share' && navigator.share)
              await navigator.share({ title: card.dataset.title, url });
            else await navigator.clipboard.writeText(url);
            toast(
              action === 'copy'
                ? 'Tautan berhasil disalin'
                : 'Tautan siap dibagikan',
            );
            track('link_action', {
              ...data,
              targetLabel:
                (action === 'copy' ? 'Salin: ' : 'Bagikan: ') +
                card.dataset.title,
            });
          } catch {
            toast('Tindakan dibatalkan atau tidak tersedia');
          }
        }
        closeMenus();
      }),
    );
  });
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.link-card')) closeMenus();
  });
})();
