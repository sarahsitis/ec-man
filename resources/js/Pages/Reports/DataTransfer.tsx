import InputError from '@/Components/InputError';
import DataImportPanel, { Dataset, Preview, Row } from '@/Components/DataImportPanel';
import { AcademicYear, yearLabel } from '@/Components/ManagementUi';
import { field, panel } from '@/Components/AssessmentUi';
import { Icon, PageHeading } from '@/Components/WorkspaceUi';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useTranslation } from '@/i18n/useTranslation';
import { Head, router, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

interface Filters {
    dataset: Dataset; period: 'week' | 'month' | 'semester'; academic_year_id: number | null;
    date: string; class_name: string; school_name: string; signatory: string; signatory_role: string; signatory_nip: string;
}
interface Section { title: string; columns: string[]; rows: Row[]; summary: Record<string, string | number> }
const statuses: Record<string, string> = { active: 'Aktif', inactive: 'Nonaktif', left: 'Keluar', alumni: 'Alumni', hadir: 'Hadir', izin: 'Izin', sakit: 'Sakit', alpa: 'Alpa', belum_diisi: 'Belum diisi' };

export default function DataTransfer({ filters, period, section, years, classes, datasets, headings, importPreview, initialTab }: {
    filters: Filters; period: { from: string; until: string; label: string } | null; section: Section | null;
    years: AcademicYear[]; classes: string[]; datasets: Record<Dataset, string>; headings: Record<string, string>; importPreview: Preview | null;
    initialTab: 'export' | 'import';
}) {
    const { t, locale } = useTranslation();
    const errors = usePage().props.errors;
    const [tab, setTab] = useState<'export' | 'import'>(initialTab);
    const [draft, setDraft] = useState(filters);
    const dirty = JSON.stringify(draft) !== JSON.stringify(filters);
    const exportUrl = route('data-transfer.export', { ...filters });
    const printUrl = (all: boolean) => route('data-transfer.print', { ...filters, dataset: all ? 'all' : filters.dataset });
    const renderValue = (column: string, value: Row[string]) => {
        if (value === '' || value === null) return '—';
        if (column === 'status') return t(statuses[String(value)] ?? String(value));
        if (column === 'semester') return t(value === 'ganjil' ? 'Ganjil' : 'Genap');
        return String(value);
    };
    const apply = (event: FormEvent) => { event.preventDefault(); router.get(route('data-transfer.index'), { ...draft }, { preserveScroll: true }); };
    return <AuthenticatedLayout>
        <Head title={t('Laporan & Arsip')} />
        <div className="mx-auto max-w-7xl space-y-6">
            <PageHeading eyebrow={t('ADMINISTRASI ENGLISH CLUB')} title={t('Laporan & Arsip')} description={t('Kelola pertukaran data dan susun laporan resmi mingguan, bulanan, atau semester.')} extra={section && period ? (dirty ? <button type="button" className="ec-action ec-action-primary" disabled title={t('Terapkan pengaturan yang berubah sebelum mengunduh laporan.')}><Icon name="save" />{t('Ekspor CSV')}</button> : <a className="ec-action ec-action-primary" href={exportUrl}><Icon name="save" />{t('Ekspor CSV')}</a>) : undefined} />
            <div className="flex flex-wrap gap-2" role="group" aria-label={t('Pilihan pengelolaan laporan')}>
                <button type="button" className={`ec-action ${tab === 'export' ? 'ec-action-primary' : 'ec-action-secondary'}`} aria-pressed={tab === 'export'} onClick={() => setTab('export')}><Icon name="chart" />{t('Ekspor & Laporan')}</button>
                <button type="button" className={`ec-action ${tab === 'import' ? 'ec-action-primary' : 'ec-action-secondary'}`} aria-pressed={tab === 'import'} onClick={() => setTab('import')}><Icon name="clipboard" />{t('Impor Data')}</button>
            </div>
            {tab === 'export' && <>
                <form className={panel} onSubmit={apply}>
                    <h2 className="text-lg font-semibold">{t('Pengaturan laporan')}</h2>
                    <div className="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div><label htmlFor="report_dataset" className="text-sm font-medium">{t('Jenis data')}</label><select id="report_dataset" className={field} value={draft.dataset} onChange={e => setDraft({ ...draft, dataset: e.target.value as Dataset })}>{Object.entries(datasets).map(([key, label]) => <option key={key} value={key}>{t(label)}</option>)}</select><InputError message={errors.dataset} /></div>
                        <div><label htmlFor="report_year" className="text-sm font-medium">{t('Tahun ajaran & semester')}</label><select id="report_year" className={field} value={draft.academic_year_id ?? ''} onChange={e => setDraft({ ...draft, academic_year_id: Number(e.target.value), class_name: '' })} required><option value="" disabled>{t('Pilih semester')}</option>{years.map(year => <option key={year.id} value={year.id}>{yearLabel(year, locale)}</option>)}</select><InputError message={errors.academic_year_id} /></div>
                        <div><label htmlFor="report_period" className="text-sm font-medium">{t('Periode Laporan')}</label><select id="report_period" className={field} value={draft.period} onChange={e => setDraft({ ...draft, period: e.target.value as Filters['period'] })}><option value="week">{t('Mingguan')}</option><option value="month">{t('Bulanan')}</option><option value="semester">{t('Semester')}</option></select><InputError message={errors.period} /></div>
                        {draft.period !== 'semester' && <div><label htmlFor="report_date" className="text-sm font-medium">{t(draft.period === 'week' ? 'Tanggal dalam minggu laporan' : 'Tanggal dalam bulan laporan')}</label><input id="report_date" type="date" className={field} value={draft.date} onChange={e => setDraft({ ...draft, date: e.target.value })} required /><InputError message={errors.date} /></div>}
                        <div><label htmlFor="report_class" className="text-sm font-medium">{t('Kelas')}</label><select id="report_class" className={field} value={draft.class_name} onChange={e => setDraft({ ...draft, class_name: e.target.value })}><option value="">{t('Semua kelas')}</option>{classes.map(name => <option key={name} value={name}>{name}</option>)}</select><InputError message={errors.class_name} /></div>
                        <div><label htmlFor="report_school" className="text-sm font-medium">{t('Nama sekolah / lembaga')}</label><input id="report_school" className={field} value={draft.school_name} maxLength={160} placeholder={t('Isi untuk kop laporan')} onChange={e => setDraft({ ...draft, school_name: e.target.value })} /><InputError message={errors.school_name} /></div>
                        <div><label htmlFor="report_signatory" className="text-sm font-medium">{t('Nama penandatangan')}</label><input id="report_signatory" className={field} value={draft.signatory} maxLength={160} onChange={e => setDraft({ ...draft, signatory: e.target.value })} /><InputError message={errors.signatory} /></div>
                        <div><label htmlFor="report_signatory_role" className="text-sm font-medium">{t('Jabatan penandatangan')}</label><input id="report_signatory_role" className={field} value={draft.signatory_role} maxLength={100} onChange={e => setDraft({ ...draft, signatory_role: e.target.value })} /><InputError message={errors.signatory_role} /></div>
                        <div><label htmlFor="report_signatory_nip" className="text-sm font-medium">{t('NIP penandatangan')}</label><input id="report_signatory_nip" className={field} value={draft.signatory_nip} inputMode="numeric" pattern="[0-9]{18}" maxLength={18} onChange={e => setDraft({ ...draft, signatory_nip: e.target.value })} /><InputError message={errors.signatory_nip} /></div>
                    </div>
                    <p className="mt-4 text-xs leading-6 text-gray-500">{t('Mingguan: Senin–Minggu. Bulanan: bulan kalender. Semester: Juli–Desember (ganjil) atau Januari–Juni (genap). Rentang dibatasi pada semester terpilih.')}</p>
                    <div className="mt-4"><button className="ec-action ec-action-primary" disabled={!years.length}><Icon name="check" />{t('Terapkan pengaturan')}</button></div>
                </form>
                {!years.length && <div role="status" className={panel}>{t('Tambahkan tahun ajaran dan keanggotaan sebelum membuat laporan. Impor siswa tetap tersedia.')} <a href={route('academic-years.index')} className="text-indigo-600 underline">{t('Tahun ajaran')}</a></div>}
                {section && period && <>
                    <div className={panel}>
                        <div className="flex flex-wrap items-start justify-between gap-4"><div><p className="text-xs uppercase tracking-widest text-gray-500">{t('Laporan English Club')}</p><h2 className="mt-1 text-xl font-semibold">{t(section.title)} · {t(period.label)}</h2><p className="mt-2 text-sm text-gray-500">{period.from} — {period.until} · WIB</p></div>
                            <div className="flex flex-wrap gap-2">{dirty ? <p role="status" className="text-sm text-amber-800">{t('Terapkan pengaturan yang berubah sebelum mengunduh laporan.')}</p> : <><a className="ec-action ec-action-primary" href={exportUrl}><Icon name="save" />{t('Unduh CSV')}</a><a className="ec-action ec-action-secondary" href={printUrl(false)} target="_blank" rel="noopener"><Icon name="book" />{t('Cetak / Simpan PDF')}</a><a className="ec-action ec-action-secondary" href={printUrl(true)} target="_blank" rel="noopener"><Icon name="clipboard" />{t('Cetak laporan lengkap')}</a></>}</div>
                        </div>
                        <dl className="mt-5 grid gap-3 sm:grid-cols-3 lg:grid-cols-4">{Object.entries(section.summary).map(([label, value]) => <div key={label} className="rounded-lg border border-gray-200 p-3"><dt className="text-xs text-gray-500">{t(label)}</dt><dd className="mt-1 text-lg font-semibold">{value}</dd></div>)}</dl>
                        <p className="mt-4 text-sm leading-6 text-gray-500">{t('Data siswa menunjukkan data induk saat ini; keanggotaan mengikuti semester terpilih. Presensi mengikuti tanggal kegiatan; nilai mengikuti tanggal pengesahan dalam WIB. Nilai menggunakan skala 1–4.')}</p>
                        <p className="mt-2 text-xs text-gray-500">{t('Filter kelas menggunakan kelas saat ini untuk data siswa dan kelas keanggotaan semester untuk data lainnya.')}</p>
                        {filters.dataset === 'attendances' && <p className="mt-2 text-sm leading-6 text-gray-500">{t('Persentase kehadiran dihitung dari presensi yang sudah diisi. Data belum diisi dan kegiatan dibatalkan tidak dihitung sebagai ketidakhadiran.')}</p>}
                        <p className="mt-2 text-xs text-gray-500">{t('CSV memakai kolom template agar dapat diimpor kembali. Laporan cetak memuat kop, ringkasan, tabel, dan ruang tanda tangan.')}</p>
                    </div>
                    <div className={panel}><h2 className="text-lg font-semibold">{t('Pratinjau laporan')}</h2><p className="mt-2 text-xs text-gray-500">{t('Pratinjau menampilkan maksimal 20 baris. Ekspor dan cetak mencakup seluruh data sesuai filter.')}</p><div className="mt-4 overflow-x-auto"><table className="w-full text-left text-sm"><caption className="sr-only">{t('Pratinjau laporan')}</caption><thead><tr className="border-b bg-gray-50"><th className="p-3">{t('No.')}</th>{section.columns.map(column => <th key={column} className="min-w-32 p-3">{t(headings[column])}</th>)}</tr></thead><tbody>{section.rows.map((row, index) => <tr key={index} className="border-b"><td className="p-3">{index + 1}</td>{section.columns.map(column => <td key={column} className="max-w-xs break-words p-3">{renderValue(column, row[column])}</td>)}</tr>)}{!section.rows.length && <tr><td colSpan={section.columns.length + 1} className="p-8 text-center text-gray-500">{t('Tidak ada data pada periode dan kelas yang dipilih.')}</td></tr>}</tbody></table></div></div>
                </>}
            </>}
            {tab === 'import' && <DataImportPanel initialDataset={filters.dataset} initialPreview={importPreview} initialHeadings={headings} />}
        </div>
    </AuthenticatedLayout>;
}
