import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import { field, panel } from '@/Components/AssessmentUi';
import { activityDate, ManagementNav, StatusBadge } from '@/Components/ManagementUi';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface Candidate { user_id: number; full_name: string; student_number: string; class_name: string }
interface Appointment {
    id: number; user_id: number; starts_on: string; ends_on: string; note: string | null;
    status: string; revoked_at: string | null; revoke_reason: string | null;
    user: { name: string; student: Candidate | null };
    appointed_by: { name: string } | null; revoked_by: { name: string } | null;
}
const statuses: Record<string, string> = {
    active: 'Aktif', scheduled: 'Terjadwal', expired: 'Berakhir', revoked: 'Dicabut', suspended: 'Ditangguhkan',
};

export default function Index({ appointments, candidates, today }: { appointments: Appointment[]; candidates: Candidate[]; today: string }) {
    const [editing, setEditing] = useState<Appointment | null>(null);
    const [revoking, setRevoking] = useState<Appointment | null>(null);
    const [filter, setFilter] = useState('all');
    const semesterEnd = today.slice(0, 4) + (Number(today.slice(5, 7)) <= 6 ? '-06-30' : '-12-31');
    const defaults = { user_id: '', starts_on: today, ends_on: semesterEnd, note: '' };
    const form = useForm(defaults);
    const revoke = useForm({ revoke_reason: '' });
    const busy = form.processing || revoke.processing;
    const reset = () => { setEditing(null); form.setData(defaults); form.clearErrors(); };
    const edit = (appointment: Appointment) => {
        setEditing(appointment);
        form.clearErrors();
        form.setData({ user_id: String(appointment.user_id), starts_on: appointment.starts_on, ends_on: appointment.ends_on, note: appointment.note ?? '' });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    const submit: FormEventHandler = e => {
        e.preventDefault();
        const options = { preserveScroll: true, onSuccess: reset };
        if (editing) { form.put(route('committee-roles.update', editing.id), options); }
        else { form.post(route('committee-roles.store'), options); }
    };
    const confirmRevoke: FormEventHandler = e => {
        e.preventDefault();
        if (!revoking) { return; }
        revoke.delete(route('committee-roles.destroy', revoking.id), {
            preserveScroll: true,
            onSuccess: () => { if (editing?.id === revoking.id) { reset(); } setRevoking(null); revoke.reset(); },
        });
    };
    const visible = appointments.filter(a => filter === 'all' || a.status === filter);

    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Panitia EC</h2>}>
        <Head title="Manajemen Panitia EC" />
        <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6">
            <ManagementNav />
            <div className={panel}>
                <h3 className="text-lg font-semibold">{editing ? `Edit masa tugas: ${editing.user.name}` : 'Angkat panitia'}</h3>
                <p className="mt-2 text-sm text-gray-500">Panitia merupakan peran tambahan untuk siswa aktif kelas XI/XII. Akses penilaian dan presensi berlaku dari tanggal mulai hingga akhir tanggal selesai (WIB). Nilai resmi tetap disahkan pembina.</p>
                <form onSubmit={submit} className="mt-5 grid gap-4 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <InputLabel htmlFor="committee_user" value="Siswa" />
                        {editing ? <p className="mt-1 font-medium">{editing.user.name} · {editing.user.student?.class_name ?? 'Profil siswa belum tersedia'}</p>
                            : <select id="committee_user" className={field} required value={form.data.user_id} onChange={e => form.setData('user_id', e.target.value)}>
                                <option value="">Pilih siswa aktif kelas XI/XII</option>
                                {candidates.map(s => <option key={s.user_id} value={s.user_id}>{s.full_name} · {s.student_number} · {s.class_name}</option>)}
                            </select>}
                        <InputError message={form.errors.user_id} />
                        {!editing && !candidates.length && <p className="mt-2 text-sm text-amber-700">Belum ada calon panitia. Lengkapi kelas dan status aktif melalui data siswa.</p>}
                    </div>
                    <div><InputLabel htmlFor="committee_start" value="Mulai masa tugas" />
                        <input id="committee_start" type="date" required className={field} value={form.data.starts_on} onChange={e => form.setData('starts_on', e.target.value)} />
                        <InputError message={form.errors.starts_on} /></div>
                    <div><InputLabel htmlFor="committee_end" value="Selesai masa tugas" />
                        <input id="committee_end" type="date" required min={form.data.starts_on > today ? form.data.starts_on : today} className={field} value={form.data.ends_on} onChange={e => form.setData('ends_on', e.target.value)} />
                        <InputError message={form.errors.ends_on} /></div>
                    <div className="md:col-span-2"><InputLabel htmlFor="committee_note" value="Catatan tugas (opsional)" />
                        <textarea id="committee_note" className={field} maxLength={2000} rows={2} value={form.data.note} onChange={e => form.setData('note', e.target.value)} />
                        <InputError message={form.errors.note} /></div>
                    <div className="flex gap-4 md:col-span-2">
                        <PrimaryButton disabled={busy || (!editing && !candidates.length)}>{editing ? 'Simpan masa tugas' : 'Angkat panitia'}</PrimaryButton>
                        {editing && <button type="button" disabled={busy} onClick={reset} className="text-gray-500">Batal edit</button>}
                    </div>
                </form>
            </div>

            {revoking && <div className={panel}>
                <h3 className="text-lg font-semibold">Cabut hak: {revoking.user.name}</h3>
                <p className="mt-2 text-sm text-gray-500">Akses panitia dari penugasan ini akan dihentikan segera. Riwayat penugasan dan penilaian tetap tersimpan.</p>
                <form onSubmit={confirmRevoke} className="mt-4 space-y-4">
                    <div><InputLabel htmlFor="revoke_reason" value="Alasan pencabutan (opsional)" />
                        <textarea id="revoke_reason" rows={2} maxLength={2000} className={field} value={revoke.data.revoke_reason} onChange={e => revoke.setData('revoke_reason', e.target.value)} />
                        <InputError message={revoke.errors.revoke_reason} /></div>
                    <div className="flex gap-4"><button disabled={busy} className="rounded bg-red-600 px-4 py-2 font-medium text-white disabled:opacity-50">Cabut hak panitia</button>
                        <button type="button" disabled={busy} onClick={() => setRevoking(null)} className="text-gray-500">Batal</button></div>
                </form>
            </div>}

            <div className={panel}>
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h3 className="text-lg font-semibold">Daftar & riwayat panitia</h3>
                    <select aria-label="Filter status panitia" className="rounded-md border-gray-300 text-sm text-gray-900" value={filter} onChange={e => setFilter(e.target.value)}>
                        <option value="all">Semua status</option>{Object.entries(statuses).map(([key, label]) => <option key={key} value={key}>{label}</option>)}
                    </select>
                </div>
                <p className="mb-4 text-sm text-gray-500">Hak berakhir otomatis setelah masa tugas selesai. Status ditangguhkan berarti status siswa, kelas, atau peran akun belum memenuhi syarat. Penugasan yang telah dicabut perlu dibuat ulang untuk mengangkat kembali.</p>
                <div className="overflow-x-auto"><table className="w-full text-left text-sm">
                    <thead><tr className="border-b">{['Siswa', 'Masa tugas', 'Status', 'Catatan & riwayat', 'Aksi'].map(label => <th key={label} className="p-3">{label}</th>)}</tr></thead>
                    <tbody>{visible.map(a => <tr key={a.id} className="border-b align-top">
                        <td className="p-3 font-medium">{a.user.name}<p className="text-xs font-normal text-gray-500">{a.user.student?.student_number} · {a.user.student?.class_name ?? 'Belum diisi'}</p></td>
                        <td className="p-3">{activityDate(a.starts_on)}<p className="text-gray-500">s.d. {activityDate(a.ends_on)}</p></td>
                        <td className="p-3"><StatusBadge status={a.status} labels={statuses} /></td>
                        <td className="max-w-xs whitespace-pre-wrap break-words p-3">{a.note || '—'}<p className="mt-1 text-xs text-gray-500">Diangkat: {a.appointed_by?.name ?? 'Migrasi penugasan lama'}</p>
                            {a.revoked_at && <p className="mt-1 text-xs text-red-700">Dicabut: {a.revoked_by?.name} · {new Date(a.revoked_at).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta' })} WIB{a.revoke_reason && ` · ${a.revoke_reason}`}</p>}</td>
                        <td className="p-3">{!a.revoked_at && <div className="flex flex-wrap gap-3">
                            <button disabled={busy} className="text-indigo-600" onClick={() => edit(a)} aria-label={`Edit masa tugas ${a.user.name}`}>Edit masa tugas</button>
                            <button disabled={busy} className="text-red-600" onClick={() => { setRevoking(a); revoke.clearErrors(); revoke.reset(); window.scrollTo({ top: 0, behavior: 'smooth' }); }} aria-label={`Cabut hak ${a.user.name}`}>Cabut hak</button>
                        </div>}</td>
                    </tr>)}{!visible.length && <tr><td colSpan={5} className="p-6 text-center text-gray-500">Belum ada penugasan panitia dengan status ini.</td></tr>}</tbody>
                </table></div>
            </div>
        </div>
    </AuthenticatedLayout>;
}
