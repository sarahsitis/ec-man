import { panel } from '@/Components/AssessmentUi';

export interface PreTestResult {
    id: number;
    version: string;
    score: number;
    max_score: number;
    level: string;
    domain_scores: Record<string, { label: string; score: number; max_score: number }>;
    self_assessment: Record<string, number>;
    interests: { id: number; name: string; is_primary: boolean }[];
    learning_goal: string;
    submitted_at: string;
}

export interface PreTestMetadata {
    skills: Record<string, string>;
    scales: Record<string, string>;
    domains: Record<string, string>;
}

export interface PreTestStudent {
    id: number;
    full_name: string;
    student_number: string;
    class_name: string | null;
}

export default function PreTestResultView({ result, skills, scales }: { result: PreTestResult } & Pick<PreTestMetadata, 'skills' | 'scales'>) {
    const percentage = Math.round(result.score / result.max_score * 100);
    return <div className="space-y-6">
        <div className={panel}>
            <h3 className="text-lg font-semibold">Kemampuan awal</h3>
            <p className="mt-2 text-sm text-gray-500">Dikirim {new Date(result.submitted_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })} WIB · {result.version}</p>
            <div className="my-5 flex flex-wrap items-center gap-4">
                <span className="text-3xl font-semibold">{result.score}/{result.max_score}</span>
                <span className="rounded bg-indigo-100 px-3 py-2 text-indigo-900">{percentage}% · {result.level}</span>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">{Object.entries(result.domain_scores).map(([key, domain]) => <div key={key} className="rounded border border-gray-200 p-4">
                <p className="font-medium">{domain.label}</p><p className="mt-1">{domain.score}/{domain.max_score} jawaban benar</p>
            </div>)}</div>
            <p className="mt-4 text-sm text-gray-500">Kategori internal: di bawah 50% perlu pendampingan, 50–74% dasar berkembang, dan mulai 75% siap pengayaan. Tes ini memetakan bacaan dan penggunaan bahasa; hasilnya menjadi acuan latihan awal.</p>
        </div>
        <div className={panel}>
            <h3 className="text-lg font-semibold">Penilaian diri siswa</h3>
            <p className="mt-1 text-sm text-gray-500">Kemampuan menurut siswa. Pembina atau panitia dapat mengonfirmasinya melalui pengamatan dan latihan.</p>
            <dl className="mt-4 space-y-4">{Object.entries(result.self_assessment).map(([skill, score]) => <div key={skill}>
                <dt className="font-medium capitalize">{skill}</dt><dd className="mt-1">{score}/4 · {scales[String(score)] || '—'}</dd>
                <dd className="mt-1 text-sm text-gray-500">{skills[skill]}</dd>
            </div>)}</dl>
        </div>
        <div className={panel}>
            <h3 className="text-lg font-semibold">Minat dan tujuan belajar</h3>
            <div className="mt-3 flex flex-wrap gap-2">{result.interests.map(interest => <span key={interest.id} className={`rounded-full px-3 py-1 text-sm ${interest.is_primary ? 'bg-indigo-100 text-indigo-900' : 'bg-gray-100 text-gray-800'}`}>
                {interest.name}{interest.is_primary && ' · Minat utama'}
            </span>)}</div>
            <p className="mt-4 font-medium">Tujuan belajar</p><p className="mt-1 whitespace-pre-wrap">{result.learning_goal}</p>
        </div>
    </div>;
}
