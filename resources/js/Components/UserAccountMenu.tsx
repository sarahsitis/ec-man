import { Avatar, Icon } from '@/Components/WorkspaceUi';
import { useTranslation } from '@/i18n/useTranslation';
import { PageProps } from '@/types';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/react';
import { Link, usePage } from '@inertiajs/react';

export default function UserAccountMenu() {
    const { t } = useTranslation();
    const { auth } = usePage<PageProps>().props;
    const user = auth.user;
    const role = user.is_panitia ? 'Panitia EC' : user.role === 'pembina' ? 'Pembina' : 'Siswa';

    return <Menu as="div" className="ec-account-menu">
        <MenuButton className="ec-account-trigger" aria-label={t('Menu akun :name', { name: user.name })} title={t('Menu akun')}>
            <Avatar name={user.name} /><Icon name="chevron" className="ec-account-chevron" />
        </MenuButton>
        <MenuItems className="ec-account-dropdown" aria-label={t('Menu akun')}>
            <div className="ec-account-info">
                <strong>{user.name}</strong>
                <dl><dt>{t('Username')}</dt><dd>{user.username}</dd></dl>
                <span className="ec-account-role">{t(role)}</span>
            </div>
            <div className="ec-account-options">
                <MenuItem><Link href={route('profile.edit')} className="ec-account-option" aria-current={route().current('profile.edit') ? 'page' : undefined}><Icon name="user" />{t('Profil akun')}</Link></MenuItem>
                <MenuItem><Link href={route('logout')} method="post" as="button" className="ec-account-option ec-account-logout"><Icon name="logout" />{t('Log Out')}</Link></MenuItem>
            </div>
        </MenuItems>
    </Menu>;
}
