(() => {
  const table = document.getElementById('activity-table');
  if (!table || !window.jQuery || !window.jQuery.fn.DataTable) return;

  // Waktu pengurutan berasal dari data-order UTC, bukan teks tanggal WIB.
  window.jQuery(table).DataTable({
    order: [], autoWidth: false, pageLength: 10, lengthMenu: [10, 25, 50], pagingType: 'simple_numbers',
    layout: { topStart: 'search', topEnd: 'pageLength', bottomStart: 'info', bottomEnd: 'paging' },
    columnDefs: [{ targets: 0, type: 'string' }],
    language: {
      search: 'Cari aktivitas _INPUT_', searchPlaceholder: 'Cari aktivitas...', lengthMenu: 'Tampilkan _MENU_ aktivitas',
      info: 'Menampilkan _START_–_END_ dari _TOTAL_ aktivitas',
      infoEmpty: 'Tidak ada aktivitas untuk ditampilkan', infoFiltered: '(disaring dari _MAX_ aktivitas)',
      zeroRecords: 'Tidak ada aktivitas yang cocok', emptyTable: 'Belum ada aktivitas',
      paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' },
      aria: { orderable: 'Urutkan kolom ini', orderableReverse: 'Balik urutan kolom ini' },
    },
  });

  const empty = document.getElementById('activity-empty');
  if (empty) empty.hidden = true;
  const tableWrap = table.closest('.activity-table-wrap');
  if (tableWrap) {
    tableWrap.classList.add('is-enhanced');
    tableWrap.removeAttribute('tabindex');
    const scrollArea = tableWrap.querySelector('.dt-layout-table');
    if (scrollArea) {
      scrollArea.setAttribute('tabindex', '0');
      scrollArea.setAttribute('role', 'region');
      scrollArea.setAttribute('aria-label', 'Tabel log aktivitas pengunjung; geser ke samping untuk melihat seluruh kolom');
    }
  }
})();
