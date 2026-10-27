const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../resources/views/Admin/adminApplicant.blade.php'), 'utf8');
const visibility = source.match(/    const scheduleSelect = document.getElementById\('panel_interview_type'\);[\s\S]*?classList.toggle\('hidden', !canCreateSchedule\);/)[0];

test('unlocked Demo Teaching remains visible alongside finished interview history', () => {
    for (const [active, disabled, hidden] of [[null, false, false], [{ id: 2 }, false, true], [null, true, true]]) {
        let isHidden;
        const document = { getElementById: id => id === 'panel_interview_type'
            ? { value: 'Demo Teaching', disabled }
            : { classList: { toggle: (name, value) => { isHidden = value; } } } };
        new Function('document', 'activeApplicantInterviewSchedule', visibility)(document, () => active);
        assert.equal(isHidden, hidden);
    }
});
