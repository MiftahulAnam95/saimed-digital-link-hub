'use strict';

// Integration UI dengan DataTables asli dan DOM simulasi. Tidak memerlukan server SSO atau MySQL.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const rows = Array.from({ length: 15 }, (_, i) => `
  <tr><td><span class="links-name"><span class="platform-icon"><img src="logo.png"></span><strong>Tautan ${i + 1}</strong></span></td>
  <td>https://example.test/${i + 1}</td><td>${i + 1}</td><td>Aktif</td><td>Tidak</td><td>
  <button type="button" class="edit-link-trigger" hidden data-id="${i + 1}" data-title="Tautan ${i + 1}" data-url="https://example.test/${i + 1}" data-order="${i + 1}" data-active="1" data-featured="0">Edit</button>
  <form class="delete-link-form" onsubmit="return confirm('Hapus?')"><input name="csrf" value="test"><input name="id" value="${i + 1}"><button>Hapus</button></form></td></tr>`).join('');
const html = process.env.DASHBOARD_HTML ? fs.readFileSync(process.env.DASHBOARD_HTML, 'utf8') : `<!doctype html><html><body>
  <p id="dashboard-notice" data-notice-type="error">&lt;gambar&gt; gagal</p>
  <button id="open-add-link" hidden>Tambah</button><dialog id="add-link-dialog"><button class="add-link-close">X</button><input id="new-link-title"><button class="add-link-cancel">Batal</button></dialog>
  <div class="links-table-wrap" tabindex="0"><table id="links-table"><thead><tr><th>Nama</th><th>URL</th><th class="dt-type-numeric"><div class="dt-column-header">Urutan</div></th><th>Status</th><th>Unggulan</th><th>Aksi</th></tr></thead><tbody>${rows}</tbody></table></div>
  <dialog id="edit-link-dialog"><button class="edit-link-close">X</button><form id="edit-link-form">
  <input name="id"><input name="title" id="edit-link-name"><input name="url"><input name="sort_order"><input type="checkbox" name="is_active"><input type="checkbox" name="featured"><input type="file" name="icon_file"><span id="edit-current-icon"></span><button class="edit-link-cancel" type="button">Batal</button></form></dialog>
  </body></html>`;

async function main() {
  const dom = new JSDOM(html, { runScripts: 'outside-only', url: 'http://localhost/' });
  const { window } = dom;
  const alerts = [];
  let confirm = false;
  window.Swal = { fire: options => { alerts.push(options); return Promise.resolve({ isConfirmed: confirm }); } };
  let submitted = 0;
  window.HTMLFormElement.prototype.submit = function () { submitted++; };
  window.eval(read('assets/vendor/jquery/jquery.min.js'));
  window.eval(read('assets/vendor/datatables/dataTables.min.js'));
  window.eval(read('assets/js/dashboard-links.js'));
  const document = window.document;
  const $ = window.jQuery;
  assert.equal(alerts[0].icon, 'error');
  assert.equal(alerts[0].text, '<gambar> gagal');
  assert.equal(alerts[0].html, undefined); // Pesan tidak disisipkan sebagai HTML.
  assert.equal(document.getElementById('dashboard-notice').hidden, true);
  const scrollArea = document.querySelector('.links-table-wrap .dt-layout-table');
  assert.equal(document.querySelector('.links-table-wrap').classList.contains('is-enhanced'), true);
  assert.equal(scrollArea.getAttribute('role'), 'region');
  assert.equal(scrollArea.getAttribute('tabindex'), '0');
  assert.equal(document.querySelector('.dt-search input').placeholder, 'Cari nama atau URL...');
  const dt = $('#links-table').DataTable();
  assert.equal(dt.settings()[0].oFeatures.bAutoWidth, false, 'Lebar kolom mengikuti tata letak CSS');
  assert.equal(dt.rows().count(), 15);
  assert.equal(document.querySelectorAll('#links-table tbody tr').length, 10);
  dt.search('Tautan 15').draw();
  assert.equal(dt.rows({ search: 'applied' }).count(), 1);
  assert.match(document.querySelector('#links-table tbody').textContent, /Tautan 15/);
  document.querySelector('.edit-link-trigger').click();
  const form = document.getElementById('edit-link-form');
  assert.equal(document.getElementById('edit-link-dialog').hasAttribute('open'), true);
  assert.equal(form.elements.namedItem('id').value, '15');
  assert.equal(form.elements.namedItem('title').value, 'Tautan 15');
  assert.equal(form.elements.namedItem('url').value, 'https://example.test/15');
  assert.equal(form.elements.namedItem('is_active').checked, true);
  assert.equal(form.elements.namedItem('featured').checked, false);
  assert.ok(document.querySelector('#edit-current-icon .platform-icon img'));
  document.querySelector('.edit-link-cancel').click();
  assert.equal(document.getElementById('edit-link-dialog').hasAttribute('open'), false);
  const deleteForm = document.querySelector('.delete-link-form');
  deleteForm.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
  await Promise.resolve();
  assert.equal(submitted, 0);
  assert.equal(alerts.at(-1).icon, 'warning');
  confirm = true;
  deleteForm.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
  await Promise.resolve();
  assert.equal(submitted, 1);
  dt.search('').draw();
  dt.page(1).draw('page');
  assert.equal(document.querySelectorAll('#links-table tbody tr').length, 5);
  assert.equal(document.querySelector('.edit-link-trigger').hidden, false);
  document.querySelector('.edit-link-trigger').click();
  assert.equal(form.elements.namedItem('id').value, '11');
  dom.window.close();
  // Tanpa pustaka pihak ketiga, tabel dasar dan pesan tetap tersedia.
  const fallback = new JSDOM(html, { runScripts: 'outside-only', url: 'http://localhost/' });
  fallback.window.eval(read('assets/js/dashboard-links.js'));
  assert.equal(fallback.window.document.getElementById('dashboard-notice').hidden, false);
  assert.equal(fallback.window.document.querySelectorAll('#links-table tbody tr').length, 15);
  fallback.window.document.querySelector('.edit-link-trigger').click();
  assert.equal(fallback.window.document.getElementById('edit-link-form').elements.namedItem('id').value, '1');
  fallback.window.close();

  // Pesan sukses juga ditampilkan sebagai teks, bukan HTML.
  const success = new JSDOM(html, { runScripts: 'outside-only', url: 'http://localhost/' });
  const successNotice = success.window.document.getElementById('dashboard-notice');
  successNotice.dataset.noticeType = 'success';
  successNotice.textContent = 'Tersimpan';
  let successAlert;
  success.window.Swal = { fire: options => { successAlert = options; return Promise.resolve({}); } };
  success.window.eval(read('assets/js/dashboard-links.js'));
  assert.equal(successAlert.icon, 'success');
  assert.equal(successAlert.text, 'Tersimpan');
  assert.equal(successNotice.hidden, true);
  success.window.close();
  // Isi Urutan di tengah; judul dan tombol Aksi tetap rata kiri.
  const alignment = new JSDOM('<style></style><div class="dashboard"><table class="dataTable links-table"><thead><tr><th><div class="dt-column-header">Nama</div></th><th>URL</th><th class="dt-type-numeric"><div class="dt-column-header">Urutan</div></th><th>Status</th><th>Unggulan</th><th><div class="dt-column-header">Aksi</div></th></tr></thead><tbody><tr><td>Nama</td><td>URL</td><td class="dt-type-numeric">1</td><td>Aktif</td><td>Tidak</td><td><div class="link-row-actions">Tombol</div></td></tr></tbody></table></div>');
  alignment.window.document.querySelector('style').textContent = ['assets/css/style.css', 'assets/vendor/datatables/dataTables.dataTables.min.css', 'assets/css/admin.css'].map(read).join('\n');
  const headers = alignment.window.document.querySelectorAll('th');
  const computed = element => alignment.window.getComputedStyle(element);
  assert.equal(computed(headers[2]).textAlign, 'left');
  assert.equal(computed(headers[2].firstElementChild).flexDirection, 'row');
  assert.equal(computed(headers[1]).width, '31%');
  assert.equal(computed(headers[5]).textAlign, 'left');
  assert.equal(computed(headers[5].firstElementChild).justifyContent, computed(headers[0].firstElementChild).justifyContent);
  assert.equal(computed(headers[5]).paddingRight, computed(headers[0]).paddingRight);
  assert.equal(computed(alignment.window.document.querySelector('td:nth-child(3)')).textAlign, 'center');
  assert.equal(computed(alignment.window.document.querySelector('td:nth-child(2)')).textAlign, 'left');
  assert.equal(computed(alignment.window.document.querySelector('td:last-child')).textAlign, 'left');
  assert.equal(computed(alignment.window.document.querySelector('.link-row-actions')).justifyContent, 'flex-start');
  alignment.window.close();
  // Tampilan header dan baris kedua DataTables sama; status dan aksi memakai warna isi.
  const appearance = new JSDOM('<style></style><div class="dashboard"><table class="dataTable display links-table"><thead><tr><th>Nama</th></tr></thead><tbody><tr><td><span class="link-status is-active">Aktif</span><span class="link-status is-inactive">Nonaktif</span><div class="link-row-actions"><button class="button edit-link-trigger">Edit</button><button class="button danger">Hapus</button></div></td></tr></tbody></table><table class="dataTable display activity-table"><thead><tr><th>Waktu</th></tr></thead><tbody><tr><td>Waktu</td></tr></tbody></table></div>');
  appearance.window.document.querySelector('style').textContent = ['assets/css/style.css', 'assets/vendor/datatables/dataTables.dataTables.min.css', 'assets/css/admin.css'].map(read).join('\n');
  const style = selector => appearance.window.getComputedStyle(appearance.window.document.querySelector(selector));
  for (const property of ['backgroundColor', 'color', 'fontSize', 'fontWeight', 'padding']) {
    assert.equal(style('.links-table th')[property], style('.activity-table th')[property], `Header harus sama: ${property}`);
  }
  for (const property of ['backgroundColor', 'color', 'fontSize', 'padding', 'boxShadow']) {
    assert.equal(style('.links-table td')[property], style('.activity-table td')[property], `Baris harus sama: ${property}`);
  }
  const expected = {
    '.link-status.is-active': 'rgb(8, 122, 84)',
    '.link-status.is-inactive': 'rgb(82, 101, 116)',
    '.button.edit-link-trigger': 'rgb(8, 117, 193)',
    '.button.danger': 'rgb(181, 54, 49)',
  };
  for (const [selector, color] of Object.entries(expected)) {
    assert.equal(style(selector).backgroundColor, color, `${selector} harus memakai warna isi`);
    assert.equal(style(selector).color, 'rgb(255, 255, 255)');
  }
  for (const selector of ['.link-status.is-active', '.link-status.is-inactive']) {
    assert.equal(style(selector).minHeight, style('.button.edit-link-trigger').minHeight, 'Tinggi status harus selaras dengan tombol Edit');
    assert.equal(style(selector).borderRadius, style('.button.edit-link-trigger').borderRadius, 'Sudut status harus selaras dengan tombol Edit');
    assert.equal(style(selector).whiteSpace, 'nowrap', 'Status tidak boleh terpotong ke beberapa baris');
  }
  appearance.window.close();
  console.log('Tes DataTables, dialog edit, dan SweetAlert2 berhasil.');
}
main().catch(error => { console.error(error); process.exitCode = 1; });
