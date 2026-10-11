export const assessmentStatuses: Record<string, string> = {
    assigned: 'Belum dinilai', draft: 'Draf', submitted: 'Menunggu pembina',
    revision: 'Perlu revisi', approved: 'Disahkan', rejected: 'Ditolak', cancelled: 'Dibatalkan',
};

export function committeeProgress(counts: Record<string, number>) {
    const count = (status: string) => Number(counts[status] ?? 0);
    const total = ['assigned', 'draft', 'submitted', 'revision', 'approved', 'rejected'].reduce((sum, status) => sum + count(status), 0);
    const sent = count('submitted') + count('approved') + count('rejected');
    return { total, sent, working: count('draft') + count('revision'), unstarted: count('assigned'), percent: total ? Math.round(sent / total * 100) : 0 };
}

export type RecommendationData = { proposed_score: string; observations: string; feedback: string };
export function recommendationErrors(data: RecommendationData, submit: boolean): Partial<Record<keyof RecommendationData, string>> {
    const errors: Partial<Record<keyof RecommendationData, string>> = {};
    if ((submit || data.proposed_score !== '') && !['1', '2', '3', '4'].includes(data.proposed_score)) {
        errors.proposed_score = 'Pilih skor usulan antara 1 dan 4.';
    }
    for (const key of ['observations', 'feedback'] as const) {
        const length = Array.from(data[key].trim()).length;
        if (submit && length < 10) { errors[key] = 'Isi minimal 10 karakter untuk mengirim rekomendasi.'; }
        if (length > 2000) { errors[key] = 'Gunakan maksimal 2.000 karakter.'; }
    }
    return errors;
}

export function dateLabel(value: string | null | undefined, locale: 'id' | 'en' = 'id') {
    if (!value) { return locale === 'en' ? 'No deadline' : 'Tanpa batas waktu'; }
    const date = new Date(`${value.slice(0, 10)}T12:00:00+07:00`);
    return Number.isNaN(date.getTime()) ? (locale === 'en' ? 'Date not available' : 'Tanggal belum tersedia') : date.toLocaleDateString(locale === 'en' ? 'en-GB' : 'id-ID', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'Asia/Jakarta' });
}

export function timeLabel(value: string | null | undefined, locale: 'id' | 'en' = 'id') {
    return value ? new Date(value).toLocaleString(locale === 'en' ? 'en-GB' : 'id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' }) + ' WIB' : (locale === 'en' ? 'Not recorded' : 'Belum tercatat');
}
