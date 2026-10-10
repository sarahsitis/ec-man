import { field, panel } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export interface ReportPeriod { from: string; until: string }
export default function ReportPeriodFilter({ filters, url }: { filters: ReportPeriod; url: string }) {
    const [from, setFrom] = useState(filters.from); const [until, setUntil] = useState(filters.until);
    const errors = usePage().props.errors;
    return <div className={panel}><form className="flex flex-wrap items-end gap-3" onSubmit={e => { e.preventDefault(); router.get(url, { from, until }); }}>
        <div><label htmlFor="report_from" className="text-sm font-medium">Dari tanggal pengesahan (WIB)</label><input id="report_from" type="date" className={field} value={from} onChange={e => setFrom(e.target.value)} /><InputError message={errors.from} /></div>
        <div><label htmlFor="report_until" className="text-sm font-medium">Sampai tanggal</label><input id="report_until" type="date" min={from || undefined} className={field} value={until} onChange={e => setUntil(e.target.value)} /><InputError message={errors.until} /></div>
        <button className="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white">Terapkan</button><button type="button" className="px-3 py-2 text-sm text-indigo-600" onClick={() => router.get(url)}>Semua periode</button>
    </form><p className="mt-3 text-xs text-gray-500">Grafik dan riwayat mengikuti periode pengesahan. Kosongkan tanggal untuk melihat seluruh nilai; minat dan penilaian diri awal tetap ditampilkan sebagai konteks.</p></div>;
}
