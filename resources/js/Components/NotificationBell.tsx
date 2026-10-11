import { Icon } from '@/Components/WorkspaceUi';
import { useTranslation } from '@/i18n/useTranslation';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function NotificationBell() {
    const { t } = useTranslation();
    const { notificationSummary } = usePage<PageProps>().props;
    const [count, setCount] = useState(notificationSummary.unreadCount);
    useEffect(() => {
        setCount(notificationSummary.unreadCount);
        const controller = new AbortController();
        let pending = false;
        const refresh = async () => {
            if (document.visibilityState !== 'visible' || pending) return;
            pending = true;
            try {
                const response = await fetch(route('notifications.summary'), { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' }, signal: controller.signal });
                if (!response.ok) return;
                const summary = await response.json();
                if (!controller.signal.aborted && Number.isSafeInteger(summary.unreadCount) && summary.unreadCount >= 0) setCount(summary.unreadCount);
            } catch { /* Refresh again when the connection or session is available. */ }
            finally { pending = false; }
        };
        const timer = window.setInterval(refresh, 30000);
        window.addEventListener('focus', refresh);
        document.addEventListener('visibilitychange', refresh);
        return () => { controller.abort(); window.clearInterval(timer); window.removeEventListener('focus', refresh); document.removeEventListener('visibilitychange', refresh); };
    }, [notificationSummary.unreadCount]);
    const label = t('Notifikasi kegiatan, :count belum dibaca.', { count });
    return <Link href={route('notifications.index')} className="ec-notification-bell" aria-label={label} title={label} aria-current={route().current('notifications.index') ? 'page' : undefined}>
        <Icon name="bell" />{count > 0 && <span className="ec-notification-badge" aria-hidden="true">{count > 99 ? '99+' : count}</span>}
    </Link>;
}
