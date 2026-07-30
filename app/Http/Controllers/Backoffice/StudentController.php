<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Students\Queries\GetStudentDetails;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student detail page (read-only). The list + add/edit CRUD is the Livewire
 * StudentsIndex component; this controller only serves the show page.
 */
final class StudentController extends Controller
{
    public function show(Student $student, GetStudentDetails $getStudentDetails): Response
    {
        $this->authorize('view', $student);

        return Inertia::render('Backoffice/Students/Show', [
            'student' => $getStudentDetails($student),
        ]);
    }
}
