import { useTranslation } from '@/i18n/useTranslation';
import TablePagination from './TablePagination';
import useTablePagination from '@/hooks/useTablePagination';

export default function ClientTablePaging<T>({ pagination }: { pagination: ReturnType<typeof useTablePagination<T>> }) {
    const { t, locale, dateLocale } = useTranslation();
    return <TablePagination {...pagination}
        previous={<button type="button" disabled={pagination.currentPage <= 1} onClick={() => pagination.go(pagination.currentPage - 1)} className="ec-action ec-action-primary">{t("Sebelumnya")}</button>}
        next={<button type="button" disabled={pagination.currentPage >= pagination.lastPage} onClick={() => pagination.go(pagination.currentPage + 1)} className="ec-action ec-action-primary">{t("Berikutnya")}</button>} />;
}
