<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StudentRequest;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class StudentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin,registrar'),
        ];
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Student::class);

        $search = trim((string) $request->query('search', ''));

        $students = Student::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereRaw('LOWER(student_number) LIKE ?', ['%'.strtolower($search).'%'])
                        ->orWhereRaw('LOWER(program) LIKE ?', ['%'.strtolower($search).'%'])
                        ->orWhereHas('user', function ($u) use ($search) {
                            // Every word in the search must appear somewhere in
                            // the student's identity, so "anna" matches all
                            // Annas and "anna bautista" also matches
                            // "Anna L. Bautista".
                            foreach (preg_split('/\s+/', $search) ?: [] as $term) {
                                $term = strtolower($term);
                                $u->where(function ($termQuery) use ($term) {
                                    $termQuery->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                                        ->orWhereRaw('LOWER(first_name) LIKE ?', ["%{$term}%"])
                                        ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$term}%"])
                                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$term}%"]);
                                });
                            }
                        });
                });
            })
            // select() must come before withCount(): called after, it replaces
            // the column list and the count silently disappears.
            ->select('students.*')
            ->withCount('academicRecords')
            ->join('users', 'users.id', '=', 'students.user_id')
            ->orderBy('users.name')
            ->paginate(10)
            ->withQueryString();

        return view('students.index', compact('students', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', Student::class);

        return view('students.create', [
            'availableUsers' => $this->assignableUsers(),
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $this->authorize('create', Student::class);

        $student = Student::create($request->validated());

        // A student profile implies the student role.
        $student->user->forceFill(['role' => Role::Student])->save();

        return redirect()
            ->route('students.show', $student)
            ->with('success', "Student {$student->student_number} was created.");
    }

    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        $student->load(['user', 'academicRecords.course']);

        $classes = $student->activeEnrollments()
            ->with('course.faculty')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->course?->code)
            ->values();

        return view('students.show', compact('student', 'classes'));
    }

    public function edit(Student $student): View
    {
        $this->authorize('update', $student);

        return view('students.edit', [
            'student' => $student,
            'availableUsers' => $this->assignableUsers($student),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('update', $student);

        $student->update($request->validated());

        return redirect()
            ->route('students.show', $student)
            ->with('success', 'Student details were updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student);

        $number = $student->student_number;
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('success', "Student {$number} was deleted.");
    }

    /**
     * Users who do not already have a student profile (plus the one being
     * edited, so the current selection stays available).
     */
    private function assignableUsers(?Student $student = null)
    {
        return User::query()
            ->whereDoesntHave('student')
            ->when($student, fn ($q) => $q->orWhere('id', $student->user_id))
            ->orderBy('name')
            ->get();
    }
}
