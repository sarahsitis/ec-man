import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { useTranslation } from '@/i18n/useTranslation';

export default function useUnsavedChanges(dirty: boolean, processing: boolean) {
    const { t } = useTranslation();
    useEffect(() => {
        if (!dirty || processing) { return; }
        const unload = (event: BeforeUnloadEvent) => { event.preventDefault(); event.returnValue = ''; };
        window.addEventListener('beforeunload', unload);
        const remove = router.on('before', event => {
            const visit = event.detail.visit;
            if (visit.method === 'get' || visit.url.pathname.endsWith('/logout')) {
                return window.confirm(t('Ada perubahan yang belum disimpan. Tinggalkan formulir ini?'));
            }
        });
        return () => { window.removeEventListener('beforeunload', unload); remove(); };
    }, [dirty, processing, t]);
}
