import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import StudentContactFields, { ContactData } from '@/Components/StudentContactFields';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export interface Member {
    id: number; student_number: string; full_name: string; joined_year: string | number;
    phone?: string | null; class_name?: string | null; email?: string | null; address?: string | null;
    profile_photo_url?: string | null; user?: { role: 'siswa' | 'panitia' | 'pembina' };
}
export default function MemberForm({ student }: { student?: Member }) {
    const title = student ? 'Edit Anggota' : 'Tambah Anggota';
    const form = useForm<ContactData & { student_number: string; full_name: string; joined_year: string; _method: string; role: string }>({
        student_number: student?.student_number ?? '', full_name: student?.full_name ?? '',
        joined_year: String(student?.joined_year ?? new Date().getFullYear()),
        phone: student?.phone ?? '', class_name: student?.class_name ?? '',
        email: student?.email ?? '', address: student?.address ?? '', profile_photo: null,
        _method: student ? 'PUT' : 'POST', role: student?.user?.role ?? 'siswa',
    });
    const submit: FormEventHandler = e => {
        e.preventDefault();
        form.post(student ? route('students.update', student.id) : route('students.store'), { forceFormData: true });
    };
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">{title}</h2>}>
        <Head title={title} />
        <div className="py-12"><div className="mx-auto max-w-2xl px-4 sm:px-6"><div className="rounded-lg bg-white p-6 shadow dark:bg-gray-800 dark:text-gray-100">
            <form onSubmit={submit} className="space-y-6">
                <div><InputLabel htmlFor="student_number" value="NIS" />
                    <TextInput id="student_number" value={form.data.student_number} maxLength={255} onChange={e => form.setData('student_number', e.target.value)} required className="mt-1 block w-full" />
                    <InputError message={form.errors.student_number} />
                </div>
                <div><InputLabel htmlFor="full_name" value="Nama lengkap" />
                    <TextInput id="full_name" value={form.data.full_name} maxLength={255} onChange={e => form.setData('full_name', e.target.value)} required className="mt-1 block w-full" />
                    <InputError message={form.errors.full_name} />
                </div>
                <div><InputLabel htmlFor="joined_year" value="Tahun bergabung" />
                    <TextInput id="joined_year" type="number" min="2000" max={new Date().getFullYear()} value={form.data.joined_year} onChange={e => form.setData('joined_year', e.target.value)} required className="mt-1 block w-full" />
                    <InputError message={form.errors.joined_year} />
                </div>
                <div><InputLabel htmlFor="role" value="Peran anggota" />
                    <select id="role" value={form.data.role} onChange={e => form.setData('role', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 text-gray-900">
                        <option value="siswa">Siswa</option>
                        <option value="panitia">Panitia EC — Asisten penilai</option>
                    </select>
                    <p className="mt-1 text-sm text-gray-500">Panitia dipilih dari kelas XI atau XII. Nilai resmi tetap disahkan pembina.</p>
                    <InputError message={form.errors.role} />
                </div>
                <StudentContactFields data={form.data} errors={form.errors} onText={(k,v) => form.setData(k,v)} onPhoto={f => form.setData('profile_photo', f)} photoUrl={student?.profile_photo_url} />
                <PrimaryButton disabled={form.processing}>Simpan</PrimaryButton>
            </form>
        </div></div></div>
    </AuthenticatedLayout>;
}
