import assert from 'node:assert/strict';

const options = [
    { value: '0', textContent: 'All', selected: false, hidden: false },
    { value: '12', textContent: 'Rigoletto Course · v2 · published · #12', selected: false, hidden: false },
    { value: '13', textContent: 'Macbeth Course · v1 · draft · #13', selected: false, hidden: false },
];
class Select {}
const select = new Select();
select.options = options;
const listeners = {};
const search = {
    value: '',
    dataset: { rosterOptionSearch: 'filter_version' },
    addEventListener: (name, callback) => { listeners[name] = callback; },
};
globalThis.HTMLSelectElement = Select;
globalThis.document = {
    querySelectorAll: () => [search],
    getElementById: id => id === 'filter_version' ? select : null,
};

await import('../component/media/js/roster-filters.js');
search.value = 'macbeth';
listeners.input();
assert.equal(options[0].hidden, false, 'all option remains available');
assert.equal(options[1].hidden, true, 'nonmatching option is hidden');
assert.equal(options[2].hidden, false, 'matching option remains available case-insensitively');
search.value = '';
listeners.input();
assert.ok(options.every(option => !option.hidden), 'clearing search restores every option');

console.log('Roster filter tests passed');
