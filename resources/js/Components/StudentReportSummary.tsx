import { useTranslation } from '@/i18n/useTranslation';
import { panel } from '@/Components/AssessmentUi';
import { useId, useState } from 'react';

export interface SkillReport { aspect: string; label: string; count: number; average: number | null; latest: number | null; self_assessment: number | null }
export interface StudentReportData {
    skills: SkillReport[]; total_scores: number; graded_aspects: number; overall_average: number | null;
    last_approved_at: string | null; baseline_submitted_at: string | null;
    interests: { id: number; name: string; is_primary: boolean }[]; learning_goal: string | null;
}
const number = (value: number | null, locale: string) => value === null ? '—' : value.toLocaleString(locale, { maximumFractionDigits: 2 });

function SkillCharts({ skills, baseline }: { skills: SkillReport[]; baseline: string | null }) {
    const { t, locale, dateLocale } = useTranslation();
    const [mode, setMode] = useState<'radar' | 'bar'>('radar');
    const [compare, setCompare] = useState(false);
    const [selected, setSelected] = useState<string | null>(null);
    const titleId = useId(); const descriptionId = useId();
    const radius = 110; const center = 170;
    const point = (index: number, value: number) => {
        const angle = index * Math.PI / 2 - Math.PI / 2;
        return [center + Math.cos(angle) * radius * value / 4, center + Math.sin(angle) * radius * value / 4];
    };
    const polygon = (values: (number | null)[]) => values.map((value, index) => point(index, value ?? 0).join(',')).join(' ');
    const allOfficial = skills.every(skill => skill.average !== null);
    const allBaseline = skills.every(skill => skill.self_assessment !== null);
    const detail = skills.find(skill => skill.aspect === selected);
    return <div className={panel}>
        <div className="flex flex-wrap items-center justify-between gap-3"><h3 className="text-lg font-semibold">{t("Profil kemampuan")}</h3>
            <div role="group" aria-label={t("Jenis grafik kemampuan")} className="flex gap-2">{(['radar', 'bar'] as const).map(value => <button key={value} type="button" aria-pressed={mode === value} onClick={() => setMode(value)} className={`rounded px-3 py-2 text-sm ${mode === value ? 'ec-chart-official text-white' : 'bg-gray-100 text-gray-700'}`}>{value === 'radar' ? t("Radar") : t("Bar")}</button>)}</div>
        </div>
        <p className="mt-2 text-sm text-gray-500">{t("Rata-rata nilai resmi per aspek, skala 1–4. Aspek tanpa nilai ditampilkan sebagai belum dinilai.")}</p>
        {baseline && <label className="mt-4 flex items-center gap-2 text-sm"><input type="checkbox" className="rounded text-indigo-600" checked={compare} onChange={e => setCompare(e.target.checked)} />{t("Bandingkan dengan penilaian diri saat pre-test")}</label>}
        <div className="my-4 flex flex-wrap gap-4 text-sm"><span className="text-indigo-600">{t("● Nilai resmi")}</span>{compare && <span className="ec-chart-baseline-label">{t("● Penilaian diri awal")}</span>}</div>
        {mode === 'radar' ? <div className="mx-auto max-w-md">
            <svg viewBox="0 0 340 340" className="w-full text-gray-600 dark:text-gray-300" role="img" aria-labelledby={`${titleId} ${descriptionId}`}>
                <title id={titleId}>{t("Grafik radar kemampuan siswa")}</title><desc id={descriptionId}>{t("Empat aspek kemampuan pada skala 0 sampai 4. Nilai tersedia pada tabel di bawah. Aspek yang belum dinilai tidak diisi dengan nol.")}</desc>
                {[1, 2, 3, 4].map(level => <g key={level}><polygon points={polygon([level, level, level, level])} fill="none" stroke="currentColor" strokeOpacity="0.2" /><text x={center + 5} y={center - radius * level / 4 - 4} fontSize="10" fill="currentColor">{level}</text></g>)}
                {skills.map((skill, index) => { const [x, y] = point(index, 4); const [lx, ly] = point(index, 5.1); return <g key={skill.aspect}>
                    <line x1={center} y1={center} x2={x} y2={y} stroke="currentColor" strokeOpacity="0.2" />
                    <text x={lx} y={ly + 4} textAnchor="middle" fontSize="12" fill="currentColor">{skill.label}</text>
                </g>; })}
                {allOfficial && <polygon points={polygon(skills.map(skill => skill.average))} fill="var(--ec-blue)" fillOpacity="0.15" stroke="var(--ec-blue)" strokeWidth="2" />}
                {compare && allBaseline && <polygon points={polygon(skills.map(skill => skill.self_assessment))} fill="none" stroke="var(--ec-pink)" strokeWidth="2" strokeDasharray="5 4" />}
                {skills.map((skill, index) => <g key={skill.aspect}>
                    {skill.average !== null && <circle cx={point(index, skill.average)[0]} cy={point(index, skill.average)[1]} r="6" fill="var(--ec-blue)" tabIndex={0}
                        aria-label={t(":skill: :score dari 4, :count nilai resmi", { skill: skill.label, score: number(skill.average, dateLocale), count: skill.count })} onFocus={() => setSelected(skill.aspect)} onBlur={() => setSelected(null)} onMouseEnter={() => setSelected(skill.aspect)} onMouseLeave={() => setSelected(null)}><title>{skill.label}: {number(skill.average, dateLocale)}/4 · {skill.count}  {t("nilai")}</title></circle>}
                    {compare && skill.self_assessment !== null && <circle cx={point(index, skill.self_assessment)[0]} cy={point(index, skill.self_assessment)[1]} r="4" fill="var(--ec-pink)"><title>{t("Penilaian diri")} {skill.label}: {skill.self_assessment}/4</title></circle>}
                </g>)}
            </svg>
            {!allOfficial && <p className="text-center text-xs text-gray-500">{t("Bidang radar terhubung setelah keempat aspek memiliki nilai resmi. Titik menunjukkan aspek yang sudah dinilai.")}</p>}
        </div> : <div className="my-6 space-y-5" role="img" aria-label={t("Grafik bar rata-rata kemampuan siswa pada skala 0 sampai 4")}>
            {skills.map(skill => <div key={skill.aspect}><div className="mb-2 flex justify-between gap-3 text-sm"><span className="font-medium">{skill.label}</span><span>{skill.average === null ? t("Belum dinilai") : `${number(skill.average, dateLocale)}/4`}</span></div>
                <div className="h-5 overflow-hidden rounded bg-gray-100 dark:bg-gray-700"><div className="h-full rounded ec-chart-official" style={{ width: `${(skill.average ?? 0) / 4 * 100}%` }} /></div>
                {compare && skill.self_assessment !== null && <div className="mt-1 h-3 overflow-hidden rounded bg-gray-100 dark:bg-gray-700"><div className="h-full rounded ec-chart-baseline" style={{ width: `${skill.self_assessment / 4 * 100}%` }} /></div>}
            </div>)}<div className="flex justify-between text-xs text-gray-500">{[0, 1, 2, 3, 4].map(tick => <span key={tick}>{tick}</span>)}</div>
        </div>}
        <p className="mt-3 min-h-5 text-sm text-gray-500" aria-live="polite">{detail ? t(":skill: :score/4 dari :count nilai resmi.", { skill: detail.label, score: number(detail.average, dateLocale), count: detail.count }) : t("Rincian nilai tersedia pada tabel berikut.")}</p>
        <div className="mt-4 overflow-x-auto"><table className="w-full text-left text-sm"><caption className="sr-only">{t("Ringkasan kemampuan siswa")}</caption><thead><tr className="border-b">{[t("Aspek"), t("Rata-rata resmi"), t("Jumlah nilai"), t("Nilai terakhir"), ...(compare ? [t("Penilaian diri awal")] : [])].map(label => <th key={label} className="p-2">{t(label)}</th>)}</tr></thead>
            <tbody>{skills.map(skill => <tr key={skill.aspect} className="border-b"><th scope="row" className="p-2 font-medium">{skill.label}</th><td className="p-2">{skill.average === null ? t("Belum dinilai") : `${number(skill.average, dateLocale)}/4`}</td><td className="p-2">{skill.count}</td><td className="p-2">{number(skill.latest, dateLocale)}</td>{compare && <td className="p-2">{number(skill.self_assessment, dateLocale)}</td>}</tr>)}</tbody></table></div>
        {compare && <p className="mt-4 text-xs text-gray-500">{t("Penilaian diri mencerminkan persepsi siswa saat pre-test, dan ditampilkan sebagai pembanding. Tanggal pre-test:")} {baseline ? new Date(baseline).toLocaleDateString(dateLocale, { timeZone: 'Asia/Jakarta' }) : '—'}.</p>}
    </div>;
}

export default function StudentReportSummary({ report }: { report: StudentReportData }) {
    const { t, locale, dateLocale } = useTranslation();
    return <div className="space-y-6">
        <div className="grid gap-4 sm:grid-cols-3">{[[report.total_scores, t("Nilai resmi")], [`${report.graded_aspects}/4`, t("Aspek sudah dinilai")], [report.overall_average === null ? '—' : `${number(report.overall_average, dateLocale)}/4`, t("Rata-rata seluruh nilai")]].map(([value, label]) => <div key={label} className={panel}><p className="text-sm text-gray-500">{t(label)}</p><p className="mt-2 text-3xl font-semibold">{value}</p></div>)}</div>
        <SkillCharts skills={report.skills} baseline={report.baseline_submitted_at} />
        <div className={panel}><h3 className="text-lg font-semibold">{t("Minat & tujuan belajar")}</h3>
            {report.interests.length ? <div className="mt-3 flex flex-wrap gap-2">{report.interests.map(interest => <span key={interest.id} className={`rounded-full px-3 py-1 text-sm ${interest.is_primary ? 'bg-indigo-100 text-indigo-900' : 'bg-gray-100 text-gray-800'}`}>{interest.name}{interest.is_primary && ' · Utama'}</span>)}</div> : <p className="mt-2 text-sm text-gray-500">{t("Minat belum tercatat. Isi pre-test untuk mengenali minat dan tujuan belajar.")}</p>}
            {report.learning_goal && <p className="mt-4 whitespace-pre-wrap text-sm">{report.learning_goal}</p>}
        </div>
    </div>;
}
