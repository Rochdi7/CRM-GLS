<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Contracts\View\View;

/**
 * Student detail page (read-only). The list + add/edit CRUD is the Livewire
 * StudentsIndex component; this controller only serves the show page.
 */
final class StudentController extends Controller
{
    public function show(Student $student): View
    {
        $this->authorize('view', $student);

        return view('backoffice.students.show', [
            'student' => $student->load([
                'etablissement',
                'inscriptions.group',
                'inscriptions.fees',
                'encaissements.caisse',
                'remboursements.caisse',
            ]),
        ]);
    }
}
