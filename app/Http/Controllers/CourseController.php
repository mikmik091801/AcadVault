<?php

namespace App\Http\Controllers;

use App\Enums\Program;
use App\Enums\Role;
use App\Http\Requests\CourseRequest;
use App\Models\AcademicRecord;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CourseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin,registrar,faculty', only: ['index', 'show']),
            new Middleware('role:admin,registrar', except: ['index', 'show']),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Course::class);

        $user = $request->user();
        $search = trim((string) $request->query('search', ''));

        // Filtered by the program's short name (?program=BSCS) so the URL reads
        // the way the college talks.
        $program = collect(Program::cases())
            ->first(fn (Program $case) => $case->shortName() === $request->query('program'));

        $courses = Course::query()
            ->with('faculty')
            ->withCount(['academicRecords', 'activeEnrollments'])
            // Faculty only ever see the courses they teach.
            ->when($user->isFaculty(), fn ($q) => $q->where('faculty_id', $user->id))
            ->when($program, fn ($q) => $q->where('program', $program->value))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('faculty', fn ($f) => $f->where('name', 'like', "%{$search}%"));
            }))
            ->orderBy('code')
            ->paginate(10)
            ->withQueryString();

        return view('courses.index', compact('courses', 'search', 'program'));
    }

    public function create(): View
    {
        $this->authorize('create', Course::class);

        return view('courses.create', ['facultyMembers' => $this->facultyMembers()]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $this->authorize('create', Course::class);

        $course = Course::create($request->validated());

        return redirect()
            ->route('courses.show', $course)
            ->with('success', "Course {$course->code} was created.");
    }

    public function show(Request $request, Course $course): View
    {
        $this->authorize('view', $course);

        $course->load(['faculty', 'academicRecords.student.user']);

        // The class list, ordered by student name so it reads like a register.
        $roster = $course->activeEnrollments()
            ->with('student.user')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->student?->user?->name)
            ->values();

        $gradesByStudent = $course->academicRecords->keyBy('student_id');

        // Faculty only ever reach their own courses here (CoursePolicy), and
        // admin/registrar may grade any class.
        $canGrade = $request->user()->can('create', AcademicRecord::class);

        return view('courses.show', compact('course', 'roster', 'gradesByStudent', 'canGrade'));
    }

    public function edit(Course $course): View
    {
        $this->authorize('update', $course);

        return view('courses.edit', [
            'course' => $course,
            'facultyMembers' => $this->facultyMembers(),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $course->update($request->validated());

        return redirect()
            ->route('courses.show', $course)
            ->with('success', "Course {$course->code} was updated.");
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        $code = $course->code;
        $course->delete();

        return redirect()
            ->route('courses.index')
            ->with('success', "Course {$code} was deleted.");
    }

    private function facultyMembers()
    {
        return User::query()
            ->where('role', Role::Faculty)
            ->orderBy('name')
            ->get();
    }
}
