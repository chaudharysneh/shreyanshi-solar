const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/js/dashboard-documents.js', 'utf8').split("document.querySelectorAll('.dashboard-document-form')")[0];
function load(fetch = async () => ({})) {
    const context = vm.createContext({ document: { querySelector: () => null }, fetch });
    vm.runInContext(source, context);
    return context;
}
test('save accepts direct and wrapped JSON responses', () => {
    const context = load();
    for (const payload of [{ id: 12, has_aadhaar: true }, { data: { id: 12, has_aadhaar: true } }, { document: { id: 12, has_aadhaar: true } }]) {
        const saved = context.documentSaveResult(payload);
        assert.equal(saved.id, '12'); assert.equal(saved.has_aadhaar, true);
    }
});
test('missing or invalid IDs never generate an undefined document URL', () => {
    const context = load();
    for (const payload of [{}, null, { message: 'saved' }, { id: 'undefined' }, { id: 0 }]) {
        assert.throws(() => context.documentSaveResult(payload), /saved document ID/);
    }
});
test('invalid JSON is reported rather than converted into an empty successful response', async () => {
    const context = load(async () => ({ ok: true, text: async () => 'Not JSON' }));
    await assert.rejects(context.documentJson('/dashboard/documents'), /Unable to confirm the save/);
});

test('a PHP upload-startup notice does not turn a successful save into a failure', async () => {
    const raw = '<br /><b>Notice</b>: PHP Request Startup: file created in the system temporary directory<br />\n{"id":42,"has_aadhaar":true,"message":"Document saved."}';
    const context = load(async () => ({ ok: true, status: 200, text: async () => raw }));
    const response = await context.documentJson('/dashboard/documents');
    assert.equal(context.documentSaveResult(response).id, '42');
    assert.equal(response.has_aadhaar, true);
});
test('PHP diagnostic recovery preserves validation failures', async () => {
    const context = load(async () => ({ ok: false, status: 422, text: async () => '<b>Warning</b>: PHP warning<br>{"message":"Invalid image","errors":{"image":["Choose a valid image."]}}' }));
    await assert.rejects(context.documentJson('/dashboard/documents'), /Choose a valid image/);
});
test('an HTML login page is never interpreted as a successful save', async () => {
    const context = load(async () => ({ ok: true, redirected: true, status: 200, text: async () => '<html><body>Login</body></html>' }));
    await assert.rejects(context.documentJson('/dashboard/documents'), /session expired/);
});
