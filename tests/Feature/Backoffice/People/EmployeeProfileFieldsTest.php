<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\People;

use App\Livewire\Backoffice\Employees\EmployeesIndex;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Employee postal address + staff picture (media library "photo" collection,
 * same convention as Student::photo).
 */
final class EmployeeProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);
    }

    public function test_an_employee_can_be_created_with_an_address(): void
    {
        Livewire::test(EmployeesIndex::class)
            ->call('create')
            ->set('nom', 'Alaoui')
            ->set('prenom', 'Driss')
            ->set('adresse', '12 rue Al Massira, Marrakech')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            '12 rue Al Massira, Marrakech',
            Employee::where('nom', 'Alaoui')->firstOrFail()->adresse,
        );
    }

    public function test_the_address_can_be_edited(): void
    {
        $employee = Employee::factory()->create(['adresse' => 'Ancienne adresse']);

        Livewire::test(EmployeesIndex::class)
            ->call('edit', $employee->id)
            ->assertSet('adresse', 'Ancienne adresse')
            ->set('adresse', 'Nouvelle adresse')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nouvelle adresse', $employee->fresh()->adresse);
    }

    public function test_employees_can_be_searched_by_address(): void
    {
        Employee::factory()->create(['nom' => 'Bennani', 'adresse' => 'Avenue Hassan II']);
        Employee::factory()->create(['nom' => 'Cherkaoui', 'adresse' => 'Rue de Safi']);

        Livewire::test(EmployeesIndex::class)
            ->set('search', 'Hassan II')
            ->assertSee('Bennani')
            ->assertDontSee('Cherkaoui');
    }

    public function test_a_photo_is_stored_in_the_media_collection(): void
    {
        Storage::fake('media');

        Livewire::test(EmployeesIndex::class)
            ->call('create')
            ->set('nom', 'Fassi')
            ->set('prenom', 'Karim')
            ->set('photo', UploadedFile::fake()->image('staff.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $employee = Employee::where('nom', 'Fassi')->firstOrFail();

        $this->assertCount(1, $employee->getMedia('photo'));
        $this->assertNotSame('', $employee->getFirstMediaUrl('photo'));
    }

    /** Single-file collection: a second upload replaces the first. */
    public function test_uploading_a_new_photo_replaces_the_previous_one(): void
    {
        Storage::fake('media');

        $employee = Employee::factory()->create();
        // addMedia() moves the source file, so hand it a throwaway copy.
        $old = UploadedFile::fake()->image('old.jpg')->store('tmp', 'local');
        $employee->addMedia(Storage::disk('local')->path($old))
            ->usingFileName('old.jpg')
            ->toMediaCollection('photo');

        Livewire::test(EmployeesIndex::class)
            ->call('edit', $employee->id)
            ->set('photo', UploadedFile::fake()->image('new.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $media = $employee->fresh()->getMedia('photo');
        $this->assertCount(1, $media);
        $this->assertStringContainsString('photo-'.$employee->id, $media->first()->file_name);
    }

    /**
     * Non-images never reach validation: Livewire's temporary-upload layer
     * refuses them first (preview_mimes). The `image` rule in rules() is the
     * second line of defence — this asserts the first one actually holds.
     */
    public function test_a_non_image_upload_is_refused_before_saving(): void
    {
        Storage::fake('media');

        $this->expectException(\Livewire\Features\SupportFileUploads\FileNotPreviewableException::class);

        Livewire::test(EmployeesIndex::class)
            ->call('create')
            ->set('photo', UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'));
    }

    /** An oversized image (> 2 MB) is rejected by the `max:2048` rule. */
    public function test_an_oversized_photo_is_rejected(): void
    {
        Storage::fake('media');

        Livewire::test(EmployeesIndex::class)
            ->call('create')
            ->set('nom', 'Idrissi')
            ->set('prenom', 'Omar')
            ->set('photo', UploadedFile::fake()->image('huge.jpg')->size(3000))
            ->call('save')
            ->assertHasErrors('photo');
    }
}
