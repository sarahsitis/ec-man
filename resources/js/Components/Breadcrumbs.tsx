import { useTranslation } from '@/i18n/useTranslation';
import { Link } from '@inertiajs/react';

export interface BreadcrumbItem { label: string; href?: string }

export default function Breadcrumbs({ items }: { items: BreadcrumbItem[] }) {
    const { t } = useTranslation();
    return <nav className="ec-breadcrumb" aria-label={t('Navigasi halaman')}>
        <ol>{items.map((item, index) => <li key={index}>
            {index > 0 && <span className="ec-breadcrumb-separator" aria-hidden="true">/</span>}
            {index === items.length - 1 ? <span aria-current="page" title={item.label}>{item.label}</span> : item.href ? <Link href={item.href} title={item.label}>{item.label}</Link> : <span title={item.label}>{item.label}</span>}
        </li>)}</ol>
    </nav>;
}
