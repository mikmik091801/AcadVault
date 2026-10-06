<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrashedTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_deleted_student_can_be_restored_with_their_records(): void
    {
        $student = Student::factory()->create();
        $record = $student->academicRecords()->create([
            'course_id' => \App\Models\Course::factory()->create()->id,
            'grade' => '1.25',
            'remarks' => 'Good',
            'created_by' => User::factory()->admin()->create()->id,
        ]);

        $student->delete();
        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertSoftDeleted('academic_records', ['id' => $record->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('trashed.students.restore', $student->id))
            ->assertRedirect();

        $this->assertDatabaseHas('students', ['id' => $student->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('academic_records', ['id' => $record->id, 'deleted_at' => null]);
    }

    public function test_students_cannot_reach_the_trash_page(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('trashed.index'))
            ->assertForbidden();
    }
}
