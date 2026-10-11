import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import { translate, Parameters, Locale } from './translate';

export function useTranslation() {
    const locale: Locale = usePage().props.locale === 'en' ? 'en' : 'id';
    const t = useCallback((message: string | number, parameters?: Parameters) => translate(message, locale, parameters), [locale]);
    return { t, locale, dateLocale: locale === 'en' ? 'en-GB' : 'id-ID' };
}
