<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Laptop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DivisionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_division_management_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $division = Division::factory()->create([
            'name' => 'Data Analyst',
            'sections' => ['CEO', 'CTO', 'Regional Director'],
        ]);

        $this->actingAs($admin)
            ->get(route('divisions.index'))
            ->assertOk()
            ->assertSee('Data Analyst')
            ->assertSee('BAGIAN / JABATAN')
            ->assertSee('<span class="division-description-item">CEO</span>', false)
            ->assertSee('<span class="division-description-item">CTO</span>', false)
            ->assertSee('<span class="division-description-item">Regional Director</span>', false)
            ->assertDontSee('DESKRIPSI');

        $this->get(route('divisions.create'))
            ->assertOk()
            ->assertSee('Tambahkan divisi baru')
            ->assertSee('add-division-section', false)
            ->assertSee('sections[__INDEX__]', false)
            ->assertDontSee('name="description"', false);

        $this->get(route('divisions.edit', $division))
            ->assertOk()
            ->assertSee('Data Analyst')
            ->assertSee('Regional Director');
    }

    public function test_admin_can_create_a_division(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('divisions.store'), [
                'name' => 'Quality Assurance',
                'sections' => ['Quality Assurance', 'Automation Testing'],
            ])
            ->assertRedirect(route('divisions.index'));

        $division = Division::query()->where('name', 'Quality Assurance')->firstOrFail();
        $this->assertSame(['Quality Assurance', 'Automation Testing'], $division->sections);
    }

    public function test_admin_cannot_create_a_division_with_duplicate_sections(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('divisions.store'), [
                'name' => 'Quality Assurance',
                'sections' => ['Testing', 'testing'],
            ])
            ->assertSessionHasErrors('sections.1');

        $this->assertDatabaseMissing('divisions', ['name' => 'Quality Assurance']);
    }

    public function test_legacy_division_descriptions_are_migrated_to_sections(): void
    {
        $migration = require database_path('migrations/2026_10_28_080948_add_sections_to_divisions_table.php');
        $migration->down();

        $division = Division::query()->create([
            'name' => 'Legacy Division',
            'description' => 'CEO, CTO',
        ]);

        $migration->up();
        $division->refresh();

        $this->assertSame(['CEO', 'CTO'], $division->sections);
    }

    public function test_admin_cannot_create_a_division_with_a_duplicate_name(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        Division::factory()->create(['name' => 'IT Support']);

        $this->actingAs($admin)
            ->post(route('divisions.store'), ['name' => 'IT Support'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('divisions', 1);
    }

    public function test_admin_can_update_a_division(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $division = Division::factory()->create(['name' => 'Back End']);

        $this->actingAs($admin)
            ->put(route('divisions.update', $division), [
                'name' => 'Backend Engineering',
                'sections' => ['API Development', 'Database'],
            ])
            ->assertRedirect(route('divisions.index'));

        $division->refresh();
        $this->assertSame('Backend Engineering', $division->name);
        $this->assertSame(['API Development', 'Database'], $division->sections);
    }

    public function test_admin_can_delete_an_unused_division(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $division = Division::factory()->create();

        $this->actingAs($admin)
            ->delete(route('divisions.destroy', $division))
            ->assertRedirect(route('divisions.index'));

        $this->assertDatabaseMissing('divisions', ['id' => $division->id]);
    }

    public function test_admin_cannot_delete_a_division_assigned_to_an_employee(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $division = Division::factory()->create();
        User::factory()->create(['division_id' => $division->id]);

        $this->actingAs($admin)
            ->delete(route('divisions.destroy', $division))
            ->assertSessionHasErrors('division');

        $this->assertModelExists($division);
    }

    public function test_admin_cannot_delete_a_division_assigned_to_a_laptop(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $division = Division::factory()->create();
        Laptop::factory()->create(['division_id' => $division->id]);

        $this->actingAs($admin)
            ->delete(route('divisions.destroy', $division))
            ->assertSessionHasErrors('division');

        $this->assertModelExists($division);
    }

    public function test_employee_cannot_manage_divisions(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'is_approved' => true,
            'is_active' => true,
        ]);

        $this->actingAs($employee)
            ->get(route('divisions.index'))
            ->assertForbidden();
    }
}
