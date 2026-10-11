import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import ts from 'typescript';

const english = JSON.parse(await readFile(new URL('../../lang/en.json', import.meta.url), 'utf8'));
const indonesian = JSON.parse(await readFile(new URL('../../lang/id.json', import.meta.url), 'utf8'));
const source = (await readFile(new URL('../../resources/js/i18n/translate.ts', import.meta.url), 'utf8'))
    .replace(/import english from[^;]+;/, `const english = ${JSON.stringify(english)};`)
    .replace(/import indonesian from[^;]+;/, `const indonesian = ${JSON.stringify(indonesian)};`);
const { outputText } = ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ES2022, target: ts.ScriptTarget.ES2022 } });
const { translate } = await import(`data:text/javascript;base64,${Buffer.from(outputText).toString('base64')}`);

test('labels change in both directions without changing unknown student data', () => {
    assert.equal(translate('Siswa', 'en'), 'Student');
    assert.equal(translate('Siswa', 'id'), 'Siswa');
    assert.equal(translate('Password', 'id'), 'Kata sandi');
    assert.equal(translate('XI PPLG - RPL 1', 'en'), 'XI PPLG - RPL 1');
    assert.equal(translate('Nama siswa pribadi', 'en'), 'Nama siswa pribadi');
});

test('count and name parameters retain their original values and are never translated recursively', () => {
    assert.equal(translate('Pilih :name', 'en', { name: 'Siswa <b>:count</b>' }), 'Select Siswa <b>:count</b>');
    assert.equal(translate('Status :count siswa diperbarui.', 'en', { count: 25 }), 'Status updated for 25 students.');
    assert.equal(translate('Pilih :name', 'en'), 'Select :name');
});

test('whitespace and numeric labels remain usable in adjacent JSX text', () => {
    assert.equal(translate(' Siswa ', 'en'), ' Student ');
    assert.equal(translate('  \n Siswa\t', 'en'), '  \n Student\t');
    assert.equal(translate(50, 'en'), '50');
    assert.equal(translate('', 'id'), '');
});

test('server metadata and recommendation errors have English translations', () => {
    for (const label of ['Perlu pendampingan', 'Pemahaman bacaan', 'Bisa dengan bimbingan', 'Pilih skor usulan antara 1 dan 4.', 'Ada perubahan yang belum disimpan. Tinggalkan formulir ini?']) {
        assert.notEqual(translate(label, 'en'), label);
    }
    assert.equal(translate('Ditolak', 'en'), 'Rejected');
});
