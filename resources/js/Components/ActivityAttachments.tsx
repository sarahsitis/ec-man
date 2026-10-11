import { field, panel } from '@/Components/AssessmentUi';
import InputError from '@/Components/InputError';
import { ConfirmAction, Icon } from '@/Components/WorkspaceUi';
import { useTranslation } from '@/i18n/useTranslation';
import { router, useForm } from '@inertiajs/react';
import { FormEvent, useRef, useState } from 'react';

type Kind = 'material' | 'documentation';
export interface ActivityAttachment {
    id: number; kind: Kind; name: string; size: number; mime_type: string; description: string | null;
    uploader_name: string; created_at: string; download_url: string; preview_url: string | null; can_delete: boolean;
}
export interface AttachmentOptions {
    kinds: Record<Kind, string>; extensions: Record<Kind, string[]>; maxFiles: number; maxFileMb: number; maxBatchMb: number;
}

export default function ActivityAttachments({ activityId, attachments, options, canUpload }: {
    activityId: number; attachments: ActivityAttachment[]; options: AttachmentOptions; canUpload: boolean;
}) {
    const { t, dateLocale } = useTranslation();
    const upload = useForm<{ kind: Kind; description: string; files: File[] }>({ kind: 'material', description: '', files: [] });
    const fileInput = useRef<HTMLInputElement>(null);
    const [remove, setRemove] = useState<ActivityAttachment | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState('');
    const errors: Record<string, string | undefined> = upload.errors;
    const sizeLabel = (size: number) => `${new Intl.NumberFormat(dateLocale, { maximumFractionDigits: 1 }).format(size / (size >= 1024 * 1024 ? 1024 * 1024 : 1024))} ${size >= 1024 * 1024 ? 'MB' : 'KB'}`;
    const totalSize = upload.data.files.reduce((total, file) => total + file.size, 0);
    const changeKind = (kind: Kind) => {
        upload.setData(data => ({ ...data, kind, files: [] })); upload.clearErrors();
        if (fileInput.current) fileInput.current.value = '';
    };
    const submit = (event: FormEvent) => {
        event.preventDefault();
        upload.clearErrors();
        if (upload.data.files.length > options.maxFiles || upload.data.files.some(file => file.size > options.maxFileMb * 1024 * 1024) || totalSize > options.maxBatchMb * 1024 * 1024) {
            upload.setError('files', t('Maksimal :count berkas, :size MB per berkas, total :total MB per unggahan.', { count: options.maxFiles, size: options.maxFileMb, total: options.maxBatchMb }));
            return;
        }
        upload.post(route('activities.attachments.store', activityId), {
            forceFormData: true, preserveScroll: true,
            onSuccess: () => { upload.reset('files', 'description'); if (fileInput.current) fileInput.current.value = ''; },
        });
    };
    const destroy = () => {
        if (!remove || deleting) return;
        setDeleting(true); setDeleteError('');
        router.delete(route('activities.attachments.destroy', [activityId, remove.id]), {
            preserveScroll: true,
            onSuccess: () => setRemove(null),
            onError: () => setDeleteError(t('Berkas gagal dihapus. Muat ulang halaman dan coba kembali.')),
            onFinish: () => setDeleting(false),
        });
    };
    return <section className={panel} aria-labelledby="activity-files-heading">
        <h3 id="activity-files-heading" className="text-lg font-semibold">{t('Materi & Dokumentasi Kegiatan')}</h3>
        <p className="mt-2 text-sm leading-6 text-gray-500">{t('Materi pembelajaran dan dokumentasi tersimpan pada kegiatan ini. Peserta yang memiliki akses ke kegiatan dapat melihat dan mengunduh berkas.')}</p>
        {canUpload && <form onSubmit={submit} className="mt-5 rounded-xl border border-gray-200 p-4">
            <fieldset disabled={upload.processing}>
                <legend className="font-semibold">{t('Unggah berkas kegiatan')}</legend>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <div><label htmlFor="attachment_kind" className="text-sm font-medium">{t('Jenis berkas')}</label><select id="attachment_kind" className={field} value={upload.data.kind} onChange={e => changeKind(e.target.value as Kind)}>{Object.entries(options.kinds).map(([kind, label]) => <option key={kind} value={kind}>{t(label)}</option>)}</select><InputError message={upload.errors.kind} /></div>
                    <div className="min-w-0"><label htmlFor="attachment_files" className="text-sm font-medium">{t('Pilih berkas')}</label><input ref={fileInput} id="attachment_files" className={`${field} ec-import-file text-sm`} type="file" multiple required accept={options.extensions[upload.data.kind].map(extension => `.${extension}`).join(',')} onChange={e => { upload.setData('files', Array.from(e.target.files ?? [])); upload.clearErrors(); }} /><InputError message={upload.errors.files} /></div>
                    <div className="sm:col-span-2"><label htmlFor="attachment_description" className="text-sm font-medium">{t('Keterangan berkas (opsional)')}</label><textarea id="attachment_description" className={field} rows={2} maxLength={2000} value={upload.data.description} onChange={e => upload.setData('description', e.target.value)} /><InputError message={upload.errors.description} /></div>
                </div>
                <p className="mt-3 break-words text-xs leading-6 text-gray-500">{t('Format yang didukung: :formats.', { formats: options.extensions[upload.data.kind].join(', ').toUpperCase() })} {t('Maksimal :count berkas, :size MB per berkas, total :total MB per unggahan.', { count: options.maxFiles, size: options.maxFileMb, total: options.maxBatchMb })}</p>
                {!!upload.data.files.length && <div className="mt-3 rounded-lg bg-gray-50 p-3 text-sm"><p className="font-medium">{t(':count berkas dipilih (:size).', { count: upload.data.files.length, size: sizeLabel(totalSize) })}</p><ul className="mt-2 space-y-1">{upload.data.files.map((file, index) => <li key={index} className="break-words">{file.name} · {sizeLabel(file.size)}<InputError message={errors[`files.${index}`]} /></li>)}</ul></div>}
                <button type="submit" disabled={upload.processing || !upload.data.files.length} className="ec-action ec-action-primary mt-4"><Icon name="send" />{upload.processing ? t('Mengunggah…') : t('Unggah berkas')}</button>
                {upload.progress && <p role="status" className="mt-2 text-sm text-gray-500">{t('Progres unggahan: :progress%.', { progress: upload.progress.percentage ?? 0 })}</p>}
            </fieldset>
        </form>}
        <div className="mt-6 grid gap-6 lg:grid-cols-2">
            {(Object.keys(options.kinds) as Kind[]).map(kind => {
                const files = attachments.filter(file => file.kind === kind);
                return <div key={kind} className="min-w-0">
                    <h4 className="flex items-center gap-2 font-semibold"><Icon name={kind === 'material' ? 'book' : 'clipboard'} />{t(options.kinds[kind])}<span className="rounded-full bg-gray-100 px-2 py-1 text-xs text-gray-600">{files.length}</span></h4>
                    {!files.length && <p className="mt-3 rounded-xl border border-dashed border-gray-200 p-5 text-sm text-gray-500">{t(kind === 'material' ? 'Belum ada materi kegiatan.' : 'Belum ada dokumentasi kegiatan.')}</p>}
                    <ul className="mt-3 space-y-3">{files.map(file => <li key={file.id} className="overflow-hidden rounded-xl border border-gray-200">
                        {file.preview_url && <a href={file.preview_url} target="_blank" rel="noopener" aria-label={t('Lihat foto :name', { name: file.name })}><img src={file.preview_url} alt={file.description || file.name} loading="lazy" className="h-48 w-full bg-gray-50 object-contain" /></a>}
                        <div className="p-4"><p className="break-words font-semibold">{file.name}</p><p className="mt-1 text-xs leading-5 text-gray-500">{sizeLabel(file.size)} · {file.uploader_name} · {new Intl.DateTimeFormat(dateLocale, { timeZone: 'Asia/Jakarta', dateStyle: 'medium', timeStyle: 'short' }).format(new Date(file.created_at))} WIB</p>
                            {file.description && <p className="mt-2 whitespace-pre-wrap break-words text-sm">{file.description}</p>}
                            <div className="mt-3 flex flex-wrap gap-2"><a href={file.download_url} className="ec-action ec-action-secondary"><Icon name="save" />{t('Unduh berkas')}</a>{file.can_delete && <button type="button" className="ec-action ec-action-danger" onClick={() => { setDeleteError(''); setRemove(file); }}><Icon name="close" />{t('Hapus berkas')}</button>}</div>
                        </div>
                    </li>)}</ul>
                </div>;
            })}
        </div>
        <ConfirmAction open={!!remove} title={t('Hapus berkas kegiatan?')} label={t('Hapus berkas')} busy={deleting} onClose={() => setRemove(null)} onConfirm={destroy}>
            <p>{t('Berkas :name akan dihapus dari kegiatan ini.', { name: remove?.name ?? '' })}</p><InputError message={deleteError} />
        </ConfirmAction>
    </section>;
}
