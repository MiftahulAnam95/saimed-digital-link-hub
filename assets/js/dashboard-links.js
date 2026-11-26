(() => {
  const dialog = document.getElementById('add-link-dialog');
  const trigger = document.getElementById('open-add-link');
  if (!dialog || !trigger) return;

  // If showModal is unavailable, keep the form usable as a non-modal dialog.
  trigger.hidden = false;
  trigger.addEventListener('click', () => {
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
    document.getElementById('new-link-title').focus();
  });
  function closeDialog() {
    if (typeof dialog.close === 'function') dialog.close();
    else dialog.removeAttribute('open');
    trigger.focus();
  }
  dialog.querySelector('.add-link-close').addEventListener('click', closeDialog);
  dialog.querySelector('.add-link-cancel').addEventListener('click', closeDialog);
})();
