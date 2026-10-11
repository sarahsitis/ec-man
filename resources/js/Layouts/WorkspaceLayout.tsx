import LanguageSwitcher from '@/Components/LanguageSwitcher';
import Breadcrumbs, { BreadcrumbItem } from '@/Components/Breadcrumbs';
import SidebarMenu, { SidebarMenuItem } from '@/Components/SidebarMenu';
import { useTranslation } from '@/i18n/useTranslation';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { Avatar, Icon } from '@/Components/WorkspaceUi';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';

export default function WorkspaceLayout({ children, header }: PropsWithChildren<{ header?: ReactNode }>) {
    const { t, locale, dateLocale } = useTranslation();
    const page = usePage();
    const user = page.props.auth.user;
    const query = new URLSearchParams(page.url.split('?')[1] ?? '');
    const history = query.get('status') === 'history';
    const detail = route().current('assessments.show');
    const item = page.props.assignment as { status?: string; title?: string } | undefined;
    const historyDetail = !!detail && ['submitted', 'approved', 'rejected'].includes(item?.status ?? '');
    const role = user.is_panitia ? t("Panitia EC") : user.role === 'pembina' ? t("Pembina") : t("Siswa");
    const dashboard = route(user.is_panitia ? 'panitia.dashboard' : 'dashboard');
    const [mobileOpen, setMobileOpen] = useState(false);
    const menus: SidebarMenuItem[] = user.is_panitia ? [
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
        { label: t("Keanggotaan"), icon: 'people', href: route('memberships.index'), active: route().current('memberships.*'), children: [
            { label: t("Daftar keanggotaan"), icon: 'people', href: route('memberships.index'), active: route().current('memberships.*') },
            { label: t("Tahun ajaran"), icon: 'calendar', href: route('academic-years.index'), active: route().current('academic-years.*') },
        ] },
        { label: t("Kegiatan"), icon: 'calendar', href: route('activities.index'), active: route().current('activities.*'), children: [
            { label: t("Daftar kegiatan"), icon: 'calendar', href: route('activities.index'), active: route().current('activities.*') },
            { label: t("Skema kegiatan"), icon: 'book', href: route('activity-schemes.index'), active: route().current('activity-schemes.*') },
        ] },
        { label: t("Penugasan"), icon: 'clipboard', href: route('assessments.index'), active: route().current('assessments.index') || (!!detail && query.get('from') !== 'queue') },
        { label: t("Pemeriksaan"), icon: 'check', href: route('assessments.queue'), active: route().current('assessments.queue') || (!!detail && query.get('from') === 'queue') },
        { label: t("Hasil Pre-test"), icon: 'book', href: route('pretests.reports'), active: route().current('pretests.*') },
        { label: t("Laporan Minat"), icon: 'grid', href: route('reports.interests'), active: route().current('reports.interests') },
        { label: t("Laporan & Arsip"), icon: 'chart', href: route('data-transfer.index'), active: route().current('data-transfer.*') },
    ] : [
        { label: t("Dashboard"), icon: 'grid', href: dashboard, active: route().current('dashboard') },
        { label: t("Perkembangan Saya"), icon: 'check', href: route('progress.index'), active: route().current('progress.index') || route().current('reports.student') },
        { label: t("Pre-test Saya"), icon: 'book', href: route('pretests.index'), active: route().current('pretests.*') },
        { label: t("Kegiatan Saya"), icon: 'calendar', href: route('activities.index'), active: route().current('activities.*') },
    ];
    const activeMenu = menus.flatMap(menu => [...(menu.children?.filter(child => child.href !== menu.href) ?? []), menu]).find(menu => menu.active);
    const breadcrumbs: BreadcrumbItem[] = [{ label: t('Dashboard'), href: dashboard }];
    const activity = page.props.activity as { id: number; title: string } | undefined;
    const student = page.props.student as { full_name: string } | undefined;
    if (!route().current('dashboard') && !route().current('panitia.dashboard')) {
        if (route().current('academic-years.*')) breadcrumbs.push({ label: t('Keanggotaan'), href: route('memberships.index') });
        if (route().current('activity-schemes.*')) breadcrumbs.push({ label: t('Kegiatan'), href: route('activities.index') });
        breadcrumbs.push(activeMenu ? { label: activeMenu.label, href: activeMenu.href } : { label: t(route().current('profile.edit') ? 'Profil akun' : 'Laporan siswa') });
        if (route().current('students.create')) breadcrumbs.push({ label: t('Tambah Anggota') });
        if (route().current('students.edit')) breadcrumbs.push({ label: t('Edit Anggota') });
        if (route().current('activities.create')) breadcrumbs.push({ label: t('Buat kegiatan') });
        if (activity && (route().current('activities.show') || route().current('activities.edit'))) {
            breadcrumbs.push({ label: activity.title, href: route('activities.show', activity.id) });
            if (route().current('activities.edit')) breadcrumbs.push({ label: t('Edit kegiatan') });
        }
        if (detail && item?.title) breadcrumbs.push({ label: item.title });
        if (student && (route().current('reports.student') || route().current('pretests.show'))) breadcrumbs.push({ label: student.full_name });
    }
    const flash = page.props.flash as { success?: string } | undefined;
    const sidebar = <>
        <Link href={dashboard} className="ec-brand" aria-label={t("EC-Man dashboard")}><span className="ec-brand-icon">ec</span><span><strong>EC-Man<span>.</span></strong><small>EXTRACURRICULAR MANAGER</small></span></Link>
        <p className="ec-workspace-label">{t("Ruang")} {t(role)}</p>
        <nav className="ec-nav" aria-label={t("Menu :value1", { value1: role })}><SidebarMenu items={menus} onNavigate={() => setMobileOpen(false)} /></nav>
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
            <Breadcrumbs items={breadcrumbs} /></div>
            <div className="ec-topbar-actions"><LanguageSwitcher /><div className="ec-topbar-user"><Avatar name={user.name} /><div><strong>{user.name}</strong><small>{t(role)}</small></div></div></div>
        </header>
            {header && <div className="ec-legacy-heading">{header}</div>}
            <main id="workspace-main" tabIndex={-1} className="ec-main">{flash?.success && <div role="status" className="ec-notice ec-notice-success">{t(flash.success)}</div>}{children}</main>
            <footer className="ec-footer"><span>EC-Man · English Club</span><span><Icon name="shield" />{t("Penilaian resmi disahkan pembina")}</span></footer>
        </div>
    </div>;
}
