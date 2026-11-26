'use strict';

// Uji perilaku dialog tanpa browser atau database.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const script = fs.readFileSync(__dirname + '/../assets/js/dashboard-links.js', 'utf8');

function fixture(native) {
  const events = {};
  const trigger = { hidden: true, addEventListener: (name, fn) => { events.trigger = fn; }, focus: () => { events.focused = 'trigger'; } };
  const title = { focus: () => { events.focused = 'title'; } };
  const close = { addEventListener: (name, fn) => { events.close = fn; } };
  const cancel = { addEventListener: (name, fn) => { events.cancel = fn; } };
  const dialog = {
    querySelector: selector => selector === '.add-link-close' ? close : cancel,
    setAttribute: name => { events.open = name === 'open'; },
    removeAttribute: () => { events.open = false; },
  };
  if (native) {
    dialog.showModal = () => { events.open = true; events.modal = true; };
    dialog.close = () => { events.open = false; };
  }
  const document = { getElementById: id => ({ 'add-link-dialog': dialog, 'open-add-link': trigger, 'new-link-title': title })[id] };
  vm.runInNewContext(script, { document, window: {} });
  assert.equal(trigger.hidden, false);
  events.trigger();
  assert.equal(events.open, true);
  assert.equal(events.focused, 'title');
  if (native) assert.equal(events.modal, true);
  events.cancel();
  assert.equal(events.open, false);
  assert.equal(events.focused, 'trigger');
  events.trigger();
  events.close();
  assert.equal(events.open, false);
  assert.equal(events.focused, 'trigger');
}
fixture(true);
fixture(false);
console.log('Tes dialog tambah tautan berhasil.');
