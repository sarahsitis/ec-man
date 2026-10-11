<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityAttachment extends Model
{
    public const KINDS = ['material' => 'Materi Kegiatan', 'documentation' => 'Dokumentasi Kegiatan'];

    public const EXTENSIONS = [
        'material' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'csv', 'jpg', 'jpeg', 'png', 'webp', 'mp3', 'mp4', 'webm'],
        'documentation' => ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'mp4', 'mov', 'webm'],
    ];

    public const MAX_FILE_KB = 20480;

    public const MAX_BATCH_KB = 30720;

    public const MAX_FILES = 10;

    protected $fillable = ['activity_id', 'kind', 'original_name', 'path', 'mime_type', 'size', 'description', 'uploaded_by', 'uploader_name'];

    protected $hidden = ['path'];

    protected function casts(): array
    {
        return ['activity_id' => 'integer', 'uploaded_by' => 'integer', 'size' => 'integer'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function isImage(): bool
    {
        return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true);
    }
}
