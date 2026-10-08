import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Assignment, Paging, Paginator, panel } from '@/Components/AssessmentUi';
import { Head } from '@inertiajs/react';
type Grade = Pick<Assignment,'id'|'title'|'aspect'|'final_score'|'feedback'|'review_note'|'reviewed_at'|'reviewer'>;
export default function Progress({grades}:{grades:Paginator<Grade>}) {
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Perkembangan Saya</h2>}>
        <Head title="Perkembangan Saya"/><div className="mx-auto max-w-4xl space-y-4 px-4 py-8 sm:px-6"><p className="text-sm text-gray-600">Hasil berikut sudah disahkan pembina. Skor menggunakan rubrik internal 1–4.</p>
            {grades.data.length===0 && <div className={panel}>Belum ada penilaian yang disahkan.</div>}
            {grades.data.map(g=><div key={g.id} className={panel}><h3 className="text-lg font-semibold">{g.title}</h3><p className="capitalize">{g.aspect} — <strong>{g.final_score}/4</strong></p><p className="mt-3 whitespace-pre-wrap">{g.feedback}</p>{g.review_note && <p className="mt-2 whitespace-pre-wrap">Catatan pembina: {g.review_note}</p>}<p className="mt-3 text-sm text-gray-500">Disahkan oleh {g.reviewer?.name}{g.reviewed_at && ` · ${new Date(g.reviewed_at).toLocaleDateString('id-ID',{timeZone:'Asia/Jakarta'})}`}</p></div>)}
            <Paging page={grades}/>
        </div>
    </AuthenticatedLayout>;
}
