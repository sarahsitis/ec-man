import InputError from '@/Components/InputError';
import { field, panel } from '@/Components/AssessmentUi';
import { Icon } from '@/Components/WorkspaceUi';
import { useTranslation } from '@/i18n/useTranslation';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { FormEvent, useEffect, useRef, useState } from 'react';

export type Dataset = 'students' | 'memberships' | 'attendances' | 'scores';
export type Row = Record<string, string | number | null>;
export interface Preview {
    token: string; dataset: Dataset; mode: 'create' | 'update'; filename: string; total: number;
    counts: Record<'create' | 'update' | 'skip', number>; columns: string[];
    errors: { row: number; nis: string; messages: string[] }[];
    rows: { row: number; nis: string; action: string; values: Row }[];
}

export const datasetLabels: Record<Dataset, string> = { students: 'Data Siswa', memberships: 'Keanggotaan', attendances: 'Presensi', scores: 'Nilai Resmi' };
const statuses: Record<string, string> = { active: 'Aktif', inactive: 'Nonaktif', left: 'Keluar', alumni: 'Alumni', hadir: 'Hadir', izin: 'Izin', sakit: 'Sakit', alpa: 'Alpa', belum_diisi: 'Belum diisi' };
const actions: Record<string, string> = { create: 'Ditambahkan', update: 'Diperbarui', skip: 'Dilewati' };


type Upload = { dataset: Dataset; mode: 'create' | 'update'; file: File | null };
export default function DataImportPanel({ initialDataset, fixedDataset = false, initialPreview = null, initialHeadings = {}, onBusyChange }: {
    initialDataset: Dataset; fixedDataset?: boolean; initialPreview?: Preview | null;
    initialHeadings?: Record<string, string>; onBusyChange?: (busy: boolean) => void;
}) {
    const { t } = useTranslation();
    const datasets = datasetLabels;
    const [data, setData] = useState<Upload>({ dataset: initialPreview?.dataset ?? initialDataset, mode: initialPreview?.mode ?? 'create', file: null });
    const [importPreview, setImportPreview] = useState<Preview | null>(initialPreview);
    const [headings, setHeadings] = useState(initialHeadings);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState<'preview' | 'save' | 'cancel' | null>(null);
    const [approveScores, setApproveScores] = useState(false);
    const [errorPage, setErrorPage] = useState(0);
    const [success, setSuccess] = useState('');
    const fileInput = useRef<HTMLInputElement>(null);
    const resultArea = useRef<HTMLDivElement>(null);
    useEffect(() => { onBusyChange?.(!!busy); }, [busy, onBusyChange]);
    useEffect(() => {
        if (importPreview || success) resultArea.current?.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }, [importPreview, success]);
    const change = (next: Upload) => { setData(next); setImportPreview(null); setErrors({}); setSuccess(''); setApproveScores(false); };
    const failed = (error: unknown) => {
        if (axios.isAxiosError(error) && error.response?.status === 422 && error.response.data?.errors) {
            const messages = error.response.data.errors as Record<string, string[]>;
            setErrors(Object.fromEntries(Object.entries(messages).map(([key, values]) => [key, values.map(value => t(value)).join(' ')])));
        } else {
            setErrors({ import: t(axios.isAxiosError(error) && [401, 419].includes(error.response?.status ?? 0)
                ? 'Sesi berakhir. Muat ulang halaman dan masuk kembali.' : 'Permintaan gagal. Periksa koneksi dan coba kembali.') });
        }
    };
    const preview = async (event: FormEvent) => {
        event.preventDefault(); if (busy || !data.file) return;
        setBusy('preview'); setErrors({}); setImportPreview(null); setSuccess(''); setErrorPage(0); setApproveScores(false);
        const form = new FormData(); form.append('dataset', data.dataset); form.append('mode', data.mode); form.append('file', data.file);
        try {
            const response = await axios.post<{ preview: Preview; headings: Record<string, string> }>(route('data-transfer.preview'), form, { headers: { Accept: 'application/json' } });
            if (!response.data.preview || !response.data.headings) throw new Error('Invalid preview response');
            setImportPreview(response.data.preview); setHeadings(response.data.headings);
        } catch (error) { failed(error); } finally { setBusy(null); }
    };
    const confirm = async (event: FormEvent) => {
        event.preventDefault(); if (busy || !importPreview || importPreview.errors.length) return;
        setBusy('save'); setErrors({});
        try {
            const response = await axios.post<{ message: string }>(route('data-transfer.import'), { token: importPreview.token, approve_scores: approveScores }, { headers: { Accept: 'application/json' } });
            if (typeof response.data.message !== 'string') throw new Error('Invalid import response');
            setSuccess(response.data.message); setImportPreview(null); setApproveScores(false); setData({ ...data, file: null });
            if (fileInput.current) fileInput.current.value = '';
            router.reload();
        } catch (error) { failed(error); } finally { setBusy(null); }
    };
    const cancel = async () => {
        if (busy) return; setBusy('cancel'); setErrors({});
        try {
            await axios.delete(route('data-transfer.cancel'), { headers: { Accept: 'application/json' } });
            setImportPreview(null); setApproveScores(false);
            router.reload();
        } catch (error) { failed(error); } finally { setBusy(null); }
    };
    const renderValue = (column: string, value: Row[string]) => {
        if (value === '' || value === null) return '—';
        if (column === 'status') return t(statuses[String(value)] ?? String(value));
        if (column === 'semester') return t(value === 'ganjil' ? 'Ganjil' : 'Genap');
        return String(value);
    };
    return <div className="space-y-5">
        {success && <div ref={resultArea} role="status" className="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">{success}</div>}
                <form className={panel} aria-busy={!!busy} onSubmit={preview}>
                    <h2 className="text-lg font-semibold">{t('Unggah data administrasi')}</h2><p className="mt-2 text-sm leading-6 text-gray-500">{t('Unduh template, isi data, lalu unggah CSV UTF-8. Maksimal 5 MB dan 2.000 baris per file. File diperiksa sebelum disimpan.')}</p>
                    <fieldset disabled={!!busy} className={`mt-5 grid gap-4 sm:grid-cols-2 ${fixedDataset ? '' : 'lg:grid-cols-3'}`}>
                        {!fixedDataset && <div><label htmlFor="import_dataset" className="text-sm font-medium">{t('Jenis data')}</label><select id="import_dataset" className={field} value={data.dataset} onChange={e => { change({ ...data, dataset: e.target.value as Dataset, mode: 'create' }); }}>{Object.entries(datasets).map(([key, label]) => <option key={key} value={key}>{t(label)}</option>)}</select><InputError message={errors.dataset} /></div>}
                        <div><label htmlFor="import_mode" className="text-sm font-medium">{t('Jika data sudah ada')}</label><select id="import_mode" className={field} value={data.mode} disabled={data.dataset === 'scores'} onChange={e => change({ ...data, mode: e.target.value as 'create' | 'update' })}><option value="create">{t('Tambahkan saja; isi berbeda ditolak')}</option><option value="update">{t('Perbarui sesuai isi file')}</option></select><InputError message={errors.mode} /></div>
                        <div><label htmlFor="import_file" className="text-sm font-medium">{t('File CSV')}</label><input ref={fileInput} id="import_file" className={`${field} ec-import-file text-sm`} type="file" accept=".csv,.txt,text/csv" required onChange={e => change({ ...data, file: e.target.files?.[0] ?? null })} /><InputError message={errors.file} /></div>
                    </fieldset>
                    <div className="mt-4 flex flex-wrap gap-3"><a className="ec-action ec-action-secondary" href={route('data-transfer.template', { dataset: data.dataset })}><Icon name="save" />{t('Unduh template CSV')}</a><button disabled={!!busy} className="ec-action ec-action-primary"><Icon name="check" />{busy === 'preview' ? t('Memeriksa…') : t('Periksa & Pratinjau')}</button></div>
                    <div className="mt-5 rounded-lg bg-gray-50 p-4 text-sm leading-6 text-gray-600">
                        <p className="mb-2 font-medium">{t('Template memuat judul dan nama sekolah pada baris ke-1–2, baris kosong ke-3, serta kolom pada baris ke-4. Isi data mulai baris ke-5, lalu simpan sebagai CSV UTF-8 dengan pemisah titik koma (;).')}</p>
                        {data.dataset === 'students' && <p>{t('NIS menjadi identitas akun. Siswa baru mendapat username dan kata sandi awal sesuai NIS, mengikuti aturan akun saat ini. Pembaruan mempertahankan kata sandi, foto, dan peran akun. Kolom kosong pada mode perbarui akan mengosongkan informasi kontak.')}</p>}
                        {data.dataset === 'memberships' && <p>{t('Tahun ajaran dan semester harus sudah tersedia. Kelas harus sesuai daftar kelas; keanggotaan aktif memerlukan siswa aktif.')}</p>}
                        {data.dataset === 'attendances' && <p>{t('Gunakan ID dan identitas kegiatan dari ekspor. Kegiatan harus tersedia dan tidak dibatalkan. Status: hadir, izin, sakit, alpa, atau belum_diisi.')}</p>}
                        {data.dataset === 'scores' && <p>{t('Impor nilai digunakan untuk arsip penilaian resmi: skala 1–4, aspek listening/speaking/reading/writing, tanggal YYYY-MM-DD HH:mm:ss dalam WIB. Judul penilaian harus berbeda dari penugasan yang sedang berjalan. Nilai resmi yang sudah ada tidak dapat ditimpa.')}</p>}
                        <p className="mt-2">{t('Data identik dilewati. Jika ada kesalahan, seluruh impor ditahan. Penyimpanan dilakukan sekaligus setelah konfirmasi pratinjau.')}</p>
                    </div>
                </form>
                {importPreview && <div ref={resultArea} className={panel}>
                    <h2 className="text-lg font-semibold">{t('Hasil pemeriksaan impor')}</h2><p className="mt-2 break-words text-sm text-gray-600">{importPreview.filename} · {t(datasets[importPreview.dataset])} · {importPreview.total} {t('baris data')}</p><p className="mt-1 text-xs text-gray-500">{t(importPreview.mode === 'update' ? 'Mode: perbarui sesuai isi file.' : 'Mode: tambahkan saja.')} {t('Pratinjau berlaku selama 30 menit.')}</p>
                    <dl className="mt-4 grid gap-3 sm:grid-cols-4">{(['create', 'update', 'skip'] as const).map(action => <div key={action} className="rounded-lg border p-3"><dt className="text-sm text-gray-500">{t(actions[action])}</dt><dd className="text-xl font-semibold">{importPreview.counts[action]}</dd></div>)}<div className="rounded-lg border p-3"><dt className="text-sm text-gray-500">{t('Baris bermasalah')}</dt><dd className="text-xl font-semibold text-red-700">{importPreview.errors.length}</dd></div></dl>
                    {!!importPreview.errors.length && <div role="alert" className="mt-5 rounded-lg border border-red-200 bg-red-50 p-4"><h3 className="font-semibold text-red-900">{t('Perbaiki file dan unggah ulang. Belum ada data yang disimpan.')}</h3><ol className="mt-3 space-y-2 text-sm text-red-900">{importPreview.errors.slice(errorPage * 20, (errorPage + 1) * 20).map(error => <li key={error.row}><strong>{t('Baris')} {error.row} · {error.nis || '—'}:</strong> {error.messages.map(message => t(message)).join(' ')}</li>)}</ol>{importPreview.errors.length > 20 && <div className="mt-4 flex items-center gap-3"><button type="button" className="ec-action ec-action-secondary" disabled={errorPage === 0} onClick={() => setErrorPage(errorPage - 1)}>{t('Sebelumnya')}</button><span className="text-sm">{errorPage + 1} / {Math.ceil(importPreview.errors.length / 20)}</span><button type="button" className="ec-action ec-action-secondary" disabled={(errorPage + 1) * 20 >= importPreview.errors.length} onClick={() => setErrorPage(errorPage + 1)}>{t('Berikutnya')}</button></div>}</div>}
                    {!!importPreview.rows.length && <div className="mt-5 overflow-x-auto"><table className="w-full text-left text-xs"><caption className="mb-3 text-left text-gray-500">{t('Pratinjau maksimal 50 baris valid. Konfirmasi memproses seluruh file.')}</caption><thead><tr className="border-b bg-gray-50"><th className="p-3">{t('Baris')}</th><th className="p-3">{t('Tindakan')}</th>{importPreview.columns.map(column => <th key={column} className="min-w-28 p-3">{t(headings[column])}</th>)}</tr></thead><tbody>{importPreview.rows.map(row => <tr key={row.row} className="border-b"><td className="p-3">{row.row}</td><td className="p-3">{t(actions[row.action])}</td>{importPreview.columns.map(column => <td key={column} className="max-w-xs break-words p-3">{renderValue(column, row.values[column])}</td>)}</tr>)}</tbody></table></div>}
                    <form className="mt-5" onSubmit={confirm}>
                        {importPreview.dataset === 'scores' && !importPreview.errors.length && <label className="mb-4 flex items-start gap-3 rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm"><input type="checkbox" className="mt-1 rounded" disabled={!!busy} checked={approveScores} onChange={e => setApproveScores(e.target.checked)} /><span>{t('Saya telah memeriksa arsip nilai dan mengesahkannya sebagai nilai resmi. Identitas pembina dan waktu impor akan dicatat.')}</span></label>}
                        <InputError message={errors.approve_scores} /><InputError message={errors.import} />
                        <div className="mt-3 flex flex-wrap gap-3"><button className="ec-action ec-action-primary" disabled={!!busy || !!importPreview.errors.length || (importPreview.dataset === 'scores' && !approveScores)}><Icon name="save" />{busy === 'save' ? t('Menyimpan…') : t('Konfirmasi & Simpan Impor')}</button><button className="ec-action ec-action-secondary" type="button" disabled={!!busy} onClick={() => cancel()}>{t('Batalkan pratinjau')}</button></div>
                    </form>
                </div>}
                {!importPreview && <InputError message={errors.import} />}
    </div>;
}
