import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Edit({ student }: { student: any }) {
    const { data, setData, put, processing, errors } = useForm({
        student_number: student.student_number || '',
        full_name: student.full_name || '',
        joined_year: student.joined_year || new Date().getFullYear().toString(),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('students.update', student.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Edit Anggota: {student.full_name}
                </h2>
            }
        >
            <Head title="Edit Anggota" />

            <div className="py-12">
                <div className="mx-auto max-w-2xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <form onSubmit={submit} className="space-y-6">
                                <div>
                                    <InputLabel htmlFor="student_number" value="NIS (Nomor Induk Siswa)" />
                                    <TextInput
                                        id="student_number"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.student_number}
                                        onChange={(e) => setData('student_number', e.target.value)}
                                        required
                                        isFocused
                                    />
                                    <InputError className="mt-2" message={errors.student_number} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="full_name" value="Nama Lengkap" />
                                    <TextInput
                                        id="full_name"
                                        type="text"
                                        className="mt-1 block w-full"
                                        value={data.full_name}
                                        onChange={(e) => setData('full_name', e.target.value)}
                                        required
                                    />
                                    <InputError className="mt-2" message={errors.full_name} />
                                </div>

                                <div>
                                    <InputLabel htmlFor="joined_year" value="Tahun Gabung Ekstrakurikuler" />
                                    <TextInput
                                        id="joined_year"
                                        type="number"
                                        className="mt-1 block w-full"
                                        value={data.joined_year}
                                        onChange={(e) => setData('joined_year', e.target.value)}
                                        required
                                        min="2000"
                                    />
                                    <InputError className="mt-2" message={errors.joined_year} />
                                </div>

                                <div className="flex items-center justify-between gap-4">
                                    <PrimaryButton disabled={processing}>Simpan Perubahan</PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
