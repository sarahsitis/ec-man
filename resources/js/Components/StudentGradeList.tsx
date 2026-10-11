import { useTranslation } from '@/i18n/useTranslation';
import { Assignment, Paging, Paginator, panel } from '@/Components/AssessmentUi';

export type Grade = Pick<Assignment, 'id' | 'title' | 'aspect' | 'final_score' | 'feedback' | 'review_note' | 'reviewed_at' | 'reviewer'>;
export default function StudentGradeList({ grades }: { grades: Paginator<Grade> }) {
    const { t, locale, dateLocale } = useTranslation();
    return <div className="space-y-4"><h3 className="text-lg font-semibold text-gray-800 dark:text-gray-200">{t("Riwayat nilai resmi")}</h3>
        {!grades.data.length && <div className={panel}>{t("Belum ada nilai resmi dalam periode ini.")}</div>}
        {grades.data.map(grade => <div key={grade.id} className={panel}><h4 className="font-semibold">{grade.title}</h4><p className="capitalize">{grade.aspect} · <strong>{grade.final_score}/4</strong></p>
            <p className="mt-3 whitespace-pre-wrap">{grade.feedback}</p>{grade.review_note && <p className="mt-2 whitespace-pre-wrap">{t("Catatan pembina:")} {grade.review_note}</p>}
            <p className="mt-3 text-sm text-gray-500">{t("Disahkan oleh")} {grade.reviewer?.name || t('Belum tercatat')}{grade.reviewed_at && ` · ${new Date(grade.reviewed_at).toLocaleDateString(dateLocale, { timeZone: 'Asia/Jakarta' })}`}</p>
        </div>)}<Paging page={grades} />
    </div>;
}
