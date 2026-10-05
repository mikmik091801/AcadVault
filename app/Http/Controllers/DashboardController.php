<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', match (true) {
            $user->isAdmin() => $this->adminData(),
            $user->isRegistrar() => $this->registrarData(),
            $user->isFaculty() => $this->facultyData($user),
            default => $this->studentData($user),
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function adminData(): array
    {
        return [
            'stats' => [
                ['label' => 'Total users', 'value' => User::count(), 'icon' => 'bi-people', 'variant' => 'navy', 'href' => route('users.index')],
                ['label' => 'Students', 'value' => Student::count(), 'icon' => 'bi-mortarboard', 'variant' => 'accent', 'href' => route('students.index')],
                ['label' => 'Courses', 'value' => Course::count(), 'icon' => 'bi-journal-bookmark', 'variant' => 'success', 'href' => route('courses.index')],
                ['label' => 'Academic records', 'value' => AcademicRecord::count(), 'icon' => 'bi-file-earmark-text', 'variant' => 'warning', 'href' => route('records.index')],
            ],
            'recentLogs' => AuditLog::with('user')->latest()->limit(8)->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function registrarData(): array
    {
        $pendingDrops = Enrollment::awaitingReview()->count();

        return [
            'stats' => [
                ['label' => 'Students', 'value' => Student::count(), 'icon' => 'bi-mortarboard', 'variant' => 'navy', 'href' => route('students.index')],
                ['label' => 'Courses', 'value' => Course::count(), 'icon' => 'bi-journal-bookmark', 'variant' => 'accent', 'href' => route('courses.index')],
                ['label' => 'Academic records', 'value' => AcademicRecord::count(), 'icon' => 'bi-file-earmark-text', 'variant' => 'success', 'href' => route('records.index')],
                ['label' => 'Drop requests', 'value' => $pendingDrops, 'icon' => 'bi-hourglass-split', 'variant' => $pendingDrops > 0 ? 'danger' : 'warning', 'hint' => $pendingDrops > 0 ? 'Waiting for your review' : 'Nothing to review', 'href' => route('drop-requests.index')],
            ],
            'recentRecords' => AcademicRecord::with(['student.user', 'course'])->latest()->limit(6)->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function facultyData(User $user): array
    {
        $courseIds = $user->courses()->pluck('id');

        // Headcount across every class they teach, counting each student once
        // even if they hold two of this instructor's classes.
        $enrolled = Enrollment::whereIn('course_id', $courseIds)->active();

        // Enrolled students who still have no grade, per course.
        $ungradedByCourse = $this->withoutGrade(clone $enrolled)
            ->selectRaw('course_id, count(*) as aggregate')
            ->groupBy('course_id')
            ->pluck('aggregate', 'course_id');

        $ungraded = $ungradedByCourse->sum();

        return [
            'stats' => [
                ['label' => 'My courses', 'value' => $courseIds->count(), 'icon' => 'bi-journal-bookmark', 'variant' => 'navy', 'href' => route('courses.index')],
                ['label' => 'Students enrolled', 'value' => (clone $enrolled)->distinct('student_id')->count('student_id'), 'icon' => 'bi-people', 'variant' => 'success'],
                ['label' => 'Grades filed', 'value' => AcademicRecord::whereIn('course_id', $courseIds)->count(), 'icon' => 'bi-file-earmark-text', 'variant' => 'accent', 'href' => route('records.index')],
                ['label' => 'Grades to file', 'value' => $ungraded, 'icon' => 'bi-pencil-square', 'variant' => $ungraded > 0 ? 'danger' : 'warning', 'hint' => $ungraded > 0 ? 'Across your classes' : 'All caught up', 'href' => route('courses.index')],
            ],
            'myCourses' => $user->courses()
                ->withCount(['academicRecords', 'activeEnrollments'])
                ->orderBy('code')
                ->limit(6)
                ->get()
                ->each(fn (Course $course) => $course->setAttribute('ungraded_count', (int) ($ungradedByCourse[$course->id] ?? 0))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function studentData(User $user): array
    {
        $student = $user->student;
        $records = $student
            ? $student->academicRecords()->with('course')->latest()->get()
            : collect();

        return [
            'student' => $student,
            'stats' => [
                ['label' => 'Enrolled classes', 'value' => $student?->activeEnrollments()->count() ?? 0, 'icon' => 'bi-journal-bookmark', 'variant' => 'accent', 'href' => route('enrollments.index')],
                ['label' => 'My records', 'value' => $records->count(), 'icon' => 'bi-file-earmark-text', 'variant' => 'navy', 'href' => route('records.index')],
                ['label' => 'Year level', 'value' => $student?->yearLevelLabel() ?? '—', 'icon' => 'bi-mortarboard', 'variant' => 'success'],
            ],
            'recentRecords' => $records->take(6),
        ];
    }

    /**
     * Narrow an enrolment query to rows that have no academic record yet.
     *
     * @param  Builder<Enrollment>  $enrollments
     * @return Builder<Enrollment>
     */
    private function withoutGrade(Builder $enrollments): Builder
    {
        return $enrollments->whereNotExists(fn (QueryBuilder $records) => $records
            ->selectRaw('1')
            ->from('academic_records')
            ->whereColumn('academic_records.student_id', 'enrollments.student_id')
            ->whereColumn('academic_records.course_id', 'enrollments.course_id'));
    }
}
