import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';

export default function Dashboard() {
    const user = usePage().props.auth.user;
    const roleLabel = user.role === 'pembina' ? 'Pembina' : user.role === 'panitia' ? 'Panitia EC — Asisten Penilai' : 'Siswa';
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Dashboard {roleLabel}
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            Selamat datang, {user.name}! Anda masuk sebagai {roleLabel}.
                            <p className="mt-3 text-sm text-gray-500">{user.role === 'pembina' ? 'Pantau kemampuan awal dan minat siswa untuk merencanakan latihan.' : 'Isi pre-test untuk mengenali kemampuan awal dan menentukan minat belajar Anda.'}</p>
                            <Link className="mt-4 inline-block rounded bg-indigo-600 px-4 py-2 text-white" href={route(user.role === 'pembina' ? 'pretests.reports' : 'pretests.index')}>{user.role === 'pembina' ? 'Lihat hasil pre-test' : 'Buka pre-test saya'}</Link>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
