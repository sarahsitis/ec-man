<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TablePagination
{
    public static function perPage(Request $request): int
    {
        $data = $request->validate(['per_page' => ['sometimes', 'integer', Rule::in([10, 25, 50])]]);

        return (int) ($data['per_page'] ?? 10);
    }
}
