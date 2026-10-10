import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import PreTestResultView, { PreTestMetadata, PreTestResult, PreTestStudent } from '@/Components/PreTestResultView';
import { panel, field } from '@/Components/AssessmentUi';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface Question { id: string; domain: string; passage: string | null; prompt: string; options: Record<string, string> }
interface Category { id: number; name: string }
interface InitialInterest { interest_category_id: number; is_primary: boolean; learning_goal: string | null }
interface Props extends PreTestMetadata {
    student: PreTestStudent | null;
    result: PreTestResult | null;
    questions: Question[];
    categories: Category[];
    version: string;
    initialInterests: InitialInterest[];
}

export default function Index({ student, result, questions, categories, version, initialInterests, skills, scales, domains }: Props) {
    const primary = initialInterests.find(i => i.is_primary);
    const form = useForm({
        version, answers: {} as Record<string, string>, self_assessment: {} as Record<string, string>,
        interest_ids: initialInterests.map(i => i.interest_category_id),
        primary_interest_id: primary ? String(primary.interest_category_id) : '', learning_goal: primary?.learning_goal || '',
    });
    const [step, setStep] = useState(0);
    const [stepError, setStepError] = useState('');
    const errors: Record<string, string | undefined> = form.errors;
    const answered = questions.filter(q => form.data.answers[q.id]).length;
    const selectedCategories = categories.filter(c => form.data.interest_ids.includes(c.id));
    const steps = ['Soal kemampuan awal', 'Penilaian diri', 'Minat dan tujuan'];
    const next = () => {
        if (step === 0 && answered < questions.length) { setStepError('Jawab semua soal untuk melanjutkan.'); return; }
        if (step === 1 && Object.keys(skills).some(skill => !form.data.self_assessment[skill])) { setStepError('Pilih tingkat kemampuan untuk setiap keterampilan.'); return; }
        setStepError(''); setStep(step + 1);
    };
    const toggleInterest = (id: number) => {
        const ids = form.data.interest_ids.includes(id) ? form.data.interest_ids.filter(selected => selected !== id) : [...form.data.interest_ids, id];
        const primaryId = ids.includes(Number(form.data.primary_interest_id)) ? form.data.primary_interest_id : String(ids[0] ?? '');
        form.setData(data => ({ ...data, interest_ids: ids, primary_interest_id: primaryId }));
    };
    const submit: FormEventHandler = e => {
        e.preventDefault();
        if (step < 2) { next(); return; }
        form.post(route('pretests.store'), {
            onError: validationErrors => {
                const keys = Object.keys(validationErrors);
                if (keys.some(key => key.startsWith('answers'))) { setStep(0); }
                else if (keys.some(key => key.startsWith('self_assessment'))) { setStep(1); }
                setStepError('Periksa isian yang ditandai sebelum mengirim.');
            },
        });
    };

    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Pre-test Saya</h2>}>
        <Head title="Pre-test Saya" />
        <div className="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
            {!student ? <div className={panel}><h3 className="font-semibold">Lengkapi profil siswa</h3><p className="my-3">Profil siswa diperlukan untuk menyimpan hasil pre-test.</p><Link className="text-indigo-600" href={route('profile.edit')}>Buka profil</Link></div>
                : result ? <><div className={panel}><h3 className="font-semibold">Pre-test sudah selesai</h3><p className="mt-2">Hasil awal Anda tersimpan. Pembina dan panitia yang ditugaskan dapat menggunakannya untuk memilih latihan sesuai kemampuan dan minat.</p></div><PreTestResultView result={result} skills={skills} scales={scales} /></>
                : <>
                    <div className={panel}>
                        <h3 className="text-lg font-semibold">Kenali kemampuan awal dan minat Anda</h3>
                        <p className="mt-2">Jawab {questions.length} soal bahasa Inggris, lalu ceritakan kemampuan dan minat Anda. Pilih jawaban sesuai kemampuan sendiri agar latihan yang diberikan sesuai kebutuhan.</p>
                        <p className="mt-2 text-sm text-gray-500">Pre-test dikerjakan satu kali. Hasil pilihan ganda dan penilaian diri dicatat terpisah dari nilai resmi.</p>
                        <ol className="mt-5 flex flex-wrap gap-3">{steps.map((label, index) => <li key={label} aria-current={step === index ? 'step' : undefined} className={`rounded px-3 py-2 text-sm ${step === index ? 'bg-indigo-100 font-semibold text-indigo-900' : 'bg-gray-100 text-gray-600'}`}>{index + 1}. {label}</li>)}</ol>
                    </div>
                    <form onSubmit={submit} className="space-y-6">
                        <InputError message={errors.pre_test} /><InputError message={form.errors.version} />
                        {stepError && <p role="alert" className="rounded bg-red-50 p-3 text-red-700">{stepError}</p>}
                        <fieldset disabled={form.processing} className="space-y-6">
                            {step === 0 && <>
                                <p className="text-sm text-gray-600 dark:text-gray-300">{answered} dari {questions.length} soal sudah dijawab.</p>
                                <InputError message={form.errors.answers} />
                                {questions.map((q, index) => <fieldset key={q.id} className={panel}>
                                    <legend className="sr-only">Soal {index + 1}: {q.prompt}</legend>
                                    <p className="mb-3 text-sm text-indigo-600">Soal {index + 1} · {domains[q.domain]}</p>
                                    {q.passage && <blockquote className="mb-4 rounded bg-gray-50 p-4 text-gray-800">{q.passage}</blockquote>}
                                    <p className="font-medium">{q.prompt}</p>
                                    <div className="mt-3 space-y-2">{Object.entries(q.options).map(([key, option]) => <label key={key} className="flex cursor-pointer items-start gap-3 rounded border border-gray-200 p-3">
                                        <input type="radio" name={`answer_${q.id}`} value={key} checked={form.data.answers[q.id] === key} onChange={()=>form.setData('answers', { ...form.data.answers, [q.id]: key })} className="mt-1 text-indigo-600 focus:ring-indigo-500" />
                                        <span>{key.toUpperCase()}. {option}</span>
                                    </label>)}</div>
                                    <InputError message={errors[`answers.${q.id}`]} />
                                </fieldset>)}
                            </>}
                            {step === 1 && <div className={panel}>
                                <h3 className="text-lg font-semibold">Bagaimana kemampuan Anda saat ini?</h3>
                                <p className="mt-2 text-sm text-gray-500">Pilih berdasarkan pengalaman Anda. Bagian ini tidak menambah skor pilihan ganda.</p>
                                <InputError message={form.errors.self_assessment} />
                                <div className="mt-5 space-y-6">{Object.entries(skills).map(([skill, description]) => <div key={skill}>
                                    <InputLabel htmlFor={`skill_${skill}`} value={skill} className="capitalize" /><p className="mt-1 text-sm">{description}</p>
                                    <select id={`skill_${skill}`} className={field} value={form.data.self_assessment[skill] || ''} onChange={e=>form.setData('self_assessment', { ...form.data.self_assessment, [skill]: e.target.value })}>
                                        <option value="">Pilih tingkat kemampuan</option>{Object.entries(scales).map(([value, label]) => <option key={value} value={value}>{value} — {label}</option>)}
                                    </select><InputError message={errors[`self_assessment.${skill}`]} />
                                </div>)}</div>
                            </div>}
                            {step === 2 && <div className={panel}>
                                <h3 className="text-lg font-semibold">Apa yang ingin Anda pelajari?</h3>
                                <p className="mt-2 text-sm text-gray-500">Pilih satu atau beberapa kegiatan, lalu tentukan minat utama Anda.</p>
                                <fieldset className="mt-4 grid gap-3 sm:grid-cols-2"><legend className="sr-only">Pilihan minat</legend>{categories.map(category => <label key={category.id} className="flex cursor-pointer items-center gap-3 rounded border border-gray-200 p-3">
                                    <input type="checkbox" checked={form.data.interest_ids.includes(category.id)} onChange={()=>toggleInterest(category.id)} className="rounded text-indigo-600 focus:ring-indigo-500" />{category.name}
                                </label>)}</fieldset>
                                <InputError message={form.errors.interest_ids} />
                                {Object.entries(errors).filter(([key])=>key.startsWith('interest_ids.')).map(([key, message])=><InputError key={key} message={message} />)}
                                <div className="mt-5"><InputLabel htmlFor="primary_interest" value="Minat utama" /><select id="primary_interest" className={field} required value={form.data.primary_interest_id} onChange={e=>form.setData('primary_interest_id', e.target.value)}>
                                    <option value="">Pilih minat utama</option>{selectedCategories.map(c=><option key={c.id} value={c.id}>{c.name}</option>)}
                                </select><InputError message={form.errors.primary_interest_id} /></div>
                                <div className="mt-5"><InputLabel htmlFor="learning_goal" value="Tujuan belajar" /><textarea id="learning_goal" className={field} required minLength={10} maxLength={1000} rows={4} value={form.data.learning_goal} onChange={e=>form.setData('learning_goal', e.target.value)} placeholder="Contoh: Saya ingin lebih percaya diri berbicara dan bisa mengikuti lomba storytelling." /><InputError message={form.errors.learning_goal} /></div>
                                <p className="mt-4 text-sm text-gray-500">Periksa jawaban sebelum mengirim. Anda dapat kembali ke langkah sebelumnya; semua pilihan tetap tersimpan selama halaman ini terbuka.</p>
                            </div>}
                            <div className="flex flex-wrap justify-between gap-3">
                                {step > 0 ? <button type="button" className="rounded border border-gray-300 px-4 py-2 dark:text-gray-100" onClick={()=>{setStep(step - 1); setStepError('');}}>Sebelumnya</button> : <span />}
                                {step < 2 ? <PrimaryButton type="button" onClick={next}>Lanjutkan</PrimaryButton> : <PrimaryButton disabled={form.processing || !selectedCategories.length}>Kirim pre-test</PrimaryButton>}
                            </div>
                        </fieldset>
                    </form>
                </>}
        </div>
    </AuthenticatedLayout>;
}
