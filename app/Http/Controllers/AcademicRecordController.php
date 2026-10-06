<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicRecordRequest;
use App\Models\AcademicRecord;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Support\AuditLogger;
use App\Support\SearchTerm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class AcademicRecordController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin,registrar,faculty', only: ['create', 'store', 'edit', 'update']),
            new Middleware('role:admin,registrar', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', AcademicRecord::class);

        $search = trim((string) $request->query('search', ''));

        $records = $this->scopedQuery($request)
            ->with(['student.user', 'course'])
            ->when($search !== '', fn ($q) => $q->where(function (Builder $sub) use ($search) {
                // NOTE: grade and remarks are encrypted at rest, so they are
                // deliberately not searchable here.
                $sub->whereHas('student', function ($s) use ($search) {
                    SearchTerm::where($s, 'student_number', $search);
                    $s->orWhereHas('user', fn ($u) => SearchTerm::whereAllWords($u, ['name', 'first_name', 'last_name', 'email'], $search));
                })
                    ->orWhereHas('course', function ($c) use ($search) {
                        SearchTerm::where($c, 'code', $search);
                        SearchTerm::orWhere($c, 'title', $search);
                    });
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('records.index', compact('records', 'search'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AcademicRecord::class);

        $courses = $this->assignableCourses($request);

        return view('records.create', [
            'students' => Student::with('user')->get()->sortBy('user.name'),
            'courses' => $courses,
            'rosters' => $this->rosters($courses),
            'prefill' => [
                'student_id' => $request->integer('student_id') ?: null,
                'course_id' => $request->integer('course_id') ?: null,
            ],
            'returnToCourse' => $request->boolean('return_to_course'),
        ]);
    }

    public function store(AcademicRecordRequest $request): RedirectResponse
    {
        $this->authorize('create', AcademicRecord::class);

        // record.created is written by AcademicRecordObserver.
        $record = AcademicRecord::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $record->load('student.user', 'course');
        $record->student?->user?->notify(new \App\Notifications\GradePosted($record, posted: true));

        // Grading from a class list goes back to that list, so the next
        // student is one click away.
        if ($request->boolean('return_to_course')) {
            return redirect()
                ->route('courses.show', $record->course_id)
                ->with('success', "Grade saved for {$record->student?->user?->name}.");
        }

        return redirect()
            ->route('records.show', $record)
            ->with('success', 'Academic record was created.');
    }

    public function show(AcademicRecord $record): View
    {
        $this->authorize('view', $record);

        $record->load(['student.user', 'course.faculty', 'creator']);

        // Reading this page decrypts the grade and remarks, which is itself
        // an auditable event.
        AuditLogger::log(AuditLogger::RECORD_VIEWED, $record);

        return view('records.show', compact('record'));
    }

    public function edit(Request $request, AcademicRecord $record): View
    {
        $this->authorize('update', $record);

        $courses = $this->assignableCourses($request);

        return view('records.edit', [
            'record' => $record,
            'students' => Student::with('user')->get()->sortBy('user.name'),
            'courses' => $courses,
            'rosters' => $this->rosters($courses),
        ]);
    }

    public function update(AcademicRecordRequest $request, AcademicRecord $record): RedirectResponse
    {
        $this->authorize('update', $record);

        // record.updated is written by AcademicRecordObserver.
        $record->update($request->validated());

        $record->load('student.user', 'course');
        $record->student?->user?->notify(new \App\Notifications\GradePosted($record, posted: false));

        return redirect()
            ->route('records.show', $record)
            ->with('success', 'Academic record was updated.');
    }

    public function destroy(AcademicRecord $record): RedirectResponse
    {
        $this->authorize('delete', $record);

        // record.deleted is written by AcademicRecordObserver.
        $record->delete();

        return redirect()
            ->route('records.index')
            ->with('success', 'Academic record was deleted.');
    }

    /**
     * Restrict the visible records to what the current role may see.
     */
    private function scopedQuery(Request $request): Builder
    {
        $user = $request->user();
        $query = AcademicRecord::query();

        if ($user->isFaculty()) {
            return $query->whereHas('course', fn ($c) => $c->where('faculty_id', $user->id));
        }

        if ($user->isStudent()) {
            return $query->whereHas('student', fn ($s) => $s->where('user_id', $user->id));
        }

        return $query; // admin + registrar see everything
    }

    /**
     * Faculty may only file grades against courses they actually teach.
     */
    private function assignableCourses(Request $request)
    {
        $user = $request->user();

        return Course::query()
            ->when($user->isFaculty(), fn ($q) => $q->where('faculty_id', $user->id))
            ->orderBy('code')
            ->get();
    }

    /**
     * Who is actively enrolled in each course, so the form can narrow the
     * student list to the chosen class.
     *
     * @param  Collection<int, Course>  $courses
     * @return array<int, array<int, int>>
     */
    private function rosters(Collection $courses): array
    {
        return Enrollment::query()
            ->whereIn('course_id', $courses->modelKeys())
            ->active()
            ->get(['course_id', 'student_id'])
            ->groupBy('course_id')
            ->map(fn (Collection $enrollments) => $enrollments->pluck('student_id')->values()->all())
            ->all();
    }
}
