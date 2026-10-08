import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { useEffect, useState } from 'react';

export type ContactKey = 'phone' | 'class_name' | 'email' | 'address';
export interface ContactData {
    phone: string; class_name: string; email: string; address: string;
    profile_photo: File | null;
}
export default function StudentContactFields({ data, errors, onText, onPhoto, photoUrl, lockClass = false }: {
    data: ContactData; errors: Partial<Record<ContactKey | 'profile_photo', string>>;
    onText: (key: ContactKey, value: string) => void;
    onPhoto: (file: File | null) => void; photoUrl?: string | null; lockClass?: boolean;
}) {
    const [preview, setPreview] = useState<string | null>(null);
    useEffect(() => {
        if (!data.profile_photo) { setPreview(null); return; }
        const url = URL.createObjectURL(data.profile_photo);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [data.profile_photo]);
    return <>
        <div>
            <InputLabel htmlFor="profile_photo" value="Foto profil" />
            {(preview || photoUrl) && <img src={preview || photoUrl || ''} alt="Foto profil" className="my-3 h-24 w-24 rounded-full object-cover" />}
            <input id="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" className="mt-2 block w-full" onChange={e => onPhoto(e.target.files?.[0] ?? null)} />
            <p className="mt-1 text-sm text-gray-500">JPG, PNG, atau WebP. Maksimal 2 MB. Kosongkan untuk mempertahankan foto lama.</p>
            <InputError message={errors.profile_photo} />
        </div>
        {([
            ['phone', 'Nomor HP', 'tel'], ['class_name', 'Kelas saat ini', 'text'], ['email', 'Email', 'email'],
        ] as const).map(([key, label, type]) => <div key={key}>
            <InputLabel htmlFor={key} value={label} />
            <TextInput id={key} readOnly={key === 'class_name' && lockClass} type={type} value={data[key]} maxLength={key === 'phone' ? 30 : key === 'class_name' ? 100 : 255} onChange={e => onText(key, e.target.value)} className="mt-1 block w-full" />
            <InputError message={errors[key]} />
        </div>)}
        <div>
            <InputLabel htmlFor="address" value="Alamat" />
            <textarea id="address" value={data.address} maxLength={2000} rows={3} onChange={e => onText('address', e.target.value)} className="mt-1 block w-full rounded-md border-gray-300 text-gray-900" />
            <InputError message={errors.address} />
        </div>
    </>;
}
