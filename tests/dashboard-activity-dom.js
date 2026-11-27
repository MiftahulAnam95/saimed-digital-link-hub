'use strict';

// Integrasi DataTables asli dalam DOM simulasi tanpa MySQL atau SSO.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const rows = Array.from({ length: 15 }, (_, i) => {
  const day = String(15 - i).padStart(2, '0');
  return `<tr><td data-order="2026-01-${day} 00:00:00">${day} Jan 2026 07:00</td><td>view</td><td>Halaman ${15 - i}</td><td>Mobile</td><td>2001:db8::1</td></tr>`;
}).join('');
function html(body, empty = '') {
  return `<!doctype html><html><body><div class="activity-table-wrap" tabindex="0"><table id="activity-table"><thead><tr><th>Waktu</th><th>Aktivitas</th><th>Label</th><th>Perangkat</th><th>Alamat IP</th></tr></thead><tbody>${body}</tbody></table>${empty}</div></body></html>`;
}
function windowFor(markup, libraries = true) {
  const dom = new JSDOM(markup, { runScripts: 'outside-only', url: 'http://localhost/' });
  if (libraries) {
    dom.window.eval(read('assets/vendor/jquery/jquery.min.js'));
    dom.window.eval(read('assets/vendor/datatables/dataTables.min.js'));
  }
  dom.window.eval(read('assets/js/dashboard-activity.js'));
  return dom;
}
const dom = windowFor(html(rows));
const { document, jQuery: $ } = dom.window;
const dt = $('#activity-table').DataTable();
assert.equal(dt.rows().count(), 15);
assert.equal(document.querySelectorAll('#activity-table tbody tr').length, 10);
assert.equal(document.querySelector('#activity-table tbody tr td').textContent, '15 Jan 2026 07:00', 'Urutan awal tetap terbaru dari server');
assert.equal(document.querySelector('.dt-search input').placeholder, 'Cari aktivitas...');
assert.equal(document.querySelector('.activity-table-wrap').classList.contains('is-enhanced'), true);
assert.equal(document.querySelector('.dt-layout-table').getAttribute('tabindex'), '0');
assert.equal(document.querySelector('.dt-layout-table').getAttribute('role'), 'region');
dt.order([0, 'asc']).draw();
assert.equal(document.querySelector('#activity-table tbody tr td').textContent, '01 Jan 2026 07:00', 'Urutan waktu harus memakai tanggal UTC');
dt.order([0, 'desc']).draw();
assert.equal(document.querySelector('#activity-table tbody tr td').textContent, '15 Jan 2026 07:00');
dt.search('Halaman 15').draw();
assert.equal(dt.rows({ search: 'applied' }).count(), 1);
assert.match(document.querySelector('#activity-table tbody').textContent, /Halaman 15/);
dt.search('2001:db8::1').draw();
assert.equal(dt.rows({ search: 'applied' }).count(), 15, 'Alamat IP dapat dicari');
dt.search('tidak ada').draw();
assert.match(document.querySelector('#activity-table tbody').textContent, /Tidak ada aktivitas yang cocok/);
dt.search('').draw();dt.page(1).draw('page');
assert.equal(document.querySelectorAll('#activity-table tbody tr').length, 5);
dom.window.close();

// Tanggal yang melintasi tahun harus diurutkan menurut UTC, bukan label WIB.
const boundary = windowFor(html('<tr><td data-order="2025-12-31 16:59:59">31 Dec 2025 23:59</td><td>view</td><td>A</td><td>Mobile</td><td>v1</td></tr><tr><td data-order="2025-12-31 17:00:00">01 Jan 2026 00:00</td><td>view</td><td>B</td><td>Mobile</td><td>v2</td></tr>'));
const boundaryTable = boundary.window.jQuery('#activity-table').DataTable();
boundaryTable.order([0, 'asc']).draw();
assert.match(boundary.window.document.querySelector('#activity-table tbody tr td').textContent, /31 Dec 2025 23:59/);
boundaryTable.order([0, 'desc']).draw();
assert.match(boundary.window.document.querySelector('#activity-table tbody tr td').textContent, /01 Jan 2026 00:00/);
boundary.window.close();

const emptyMarkup = html('', '<p id="activity-empty">Belum ada aktivitas.</p>');
const empty = windowFor(emptyMarkup);
assert.equal(empty.window.document.querySelector('#activity-empty').hidden, true);
assert.match(empty.window.document.querySelector('#activity-table tbody').textContent, /Belum ada aktivitas/);
empty.window.close();
const fallback = windowFor(emptyMarkup, false);
assert.equal(fallback.window.document.querySelector('#activity-empty').hidden, false);
assert.equal(fallback.window.document.querySelector('.activity-table-wrap').getAttribute('tabindex'), '0');
fallback.window.close();
console.log('Tes DataTables aktivitas berhasil.');
