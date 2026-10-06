<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Soft-deleted accounts, students and courses, with a one-click restore.
 */
class TrashedController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:admin,registrar'),
        ];
    }

    public function index(): View
    {
        return view('trashed.index', [
            'students' => Student::onlyTrashed()->with('user')->latest('deleted_at')->get(),
            'courses' => Course::onlyTrashed()->with('faculty')->latest('deleted_at')->get(),
            'users' => User::onlyTrashed()->latest('deleted_at')->get(),
        ]);
    }

    public function restoreStudent(int $id): RedirectResponse
    {
        $student = Student::onlyTrashed()->findOrFail($id);
        $student->restore();

        AuditLogger::log(AuditLogger::STUDENT_RESTORED, $student);

        return back()->with('success', "Student {$student->student_number} was restored.");
    }

    public function restoreCourse(int $id): RedirectResponse
    {
        $course = Course::onlyTrashed()->findOrFail($id);
        $course->restore();

        AuditLogger::log(AuditLogger::COURSE_RESTORED, $course);

        return back()->with('success', "Course {$course->code} was restored.");
    }

    public function restoreUser(int $id): RedirectResponse
    {
        abort_unless(request()->user()->isAdmin(), 403);

        $user = User::onlyTrashed()->findOrFail($id);
        $user->restore();

        AuditLogger::log(AuditLogger::USER_RESTORED, $user);

        return back()->with('success', "{$user->name}'s account was restored.");
    }
}
