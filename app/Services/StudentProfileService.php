<?php
namespace App\Services;

use App\Models\Student;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StudentProfileService
{
    public static function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]+$/'],
            'class_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function save(Student $student, array $data, ?UploadedFile $photo, ?Closure $accountUpdate = null): Student
    {
        $old = $student->profile_photo_path;
        $path = null;
        unset($data['profile_photo']);
        try {
            if ($photo) {
                $path = $photo->store('profile-photos', 'local');
                if (!$path) { throw new RuntimeException('Foto gagal disimpan.'); }
                $data['profile_photo_path'] = $path;
            }
            DB::transaction(function () use ($student, $data, $accountUpdate) {
                if ($accountUpdate) { $accountUpdate($student); }
                $student->fill($data)->save();
            });
        } catch (Throwable $e) {
            if ($path) { Storage::disk('local')->delete($path); }
            throw $e;
        }
        if ($path && $old) { Storage::disk('local')->delete($old); }
        return $student;
    }
}
