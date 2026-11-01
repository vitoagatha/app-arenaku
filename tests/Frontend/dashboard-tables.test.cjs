const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

function initialize(caption) {
    let options;
    const region = { classes: ['table-responsive'], attributes: { role: 'region', tabindex: '0' } };
    const scroll = { attributes: {} };
    const oldRegion = {
        removeClass(name) { region.classes = region.classes.filter(item => item !== name); return this; },
        removeAttr(names) { names.split(' ').forEach(name => delete region.attributes[name]); return this; }
    };
    const wrapper = {
        closest(selector) { assert.equal(selector, '.table-responsive'); return oldRegion; },
        find(selector) {
            if (selector === 'caption') return { text: () => caption };
            assert.equal(selector, '.akd-table-scroll');
            return { attr: attributes => Object.assign(scroll.attributes, attributes) };
        }
    };
    const document = {};
    const container = {};
    const $ = target => {
        if (target === document) return { ready: callback => callback() };
        if (target === '#dataTable') return { DataTable: value => { options = value; } };
        assert.equal(target, container);
        return wrapper;
    };
    vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../public/js/demo/datatables-demo.js'), 'utf8'), { $, document });
    options.initComplete.call({ api: () => ({ table: () => ({ container: () => container }) }) });
    return { options, region, scroll };
}

test('table scrolling keeps labeled keyboard access without scrolling its controls', () => {
    const { options, region, scroll } = initialize('  Daftar booking  ');
    assert.match(options.dom, /akd-table-controls.*akd-table-scroll.*akd-table-footer/);
    assert.equal(region.classes.includes('table-responsive'), false);
    assert.equal(region.attributes.tabindex, undefined);
    assert.equal(scroll.attributes.role, 'region');
    assert.equal(scroll.attributes['aria-label'], 'Daftar booking');
    assert.equal(scroll.attributes.tabindex, '0');
});

test('empty captions have a label and table behavior retains DataTables defaults', () => {
    const { options, scroll } = initialize('');
    assert.equal(scroll.attributes['aria-label'], 'Data pengelolaan');
    for (const setting of ['paging', 'ordering', 'searching', 'pageLength', 'order']) {
        assert.equal(Object.hasOwn(options, setting), false);
    }
    assert.match(options.language.zeroRecords, /Coba kata pencarian lain/);
});
