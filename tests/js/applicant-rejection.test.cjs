const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../../resources/views/Admin/adminApplicant.blade.php'), 'utf8');
const helper = source.match(/  function updateApplicantStatusFromModal\(status, options = \{\}\) \{[\s\S]*?(?=\n  function rejectApplicantFromTable)/)[0];
const action = source.match(/  function rejectApplicantFromTable\(applicantId\) \{[\s\S]*?(?=\n  function rejectApplicantAfterInterview)/)[0];

test('table rejection submits the clicked applicant only after confirmation', async () => {
    for (const confirmed of [false, true]) {
        let submissions = 0;
        const fields = { statusId: {}, statusAutoValue: {}, statusDateHired: {}, updateStatus: { submit: () => submissions++ } };
        const document = { getElementById: id => fields[id] };
        const Swal = { fire: () => Promise.resolve({ isConfirmed: confirmed }) };
        const reject = new Function('document', 'Swal', 'currentApplicantId', `${helper}\n${action}\nreturn rejectApplicantFromTable;`)(document, Swal, 999);
        reject(123);
        await Promise.resolve();
        assert.equal(submissions, confirmed ? 1 : 0);
        if (confirmed) {
            assert.equal(fields.statusId.value, 123);
            assert.equal(fields.statusAutoValue.value, 'Rejected');
        }
    }
});
