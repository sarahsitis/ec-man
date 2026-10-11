import { LabelHTMLAttributes } from 'react';
import { useTranslation } from '@/i18n/useTranslation';

export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}: LabelHTMLAttributes<HTMLLabelElement> & { value?: string }) {
    const { t } = useTranslation();
    return (
        <label
            {...props}
            className={
                `block text-sm font-medium text-gray-700 dark:text-gray-300 ` +
                className
            }
        >
            {value ? t(value) : children}
        </label>
    );
}
