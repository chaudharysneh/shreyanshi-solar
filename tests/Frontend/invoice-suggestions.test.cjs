const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/js/invoice-form.js', 'utf8');
const suggestionSource = source.slice(source.indexOf('    function initEstimateSuggestions('), source.indexOf('    function initEstimatePriceModeSelector('));
class Element {
    constructor(value = '') { this.value = value; this.children = []; this.listeners = {}; this.dataset = {}; }
    setAttribute() {}
    appendChild(child) { this.children.push(child); }
    replaceChildren(...children) { this.children = children; }
    insertAdjacentElement(_, group) { this.suggestions = group; }
    addEventListener(type, callback) { (this.listeners[type] ??= []).push(callback); }
    dispatchEvent(event) { for (const callback of this.listeners[event.type] || []) callback(event); }
    focus() {}
}
function load(fetch) {
    const quantity = new Element();
    const price = new Element('123456');
    const timers = new Map();
    let id = 0;
    const form = { classList: { contains: () => true }, dataset: { priceSuggestionsUrl: '/api/invoices/price-suggestions' },
        querySelector: selector => selector.includes('quantity') ? quantity : price };
    const context = vm.createContext({ document: { createElement: () => new Element() }, window: { location: { origin: 'https://crm.test' } },
        fetch, URL, AbortController, Event, setTimeout: callback => { timers.set(++id, callback); return id; }, clearTimeout: key => timers.delete(key) });
    vm.runInContext(suggestionSource, context);
    context.initEstimateSuggestions(form);
    return { quantity, price, start: () => { const callbacks = [...timers.values()]; timers.clear(); return Promise.all(callbacks.map(callback => callback())); } };
}
const response = prices => ({ ok: true, json: async () => ({ success: true, suggestions: prices.map((price, i) => ({ price, rate_per_kw: [30000, 40000, 45000][i] })) }) });
test('kW chips request backend totals; selecting a price is optional and editable', async () => {
    const requests = [];
    const form = load(async url => { requests.push(url); return response([300000, 400000, 450000]); });
    assert.deepEqual(form.quantity.suggestions.children.map(button => button.textContent), ['3 kW', '5 kW', '7 kW', '8 kW', '10 kW']);
    form.quantity.suggestions.children[4].dispatchEvent(new Event('click'));
    await form.start();
    assert.equal(requests.length, 1);
    assert.equal(requests[0].searchParams.get('quantity'), '10');
    assert.deepEqual(form.price.suggestions.children.map(button => button.textContent), ['3,00,000', '4,00,000', '4,50,000']);
    assert.equal(form.price.value, '123456');
    form.price.suggestions.children[0].dispatchEvent(new Event('click'));
    assert.equal(form.price.value, '300000');
    form.price.value = '310000';
    form.price.dispatchEvent(new Event('input'));
    assert.equal(form.price.value, '310000');
});
test('late response for a previous capacity cannot replace current suggestions', async () => {
    let resolveOld;
    const form = load(url => url.searchParams.get('quantity') === '3' ? new Promise(resolve => { resolveOld = resolve; }) : Promise.resolve(response([300000, 400000, 450000])));
    form.quantity.value = '3'; form.quantity.dispatchEvent(new Event('input'));
    const old = form.start();
    form.quantity.value = '10'; form.quantity.dispatchEvent(new Event('input'));
    await form.start();
    resolveOld(response([90000, 120000, 135000])); await old;
    assert.equal(form.price.suggestions.children[0].textContent, '3,00,000');
});
test('empty or invalid capacity clears suggestions without requesting prices', async () => {
    let requests = 0;
    const form = load(async () => { requests++; return response([]); });
    for (const value of ['', '0', '-1', 'invalid']) {
        form.quantity.value = value; form.quantity.dispatchEvent(new Event('input')); await form.start();
        assert.equal(form.price.suggestions.children[0].textContent, 'Enter kW to see price suggestions.');
    }
    assert.equal(requests, 0);
});
test('a backend failure leaves the manually entered price usable', async () => {
    const form = load(async () => { throw new Error('Offline'); });
    form.quantity.value = '10'; form.quantity.dispatchEvent(new Event('input')); await form.start();
    assert.equal(form.price.value, '123456');
    assert.match(form.price.suggestions.children[0].textContent, /enter a price manually/);
});
