<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ActivityAttachmentController extends Controller
{
    public function store(Request $request, Activity $activity)
    {
        abort_unless($request->user()->isPembina() || $request->user()->isPanitia(), 403);
        $kind = $request->validate(['kind' => ['required', 'string', Rule::in(array_keys(ActivityAttachment::KINDS))]]);
        $extensions = ActivityAttachment::EXTENSIONS[$kind['kind']];
        $data = $request->validate([
            'kind' => ['required', 'string', Rule::in(array_keys(ActivityAttachment::KINDS))],
            'description' => ['nullable', 'string', 'max:2000'],
            'files' => ['required', 'array', 'min:1', 'max:'.ActivityAttachment::MAX_FILES],
            'files.*' => ['required', 'file', 'extensions:'.implode(',', $extensions), 'mimes:'.implode(',', $extensions), 'max:'.ActivityAttachment::MAX_FILE_KB],
        ]);
        if (array_sum(array_map(fn ($file) => $file->getSize(), $data['files'])) > ActivityAttachment::MAX_BATCH_KB * 1024) {
            throw ValidationException::withMessages(['files' => __('Total ukuran unggahan maksimal 30 MB.')]);
        }
        $paths = [];
        try {
            DB::transaction(function () use ($activity, $request, $data, &$paths) {
                $item = Activity::lockForUpdate()->findOrFail($activity->id);
                foreach ($data['files'] as $file) {
                    $path = $file->store('activity-attachments/'.$item->id, 'local');
                    if (! $path) {
                        throw ValidationException::withMessages(['files' => __('Berkas gagal disimpan. Silakan coba kembali.')]);
                    }
                    $paths[] = $path;
                    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $file->getClientOriginalName());
                    $item->attachments()->create([
                        'kind' => $data['kind'], 'description' => $data['description'] ?? null,
                        'path' => $path, 'original_name' => Str::limit($name ?: 'berkas', 255, ''),
                        'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
                        'uploaded_by' => $request->user()->id, 'uploader_name' => $request->user()->name,
                    ]);
                }
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($paths);
            throw $e;
        }

        return back(303)->with('success', __(':count berkas kegiatan berhasil diunggah.', ['count' => count($data['files'])]));
    }

    private function readable(Request $request, Activity $activity, ActivityAttachment $attachment): void
    {
        abort_unless($attachment->activity_id === $activity->id, 404);
        abort_unless(Activity::visibleTo($request->user())->whereKey($activity->id)->exists(), 403);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);
    }

    public function download(Request $request, Activity $activity, ActivityAttachment $attachment)
    {
        $this->readable($request, $activity, $attachment);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function preview(Request $request, Activity $activity, ActivityAttachment $attachment)
    {
        $this->readable($request, $activity, $attachment);
        abort_unless($attachment->isImage(), 404);

        return Storage::disk('local')->response($attachment->path, null, [
            'Content-Type' => $attachment->mime_type, 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, Activity $activity, ActivityAttachment $attachment)
    {
        abort_unless($attachment->activity_id === $activity->id, 404);
        $actor = $request->user();
        abort_unless($actor->isPembina() || ($actor->isPanitia() && $attachment->uploaded_by === $actor->id), 403);
        $path = DB::transaction(function () use ($activity, $attachment) {
            Activity::lockForUpdate()->findOrFail($activity->id);
            $item = $activity->attachments()->lockForUpdate()->findOrFail($attachment->id);
            $path = $item->path;
            $item->delete();

            return $path;
        });
        Storage::disk('local')->delete($path);

        return back(303)->with('success', __('Berkas kegiatan dihapus.'));
    }
}
