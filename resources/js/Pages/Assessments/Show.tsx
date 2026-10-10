import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { Assignment, panel, field, statuses } from '@/Components/AssessmentUi';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';
function Recommendation({item, expired}:{item:Assignment;expired:boolean}) {
    const form = useForm<{proposed_score:string;observations:string;feedback:string;action:string;deadline?:string}>({proposed_score:String(item.proposed_score ?? ''),observations:item.observations ?? '',feedback:item.feedback ?? '',action:'draft'});
    const submit = (action:string) => {
        form.transform(data=>({...data,action}));
        form.post(route('assessments.recommend',item.id));
    };
    return <div className={panel}><h3 className="text-lg font-semibold">Rekomendasi penilaian</h3>
        {expired && <p className="mt-2 text-red-600">Batas waktu telah lewat. Minta pembina memperpanjangnya.</p>}
        <form onSubmit={e=>e.preventDefault()} className="mt-4 space-y-4">
            <div><InputLabel value="Skor usulan (1–4)"/><select className={field} value={form.data.proposed_score} disabled={expired} onChange={e=>form.setData('proposed_score',e.target.value)}><option value="">Belum dinilai</option>{[1,2,3,4].map(n=><option key={n} value={n}>{n}</option>)}</select><InputError message={form.errors.proposed_score}/></div>
            <div><InputLabel value="Catatan pengamatan / Bukti"/><textarea className={field} rows={3} maxLength={2000} value={form.data.observations} disabled={expired} onChange={e=>form.setData('observations',e.target.value)}/><InputError message={form.errors.observations}/></div>
            <div><InputLabel value="Umpan balik dan saran latihan"/><textarea className={field} rows={3} maxLength={2000} value={form.data.feedback} disabled={expired} onChange={e=>form.setData('feedback',e.target.value)}/><InputError message={form.errors.feedback}/></div>
            <InputError message={form.errors.deadline}/><InputError message={form.errors.action}/>
            <div className="flex flex-wrap gap-3"><button type="button" disabled={expired || form.processing} className="rounded border px-4 py-2 disabled:opacity-50" onClick={()=>submit('draft')}>Simpan draf</button><PrimaryButton type="button" disabled={expired || form.processing} onClick={()=>submit('submit')}>Kirim ke pembina</PrimaryButton></div>
            <p className="text-sm text-gray-500">Pengajuan membutuhkan skor serta catatan dan umpan balik masing-masing minimal 10 karakter. Setelah diajukan, rekomendasi terkunci sampai pembina mengembalikannya untuk revisi.</p>
        </form>
    </div>;
}
function Review({item}:{item:Assignment}) {
    const form=useForm({decision:'approve',final_score:String(item.proposed_score ?? ''),review_note:''});
    const submit:FormEventHandler=e=>{e.preventDefault();form.post(route('assessments.review',item.id));};
    return <div className={panel}><h3 className="text-lg font-semibold">Keputusan pembina</h3><p className="mt-2 text-sm text-gray-500">Sahkan rekomendasi untuk menerbitkan nilai resmi. Revisi dan penolakan membutuhkan catatan untuk panitia.</p><form onSubmit={submit} className="mt-4 space-y-4">
        <div><InputLabel value="Keputusan"/><select className={field} value={form.data.decision} onChange={e=>form.setData('decision',e.target.value)}><option value="approve">Sahkan nilai</option><option value="revise">Kembalikan untuk revisi</option><option value="reject">Tolak rekomendasi</option></select><InputError message={form.errors.decision}/></div>
        {form.data.decision==='approve' && <div><InputLabel value="Skor resmi (1–4)"/><select className={field} required value={form.data.final_score} onChange={e=>form.setData('final_score',e.target.value)}><option value="">Pilih skor</option>{[1,2,3,4].map(n=><option key={n} value={n}>{n}</option>)}</select><InputError message={form.errors.final_score}/></div>}
        <div><InputLabel value="Catatan pembina"/><textarea className={field} rows={3} value={form.data.review_note} maxLength={2000} onChange={e=>form.setData('review_note',e.target.value)}/><InputError message={form.errors.review_note}/><p className="text-sm text-gray-500">Wajib jika mengubah skor, meminta revisi, atau menolak. Catatan pengesahan dapat dilihat siswa.</p></div>
        <PrimaryButton disabled={form.processing}>Simpan keputusan</PrimaryButton>
    </form></div>;
}
function Deadline({item}:{item:Assignment}) {
    const form=useForm({due_date:item.due_date ?? ''});
    return <div className={panel}><h3 className="font-semibold">Batas waktu penugasan</h3>
        <form onSubmit={e=>{e.preventDefault();form.post(route('assessments.deadline',item.id));}} className="mt-3 space-y-3">
            <input type="date" className={field} value={form.data.due_date} onChange={e=>form.setData('due_date',e.target.value)}/>
            <p className="text-sm text-gray-500">Kosongkan untuk tanpa batas waktu. Batas berlaku sampai akhir hari dalam WIB.</p>
            <InputError message={form.errors.due_date}/><PrimaryButton disabled={form.processing}>Perbarui batas waktu</PrimaryButton>
        </form>
    </div>;
}
export default function Show({assignment:item,expired,nextPendingId}:{assignment:Assignment;expired:boolean;nextPendingId:number|null}) {
    const user=usePage().props.auth.user;const pembina=user.role==='pembina';const editable=['assigned','draft','revision'].includes(item.status);
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{item.title}</h2>}>
        <Head title="Detail Penilaian"/><div className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
            <div className="flex flex-wrap gap-4"><Link href={pembina?route('assessments.queue'):route('panitia.assignments')} className="text-indigo-600">{pembina ? 'Kembali ke antrean pemeriksaan' : 'Kembali ke penugasan saya'}</Link>{pembina && <Link href={route('assessments.index')} className="text-indigo-600">Semua penugasan</Link>}{pembina && nextPendingId && item.status !== 'submitted' && <Link href={route('assessments.show', {assignment:nextPendingId,from:'queue'})} className="font-medium text-indigo-600">Periksa rekomendasi berikutnya</Link>}</div>
            <div className={panel}><h3 className="text-lg font-semibold">{item.student.full_name}</h3><p>{item.student.student_number} · {item.student.class_name || 'Kelas belum diisi'}</p><p className="mt-2 capitalize">Aspek: {item.aspect}</p><p>Panitia: {item.assessor?.name}</p><p>Status: {statuses[item.status]}</p><p>Batas waktu: {item.due_date || 'Tanpa batas'} (WIB)</p></div>
            {item.assessment?.instructions && <div className={panel}><h3 className="font-semibold">Instruksi pembina</h3><p className="mt-2 whitespace-pre-wrap">{item.assessment.instructions}</p></div>}
            <div className={panel}><h3 className="font-semibold">Kemampuan awal dan minat siswa</h3><p className="my-2 text-sm text-gray-500">Lihat pre-test sebagai acuan untuk pengamatan dan saran latihan.</p><Link className="text-indigo-600" href={route('pretests.show', item.student.id)}>Lihat hasil pre-test siswa</Link></div>
            <div className={panel}><h3 className="text-lg font-semibold">Rubrik skala internal 1–4</h3><p className="mb-3 text-sm text-gray-500">{item.rubric.version}</p>{Object.entries(item.rubric.levels).map(([score,description])=><p key={score} className="mb-2"><strong>{score}:</strong> {description}</p>)}</div>
            {item.review_note && <div className={panel}><h3 className="font-semibold">Catatan pembina</h3><p className="mt-2 whitespace-pre-wrap">{item.review_note}</p></div>}
            {!pembina && editable ? <Recommendation key={item.status} item={item} expired={expired}/> : <div className={panel}><h3 className="font-semibold">Rekomendasi panitia</h3><p className="mt-2">Skor usulan: {item.proposed_score ?? 'Belum dinilai'}</p><p className="mt-3 whitespace-pre-wrap">Pengamatan: {item.observations || '—'}</p><p className="mt-3 whitespace-pre-wrap">Umpan balik: {item.feedback || '—'}</p></div>}
            {pembina && !['approved','rejected'].includes(item.status) && <Deadline item={item}/>}
            {pembina && item.status==='submitted' && <Review item={item}/>}
            {item.official_score && <div className={panel}><h3 className="font-semibold">Nilai resmi: {item.official_score.value}/4</h3><p>Disahkan oleh {item.official_score.approver?.name || item.reviewer?.name}</p><p className="mt-2 text-sm text-gray-500">Nilai telah diterbitkan ke halaman perkembangan siswa.</p></div>}
            <div className={panel}><h3 className="mb-3 font-semibold">Riwayat penilaian</h3>{item.events?.map(e=><p key={e.id} className="mb-2 text-sm">{new Date(e.created_at).toLocaleString('id-ID',{timeZone:'Asia/Jakarta'})} WIB — {statuses[e.action] || e.action}</p>)}</div>
        </div>
    </AuthenticatedLayout>;
}
