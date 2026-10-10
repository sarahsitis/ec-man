import { router } from '@inertiajs/react';
import { useEffect } from 'react';

export default function useUnsavedChanges(dirty: boolean, processing: boolean) {
    useEffect(() => {
        if (!dirty || processing) { return; }
        const unload = (event: BeforeUnloadEvent) => { event.preventDefault(); event.returnValue = ''; };
        window.addEventListener('beforeunload', unload);
        const remove = router.on('before', event => {
            const visit = event.detail.visit;
            if (visit.method === 'get' || visit.url.pathname.endsWith('/logout')) {
                return window.confirm('Ada perubahan yang belum disimpan. Tinggalkan formulir ini?');
            }
        });
        return () => { window.removeEventListener('beforeunload', unload); remove(); };
    }, [dirty, processing]);
}
