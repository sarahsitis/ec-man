import InputError from '@/Components/InputError';
import { ManagedStudent } from '@/Components/ManagementUi';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function StudentStatusDropdown({ student, statuses, disabled = false }: {
    student: ManagedStudent; statuses: Record<string, string>; disabled?: boolean;
}) {
    const form = useForm({ status: student.status });
    const [pendingStatus, setPendingStatus] = useState<string | null>(null);
    const change = (status: string) => {
        if (status === student.status) return;
        setPendingStatus(status);
        form.transform(() => ({ status }));
        form.patch(route('students.status', student.id), { preserveScroll: true, onFinish: () => setPendingStatus(null) });
    };
    return <div className="min-w-40">
        <select aria-label={`Ganti status ${student.full_name}`} className="block w-full rounded-md border-gray-300 text-sm text-gray-900 disabled:bg-gray-100" value={pendingStatus ?? student.status} disabled={disabled || form.processing} onChange={e => change(e.target.value)}>
            {Object.entries(statuses).map(([value, label]) => <option key={value} value={value}>{value === 'alumni' ? 'Alumni (sudah lulus)' : label}</option>)}
        </select>
        <InputError message={form.errors.status} />
        {form.processing ? <p role="status" className="mt-1 text-xs text-gray-500">Menyimpan...</p> : form.recentlySuccessful && <p role="status" className="mt-1 text-xs text-green-600">Status tersimpan.</p>}
    </div>;
}
