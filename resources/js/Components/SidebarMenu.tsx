import { Icon, IconName } from '@/Components/WorkspaceUi';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useId, useState } from 'react';

export interface SidebarMenuItem {
    label: string; href: string; active?: boolean; icon: IconName; children?: SidebarMenuItem[];
}

function MenuLink({ item, onNavigate, nested = false }: { item: SidebarMenuItem; onNavigate: () => void; nested?: boolean }) {
    return <Link href={item.href} aria-current={item.active ? 'page' : undefined} className={[item.active ? 'is-active' : '', nested ? 'ec-nav-child' : ''].filter(Boolean).join(' ')} onClick={onNavigate}><Icon name={item.icon} />{item.label}</Link>;
}

function MenuGroup({ item, onNavigate }: { item: SidebarMenuItem; onNavigate: () => void }) {
    const id = useId();
    const { url } = usePage();
    const active = !!item.active || !!item.children?.some(child => child.active);
    const [expanded, setExpanded] = useState(active);
    useEffect(() => { if (active) setExpanded(true); }, [active, url]);
    return <>
        <button id={`${id}-toggle`} type="button" className={`ec-nav-toggle${active ? ' is-section-active' : ''}`} aria-expanded={expanded} aria-controls={id} onClick={() => setExpanded(value => !value)}>
            <Icon name={item.icon} /><span>{item.label}</span><Icon name="chevron" className="ec-nav-chevron" />
        </button>
        <ul id={id} className="ec-nav-submenu" aria-labelledby={`${id}-toggle`} hidden={!expanded}>
            {item.children?.map(child => <li key={child.href}><MenuLink item={child} nested onNavigate={onNavigate} /></li>)}
        </ul>
    </>;
}

export default function SidebarMenu({ items, onNavigate }: { items: SidebarMenuItem[]; onNavigate: () => void }) {
    return <ul className="ec-nav-list">{items.map(item => <li key={item.href}>{item.children?.length ? <MenuGroup item={item} onNavigate={onNavigate} /> : <MenuLink item={item} onNavigate={onNavigate} />}</li>)}</ul>;
}
