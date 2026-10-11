<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Laporan English Club') }} — {{ $period['from'] }} — {{ $period['until'] }}</title>
    <style>
        @page { size: A4 landscape; margin: 13mm 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f4; color: #172033; font: 12px/1.55 Arial, Helvetica, sans-serif; }
        .toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; max-width: 1120px; margin: 20px auto; padding: 0 20px; }
        .toolbar a { color: #5b21b6; } button { padding: 10px 18px; border: 0; border-radius: 6px; background: #5b21b6; color: white; font: inherit; cursor: pointer; }
        .document { max-width: 1120px; margin: 20px auto 40px; padding: 36px; background: white; box-shadow: 0 4px 24px #17203312; }
        .letterhead { text-align: center; padding-bottom: 18px; border-bottom: 3px double #172033; }
        .letterhead .club { font-size: 13px; font-weight: bold; letter-spacing: 2px; }
        .letterhead h1 { font-size: 22px; margin: 6px 0; text-transform: uppercase; letter-spacing: 1px; }
        .letterhead p { margin: 3px 0; color: #455166; }
        .report-title { text-align: center; margin: 23px 0; } .report-title h2 { font-size: 17px; margin: 0; text-transform: uppercase; }
        .metadata { display: grid; grid-template-columns: 1fr 1fr; gap: 7px 24px; margin: 20px 0; }
        .metadata p { margin: 0; display: grid; grid-template-columns: 140px 1fr; } .metadata span { overflow-wrap: anywhere; }
        .note { border-left: 3px solid #94a3b8; padding: 10px 12px; background: #f8fafc; color: #455166; }
        .section { margin-top: 26px; } .section + .section { break-before: page; }
        h3 { font-size: 15px; margin: 0 0 10px; text-transform: uppercase; letter-spacing: .4px; }
        .summary { display: flex; flex-wrap: wrap; gap: 8px 24px; margin: 8px 0 16px; padding: 10px 12px; border: 1px solid #cbd5e1; }
        .summary span { display: inline-block; } .summary strong { margin-left: 5px; }
        .table-wrap { overflow-x: auto; } table { width: 100%; border-collapse: collapse; font-size: 10px; table-layout: fixed; }
        th, td { border: 1px solid #cbd5e1; padding: 7px 6px; text-align: left; vertical-align: top; overflow-wrap: anywhere; white-space: pre-line; }
        thead { display: table-header-group; } th { background: #edf0f5; font-weight: bold; } tbody tr:nth-child(even) { background: #fafbfd; }
        th.number { width: 34px; } td.number { text-align: center; } th.nis { width: 90px; } th.nama_lengkap { width: 130px; }
        tr { break-inside: avoid; } .empty { padding: 20px; text-align: center; }
        .signatures { display: flex; justify-content: space-between; gap: 24px; margin-top: 32px; break-inside: avoid; }
        .signature { width: 260px; text-align: center; } .signature .name { margin-top: 66px; font-weight: bold; text-decoration: underline; overflow-wrap: anywhere; }
        footer { margin-top: 28px; padding-top: 10px; border-top: 1px solid #cbd5e1; color: #64748b; font-size: 10px; }
        @media (max-width: 700px) { .document { padding: 20px; } .metadata { grid-template-columns: 1fr; } table { min-width: 850px; } .signatures { flex-wrap: wrap; } }
        @media print { body { background: white; font-size: 10px; } .toolbar { display: none; } .document { max-width: none; margin: 0; padding: 0; box-shadow: none; } table { min-width: 0; } .table-wrap { overflow: visible; } th { print-color-adjust: exact; } .letterhead h1 { font-size: 20px; } .summary, .note { break-inside: avoid; } }
    </style>
</head>
<body>
    <div class="toolbar"><a href="{{ route('data-transfer.index', array_merge($filters, ['dataset' => $filters['dataset'] === 'all' ? 'students' : $filters['dataset']])) }}">← {{ __('Kembali ke Laporan & Arsip') }}</a><span>{{ __('Pilih Simpan sebagai PDF pada dialog cetak.') }}</span><button type="button" onclick="window.print()">{{ __('Cetak / Simpan PDF') }}</button></div>
    <main class="document">
        <header class="letterhead"><div class="club">ENGLISH CLUB</div><h1>{{ $filters['school_name'] ?: 'EC-Man' }}</h1><p>{{ __('Administrasi dan Laporan Kegiatan Ekstrakurikuler') }}</p></header>
        <div class="report-title"><h2>{{ __('Laporan English Club') }} — {{ __($period['label']) }}</h2><p>{{ __('Nomor Dokumen') }}: EC/{{ strtoupper($filters['dataset']) }}/{{ str_replace('-', '', $period['from']) }}–{{ str_replace('-', '', $period['until']) }}</p></div>
        <div class="metadata">
            <p><strong>{{ __('Tahun Ajaran') }}</strong><span>: {{ $period['year']['name'] }}</span></p>
            <p><strong>{{ __('Semester') }}</strong><span>: {{ __(ucfirst($period['year']['semester'])) }}</span></p>
            <p><strong>{{ __('Periode Laporan') }}</strong><span>: {{ \Illuminate\Support\Carbon::parse($period['from'])->locale(app()->getLocale())->translatedFormat('d F Y') }} — {{ \Illuminate\Support\Carbon::parse($period['until'])->locale(app()->getLocale())->translatedFormat('d F Y') }}</span></p>
            <p><strong>{{ __('Kelas') }}</strong><span>: {{ $filters['class_name'] ?: __('Semua kelas') }}</span></p>
            <p><strong>{{ __('Tanggal Penyusunan') }}</strong><span>: {{ $generatedAt->locale(app()->getLocale())->translatedFormat('d F Y, H:i') }} WIB</span></p>
            <p><strong>{{ __('Disusun oleh') }}</strong><span>: {{ $preparedBy }}</span></p>
        </div>
        <p class="note">{{ __('Data siswa menunjukkan data induk saat ini; keanggotaan mengikuti semester terpilih. Presensi mengikuti tanggal kegiatan; nilai mengikuti tanggal pengesahan dalam WIB. Nilai menggunakan skala 1–4.') }} {{ __('Filter kelas menggunakan kelas saat ini untuk data siswa dan kelas keanggotaan semester untuk data lainnya.') }}</p>
        @foreach ($sections as $index => $section)
            <section class="section">
                <h3>{{ $index + 1 }}. {{ __($section['title']) }}</h3>
                <div class="summary">@foreach ($section['summary'] as $label => $value)<span>{{ __($label) }}:<strong>{{ $value }}</strong></span>@endforeach</div>
                @if ($section['dataset'] === 'attendances')<p>{{ __('Persentase kehadiran dihitung dari presensi yang sudah diisi. Data belum diisi dan kegiatan dibatalkan tidak dihitung sebagai ketidakhadiran.') }}</p>@endif
                <div class="table-wrap"><table><thead><tr><th class="number">{{ __('No.') }}</th>@foreach ($section['columns'] as $column)<th class="{{ $column }}">{{ __($headings[$column]) }}</th>@endforeach</tr></thead><tbody>
                    @forelse ($section['rows'] as $row)
                        <tr><td class="number">{{ $loop->iteration }}</td>@foreach ($section['columns'] as $column)
                            @php
                                $value = $row[$column];
                                if ($column === 'status') { $value = \App\Models\Student::STATUSES[$value] ?? \App\Models\Attendance::STATUSES[$value] ?? $value; }
                                elseif ($column === 'semester' || $column === 'aspek') { $value = ucfirst($value); }
                            @endphp
                            <td>{{ in_array($column, ['status', 'semester', 'aspek']) ? __($value) : ($value === null || $value === '' ? '—' : $value) }}</td>
                        @endforeach</tr>
                    @empty <tr><td class="empty" colspan="{{ count($section['columns']) + 1 }}">{{ __('Tidak ada data pada periode dan kelas yang dipilih.') }}</td></tr>
                    @endforelse
                </tbody></table></div>
            </section>
        @endforeach
        <div class="signatures"><div class="signature"><p>{{ __('Disusun oleh') }},</p><p>{{ __('Petugas Administrasi English Club') }}</p><p class="name">{{ $preparedBy }}</p></div><div class="signature"><p>{{ __('Mengetahui') }},</p><p>{{ $filters['signatory_role'] }}</p><p class="name">{{ $filters['signatory'] }}</p>@if ($filters['signatory_nip'])<p>NIP {{ $filters['signatory_nip'] }}</p>@endif</div></div>
        <footer>{{ __('Dokumen dibuat melalui EC-Man. Ruang tanda tangan disediakan untuk pengesahan setelah pemeriksaan laporan.') }}</footer>
    </main>
</body>
</html>
