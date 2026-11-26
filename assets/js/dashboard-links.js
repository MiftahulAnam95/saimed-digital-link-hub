(() => {
  const addDialog = document.getElementById('add-link-dialog');
  const addTrigger = document.getElementById('open-add-link');
  const editDialog = document.getElementById('edit-link-dialog');
  const editForm = document.getElementById('edit-link-form');
  const table = document.getElementById('links-table');
  let editTrigger = null;

  function openDialog(dialog, focus) {
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
    focus.focus();
  }
  function closeDialog(dialog, trigger) {
    if (typeof dialog.close === 'function') dialog.close();
    else dialog.removeAttribute('open');
    if (trigger) trigger.focus();
  }
  if (addDialog && addTrigger) {
    addTrigger.hidden = false;
    addTrigger.addEventListener('click', () => openDialog(addDialog, document.getElementById('new-link-title')));
    addDialog.querySelector('.add-link-close').addEventListener('click', () => closeDialog(addDialog, addTrigger));
    addDialog.querySelector('.add-link-cancel').addEventListener('click', () => closeDialog(addDialog, addTrigger));
  }

  if (table && editDialog && editForm) {
    // Set up before DataTables detaches rows during pagination.
    table.querySelectorAll('.edit-link-trigger').forEach(button => { button.hidden = false; });
    table.querySelectorAll('.delete-link-form').forEach(form => form.removeAttribute('onsubmit'));
    table.addEventListener('click', event => {
      const button = event.target.closest('.edit-link-trigger');
      if (!button || !table.contains(button)) return;
      editTrigger = button;
      editForm.reset();
      const fields = editForm.elements;
      fields.namedItem('id').value = button.dataset.id;
      fields.namedItem('title').value = button.dataset.title;
      fields.namedItem('url').value = button.dataset.url;
      fields.namedItem('sort_order').value = button.dataset.order;
      fields.namedItem('is_active').checked = button.dataset.active === '1';
      fields.namedItem('featured').checked = button.dataset.featured === '1';
      const icon = button.closest('tr').querySelector('.links-name .platform-icon');
      const preview = document.getElementById('edit-current-icon');
      preview.replaceChildren();
      if (icon) preview.appendChild(icon.cloneNode(true));
      openDialog(editDialog, document.getElementById('edit-link-name'));
    });
    editDialog.querySelector('.edit-link-close').addEventListener('click', () => closeDialog(editDialog, editTrigger));
    editDialog.querySelector('.edit-link-cancel').addEventListener('click', () => closeDialog(editDialog, editTrigger));

    table.addEventListener('submit', event => {
      const form = event.target;
      if (!form.matches('.delete-link-form')) return;
      event.preventDefault();
      const submit = () => HTMLFormElement.prototype.submit.call(form);
      if (!window.Swal) {
        if (window.confirm('Hapus tautan ini?')) submit();
        return;
      }
      window.Swal.fire({
        title: 'Hapus tautan?', text: 'Tautan yang dihapus tidak dapat dikembalikan.', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal',
        confirmButtonColor: '#c94c43', cancelButtonColor: '#718b99',
      }).then(result => { if (result.isConfirmed) submit(); });
    });
  }

  if (table && window.jQuery && window.jQuery.fn.DataTable) {
    window.jQuery(table).DataTable({
      order: [], pageLength: 10, lengthMenu: [10, 25, 50, 100], pagingType: 'simple_numbers',
      layout: { topStart: 'search', topEnd: 'pageLength', bottomStart: 'info', bottomEnd: 'paging' },
      columnDefs: [{ targets: -1, orderable: false, searchable: false }],
      language: {
        search: 'Cari tautan _INPUT_', searchPlaceholder: 'Cari nama atau URL...', lengthMenu: 'Tampilkan _MENU_ tautan',
        info: 'Menampilkan _START_–_END_ dari _TOTAL_ tautan',
        infoEmpty: 'Tidak ada tautan untuk ditampilkan', infoFiltered: '(disaring dari _MAX_ tautan)',
        zeroRecords: 'Tidak ada tautan yang cocok', emptyTable: 'Belum ada tautan publik',
        paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' },
        aria: { orderable: 'Urutkan kolom ini', orderableReverse: 'Balik urutan kolom ini' },
      },
    });
    const tableWrap = table.closest('.links-table-wrap');
    if (tableWrap) {
      tableWrap.classList.add('is-enhanced');
      tableWrap.removeAttribute('tabindex');
      const scrollArea = tableWrap.querySelector('.dt-layout-table');
      if (scrollArea) {
        scrollArea.setAttribute('tabindex', '0');
        scrollArea.setAttribute('role', 'region');
        scrollArea.setAttribute('aria-label', 'Tabel tautan publik; geser ke samping untuk melihat seluruh kolom');
      }
    }
  }

  const notice = document.getElementById('dashboard-notice');
  if (notice && window.Swal && ['success', 'error'].includes(notice.dataset.noticeType)) {
    const type = notice.dataset.noticeType;
    window.Swal.fire({ icon: type, title: type === 'success' ? 'Berhasil' : 'Terjadi kesalahan', text: notice.textContent, confirmButtonText: 'OK', confirmButtonColor: '#188fe5' });
    notice.hidden = true;
  }
})();
