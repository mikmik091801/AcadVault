<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
use App\Models\Export;
use App\Support\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ExportController extends Controller
{
    public function __construct(private ExportService $exports) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Export::class);

        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        $exports = Export::query()
            ->with(['academicRecord.student.user', 'academicRecord.course', 'exporter'])
            // Students only ever see exports of their own records.
            ->when($user->isStudent(), fn ($q) => $q->whereHas(
                'academicRecord.student',
                fn ($s) => $s->where('user_id', $user->id),
            ))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('uuid', 'like', "%{$search}%")
                    ->orWhere('file_hash', 'like', "%{$search}%")
                    ->orWhereHas('academicRecord.student', fn ($s) => $s->where('student_number', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")))
                    ->orWhereHas('academicRecord.course', fn ($c) => $c->where('code', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('exports.index', compact('exports', 'search'));
    }

    /**
     * Issue a new verifiable export for a record.
     */
    public function store(Request $request, AcademicRecord $record): RedirectResponse
    {
        $this->authorize('export', $record);

        $export = $this->exports->issue($record, $request->user());

        return redirect()
            ->route('exports.show', $export)
            ->with('success', 'Export issued. The document is ready to download.');
    }

    public function show(Export $export): View
    {
        $this->authorize('view', $export);

        $export->load(['academicRecord.student.user', 'academicRecord.course', 'exporter']);

        return view('exports.show', [
            'export' => $export,
            'verifyUrl' => $this->exports->verifyUrl($export),
        ]);
    }

    /**
     * Stream the certificate PDF. The document is re-rendered from the record
     * rather than stored, so it always reflects what the hash is checked against.
     */
    public function download(Export $export): Response
    {
        $this->authorize('download', $export);

        return $this->exports->pdf($export)
            ->download($this->exports->filename($export));
    }
}
