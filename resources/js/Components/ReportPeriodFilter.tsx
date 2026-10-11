import { useTranslation } from '@/i18n/useTranslation';
import { field, panel } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export interface ReportPeriod { from: string; until: string }
export default function ReportPeriodFilter({ filters, url }: { filters: ReportPeriod; url: string }) {
    const { t, locale, dateLocale } = useTranslation();
    const [from, setFrom] = useState(filters.from); const [until, setUntil] = useState(filters.until);
    const page = usePage();
    const errors = page.props.errors;
    const perPage = new URL(page.url, window.location.origin).searchParams.get('per_page') ?? '10';
    return <div className={panel}><form className="flex flex-wrap items-end gap-3" onSubmit={e => { e.preventDefault(); router.get(url, { from, until, per_page: perPage }); }}>
        <div><label htmlFor="report_from" className="text-sm font-medium">{t("Dari tanggal pengesahan (WIB)")}</label><input id="report_from" type="date" className={field} value={from} onChange={e => setFrom(e.target.value)} /><InputError message={errors.from} /></div>
        <div><label htmlFor="report_until" className="text-sm font-medium">{t("Sampai tanggal")}</label><input id="report_until" type="date" min={from || undefined} className={field} value={until} onChange={e => setUntil(e.target.value)} /><InputError message={errors.until} /></div>
        <button className="ec-action ec-action-primary">{t("Terapkan")}</button><button type="button" className="ec-action ec-action-secondary" onClick={() => router.get(url, { per_page: perPage })}>{t("Semua periode")}</button>
    </form><p className="mt-3 text-xs text-gray-500">{t("Grafik dan riwayat mengikuti periode pengesahan. Kosongkan tanggal untuk melihat seluruh nilai; minat dan penilaian diri awal tetap ditampilkan sebagai konteks.")}</p></div>;
}
