import { usePage } from '@inertiajs/react';
import { SelectHTMLAttributes } from 'react';

export default function ClassSelect({ value, ...props }: SelectHTMLAttributes<HTMLSelectElement> & { value: string }) {
    const groups = usePage().props.classGroups;
    const knownClass = Object.values(groups).some(classes => classes.includes(value));
    return <select {...props} value={value} className={`mt-1 block w-full rounded-md border-gray-300 text-gray-900 disabled:bg-gray-100 ${props.className || ''}`}>
        <option value="">Pilih kelas</option>
        {value && !knownClass && <option value={value}>{value} (kelas tersimpan)</option>}
        {Object.entries(groups).map(([group, classes]) => <optgroup key={group} label={group}>{classes.map(className => <option key={className} value={className}>{className}</option>)}</optgroup>)}
    </select>;
}
