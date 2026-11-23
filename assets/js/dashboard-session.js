(() => {
  const timer = document.querySelector('.session-timer');
  const expiredLink = document.querySelector('.session-expired');
  const photo = document.querySelector('.account-avatar img');
  if (photo) {
    photo.addEventListener('error', () => photo.remove());
    if (photo.complete && photo.naturalWidth === 0) photo.remove();
  }
  if (!timer) return;

  // Server menghitung batas terdekat: idle 30 menit, JWT, atau token SSO.
  // Hitung selisih dari saat halaman dimuat, bukan jam absolut browser.
  const initial = Math.max(0, Number(timer.dataset.remaining) || 0);
  const loadedAt = Date.now();
  let timeout;
  function update() {
    clearTimeout(timeout);
    const elapsed = Math.max(0, Math.floor((Date.now() - loadedAt) / 1000));
    const seconds = Math.max(0, initial - elapsed);
    timer.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
    if (seconds === 0) {
      if (expiredLink) expiredLink.hidden = false;
      return;
    }
    timeout = setTimeout(update, 1000);
  }
  update();
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) update();
  });
})();
