import { Icon } from '@/Components/WorkspaceUi';
import DataImportPanel, { Dataset, datasetLabels } from '@/Components/DataImportPanel';
import Modal from '@/Components/Modal';
import { useTranslation } from '@/i18n/useTranslation';
import { usePage } from '@inertiajs/react';
import { DialogTitle } from '@headlessui/react';
import { useState } from 'react';

export default function DataTransferActions({ dataset, academicYearId }: {
    dataset: Dataset;
    academicYearId?: number | null;
}) {
    const { t } = useTranslation();
    const user = usePage().props.auth.user;
    const [open, setOpen] = useState(false);
    const [busy, setBusy] = useState(false);
    if (user.role !== 'pembina') return null;

    const params = { dataset, period: 'semester', ...(academicYearId ? { academic_year_id: academicYearId } : {}) };
    const exportScope = dataset === 'students' ? 'Ekspor mencakup seluruh data siswa.'
        : academicYearId ? 'Ekspor mencakup data semester yang dipilih.' : 'Ekspor mencakup data semester aktif atau semester terbaru.';
    return <>
        <div>
            <nav className="flex flex-wrap gap-3" aria-label={t('Ekspor dan impor data')}>
                <button type="button" className="ec-action ec-action-primary" onClick={() => setOpen(true)}>
                    <Icon name="clipboard" />{t('Impor Data')}
                </button>
                <a className="ec-action ec-action-secondary" href={route('data-transfer.export', params)}>
                    <Icon name="save" />{t('Ekspor Data')}
                </a>
            </nav>
            <p className="mt-2 text-xs text-gray-500">{t(exportScope)}</p>
        </div>
        <Modal show={open} maxWidth="2xl" closeable={!busy} onClose={() => setOpen(false)}>
            <div className="flex items-center justify-between gap-4 border-b border-gray-200 px-5 py-4">
                <DialogTitle className="text-lg font-semibold">{t('Impor Data')} · {t(datasetLabels[dataset])}</DialogTitle>
                <button type="button" className="ec-action ec-action-secondary" disabled={busy} onClick={() => setOpen(false)}>{t('Tutup')}</button>
            </div>
            <div className="max-h-[calc(100dvh-10rem)] overflow-y-auto p-3 sm:p-5">
                {open && <DataImportPanel initialDataset={dataset} fixedDataset onBusyChange={setBusy} />}
            </div>
        </Modal>
    </>;
}
