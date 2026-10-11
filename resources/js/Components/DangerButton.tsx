import { ButtonHTMLAttributes } from 'react';

export default function DangerButton({
    className = '',
    disabled,
    children,
    ...props
}: ButtonHTMLAttributes<HTMLButtonElement>) {
    return (
        <button
            {...props}
            className={
                `ec-action ec-action-danger ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
