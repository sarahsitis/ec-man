import { useTranslation } from '@/i18n/useTranslation';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { ReactNode, SVGProps, useId } from 'react';
import { assessmentStatuses } from './assessmentPresentation';

const paths = {
    globe: <><circle cx="12" cy="12" r="9" /><path d="M3 12h18M12 3a18 18 0 0 1 0 18 18 18 0 0 1 0-18Z" /></>,
    grid: <><rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" /></>,
    people: <><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M22 21v-2a4 4 0 0 0-3-3.87" /><circle cx="9" cy="7" r="4" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></>,
    clipboard: <><rect x="5" y="5" width="14" height="16" rx="2" /><rect x="9" y="2" width="6" height="6" rx="1" /><path d="m9 15 2 2 4-4" /></>,
    book: <path d="M12 5c-3-2-7-2-10-1v16c3-1 7-1 10 1 3-2 7-2 10-1V4c-3-1-7-1-10 1Zm0 0v16" />,
    lock: <><rect x="5" y="10" width="14" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" /></>,
    home: <><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Z" /><path d="M9 21v-8h6v8" /></>,
    key: <><circle cx="8" cy="15" r="5" /><path d="m12 11 8-8m-3 3 3 3m-6 0 3 3" /></>,
    chart: <><rect x="3" y="3" width="18" height="18" rx="2" /><path d="M7 17v-4m5 4V7m5 10v-7" /></>,
    check: <><path d="m5 12 4 4L19 6" /><circle cx="12" cy="12" r="9" /></>,
    clock: <><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>,
    calendar: <><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 11h18" /></>,
    user: <><circle cx="12" cy="8" r="4" /><path d="M4 21v-2a8 8 0 0 1 16 0v2" /></>,
    save: <><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2Z" /><path d="M7 3v6h10V3M7 21v-8h10v8" /></>,
    send: <path d="m22 2-7 20-4-9-9-4 20-7Zm0 0L11 13" />,
    menu: <path d="M4 6h16M4 12h16M4 18h16" />,
    close: <path d="m6 6 12 12M6 18 18 6" />,
    shield: <><path d="m12 2 8 4v6c0 5-8 10-8 10S4 17 4 12V6l8-4Z" /><path d="m8 12 3 3 5-6" /></>,
    logout: <><path d="M12 3v9" /><path d="M6.4 5.6a9 9 0 1 0 11.2 0" /></>,
};
export type IconName = keyof typeof paths;
export function Icon({ name, ...props }: SVGProps<SVGSVGElement> & { name: IconName }) {
    return <svg width="20" height="20" {...props} className={`ec-icon ${props.className ?? ''}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">{paths[name]}</svg>;
}
export function Avatar({ name }: { name: string }) {
    const initials = name.trim().split(/\s+/).slice(0, 2).map(word => word[0]).join('').toUpperCase();
    return <span className="ec-avatar" aria-hidden="true">{initials}</span>;
}
export function AssessmentBadge({ status }: { status: string }) {
    const { t, locale, dateLocale } = useTranslation();
    const tones: Record<string, string> = { assigned: 'neutral', draft: 'orange', submitted: 'blue', revision: 'red', approved: 'green', rejected: 'red', cancelled: 'neutral' };
    return <span className={`ec-badge ec-badge-${tones[status] ?? 'neutral'}`}>{t(assessmentStatuses[status] ?? status)}</span>;
}
export function PageHeading({ eyebrow, title, description, extra }: { eyebrow: string; title: string; description: string; extra?: ReactNode }) {
    const { t, locale, dateLocale } = useTranslation();
    return <div className="ec-heading"><div><p className="ec-eyebrow">{eyebrow}</p><h1>{title}</h1><p className="ec-subtitle">{t(description)}</p></div>{extra}</div>;
}
export function EmptyState({ title, children }: { title: string; children: ReactNode }) {
    return <div className="ec-empty"><Icon name="clipboard" /><h3>{title}</h3><div>{children}</div></div>;
}
export function HeroArt() {
    return <svg className="ec-hero-art" viewBox="0 0 240 200" fill="none" aria-hidden="true">
        <circle cx="128" cy="101" r="76" fill="var(--ec-primary-soft)" />
        <ellipse cx="124" cy="111" rx="95" ry="47" transform="rotate(-22 124 111)" stroke="#f9a8d4" strokeWidth="2" strokeDasharray="4 6" />
        <g transform="rotate(-10 122 111)"><rect x="74" y="57" width="94" height="112" rx="17" fill="var(--ec-primary)" />
            <rect x="82" y="63" width="84" height="98" rx="12" fill="white" /><path d="M96 81h55M96 93h40M96 137h42M96 148h30" stroke="#d8b4fe" strokeWidth="4" strokeLinecap="round" />
            <path d="m121 103 5 10 11 2-8 8 2 11-10-5-10 5 2-11-8-8 11-2 5-10Z" fill="var(--ec-secondary)" />
        </g>
        <rect x="145" y="30" width="62" height="47" rx="17" fill="var(--ec-secondary)" />
        <path d="m158 72-5 13 19-10" fill="var(--ec-secondary)" /><path d="m161 54 9 8 19-17" stroke="white" strokeWidth="4" strokeLinecap="round" strokeLinejoin="round" />
        <circle cx="47" cy="132" r="18" fill="#ede9fe" stroke="#c4b5fd" strokeWidth="2" /><path d="M47 123v18m-9-9h18" stroke="var(--ec-primary)" strokeWidth="3" strokeLinecap="round" />
        <path d="m58 31 3 7 8 3-8 3-3 7-3-7-8-3 8-3 3-7Z" fill="var(--ec-secondary)" /><path d="m201 130 3 7 8 3-8 3-3 7-3-7-8-3 8-3 3-7Z" fill="var(--ec-primary)" />
        <circle cx="187" cy="171" r="5" fill="#f9a8d4" /><circle cx="90" cy="26" r="4" fill="#c4b5fd" />
    </svg>;
}
export function ConfirmAction({ open, title, children, label, busy, onClose, onConfirm }: {
    open: boolean; title: string; children: ReactNode; label: string; busy: boolean; onClose: () => void; onConfirm: () => void;
}) {
    const { t, locale, dateLocale } = useTranslation();
    const descriptionId = useId();
    return <Dialog open={open} onClose={() => { if (!busy) { onClose(); } }} className="ec-workspace ec-dialog-root" aria-describedby={descriptionId}>
        <div className="ec-dialog-backdrop" aria-hidden="true" /><div className="ec-dialog-wrap"><DialogPanel className="ec-dialog">
            <DialogTitle className="ec-dialog-title">{title}</DialogTitle><div id={descriptionId} className="ec-dialog-description">{children}</div>
            <div className="ec-actions"><button type="button" className="ec-btn" data-autofocus disabled={busy} onClick={onClose}>{t("Batal")}</button><button type="button" className="ec-btn ec-btn-primary" disabled={busy} onClick={onConfirm}>{busy ? t("Memproses…") : label}</button></div>
        </DialogPanel></div>
    </Dialog>;
}
