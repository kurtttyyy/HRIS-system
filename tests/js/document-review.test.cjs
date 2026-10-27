const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../../resources/views/guest/applicationNonTeachingSteps.blade.php'), 'utf8');
const body = source.match(/const renderFileNames = \(targetId, input\) => \{([\s\S]*?)\n        \};/)[1];
const render = new Function('document', 'selectedFileNames', 'targetId', 'input', body);

test('non-teaching review skips absent teaching-only fields and continues', () => {
    const target = { textContent: '' };
    const document = { getElementById: id => id === 'review-resume-file' ? target : null };
    const filenames = () => ['saved-resume.pdf'];
    assert.doesNotThrow(() => render(document, filenames, 'review-research-publication', null));
    assert.doesNotThrow(() => render(document, filenames, 'review-training-certification', null));
    render(document, filenames, 'review-resume-file', {});
    assert.equal(target.textContent, 'saved-resume.pdf');
});

test('teaching review still displays its uploaded document', () => {
    const target = { textContent: '' };
    render({ getElementById: () => target }, () => ['research.docx'], 'review-research-publication', {});
    assert.equal(target.textContent, 'research.docx');
});
