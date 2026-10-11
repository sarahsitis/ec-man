import { HTMLAttributes } from 'react';
import { useTranslation } from '@/i18n/useTranslation';

export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    const { t } = useTranslation();
    return message ? (
        <p
            {...props}
            className={'text-sm text-red-600 dark:text-red-400 ' + className}
        >
            {t(message)}
        </p>
    ) : null;
}
