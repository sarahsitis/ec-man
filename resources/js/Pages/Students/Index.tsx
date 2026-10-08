import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Index({ students }: { students: any[] }) {
    const user = usePage().props.auth.user;
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Kelola Anggota
                    </h2>
                    <Link
                        href={route('students.create')}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        + Tambah Anggota
                    </Link>
                </div>
            }
        >
            <Head title="Kelola Anggota" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <div className="overflow-x-auto"><table className="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                                <thead className="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th className="px-6 py-3">Foto</th>
                                        <th className="px-6 py-3">NIS</th>
                                        <th className="px-6 py-3">Nama Lengkap</th>
                                        <th className="px-6 py-3">Kelas</th>
                                        <th className="px-6 py-3">Tahun Gabung</th>
                                        <th className="px-6 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {students.map((student) => (
                                        <tr key={student.id} className="border-b bg-white dark:border-gray-700 dark:bg-gray-800">
                                            <td className="px-6 py-4">{student.profile_photo_url ? <img src={student.profile_photo_url} alt={`Foto ${student.full_name}`} className="h-10 w-10 rounded-full object-cover" /> : <span>—</span>}</td>
                                            <td className="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                                {student.student_number}
                                            </td>
                                            <td className="px-6 py-4">{student.full_name}</td>
                                            <td className="px-6 py-4">{student.class_name || 'Belum diisi'}</td>
                                            <td className="px-6 py-4">{student.joined_year}</td>
                                            <td className="px-6 py-4 text-right">
                                                <Link href={route('students.edit', student.id)} className="font-medium text-indigo-600 hover:underline dark:text-indigo-500">Edit</Link>
                                            </td>
                                        </tr>
                                    ))}
                                    {students.length === 0 && (
                                        <tr>
                                            <td colSpan={6} className="px-6 py-4 text-center">Belum ada anggota.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table></div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
