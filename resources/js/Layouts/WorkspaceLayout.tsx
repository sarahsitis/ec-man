import LanguageSwitcher from '@/Components/LanguageSwitcher';
import { useTranslation } from '@/i18n/useTranslation';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { Avatar, Icon, IconName } from '@/Components/WorkspaceUi';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';

export default function WorkspaceLayout({ children, header }: PropsWithChildren<{ header?: ReactNode }>) {
    const { t, locale, dateLocale } = useTranslation();
    const page = usePage();
    const user = page.props.auth.user;
    const query = new URLSearchParams(page.url.split('?')[1] ?? '');
    const history = query.get('status') === 'history';
    const detail = route().current('assessments.show');
    const item = page.props.assignment as { status?: string } | undefined;
    const historyDetail = !!detail && ['submitted', 'approved', 'rejected'].includes(item?.status ?? '');
    const role = user.is_panitia ? t("Panitia EC") : user.role === 'pembina' ? t("Pembina") : t("Siswa");
    const dashboard = route(user.is_panitia ? 'panitia.dashboard' : 'dashboard');
    const [mobileOpen, setMobileOpen] = useState(false);
    type Menu = { label: string; href: string; active?: boolean; icon: IconName };
    const menus: Menu[] = user.is_panitia ? [
        { label: t("Dashboard Panitia"), icon: 'grid', href: dashboard, active: route().current('panitia.dashboard') },
        { label: t("Penugasan Saya"), icon: 'people', href: route('panitia.assignments'), active: (route().current('panitia.assignments') && !history) || (!!detail && !historyDetail) },
        { label: t("Riwayat Rekomendasi"), icon: 'clipboard', href: route('panitia.assignments', { status: 'history' }), active: (route().current('panitia.assignments') && history) || historyDetail },
        { label: t("Perkembangan Saya"), icon: 'check', href: route('progress.index'), active: route().current('progress.index') },
        { label: t("Pre-test Saya"), icon: 'book', href: route('pretests.index'), active: route().current('pretests.*') },
        { label: t("Kegiatan & Presensi"), icon: 'calendar', href: route('activities.index'), active: route().current('activities.*') },
    ] : user.role === 'pembina' ? [
        { label: t("Dashboard"), icon: 'grid', href: dashboard, active: route().current('dashboard') },
        { label: t("Anggota"), icon: 'people', href: route('students.index'), active: route().current('students.*') || route().current('reports.student') },
        { label: t("Panitia EC"), icon: 'shield', href: route('committee-roles.index'), active: route().current('committee-roles.*') },
        { label: t("Keanggotaan"), icon: 'people', href: route('memberships.index'), active: route().current('memberships.*') || route().current('academic-years.*') },
        { label: t("Kegiatan"), icon: 'calendar', href: route('activities.index'), active: route().current('activities.*') || route().current('activity-schemes.*') },
        { label: t("Penugasan"), icon: 'clipboard', href: route('assessments.index'), active: route().current('assessments.index') || (!!detail && query.get('from') !== 'queue') },
        { label: t("Pemeriksaan"), icon: 'check', href: route('assessments.queue'), active: route().current('assessments.queue') || (!!detail && query.get('from') === 'queue') },
        { label: t("Hasil Pre-test"), icon: 'book', href: route('pretests.reports'), active: route().current('pretests.*') },
        { label: t("Laporan Minat"), icon: 'grid', href: route('reports.interests'), active: route().current('reports.interests') },
    ] : [
        { label: t("Dashboard"), icon: 'grid', href: dashboard, active: route().current('dashboard') },
        { label: t("Perkembangan Saya"), icon: 'check', href: route('progress.index'), active: route().current('progress.index') || route().current('reports.student') },
        { label: t("Pre-test Saya"), icon: 'book', href: route('pretests.index'), active: route().current('pretests.*') },
        { label: t("Kegiatan Saya"), icon: 'calendar', href: route('activities.index'), active: route().current('activities.*') },
    ];
    const flash = page.props.flash as { success?: string } | undefined;
    const sidebar = <>
        <Link href={dashboard} className="ec-brand" aria-label={t("EC-Man dashboard")}><span className="ec-brand-icon">ec</span><span><strong>EC-Man<span>.</span></strong><small>EXTRACURRICULAR MANAGER</small></span></Link>
        <p className="ec-workspace-label">{t("Ruang")} {t(role)}</p>
        <nav className="ec-nav" aria-label={t("Menu :value1", { value1: role })}>{menus.map(menu => <Link key={menu.label} href={menu.href} aria-current={menu.active ? 'page' : undefined} className={menu.active ? 'is-active' : ''} onClick={() => setMobileOpen(false)}><Icon name={menu.icon} />{t(menu.label)}</Link>)}</nav>
        {user.is_panitia && <div className="ec-role-card"><p className="ec-eyebrow">{t("Peran Anda")}</p><strong>{t("Asisten penilai pembina")}</strong><p>{t("Catat pengamatan pada penugasan Anda. Pembina memutuskan nilai resmi.")}</p></div>}
        <div className="ec-sidebar-bottom"><div className="ec-user"><Avatar name={user.name} /><div><strong>{user.name}</strong><small>{user.username} · {t(role)}</small></div></div>
            <Link href={route('profile.edit')} className="ec-account-link" aria-current={route().current('profile.edit') ? 'page' : undefined}><Icon name="user" />{t("Profil akun")}</Link>
            <Link href={route('logout')} method="post" as="button" className="ec-account-link"><Icon name="logout" />{t("Log Out")}</Link>
        </div>
    </>;
    return <div className="ec-workspace ec-shell">
        <a className="ec-skip" href="#workspace-main">{t("Lewati ke konten")}</a>
        <aside className="ec-sidebar ec-sidebar-desktop">{sidebar}</aside>
        <Dialog open={mobileOpen} onClose={setMobileOpen} className="ec-workspace ec-mobile-dialog">
            <div className="ec-mobile-backdrop" aria-hidden="true" /><DialogPanel id="workspace-mobile-menu" className="ec-sidebar ec-sidebar-mobile">
                <DialogTitle className="sr-only">{t("Menu")} {t(role)}</DialogTitle><button type="button" className="ec-mobile-close ec-icon-button" data-autofocus onClick={() => setMobileOpen(false)} aria-label={t("Tutup menu")}><Icon name="close" /></button>{sidebar}
            </DialogPanel>
        </Dialog>
        <div className="ec-body"><header className="ec-topbar"><div className="ec-topbar-start"><button type="button" className="ec-mobile-toggle ec-icon-button" onClick={() => setMobileOpen(true)} aria-label={t("Buka menu")} aria-expanded={mobileOpen} aria-controls="workspace-mobile-menu"><Icon name="menu" /></button>
            <div className="ec-breadcrumb"><span>EC-Man</span><span aria-hidden="true">/</span><strong>{menus.find(menu => menu.active)?.label ?? (route().current('profile.edit') ? t("Profil akun") : t("Laporan siswa"))}</strong></div></div>
            <div className="ec-topbar-actions"><LanguageSwitcher /><div className="ec-topbar-user"><Avatar name={user.name} /><div><strong>{user.name}</strong><small>{t(role)}</small></div></div></div>
        </header>
            {header && <div className="ec-legacy-heading">{header}</div>}
            <main id="workspace-main" tabIndex={-1} className="ec-main">{flash?.success && <div role="status" className="ec-notice ec-notice-success">{t(flash.success)}</div>}{children}</main>
            <footer className="ec-footer"><span>EC-Man · English Club</span><span><Icon name="shield" />{t("Penilaian resmi disahkan pembina")}</span></footer>
        </div>
    </div>;
}
