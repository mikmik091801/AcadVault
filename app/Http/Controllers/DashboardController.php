<?php

namespace App\Http\Controllers;

use App\Models\AcademicRecord;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Export;
use App\Models\Student;
use App\Models\User;
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
                ['label' => 'Total users', 'value' => User::count(), 'icon' => 'bi-people', 'variant' => 'navy'],
                ['label' => 'Students', 'value' => Student::count(), 'icon' => 'bi-mortarboard', 'variant' => 'accent'],
                ['label' => 'Courses', 'value' => Course::count(), 'icon' => 'bi-journal-bookmark', 'variant' => 'success'],
                ['label' => 'Academic records', 'value' => AcademicRecord::count(), 'icon' => 'bi-file-earmark-text', 'variant' => 'warning'],
            ],
            'recentLogs' => AuditLog::with('user')->latest()->limit(8)->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function registrarData(): array
    {
        return [
            'stats' => [
                ['label' => 'Students', 'value' => Student::count(), 'icon' => 'bi-mortarboard', 'variant' => 'navy'],
                ['label' => 'Courses', 'value' => Course::count(), 'icon' => 'bi-journal-bookmark', 'variant' => 'accent'],
                ['label' => 'Academic records', 'value' => AcademicRecord::count(), 'icon' => 'bi-file-earmark-text', 'variant' => 'success'],
                ['label' => 'Exports issued', 'value' => Export::count(), 'icon' => 'bi-file-earmark-pdf', 'variant' => 'warning'],
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

        return [
            'stats' => [
                ['label' => 'My courses', 'value' => $courseIds->count(), 'icon' => 'bi-journal-bookmark', 'variant' => 'navy'],
                ['label' => 'Students enrolled', 'value' => (clone $enrolled)->distinct('student_id')->count('student_id'), 'icon' => 'bi-people', 'variant' => 'success'],
                ['label' => 'Grades filed', 'value' => AcademicRecord::whereIn('course_id', $courseIds)->count(), 'icon' => 'bi-file-earmark-text', 'variant' => 'accent'],
            ],
            'myCourses' => $user->courses()
                ->withCount(['academicRecords', 'activeEnrollments'])
                ->orderBy('code')
                ->limit(6)
                ->get(),
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
                ['label' => 'My records', 'value' => $records->count(), 'icon' => 'bi-file-earmark-text', 'variant' => 'navy'],
                ['label' => 'Courses taken', 'value' => $records->pluck('course_id')->unique()->count(), 'icon' => 'bi-journal-bookmark', 'variant' => 'accent'],
                ['label' => 'Year level', 'value' => $student?->yearLevelLabel() ?? '—', 'icon' => 'bi-mortarboard', 'variant' => 'success'],
            ],
            'recentRecords' => $records->take(6),
        ];
    }
}
