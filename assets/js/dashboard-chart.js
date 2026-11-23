(() => {
  const plot = document.querySelector('.chart-plot[data-points]');
  if (!plot) return;
  let points;
  try { points = JSON.parse(plot.dataset.points); } catch { return; }
  if (!Array.isArray(points) || !points.length) return;
  const crosshair = plot.querySelector('.chart-crosshair');
  const viewsPoint = plot.querySelector('.chart-point-views');
  const clicksPoint = plot.querySelector('.chart-point-clicks');
  const tooltip = plot.querySelector('.chart-tooltip');
  const date = tooltip.querySelector('.chart-tooltip-date');
  const views = tooltip.querySelector('.chart-tooltip-views');
  const clicks = tooltip.querySelector('.chart-tooltip-clicks');
  const number = new Intl.NumberFormat('id-ID');
  let selected = 0;

  function show(index) {
    selected = Math.max(0, Math.min(points.length - 1, index));
    const point = points[selected];
    date.textContent = `${point.date} (WIB)`;
    views.textContent = number.format(point.views);
    clicks.textContent = number.format(point.clicks);
    plot.setAttribute('aria-valuenow', String(selected + 1));
    plot.setAttribute('aria-valuetext', `${point.date} WIB: ${point.views} kunjungan, ${point.clicks} klik`);
    [crosshair, viewsPoint, clicksPoint, tooltip].forEach(element => { element.hidden = false; });
    crosshair.style.left = `${point.x}%`;
    for (const [element, y] of [[viewsPoint, point.viewY], [clicksPoint, point.clickY]]) {
      element.style.left = `${point.x}%`;
      element.style.top = `${y}%`;
    }
    const width = plot.clientWidth;
    const tooltipWidth = tooltip.offsetWidth;
    const x = width * point.x / 100;
    const desired = x + 12 + tooltipWidth <= width ? x + 12 : x - tooltipWidth - 12;
    tooltip.style.left = `${Math.max(0, Math.min(width - tooltipWidth, desired))}px`;
  }
  function hide() {
    [crosshair, viewsPoint, clicksPoint, tooltip].forEach(element => { element.hidden = true; });
  }
  function selectAt(event) {
    const rect = plot.getBoundingClientRect();
    if (!rect.width) return;
    const ratio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
    show(Math.round(ratio * (points.length - 1)));
  }
  plot.addEventListener('pointermove', selectAt);
  plot.addEventListener('pointerdown', selectAt);
  plot.addEventListener('pointerleave', event => { if (event.pointerType !== 'touch' && document.activeElement !== plot) hide(); });
  plot.addEventListener('focus', () => show(selected));
  plot.addEventListener('blur', hide);
  plot.addEventListener('keydown', event => {
    if (event.key === 'Escape') { hide(); return; }
    let next = selected;
    if (event.key === 'ArrowRight' || event.key === 'ArrowUp') next++;
    else if (event.key === 'ArrowLeft' || event.key === 'ArrowDown') next--;
    else if (event.key === 'Home') next = 0;
    else if (event.key === 'End') next = points.length - 1;
    else return;
    event.preventDefault();
    show(next);
  });
  document.addEventListener('pointerdown', event => { if (!plot.contains(event.target)) hide(); });
  window.addEventListener('resize', () => { if (!tooltip.hidden) show(selected); });
})();
