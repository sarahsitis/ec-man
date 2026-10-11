<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Membership;
use App\Models\Student;
use App\Services\DataImportService;
use App\Services\DataTransferSchema;
use App\Services\PeriodicReportService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DataTransferController extends Controller
{
    private const SESSION_KEY = 'data_import_preview';

    private function filters(Request $request, bool $all = false): array
    {
        $data = $request->validate([
            'dataset' => ['nullable', Rule::in(array_merge(array_keys(DataTransferSchema::LABELS), $all ? ['all'] : []))],
            'period' => ['nullable', Rule::in(['week', 'month', 'semester'])],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'date' => ['nullable', 'date_format:Y-m-d', 'after:1999-12-31', 'before:2101-01-01'],
            'class_name' => ['nullable', 'string', 'max:100'], 'school_name' => ['nullable', 'string', 'max:160'],
            'signatory' => ['nullable', 'string', 'max:160'], 'signatory_role' => ['nullable', 'string', 'max:100'],
            'signatory_nip' => ['nullable', 'string', 'regex:/^[0-9]{18}$/'],
        ]);
        $yearId = $data['academic_year_id'] ?? AcademicYear::orderByDesc('is_active')->orderByDesc('name')->orderBy('semester')->value('id');

        return [
            'dataset' => $data['dataset'] ?? 'students', 'period' => $data['period'] ?? 'semester',
            'academic_year_id' => $yearId, 'date' => $data['date'] ?? now('Asia/Jakarta')->toDateString(),
            'class_name' => $data['class_name'] ?? '', 'school_name' => $data['school_name'] ?? config('reports.school_name'),
            'signatory' => $data['signatory'] ?? config('reports.signatory'), 'signatory_role' => $data['signatory_role'] ?? config('reports.signatory_role'),
            'signatory_nip' => array_key_exists('signatory_nip', $data) ? ($data['signatory_nip'] ?? '') : config('reports.signatory_nip'),
        ];
    }

    public function index(Request $request, PeriodicReportService $reports)
    {
        $navigation = $request->validate(['tab' => ['nullable', Rule::in(['export', 'import'])]]);
        $filters = $this->filters($request);
        $period = $filters['academic_year_id'] ? $reports->period($filters) : null;
        $section = $period ? $reports->section($filters['dataset'], $filters, $period) : null;
        if ($section) {
            $section['rows'] = array_slice($section['rows'], 0, 20);
        }
        $preview = $request->session()->get(self::SESSION_KEY);
        if ($preview && $preview['expires_at'] < time()) {
            $request->session()->forget(self::SESSION_KEY);
            $preview = null;
        }

        return Inertia::render('Reports/DataTransfer', [
            'filters' => $filters, 'period' => $period, 'section' => $section,
            'initialTab' => $navigation['tab'] ?? ($preview ? 'import' : 'export'),
            'years' => AcademicYear::orderByDesc('name')->orderBy('semester')->get(),
            'classes' => Membership::where('academic_year_id', $filters['academic_year_id'])->whereNotNull('class_name')->pluck('class_name')
                ->merge(Student::whereNotNull('class_name')->pluck('class_name'))->unique()->sort()->values(),
            'datasets' => DataTransferSchema::LABELS, 'headings' => DataTransferSchema::HEADINGS,
            'importPreview' => $preview ? $this->previewPayload($preview) : null,
        ]);
    }

    public function export(Request $request, PeriodicReportService $reports)
    {
        $filters = $this->filters($request);
        if ($filters['dataset'] === 'students' && ! $filters['academic_year_id']) {
            return $this->csv(DataTransferSchema::COLUMNS['students'], $reports->students($filters['class_name']), 'ec-man-students-'.now('Asia/Jakarta')->toDateString().'.csv');
        }
        if (! $filters['academic_year_id']) {
            throw ValidationException::withMessages(['academic_year_id' => 'Tambahkan tahun ajaran terlebih dahulu.']);
        }
        $period = $reports->period($filters);
        $section = $reports->section($filters['dataset'], $filters, $period);

        return $this->csv($section['columns'], $section['rows'], 'ec-man-'.$filters['dataset'].'-'.$period['from'].'-'.$period['until'].'.csv');
    }

    public function template(Request $request)
    {
        $data = $request->validate(['dataset' => ['required', Rule::in(array_keys(DataTransferSchema::LABELS))]]);

        $columns = array_map('strtoupper', DataTransferSchema::COLUMNS[$data['dataset']]);

        return $this->csv($columns, [], 'template-'.$data['dataset'].'.csv', ';', [
            array_pad([DataTransferSchema::TEMPLATE_TITLES[$data['dataset']]], count($columns), ''),
            array_pad([DataTransferSchema::csvCell(config('reports.school_name'))], count($columns), ''),
            array_fill(0, count($columns), ''),
        ]);
    }

    private function csv(array $columns, array $rows, string $filename, string $delimiter = ',', array $preamble = [])
    {
        return response()->streamDownload(function () use ($columns, $rows, $delimiter, $preamble) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($preamble as $line) {
                fputcsv($handle, $line, $delimiter, '"', '', "\r\n");
            }
            fputcsv($handle, $columns, $delimiter, '"', '', "\r\n");
            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($column) => DataTransferSchema::csvCell($row[$column], $column === 'nis'), $columns), $delimiter, '"', '', "\r\n");
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function print(Request $request, PeriodicReportService $reports)
    {
        $filters = $this->filters($request, true);
        if (! $filters['academic_year_id']) {
            throw ValidationException::withMessages(['academic_year_id' => 'Tambahkan tahun ajaran terlebih dahulu.']);
        }
        $period = $reports->period($filters);
        $datasets = $filters['dataset'] === 'all' ? array_keys(DataTransferSchema::LABELS) : [$filters['dataset']];

        return response()->view('reports.periodic', [
            'filters' => $filters, 'period' => $period, 'headings' => DataTransferSchema::HEADINGS,
            'sections' => array_map(fn ($dataset) => $reports->section($dataset, $filters, $period), $datasets),
            'preparedBy' => $request->user()->name, 'generatedAt' => now('Asia/Jakarta'),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function preview(Request $request, DataImportService $imports)
    {
        $request->session()->forget(self::SESSION_KEY);
        $data = $request->validate([
            'dataset' => ['required', Rule::in(array_keys(DataTransferSchema::LABELS))],
            'mode' => ['required', Rule::in(['create', 'update'])],
            'file' => ['required', 'file', 'max:5120', 'extensions:csv,txt', 'mimetypes:text/plain,text/csv,text/x-csv,application/csv,application/vnd.ms-excel'],
        ]);
        if ($data['dataset'] === 'scores' && $data['mode'] !== 'create') {
            throw ValidationException::withMessages(['mode' => 'Nilai resmi hanya dapat ditambahkan, bukan ditimpa.']);
        }
        $rows = $imports->read($request->file('file'), $data['dataset']);
        $plan = $imports->plan($data['dataset'], $rows, $data['mode']);
        $request->session()->put(self::SESSION_KEY, [
            'token' => (string) Str::uuid(), 'actor_id' => $request->user()->id, 'expires_at' => time() + 1800,
            'dataset' => $data['dataset'], 'mode' => $data['mode'], 'filename' => $request->file('file')->getClientOriginalName(),
            'rows' => $rows, 'plan' => $plan,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'preview' => $this->previewPayload($request->session()->get(self::SESSION_KEY)),
                'headings' => DataTransferSchema::HEADINGS,
            ]);
        }

        return back(303);
    }

    private function previewPayload(array $preview): array
    {
        return [
            'token' => $preview['token'], 'dataset' => $preview['dataset'], 'mode' => $preview['mode'],
            'filename' => $preview['filename'], 'counts' => $preview['plan']['counts'], 'total' => $preview['plan']['total'],
            'errors' => $preview['plan']['errors'], 'rows' => array_map(fn ($command) => [
                'row' => $command['row'], 'nis' => $command['nis'], 'action' => $command['action'],
                'values' => $preview['rows'][$command['row'] - 2],
            ], array_slice($preview['plan']['commands'], 0, 50)),
            'columns' => DataTransferSchema::COLUMNS[$preview['dataset']],
        ];
    }

    public function import(Request $request, DataImportService $imports)
    {
        $request->validate(['token' => ['required', 'uuid']]);
        $preview = $request->session()->get(self::SESSION_KEY);
        if (! $preview || ! hash_equals($preview['token'], $request->input('token')) || $preview['actor_id'] !== $request->user()->id || $preview['expires_at'] < time()) {
            throw ValidationException::withMessages(['import' => 'Pratinjau tidak tersedia atau sudah kedaluwarsa. Unggah ulang file.']);
        }
        if ($preview['plan']['errors']) {
            throw ValidationException::withMessages(['import' => 'Perbaiki seluruh kesalahan sebelum menyimpan impor.']);
        }
        if ($preview['dataset'] === 'scores') {
            $request->validate(['approve_scores' => ['accepted']]);
        }
        try {
            $counts = $imports->commit($preview['dataset'], $preview['rows'], $preview['mode'], $request->user(), $preview['plan']['commands']);
        } catch (QueryException $e) {
            report($e);
            throw ValidationException::withMessages(['import' => 'Impor gagal karena konflik data. Tidak ada perubahan disimpan. Periksa kembali file dan buat pratinjau baru.']);
        }
        $request->session()->forget(self::SESSION_KEY);

        $message = __('Impor selesai: :created ditambahkan, :updated diperbarui, :skipped dilewati.', [
            'created' => $counts['create'], 'updated' => $counts['update'], 'skipped' => $counts['skip'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => $message, 'counts' => $counts]);
        }

        return back(303)->with('success', $message);
    }

    public function cancel(Request $request)
    {
        $request->session()->forget(self::SESSION_KEY);

        if ($request->wantsJson()) {
            return response()->noContent();
        }

        return back(303);
    }
}
