import WorkspaceLayout from '@/Layouts/WorkspaceLayout';
import { Assignment } from '@/Components/AssessmentUi';
import { AssessmentBadge, Avatar, ConfirmAction, Icon, PageHeading } from '@/Components/WorkspaceUi';
import { dateLabel, recommendationErrors, RecommendationData, timeLabel } from '@/Components/assessmentPresentation';
import useUnsavedChanges from '@/hooks/useUnsavedChanges';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

function ErrorMessage({ id, message }: { id: string; message?: string }) {
    return message ? <p id={id} className="ec-error" role="alert">{message}</p> : null;
}
function focusError() {
    requestAnimationFrame(() => document.querySelector<HTMLElement>('[aria-invalid="true"]')?.focus());
}

function Recommendation({ item, expired, onScoreChange }: { item: Assignment; expired: boolean; onScoreChange: (score: string) => void }) {
    const form = useForm<RecommendationData & { action: 'draft' | 'submit'; deadline?: string }>({
        proposed_score: String(item.proposed_score ?? ''), observations: item.observations ?? '', feedback: item.feedback ?? '', action: 'draft',
    });
    const [confirm, setConfirm] = useState(false);
    const requestLock = useRef(false);
    useUnsavedChanges(form.isDirty, form.processing);

    const validate = (submit: boolean) => {
        form.clearErrors();
        const errors = recommendationErrors(form.data, submit);
        if (Object.keys(errors).length) { form.setError(errors); focusError(); return false; }
        return true;
    };
    const send = (action: 'draft' | 'submit') => {
        if (expired || requestLock.current || form.processing) { return; }
        requestLock.current = true;
        setConfirm(false);
        form.transform(data => ({ ...data, action }));
        form.post(route('assessments.recommend', item.id), {
            preserveScroll: true,
            onSuccess: () => form.setDefaults(form.data),
            onError: focusError,
            onFinish: () => { requestLock.current = false; },
        });
    };
    const disabled = expired || form.processing;
    return <section className="ec-card"><form noValidate aria-busy={form.processing} onSubmit={event => { event.preventDefault(); if (!disabled && validate(true)) { setConfirm(true); } }}>
        <div className="ec-card-body"><p className="ec-eyebrow">Observasi panitia</p><h2>Rekomendasi penilaian</h2><p className="ec-hint">Simpan pengamatan sebagai draf atau kirim rekomendasi lengkap untuk diperiksa pembina.</p>
            {expired && <div className="ec-notice ec-notice-error mt-4"><strong>Batas waktu telah lewat</strong>Minta pembina memperpanjang batas waktu sebelum menyimpan atau mengirim rekomendasi.</div>}
            <div className="ec-field"><label className="ec-label" htmlFor="proposed_score">Skor usulan (1–4)</label>
                <select id="proposed_score" className="ec-input" value={form.data.proposed_score} disabled={disabled} aria-invalid={!!form.errors.proposed_score} aria-describedby={`proposed_score_hint${form.errors.proposed_score ? ' proposed_score_error' : ''}`} onChange={event => { form.setData('proposed_score', event.target.value); onScoreChange(event.target.value); }}>
                    <option value="">Belum dinilai</option>{Object.entries(item.rubric.levels).map(([score]) => <option key={score} value={score}>{score}</option>)}
                </select><p id="proposed_score_hint" className="ec-hint">Pilih skor berdasarkan rubrik {item.aspect} pada penugasan ini.</p><ErrorMessage id="proposed_score_error" message={form.errors.proposed_score} />
            </div>
            {([{ key: 'observations', label: 'Catatan pengamatan / bukti', placeholder: 'Catat respons, perilaku, atau contoh hasil praktik yang Anda amati.' }, { key: 'feedback', label: 'Umpan balik dan saran latihan', placeholder: 'Jelaskan kekuatan siswa dan latihan yang dapat dilakukan berikutnya.' }] as const).map(field => <div className="ec-field" key={field.key}>
                <label className="ec-label" htmlFor={field.key}>{field.label}</label><textarea id={field.key} className="ec-input" rows={4} maxLength={2000} value={form.data[field.key]} disabled={disabled} placeholder={field.placeholder}
                    aria-invalid={!!form.errors[field.key]} aria-describedby={`${field.key}_hint${form.errors[field.key] ? ` ${field.key}_error` : ''}`} onChange={event => form.setData(field.key, event.target.value)} />
                <p id={`${field.key}_hint`} className="ec-hint">Minimal 10 karakter saat dikirim; maksimal 2.000 karakter. {form.data[field.key].length}/2.000</p><ErrorMessage id={`${field.key}_error`} message={form.errors[field.key]} />
            </div>)}
            <ErrorMessage id="deadline_error" message={form.errors.deadline} /><ErrorMessage id="action_error" message={form.errors.action} />
            <p className="ec-info mt-5">Setelah dikirim, rekomendasi terkunci selama ditinjau. Anda dapat mengedit lagi jika pembina meminta revisi.</p>
        </div><div className="ec-form-actions">{form.isDirty && <span className="ec-unsaved" role="status">Ada perubahan belum disimpan</span>}
            <button type="button" className="ec-btn" disabled={disabled} onClick={() => { if (validate(false)) { send('draft'); } }}><Icon name="save" />{form.processing ? 'Menyimpan…' : 'Simpan draf'}</button>
            <button type="submit" className="ec-btn ec-btn-primary" disabled={disabled}><Icon name="send" />{form.processing ? 'Memproses…' : 'Kirim ke pembina'}</button>
        </div>
    </form><ConfirmAction open={confirm} title="Kirim rekomendasi ke pembina?" label="Kirim rekomendasi" busy={form.processing} onClose={() => setConfirm(false)} onConfirm={() => send('submit')}>
        <p>Rekomendasi untuk <strong>{item.student.full_name}</strong> akan terkunci sampai pembina meminta revisi atau memberikan keputusan.</p><div className="ec-dialog-score"><span>Skor usulan · {item.aspect}</span><strong>{form.data.proposed_score}/4</strong></div><p>Siswa melihat nilai resmi setelah pembina mengesahkannya.</p>
    </ConfirmAction></section>;
}

function Review({ item }: { item: Assignment }) {
    const form = useForm({ decision: 'approve', final_score: String(item.proposed_score ?? ''), review_note: '' });
    const [confirm, setConfirm] = useState(false);
    const requestLock = useRef(false);
    useUnsavedChanges(form.isDirty, form.processing);
    const noteRequired = form.data.decision !== 'approve' || form.data.final_score !== String(item.proposed_score ?? '');
    const approve = form.data.decision === 'approve';
    const label = approve ? 'Sahkan nilai' : form.data.decision === 'revise' ? 'Kirim permintaan revisi' : 'Tolak rekomendasi';
    const save = () => {
        if (requestLock.current || form.processing) { return; }
        requestLock.current = true; setConfirm(false);
        form.post(route('assessments.review', item.id), { preserveScroll: true, onError: focusError, onSuccess: () => form.setDefaults(form.data), onFinish: () => { requestLock.current = false; } });
    };
    return <section className="ec-card"><form aria-busy={form.processing} onSubmit={event => { event.preventDefault(); form.clearErrors(); if (noteRequired && !form.data.review_note.trim()) { form.setError('review_note', 'Tuliskan catatan untuk keputusan ini.'); focusError(); return; } setConfirm(true); }}>
        <div className="ec-card-body"><p className="ec-eyebrow">Tinjauan pembina</p><h2>Keputusan rekomendasi</h2><p className="ec-hint">Periksa rubrik, bukti pengamatan, dan saran latihan sebelum memberikan keputusan.</p>
            <div className="ec-field"><label className="ec-label" htmlFor="decision">Keputusan</label><select id="decision" className="ec-input" disabled={form.processing} value={form.data.decision} aria-invalid={!!form.errors.decision} aria-describedby={form.errors.decision ? 'decision_error' : undefined} onChange={event => form.setData('decision', event.target.value)}><option value="approve">Sahkan nilai</option><option value="revise">Kembalikan untuk revisi</option><option value="reject">Tolak rekomendasi</option></select><ErrorMessage id="decision_error" message={form.errors.decision} /></div>
            {approve && <div className="ec-field"><label className="ec-label" htmlFor="final_score">Skor resmi (1–4)</label><select id="final_score" className="ec-input" required disabled={form.processing} value={form.data.final_score} aria-invalid={!!form.errors.final_score} aria-describedby={form.errors.final_score ? 'final_score_error' : undefined} onChange={event => form.setData('final_score', event.target.value)}><option value="">Pilih skor</option>{Object.keys(item.rubric.levels).map(score => <option key={score} value={score}>{score}</option>)}</select><ErrorMessage id="final_score_error" message={form.errors.final_score} /></div>}
            <div className="ec-field"><label className="ec-label" htmlFor="review_note">Catatan pembina {noteRequired ? '(wajib)' : '(opsional)'}</label><textarea id="review_note" className="ec-input" rows={4} disabled={form.processing} maxLength={2000} value={form.data.review_note} aria-invalid={!!form.errors.review_note} aria-describedby={`review_note_hint${form.errors.review_note ? ' review_note_error' : ''}`} onChange={event => form.setData('review_note', event.target.value)} />
                <p id="review_note_hint" className="ec-hint">Wajib jika mengubah skor, meminta revisi, atau menolak. Catatan pengesahan dapat dilihat siswa. Maksimal 2.000 karakter.</p><ErrorMessage id="review_note_error" message={form.errors.review_note} />
            </div>
        </div><div className="ec-form-actions">{form.isDirty && <span className="ec-unsaved">Ada perubahan belum disimpan</span>}<button type="submit" className="ec-btn ec-btn-primary" disabled={form.processing}><Icon name={approve ? 'check' : 'send'} />{form.processing ? 'Memproses…' : label}</button></div>
    </form><ConfirmAction open={confirm} title={approve ? 'Sahkan nilai siswa?' : form.data.decision === 'revise' ? 'Kembalikan rekomendasi untuk revisi?' : 'Tolak rekomendasi?'} label={label} busy={form.processing} onClose={() => setConfirm(false)} onConfirm={save}>
        <p>Penugasan untuk <strong>{item.student.full_name}</strong>.</p>{approve ? <><div className="ec-dialog-score"><span>Nilai resmi · {item.aspect}</span><strong>{form.data.final_score}/4</strong></div><p>Nilai akan diterbitkan ke halaman perkembangan siswa. Keputusan pengesahan mengunci penugasan ini.</p></> : <p>{form.data.decision === 'revise' ? 'Panitia dapat memperbaiki pengamatan dan mengirim rekomendasi kembali.' : 'Rekomendasi akan ditutup dengan status ditolak dan tidak diterbitkan sebagai nilai resmi.'}</p>}
    </ConfirmAction></section>;
}

function Deadline({ item }: { item: Assignment }) {
    const form = useForm({ due_date: item.due_date ?? '' });
    return <section className="ec-card ec-card-body"><h2>Batas waktu penugasan</h2><form aria-busy={form.processing} className="ec-field" onSubmit={event => { event.preventDefault(); form.post(route('assessments.deadline', item.id), { preserveScroll: true, onError: focusError }); }}>
        <label className="ec-label" htmlFor="due_date">Tanggal batas waktu (WIB)</label><input type="date" id="due_date" className="ec-input" value={form.data.due_date} disabled={form.processing} aria-invalid={!!form.errors.due_date} aria-describedby={`due_date_hint${form.errors.due_date ? ' due_date_error' : ''}`} onChange={event => form.setData('due_date', event.target.value)} /><p id="due_date_hint" className="ec-hint">Kosongkan untuk tanpa batas waktu. Batas berlaku sampai akhir hari dalam WIB.</p><ErrorMessage id="due_date_error" message={form.errors.due_date} /><button type="submit" className="ec-btn mt-4" disabled={form.processing}>{form.processing ? 'Memperbarui…' : 'Perbarui batas waktu'}</button>
    </form></section>;
}

function ScoreSummary({ item, score }: { item: Assignment; score: string }) {
    const official = !!item.official_score;
    const value = official ? item.official_score!.value : score || '—';
    const step = official ? 3 : ['submitted', 'rejected'].includes(item.status) ? 2 : 1;
    return <aside className="ec-card ec-card-body ec-score-summary" aria-label="Ringkasan penilaian"><p className="ec-eyebrow">{official ? 'Nilai resmi' : 'Ringkasan observasi'}</p><h2>{official ? 'Hasil yang disahkan' : 'Skor usulan'}</h2><p className="ec-score-large" aria-live="polite">{value} <small>/ 4</small></p><AssessmentBadge status={item.status} />
        <dl className="ec-summary-meta"><div><dt>Aspek kemampuan</dt><dd className="capitalize">{item.aspect}</dd></div><div><dt>Panitia penilai</dt><dd>{item.assessor?.name || 'Belum tercatat'}</dd></div><div><dt>Batas waktu (WIB)</dt><dd>{dateLabel(item.due_date)}</dd></div>{item.submitted_at && <div><dt>Rekomendasi dikirim</dt><dd>{timeLabel(item.submitted_at)}</dd></div>}{item.reviewed_at && <div><dt>Tinjauan pembina</dt><dd>{timeLabel(item.reviewed_at)}</dd></div>}</dl>
        <ol className="ec-flow" aria-label="Tahap penilaian">{['Observasi panitia', item.status === 'rejected' ? 'Rekomendasi ditolak' : 'Tinjauan pembina', item.status === 'rejected' ? 'Nilai tidak diterbitkan' : 'Nilai resmi untuk siswa'].map((label, index) => <li key={label} className={index + 1 < step || official ? 'is-done' : index + 1 === step ? 'is-current' : ''} aria-current={!official && index + 1 === step ? 'step' : undefined}><span aria-hidden="true">{index + 1 < step || official ? '✓' : index + 1}</span>{label}</li>)}</ol>
        <p className="ec-info">{official ? 'Nilai resmi sudah diterbitkan dan tersedia pada halaman perkembangan siswa.' : item.status === 'rejected' ? 'Rekomendasi ditutup oleh pembina. Tidak ada nilai resmi dari penugasan ini.' : 'Skor usulan menggunakan rubrik 1–4. Pembina memegang keputusan akhir sebelum nilai diterbitkan.'}</p>
    </aside>;
}

export default function Show({ assignment: item, expired, nextPendingId }: { assignment: Assignment; expired: boolean; nextPendingId: number | null }) {
    const user = usePage().props.auth.user;
    const pembina = user.role === 'pembina';
    const editable = user.is_panitia && ['assigned', 'draft', 'revision'].includes(item.status);
    const [liveScore, setLiveScore] = useState(String(item.proposed_score ?? ''));
    useEffect(() => { setLiveScore(String(item.proposed_score ?? '')); }, [item.id, item.proposed_score]);
    const eventLabels: Record<string, string> = { assigned: 'Penugasan dibuat', draft: 'Draf disimpan', submitted: 'Rekomendasi dikirim', revision: 'Revisi diminta pembina', approved: 'Nilai disahkan', rejected: 'Rekomendasi ditolak', deadline: 'Batas waktu diperbarui' };
    return <WorkspaceLayout><Head title={`Penilaian ${item.student.full_name}`} />
        <div className="flex flex-wrap gap-x-5"><Link className="ec-link ec-back" href={pembina ? route('assessments.queue') : route('panitia.assignments', ['submitted', 'approved', 'rejected'].includes(item.status) ? { status: 'history' } : {})}><Icon name="back" />{pembina ? 'Kembali ke antrean pemeriksaan' : 'Kembali ke penugasan saya'}</Link>{pembina && <Link className="ec-link ec-back" href={route('assessments.index')}>Semua penugasan</Link>}</div>
        <PageHeading eyebrow={pembina ? 'Ruang pembina' : 'Ruang panitia EC'} title={item.title} description={pembina ? 'Periksa rekomendasi panitia dan tentukan hasil tinjauan.' : 'Catat kemampuan siswa berdasarkan rubrik dan bukti pengamatan.'} />
        {item.status === 'revision' && <div className="ec-notice"><strong>Perlu revisi</strong>{item.review_note || 'Pembina meminta Anda memperbaiki rekomendasi sebelum mengirim kembali.'}</div>}
        {item.status === 'submitted' && !pembina && <div className="ec-notice"><strong>Rekomendasi sudah dikirim</strong>Penilaian terkunci selama ditinjau pembina. Anda dapat mengedit lagi jika pembina meminta revisi.</div>}
        {item.status === 'rejected' && <div className="ec-notice ec-notice-error"><strong>Rekomendasi ditolak</strong>{item.review_note || 'Pembina menolak rekomendasi ini. Penugasan terkunci dan nilai resmi tidak diterbitkan.'}</div>}
        {item.official_score && <div className="ec-notice ec-notice-success"><strong>Nilai resmi telah diterbitkan</strong>Disahkan oleh {item.official_score.approver?.name || item.reviewer?.name || 'pembina'} dan tersedia pada halaman perkembangan siswa.</div>}
        <div className="ec-detail-grid"><div className="space-y-5">
            <section className="ec-card"><div className="ec-profile-strip"><Avatar name={item.student.full_name} /><div><h2>{item.student.full_name}</h2><p>{item.student.student_number} · {item.student.class_name || 'Kelas belum diisi'}</p></div><AssessmentBadge status={item.status} /></div>
                <div className="ec-card-body">{item.assessment?.instructions && <div className="ec-read-section"><h3>Instruksi pembina</h3><p>{item.assessment.instructions}</p></div>}<div className="ec-read-section"><h3>Kemampuan awal dan minat</h3><p>Gunakan pre-test sebagai acuan pengamatan dan saran latihan.</p><div className="mt-3 flex flex-wrap gap-x-5"><Link className="ec-link" href={route('pretests.show', item.student.id)}>Lihat hasil pre-test<Icon name="arrow" /></Link><Link className="ec-link" href={route('reports.student', item.student.id)}>Profil dan perkembangan siswa<Icon name="arrow" /></Link></div></div></div>
            </section>
            <section className="ec-card ec-card-body"><p className="ec-eyebrow">Rubrik {item.aspect}</p><h2>Skala kemampuan 1–4</h2><p className="ec-hint">{item.rubric.version} · Gunakan deskripsi berikut sebagai acuan pengamatan.</p><ol className="ec-rubric">{Object.entries(item.rubric.levels).map(([score, description]) => <li key={score}><strong>{score}</strong><span>{description}</span></li>)}</ol></section>
            {item.review_note && !['revision', 'rejected'].includes(item.status) && <section className="ec-card ec-card-body"><div className="ec-read-section"><h3>Catatan pembina</h3><p>{item.review_note}</p></div></section>}
            {editable ? <Recommendation key={`${item.id}-${item.status}`} item={item} expired={expired} onScoreChange={setLiveScore} /> : <section className="ec-card ec-card-body"><h2>Rekomendasi panitia</h2><div className="ec-read-section mt-5"><h3>Skor usulan</h3><p>{item.proposed_score !== null ? `${item.proposed_score}/4` : 'Belum dinilai'}</p></div><div className="ec-read-section"><h3>Catatan pengamatan / bukti</h3><p>{item.observations || 'Belum ada catatan pengamatan.'}</p></div><div className="ec-read-section"><h3>Umpan balik dan saran latihan</h3><p>{item.feedback || 'Belum ada saran latihan.'}</p></div></section>}
            {pembina && item.status === 'submitted' && <Review key={item.id} item={item} />}
            {pembina && !['approved', 'rejected'].includes(item.status) && <Deadline item={item} />}
            {pembina && nextPendingId && item.status !== 'submitted' && <Link className="ec-btn ec-btn-primary" href={route('assessments.show', { assignment: nextPendingId, from: 'queue' })}>Periksa rekomendasi berikutnya<Icon name="arrow" /></Link>}
            <section className="ec-card ec-card-body"><h2>Riwayat penilaian</h2>{item.events?.length ? <ol className="ec-history">{item.events.map(event => <li key={event.id}><Icon name={event.action === 'approved' ? 'check' : 'clock'} /><span>{eventLabels[event.action] || event.action}</span><time dateTime={event.created_at}>{timeLabel(event.created_at)}</time></li>)}</ol> : <p className="ec-hint">Belum ada aktivitas penilaian.</p>}</section>
        </div><ScoreSummary item={item} score={liveScore} /></div>
    </WorkspaceLayout>;
}
