<?php

namespace App\Services;

class DataTransferSchema
{
    public const STUDENT_TEMPLATE_TITLE = 'DAFTAR PESERTA EKTRAKULIKULER ENGLISH CLUB';

    public const TEMPLATE_TITLES = [
        'students' => self::STUDENT_TEMPLATE_TITLE,
        'memberships' => 'DAFTAR KEANGGOTAAN EKSTRAKURIKULER ENGLISH CLUB',
        'attendances' => 'REKAP PRESENSI EKSTRAKURIKULER ENGLISH CLUB',
        'scores' => 'DAFTAR NILAI RESMI EKSTRAKURIKULER ENGLISH CLUB',
    ];

    public const LABELS = [
        'students' => 'Data Siswa', 'memberships' => 'Keanggotaan',
        'attendances' => 'Presensi', 'scores' => 'Nilai Resmi',
    ];

    public const COLUMNS = [
        'students' => ['nis', 'nama_lengkap', 'tahun_bergabung', 'kelas', 'status', 'telepon', 'email', 'alamat'],
        'memberships' => ['nis', 'nama_lengkap', 'tahun_ajaran', 'semester', 'kelas', 'status'],
        'attendances' => ['nis', 'nama_lengkap', 'kegiatan_id', 'tanggal_kegiatan', 'judul_kegiatan', 'tahun_ajaran', 'semester', 'status'],
        'scores' => ['nis', 'nama_lengkap', 'judul_penilaian', 'aspek', 'nilai', 'tanggal_pengesahan', 'umpan_balik', 'catatan_pembina'],
    ];

    public const HEADINGS = [
        'nis' => 'Nomor Induk Siswa', 'nama_lengkap' => 'Nama Lengkap', 'tahun_bergabung' => 'Tahun Bergabung',
        'kelas' => 'Kelas', 'status' => 'Status', 'telepon' => 'Telepon', 'email' => 'Email', 'alamat' => 'Alamat',
        'tahun_ajaran' => 'Tahun Ajaran', 'semester' => 'Semester', 'kegiatan_id' => 'ID Kegiatan',
        'tanggal_kegiatan' => 'Tanggal Kegiatan', 'judul_kegiatan' => 'Nama Kegiatan',
        'judul_penilaian' => 'Nama Penilaian', 'aspek' => 'Aspek', 'nilai' => 'Nilai',
        'tanggal_pengesahan' => 'Tanggal Pengesahan (WIB)', 'umpan_balik' => 'Umpan Balik', 'catatan_pembina' => 'Catatan Pembina',
    ];

    // Escaping is reversible on import, including phone numbers beginning with + and existing apostrophes.
    public static function csvCell(mixed $value, bool $identifier = false): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@\t\r\n\']/', $value) || ($identifier && preg_match('/^[0-9]+$/', $value)) ? "'".$value : $value;
    }

    public static function importCell(string $value): string
    {
        if (str_starts_with($value, "'") && (preg_match('/^[=+\-@\t\r\n\']/', substr($value, 1)) || preg_match('/^[0-9]+$/', substr($value, 1)))) {
            $value = substr($value, 1);
        }

        return trim($value);
    }
}
