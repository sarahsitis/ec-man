import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';

const source = await readFile(new URL('../../resources/js/Components/assessmentPresentation.ts', import.meta.url), 'utf8');
const { outputText } = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ES2022, target: ts.ScriptTarget.ES2022 } });
const { committeeProgress, recommendationErrors, assessmentStatuses, dateLabel } = await import(`data:text/javascript;base64,${Buffer.from(outputText).toString('base64')}`);

test('progress counts assignments across every status and returns rejected decisions as completed work', () => {
    assert.deepEqual(committeeProgress({ assigned: 4, draft: 2, revision: 1, submitted: 3, approved: 2, rejected: 1 }), { total: 13, sent: 6, working: 3, unstarted: 4, percent: 46 });
    assert.deepEqual(committeeProgress({ assigned: 21, approved: 1 }), { total: 22, sent: 1, working: 0, unstarted: 21, percent: 5 });
});
test('empty assignments remain zero and never produce NaN progress', () => {
    assert.deepEqual(committeeProgress({}), { total: 0, sent: 0, working: 0, unstarted: 0, percent: 0 });
    assert.equal(committeeProgress({ approved: 2, rejected: 1 }).percent, 100);
});
test('every backend status has a label including rejection', () => {
    for (const status of ['assigned', 'draft', 'submitted', 'revision', 'approved', 'rejected']) { assert.ok(assessmentStatuses[status]); }
    assert.equal(assessmentStatuses.rejected, 'Ditolak');
});
test('an incomplete draft can be saved but submitting requires a score and both notes', () => {
    const draft = { proposed_score: '', observations: '', feedback: '' };
    assert.deepEqual(recommendationErrors(draft, false), {});
    assert.deepEqual(Object.keys(recommendationErrors(draft, true)).sort(), ['feedback', 'observations', 'proposed_score']);
});
test('recommendations keep the backend scale and note limits, including whitespace trimming', () => {
    const valid = { proposed_score: '4', observations: '0123456789', feedback: ' 0123456789 ' };
    assert.deepEqual(recommendationErrors(valid, true), {});
    for (const value of ['0', '5', '100', '3.5']) { assert.ok(recommendationErrors({ ...valid, proposed_score: value }, false).proposed_score); }
    assert.ok(recommendationErrors({ ...valid, observations: ' 123456789 ' }, true).observations);
    assert.deepEqual(recommendationErrors({ ...valid, observations: 'a'.repeat(2000), feedback: 'b'.repeat(2000) }, true), {});
    assert.ok(recommendationErrors({ ...valid, observations: 'a'.repeat(2001) }, false).observations);
    assert.ok(recommendationErrors({ ...valid, feedback: 'b'.repeat(2001) }, true).feedback);
});
test('deadline labels keep Jakarta calendar dates and handle missing values', () => {
    assert.equal(dateLabel(null), 'Tanpa batas waktu');
    assert.match(dateLabel('2026-10-10'), /10.*2026/);
    assert.match(dateLabel('2026-10-10T00:00:00.000000Z'), /10.*2026/);
});
