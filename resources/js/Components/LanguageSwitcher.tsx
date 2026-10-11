import { router } from '@inertiajs/react';
import { useEffect, useId, useState } from 'react';
import { useTranslation } from '@/i18n/useTranslation';
import { Icon } from './WorkspaceUi';

export default function LanguageSwitcher() {
    const { t, locale } = useTranslation();
    const id = useId();
    const [busy, setBusy] = useState(false);
    useEffect(() => { document.documentElement.lang = locale; }, [locale]);
    return <div className="ec-language" aria-busy={busy}>
        <label htmlFor={id}><Icon name="globe" /><span className="sr-only">{t('Bahasa')}</span></label>
        <select id={id} value={locale} disabled={busy} onChange={event => {
            router.post(route('locale.update'), { locale: event.target.value }, {
                preserveState: true, preserveScroll: true,
                onStart: () => setBusy(true), onFinish: () => setBusy(false),
            });
        }}><option value="id" lang="id">Indonesia</option><option value="en" lang="en">English</option></select>
    </div>;
}
