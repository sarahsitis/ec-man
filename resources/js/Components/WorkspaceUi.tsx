import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { ReactNode, SVGProps, useId } from 'react';
import { assessmentStatuses } from './assessmentPresentation';

const paths = {
    grid: <><rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" /></>,
    people: <><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M22 21v-2a4 4 0 0 0-3-3.87" /><circle cx="9" cy="7" r="4" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /></>,
    clipboard: <><rect x="5" y="5" width="14" height="16" rx="2" /><rect x="9" y="2" width="6" height="6" rx="1" /><path d="m9 15 2 2 4-4" /></>,
    book: <path d="M12 5c-3-2-7-2-10-1v16c3-1 7-1 10 1 3-2 7-2 10-1V4c-3-1-7-1-10 1Zm0 0v16" />,
    lock: <><rect x="5" y="10" width="14" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" /></>,
    arrow: <path d="M5 12h14m-5-5 5 5-5 5" />,
    back: <path d="M19 12H5m5-5-5 5 5 5" />,
    check: <><path d="m5 12 4 4L19 6" /><circle cx="12" cy="12" r="9" /></>,
    clock: <><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></>,
    calendar: <><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 11h18" /></>,
    user: <><circle cx="12" cy="8" r="4" /><path d="M4 21v-2a8 8 0 0 1 16 0v2" /></>,
    save: <><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h12l4 4v12a2 2 0 0 1-2 2Z" /><path d="M7 3v6h10V3M7 21v-8h10v8" /></>,
    send: <path d="m22 2-7 20-4-9-9-4 20-7Zm0 0L11 13" />,
    menu: <path d="M4 6h16M4 12h16M4 18h16" />,
    close: <path d="m6 6 12 12M6 18 18 6" />,
    shield: <><path d="m12 2 8 4v6c0 5-8 10-8 10S4 17 4 12V6l8-4Z" /><path d="m8 12 3 3 5-6" /></>,
    logout: <><path d="M9 5H4v14h5M14 8l4 4-4 4M9 12h9" /></>,
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
    const tones: Record<string, string> = { assigned: 'neutral', draft: 'orange', submitted: 'blue', revision: 'red', approved: 'green', rejected: 'red', cancelled: 'neutral' };
    return <span className={`ec-badge ec-badge-${tones[status] ?? 'neutral'}`}>{assessmentStatuses[status] ?? status}</span>;
}
export function PageHeading({ eyebrow, title, description, extra }: { eyebrow: string; title: string; description: string; extra?: ReactNode }) {
    return <div className="ec-heading"><div><p className="ec-eyebrow">{eyebrow}</p><h1>{title}</h1><p className="ec-subtitle">{description}</p></div>{extra}</div>;
}
export function EmptyState({ title, children }: { title: string; children: ReactNode }) {
    return <div className="ec-empty"><Icon name="clipboard" /><h3>{title}</h3><div>{children}</div></div>;
}
export function HeroArt() {
    return <svg className="ec-hero-art" viewBox="0 0 210 180" fill="none" aria-hidden="true"><circle cx="119" cy="84" r="69" fill="#dce7cf" /><path d="M52 143h112" stroke="#a7b995" strokeWidth="1.5" /><path d="M127 90c-28-40-62-17-50 7 7 14 29 18 41 30 2-16-8-33-24-40" fill="#b5c6a3" /><path d="M119 129c3-27 12-48 24-55 22-12 33 9 18 25-10 11-23 16-37 21" fill="#8ea97a" /><path d="M121 142c0-37 11-59 26-61" stroke="#57794a" strokeWidth="2" /><rect x="62" y="53" width="60" height="79" rx="6" transform="rotate(-13 62 53)" fill="#fcfdf7" stroke="#c4d0b6" strokeWidth="1.5" /><path d="m81 71 24-6m-21 19 24-6m-21 19 18-4" stroke="#b7c5a8" strokeWidth="2.5" strokeLinecap="round" /><circle cx="53" cy="109" r="17" fill="#d8a675" /><path d="m46 109 5 5 9-10" stroke="#fff9f0" strokeWidth="2.5" strokeLinecap="round" /></svg>;
}
export function ConfirmAction({ open, title, children, label, busy, onClose, onConfirm }: {
    open: boolean; title: string; children: ReactNode; label: string; busy: boolean; onClose: () => void; onConfirm: () => void;
}) {
    const descriptionId = useId();
    return <Dialog open={open} onClose={() => { if (!busy) { onClose(); } }} className="ec-workspace ec-dialog-root" aria-describedby={descriptionId}>
        <div className="ec-dialog-backdrop" aria-hidden="true" /><div className="ec-dialog-wrap"><DialogPanel className="ec-dialog">
            <DialogTitle className="ec-dialog-title">{title}</DialogTitle><div id={descriptionId} className="ec-dialog-description">{children}</div>
            <div className="ec-actions"><button type="button" className="ec-btn" data-autofocus disabled={busy} onClick={onClose}>Batal</button><button type="button" className="ec-btn ec-btn-primary" disabled={busy} onClick={onConfirm}>{busy ? 'Memproses…' : label}</button></div>
        </DialogPanel></div>
    </Dialog>;
}
