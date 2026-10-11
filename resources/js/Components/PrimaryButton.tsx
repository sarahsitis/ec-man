import { ButtonHTMLAttributes } from 'react';

export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            className={
                `ec-action ec-action-primary ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
