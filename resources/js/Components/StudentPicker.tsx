import { useId, useState } from 'react';
import InputLabel from '@/Components/InputLabel';
import { field } from '@/Components/AssessmentUi';
import { ManagedStudent, StatusBadge } from '@/Components/ManagementUi';

export default function StudentPicker({ students, selected, onChange, disabled = false }: {
    students: ManagedStudent[]; selected: number[]; onChange: (ids: number[]) => void; disabled?: boolean;
}) {
    const [search, setSearch] = useState('');
    const id = useId();
    const visible = students.filter(s => `${s.full_name} ${s.student_number} ${s.class_name || ''}`.toLocaleLowerCase('id-ID').includes(search.toLocaleLowerCase('id-ID')));
    const allSelected = visible.length > 0 && visible.every(s => selected.includes(s.id));
    const toggleAll = () => {
        const ids = visible.map(s => s.id);
        onChange(allSelected ? selected.filter(value => !ids.includes(value)) : [...new Set([...selected, ...ids])]);
    };
    return <fieldset disabled={disabled}>
        <legend className="text-sm font-medium">Pilih siswa ({selected.length} dipilih)</legend>
        <InputLabel htmlFor={id} value="Cari nama, NIS, atau kelas" className="mt-2" />
        <input id={id} type="search" className={field} value={search} onChange={e=>setSearch(e.target.value)} />
        <div className="my-2 flex flex-wrap gap-3 text-sm"><button type="button" className="text-indigo-600" disabled={!visible.length} onClick={toggleAll}>{allSelected ? 'Batalkan pilihan ditampilkan' : 'Pilih semua ditampilkan'}</button>{selected.length > 0 && <button type="button" className="text-indigo-600" onClick={()=>onChange([])}>Hapus pilihan</button>}</div>
        <div className="max-h-64 overflow-y-auto rounded border border-gray-300">{visible.map(s=><label key={s.id} className="flex cursor-pointer items-center gap-3 border-b border-gray-200 p-3 last:border-b-0">
            <input type="checkbox" className="rounded text-indigo-600 focus:ring-indigo-500" checked={selected.includes(s.id)} onChange={()=>onChange(selected.includes(s.id) ? selected.filter(value=>value!==s.id) : [...selected, s.id])} />
            <span className="flex-1">{s.full_name}<span className="block text-xs text-gray-500">{s.student_number} · {s.class_name || 'Kelas belum diisi'}</span></span><StatusBadge status={s.status} />
        </label>)}{visible.length === 0 && <p className="p-4 text-sm text-gray-500">Tidak ada siswa yang sesuai.</p>}</div>
    </fieldset>;
}
