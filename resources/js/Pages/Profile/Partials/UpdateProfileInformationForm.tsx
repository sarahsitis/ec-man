import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import StudentContactFields, { ContactData } from '@/Components/StudentContactFields';
import { Member } from '@/Pages/Students/MemberForm';
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

function StudentProfile({ student }: { student?: Member | null }) {
    const user = usePage().props.auth.user;
    const form = useForm<ContactData & { _method: string }>({
        phone: student?.phone ?? '', class_name: student?.class_name ?? '', email: student?.email ?? '',
        address: student?.address ?? '', profile_photo: null, _method: 'PATCH',
    });
    const submit: FormEventHandler = e => {
        e.preventDefault();
        form.transform(data => {
            if (user.committee_class_locked) {
                const { class_name, ...permitted } = data;
                return permitted;
            }
            return data;
        });
        form.post(route('profile.update'), { forceFormData: true });
    };
    return <form onSubmit={submit} className="mt-6 space-y-6">
        <div><InputLabel htmlFor="full_name" value="Nama lengkap" /><TextInput id="full_name" value={student?.full_name ?? user.name} readOnly className="mt-1 block w-full bg-gray-100" /></div>
        <div><InputLabel htmlFor="student_number" value="NIS" /><TextInput id="student_number" value={student?.student_number ?? user.username} readOnly className="mt-1 block w-full bg-gray-100" /></div>
        <p className="text-sm text-gray-500">Untuk memperbaiki nama atau NIS, hubungi pembina English Club.</p>
        {user.committee_class_locked && <p className="text-sm text-gray-500">Perubahan kelas panitia dilakukan oleh pembina.</p>}
        <StudentContactFields lockClass={user.committee_class_locked} data={form.data} errors={form.errors} onText={(k,v) => form.setData(k,v)} onPhoto={f => form.setData('profile_photo',f)} photoUrl={student?.profile_photo_url} />
        <PrimaryButton disabled={form.processing}>Simpan profil</PrimaryButton>
        {form.recentlySuccessful && <p role="status">Profil tersimpan.</p>}
    </form>;
}
function PembinaProfile() {
    const user = usePage().props.auth.user;
    const form = useForm({ name: user.name, username: user.username });
    const submit: FormEventHandler = e => { e.preventDefault(); form.patch(route('profile.update')); };
    return <form onSubmit={submit} className="mt-6 space-y-6">
        <div><InputLabel htmlFor="name" value="Nama" /><TextInput id="name" value={form.data.name} onChange={e=>form.setData('name',e.target.value)} required className="mt-1 block w-full" /><InputError message={form.errors.name} /></div>
        <div><InputLabel htmlFor="username" value="Username" /><TextInput id="username" value={form.data.username} onChange={e=>form.setData('username',e.target.value)} required className="mt-1 block w-full" /><InputError message={form.errors.username} /></div>
        <PrimaryButton disabled={form.processing}>Simpan profil</PrimaryButton>
        {form.recentlySuccessful && <p role="status">Profil tersimpan.</p>}
    </form>;
}
export default function UpdateProfileInformation({ className = '', student }: { status?: string; className?: string; student?: Member | null }) {
    const user = usePage().props.auth.user;
    return <section className={className}><h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">Profil pengguna</h2>
        {user.role === 'pembina' ? <PembinaProfile /> : <StudentProfile student={student} />}
    </section>;
}
