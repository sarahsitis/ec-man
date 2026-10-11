import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Paging, Paginator, panel } from '@/Components/AssessmentUi';
import { activityDate, StatusBadge } from '@/Components/ManagementUi';
import { Icon } from '@/Components/WorkspaceUi';
import { useTranslation } from '@/i18n/useTranslation';
import { PageProps } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface ActivityNotification {
    id: string; read_at: string | null; created_at: string; activity_available: boolean;
    data: { title: string; activity_date: string; start_time: string; end_time: string | null; location: string | null; creator_name: string; status: string };
}

export default function Index({ notifications, filters, activityStatuses }: {
    notifications: Paginator<ActivityNotification>; filters: { status: 'all' | 'unread' }; activityStatuses: Record<string, string>;
}) {
    const { t, locale, dateLocale } = useTranslation();
    const { notificationSummary } = usePage<PageProps>().props;
    const [busy, setBusy] = useState(false);
    const post = (url: string) => {
        if (busy) return;
        router.post(url, {}, { preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });
    };
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{t('Notifikasi Kegiatan')}</h2>}>
        <Head title={t('Notifikasi Kegiatan')} />
        <div className="mx-auto max-w-5xl space-y-5 px-4 py-8 sm:px-6">
            <div className={panel}>
                <p className="text-sm leading-6 text-gray-500">{t('Jadwal pertemuan English Club agar Anda dapat mempersiapkan diri. Informasi kegiatan menampilkan jadwal dan status terbaru.')}</p>
                <div className="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap gap-2" role="group" aria-label={t('Filter notifikasi')}>
                        <Link href={route('notifications.index', { status: 'all' })} className={`ec-action ${filters.status === 'all' ? 'ec-action-primary' : 'ec-action-secondary'}`} aria-current={filters.status === 'all' ? 'page' : undefined}>{t('Semua')}</Link>
                        <Link href={route('notifications.index', { status: 'unread' })} className={`ec-action ${filters.status === 'unread' ? 'ec-action-primary' : 'ec-action-secondary'}`} aria-current={filters.status === 'unread' ? 'page' : undefined}>{t('Belum dibaca')} ({notificationSummary.unreadCount})</Link>
                    </div>
                    <button type="button" className="ec-action ec-action-secondary" disabled={busy || !notificationSummary.unreadCount} onClick={() => post(route('notifications.read-all'))}><Icon name="check" />{t('Tandai semua sudah dibaca')}</button>
                </div>
            </div>
            {!notifications.data.length ? <div className={panel}><p className="font-semibold">{t(filters.status === 'unread' ? 'Semua notifikasi sudah dibaca.' : 'Belum ada notifikasi kegiatan.')}</p><p className="mt-2 text-sm text-gray-500">{t('Notifikasi akan muncul saat pembina atau panitia EC membuat pertemuan pada semester keanggotaan Anda.')}</p></div> : <ul className="space-y-4" aria-label={t('Daftar notifikasi kegiatan')}>
                {notifications.data.map(item => <li key={item.id} className={panel} data-notification-id={item.id}>
                    <div className="flex flex-wrap items-start justify-between gap-3"><div className="min-w-0 flex-1"><p className="text-xs font-medium text-purple-700">{t(item.read_at ? 'Sudah dibaca' : 'Belum dibaca')}</p><h3 className="mt-1 break-words text-lg font-semibold">{item.data.title}</h3></div><StatusBadge status={item.data.status} labels={activityStatuses} /></div>
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt className="text-xs text-gray-500">{t('Tanggal / Waktu (WIB)')}</dt><dd className="mt-1">{activityDate(item.data.activity_date, locale)} · {item.data.start_time}{item.data.end_time && `–${item.data.end_time}`} WIB</dd></div>
                        <div><dt className="text-xs text-gray-500">{t('Lokasi')}</dt><dd className="mt-1 break-words">{item.data.location || t('Belum diisi')}</dd></div>
                        <div><dt className="text-xs text-gray-500">{t('Dibuat oleh')}</dt><dd className="mt-1 break-words">{item.data.creator_name}</dd></div>
                        <div><dt className="text-xs text-gray-500">{t('Notifikasi diterima')}</dt><dd className="mt-1">{new Intl.DateTimeFormat(dateLocale, { timeZone: 'Asia/Jakarta', dateStyle: 'medium', timeStyle: 'short' }).format(new Date(item.created_at))} WIB</dd></div>
                    </dl>
                    {!item.activity_available && <p className="mt-4 text-sm text-gray-500">{t('Kegiatan tidak lagi tersedia untuk akun Anda.')}</p>}
                    <div className="mt-4 flex flex-wrap gap-2">{item.activity_available && <button type="button" disabled={busy} className="ec-action ec-action-primary" onClick={() => post(route('notifications.open', item.id))}><Icon name="calendar" />{t('Lihat kegiatan')}</button>}{!item.read_at && <button type="button" disabled={busy} className="ec-action ec-action-secondary" onClick={() => post(route('notifications.read', item.id))}>{t('Tandai sudah dibaca')}</button>}</div>
                </li>)}
            </ul>}
            <Paging page={notifications} />
        </div>
    </AuthenticatedLayout>;
}
