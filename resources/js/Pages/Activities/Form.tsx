import { useTranslation } from '@/i18n/useTranslation';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { AcademicYear, Activity, ActivityScheme, yearLabel } from '@/Components/ManagementUi';
import { panel, field } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Form({ activity, years, schemes, statuses }: { activity: Activity | null; years: AcademicYear[]; schemes: ActivityScheme[]; statuses: Record<string, string> }) {
    const { t, locale, dateLocale } = useTranslation();
    const pembina = usePage().props.auth.user.role === 'pembina';
    const today = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
    const form = useForm({
        academic_year_id: String(activity?.academic_year_id ?? years.find(y=>y.is_active)?.id ?? years[0]?.id ?? ''),
        activity_scheme_id: String(activity?.activity_scheme_id ?? ''), title: activity?.title || '', category: activity?.category || '',
        description: activity?.description || '', objectives: activity?.objectives || '', agenda: activity?.agenda || '',
        activity_date: activity?.activity_date?.slice(0,10) || today, start_time: activity?.start_time?.slice(0,5) || '15:00',
        end_time: activity?.end_time?.slice(0,5) || '', location: activity?.location || '', pic: activity?.pic || '',
        target_audience: activity?.target_audience || '', status: activity?.status || 'scheduled',
    });
    const selectedScheme = schemes.find(s=>String(s.id)===form.data.activity_scheme_id);
    const applyScheme = () => {
        if (!selectedScheme) return;
        const [hour, minute] = form.data.start_time.split(':').map(Number);
        const end = hour*60 + minute + selectedScheme.duration_minutes;
        const endTime = Number.isFinite(end) && end<1440 ? `${String(Math.floor(end/60)).padStart(2,'0')}:${String(end%60).padStart(2,'0')}` : '';
        form.setData(data=>({ ...data, title: selectedScheme.name, category: selectedScheme.category, objectives: selectedScheme.objectives, agenda: selectedScheme.agenda, end_time: endTime }));
    };
    const title = activity ? t("Edit Kegiatan") : t("Buat Kegiatan");
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{title}</h2>}>
        <Head title={title} /><div className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6"><Link className="ec-action ec-action-secondary" href={activity ? route('activities.show',activity.id) : route('activities.index')}>{t("Kembali")}</Link>
            {years.length===0 ? <div className={panel}>{t("Tambahkan semester sebelum membuat kegiatan.")} {pembina ? <Link className="ec-action ec-action-edit" href={route('academic-years.index')}>{t("Kelola tahun ajaran")}</Link> : <p className="mt-2 text-sm">{t('Hubungi pembina untuk menambahkan semester.')}</p>}</div> : <div className={panel}>
                {!activity && <p className="mb-4 text-sm leading-6 text-gray-500">{t('Pertemuan berstatus terjadwal akan mengirim notifikasi kepada siswa aktif yang terdaftar pada semester kegiatan.')}</p>}
                <form className="grid gap-4 sm:grid-cols-2" onSubmit={e=>{e.preventDefault(); activity ? form.put(route('activities.update',activity.id)) : form.post(route('activities.store'));}}>
                    <fieldset disabled={form.processing} className="contents">
                        <div className="sm:col-span-2"><InputLabel htmlFor="form_year" value={t("Tahun ajaran dan semester")} /><select id="form_year" className={field} required value={form.data.academic_year_id} onChange={e=>form.setData('academic_year_id',e.target.value)}>{years.map(y=><option key={y.id} value={y.id}>{yearLabel(y, locale)}</option>)}</select><InputError message={form.errors.academic_year_id} /></div>
                        <div className="sm:col-span-2 rounded border border-indigo-200 p-4"><InputLabel htmlFor="form_scheme" value={t("Gunakan skema kegiatan (opsional)")} /><select id="form_scheme" className={field} value={form.data.activity_scheme_id} onChange={e=>form.setData('activity_scheme_id',e.target.value)}><option value="">{t("Tanpa skema")}</option>{schemes.map(s=><option key={s.id} value={s.id}>{s.name} · {s.duration_minutes}  {t("menit")}</option>)}</select><InputError message={form.errors.activity_scheme_id} /><button type="button" disabled={!selectedScheme} onClick={applyScheme} className="mt-3 rounded bg-indigo-100 px-3 py-2 text-sm text-indigo-900 disabled:opacity-50">{t("Terapkan judul, tujuan, dan agenda")}</button><p className="mt-2 text-xs text-gray-500">{t("Skema mengisi formulir dan waktu selesai berdasarkan jam mulai. Isian dapat disesuaikan untuk pertemuan ini.")}</p></div>
                        <div><InputLabel htmlFor="form_title" value={t("Judul kegiatan")} /><input id="form_title" className={field} required maxLength={255} value={form.data.title} onChange={e=>form.setData('title',e.target.value)} /><InputError message={form.errors.title} /></div>
                        <div><InputLabel htmlFor="form_category" value={t("Kategori")} /><input id="form_category" className={field} required maxLength={100} value={form.data.category} onChange={e=>form.setData('category',e.target.value)} placeholder={t("Percakapan / Debat / Lomba")} /><InputError message={form.errors.category} /></div>
                        <div><InputLabel htmlFor="form_date" value={t("Tanggal (WIB)")} /><input id="form_date" className={field} required type="date" value={form.data.activity_date} onChange={e=>form.setData('activity_date',e.target.value)} /><InputError message={form.errors.activity_date} /></div>
                        <div><InputLabel htmlFor="form_status" value={t("Status kegiatan")} /><select id="form_status" className={field} value={form.data.status} onChange={e=>form.setData('status',e.target.value)}>{Object.entries(statuses).map(([key,label])=><option key={key} value={key}>{t(label)}</option>)}</select><InputError message={form.errors.status} /></div>
                        <div><InputLabel htmlFor="form_start" value={t("Jam mulai (WIB)")} /><input id="form_start" className={field} type="time" required value={form.data.start_time} onChange={e=>form.setData('start_time',e.target.value)} /><InputError message={form.errors.start_time} /></div>
                        <div><InputLabel htmlFor="form_end" value={t("Jam selesai (opsional, WIB)")} /><input id="form_end" className={field} type="time" value={form.data.end_time} onChange={e=>form.setData('end_time',e.target.value)} /><InputError message={form.errors.end_time} /></div>
                        {(['location','pic','target_audience'] as const).map(key=><div key={key} className={key==='target_audience' ? 'sm:col-span-2' : ''}><InputLabel htmlFor={`form_${key}`} value={{ location:'Lokasi', pic:'Penanggung jawab', target_audience:'Sasaran peserta (keterangan)' }[key]} /><input id={`form_${key}`} className={field} maxLength={255} value={form.data[key]} onChange={e=>form.setData(key,e.target.value)} /><InputError message={form.errors[key]} />{key==='target_audience' && <p className="mt-1 text-xs text-gray-500">{t("Daftar presensi diambil dari anggota aktif semester kegiatan. Keterangan ini membantu menjelaskan sasaran kegiatan.")}</p>}</div>)}
                        {(['description','objectives','agenda'] as const).map(key=><div key={key} className="sm:col-span-2"><InputLabel htmlFor={`form_${key}`} value={{ description:t("Deskripsi"), objectives:t("Tujuan kegiatan"), agenda:t("Susunan agenda") }[key]} /><textarea id={`form_${key}`} className={field} rows={key==='agenda' ? 5 : 3} maxLength={key==='agenda' ? 10000 : 5000} value={form.data[key]} onChange={e=>form.setData(key,e.target.value)} /><InputError message={form.errors[key]} /></div>)}
                        <div className="sm:col-span-2"><PrimaryButton disabled={form.processing}>{t("Simpan kegiatan")}</PrimaryButton></div>
                    </fieldset>
                </form>
            </div>}
        </div>
    </AuthenticatedLayout>;
}
