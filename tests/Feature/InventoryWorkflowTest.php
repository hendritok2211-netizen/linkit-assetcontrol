<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Laptop;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_registration_waits_for_admin_approval(): void
    {
        $division = Division::factory()->create();

        $this->post(route('register.store'), [
            'name' => 'Rina Karyawan',
            'email' => 'rina@example.test',
            'division_id' => $division->id,
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
        ])->assertRedirect(route('login'));

        $employee = User::query()->where('email', 'rina@example.test')->firstOrFail();
        $this->assertSame('employee', $employee->role);
        $this->assertFalse($employee->is_approved);

        $this->post(route('login.store'), [
            'email' => $employee->email,
            'password' => 'password-123',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_add_asset_and_render_its_qr_code(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $division = Division::factory()->create();

        $this->actingAs($admin)->post(route('laptops.store'), [
            'quantity' => 1,
            'units' => [[
                'asset_code' => 'LT-TEST01',
                'serial_number' => 'SERIAL-TEST01',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad E14',
                'ram' => '16 GB',
                'storage_capacity' => '512 GB',
                'division_id' => $division->id,
                'condition' => 'good',
                'status' => 'available',
            ]],
        ])->assertRedirect(route('laptops.index'));

        $laptop = Laptop::query()->where('asset_code', 'LT-TEST01')->firstOrFail();

        $this->assertDatabaseHas('laptops', [
            'asset_code' => 'LT-TEST01',
            'serial_number' => 'SERIAL-TEST01',
            'ram' => '16 GB',
            'storage_capacity' => '512 GB',
        ]);
        $this->get(route('laptops.qr', $laptop))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_admin_can_add_multiple_laptops_with_unique_asset_codes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('laptops.create'))
            ->assertOk()
            ->assertSee('Jumlah perangkat')
            ->assertSee('units[__INDEX__][ram]', false)
            ->assertSee('units[__INDEX__][storage_capacity]', false)
            ->assertSee('units[__INDEX__][brand]', false)
            ->assertSee('quantity-increase', false)
            ->assertSee('renderLaptopUnits()', false)
            ->assertSee('Perangkat ${index + 1}', false);

        $this->actingAs($admin)->post(route('laptops.store'), [
            'quantity' => 2,
            'units' => [
                [
                    'serial_number' => 'SERIAL-BULK01',
                    'brand' => 'Lenovo',
                    'model' => 'ThinkPad E14',
                    'ram' => '32 GB',
                    'storage_capacity' => '1 TB',
                    'condition' => 'good',
                    'status' => 'available',
                ],
                [
                    'serial_number' => 'SERIAL-BULK02',
                    'brand' => 'Lenovo',
                    'model' => 'ThinkPad E14',
                    'ram' => '16 GB',
                    'storage_capacity' => '512 GB',
                    'condition' => 'minor_damage',
                    'status' => 'maintenance',
                    'notes' => 'Baterai perlu diperiksa',
                ],
            ],
        ])->assertRedirect(route('laptops.index'))
            ->assertSessionHas('status', '2 data laptop berhasil ditambahkan.');

        $this->assertDatabaseCount('laptops', 2);
        $this->assertDatabaseHas('laptops', [
            'asset_code' => 'LT-0001',
            'serial_number' => 'SERIAL-BULK01',
            'brand' => 'Lenovo',
            'ram' => '32 GB',
            'storage_capacity' => '1 TB',
        ]);
        $this->assertDatabaseHas('laptops', [
            'asset_code' => 'LT-0002',
            'serial_number' => 'SERIAL-BULK02',
            'brand' => 'Lenovo',
            'ram' => '16 GB',
            'storage_capacity' => '512 GB',
            'condition' => 'minor_damage',
            'status' => 'maintenance',
            'notes' => 'Baterai perlu diperiksa',
        ]);
        $this->assertDatabaseCount('activity_logs', 2);

        $this->get(route('laptops.index'))
            ->assertOk()
            ->assertSee('<strong>Lenovo ThinkPad E14</strong><small>Kode aset: LT-0001 · SN SERIAL-BULK01</small><small>RAM 32 GB · Storage 1 TB</small>', false)
            ->assertSee('<small>RAM 16 GB · Storage 512 GB</small>', false);

        $this->get(route('reports.index'))
            ->assertOk()
            ->assertSee('32 GB')
            ->assertSee('1 TB');

        $this->post(route('laptops.verify'), ['asset_code' => 'SERIAL-BULK01'])
            ->assertOk()
            ->assertSee('<dt>RAM</dt><dd>32 GB</dd>', false)
            ->assertSee('<dt>Penyimpanan</dt><dd>1 TB</dd>', false);
    }

    public function test_admin_can_assign_a_division_detail_when_adding_a_laptop(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $division = Division::factory()->create([
            'name' => 'Technology',
            'sections' => ['CEO', 'CTO', 'Regional Director'],
        ]);

        $this->actingAs($admin)->get(route('laptops.create'))
            ->assertOk()
            ->assertSee('units[__INDEX__][division_detail]', false)
            ->assertSee('Regional Director');

        $this->post(route('laptops.store'), [
            'quantity' => 1,
            'units' => [[
                'serial_number' => 'SERIAL-DETAIL01',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad E14',
                'ram' => '16 GB',
                'storage_capacity' => '512 GB',
                'division_id' => $division->id,
                'division_detail' => 'CTO',
                'condition' => 'good',
                'status' => 'available',
            ]],
        ])->assertRedirect(route('laptops.index'));

        $this->assertDatabaseHas('laptops', [
            'serial_number' => 'SERIAL-DETAIL01',
            'division_id' => $division->id,
            'division_detail' => 'CTO',
        ]);

        $laptop = Laptop::query()->where('serial_number', 'SERIAL-DETAIL01')->firstOrFail();
        $this->get(route('laptops.index'))
            ->assertOk()
            ->assertSee('CTO');
        $this->post(route('laptops.verify'), ['asset_code' => $laptop->serial_number])
            ->assertOk()
            ->assertSee('<dt>Bagian / jabatan</dt><dd>CTO</dd>', false);
    }

    public function test_inventory_report_includes_asset_notes_and_active_loan_details_in_html_and_csv(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $laptop = Laptop::factory()->create([
            'asset_code' => 'LT-REPORT01',
            'serial_number' => 'SERIAL-REPORT01',
            'notes' => 'Charger dan tas tercatat',
            'status' => 'borrowed',
        ]);
        Loan::factory()->create([
            'laptop_id' => $laptop->id,
            'user_id' => $employee->id,
            'checked_out_at' => '2026-10-20 09:30:00',
            'status' => 'active',
            'condition_out' => 'good',
            'handover_notes' => 'Charger diserahkan',
        ]);

        $this->actingAs($admin)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('PEMEGANG TERAKHIR')
            ->assertSee('TANGGAL PINJAM')
            ->assertDontSee('JATUH TEMPO')
            ->assertSee('CATATAN ASET')
            ->assertSee('CATATAN SERAH TERIMA')
            ->assertSee('Charger dan tas tercatat')
            ->assertSee('Charger diserahkan')
            ->assertSee('20 Oct 2026 09:30');

        $csvResponse = $this->get(route('reports.export'));
        $csvResponse->assertDownload('laporan-inventaris.csv');

        $csv = $csvResponse->streamedContent();
        $this->assertStringContainsString('Tanggal Pinjam', $csv);
        $this->assertStringContainsString('Catatan Pengembalian', $csv);
        $this->assertStringNotContainsString('Jatuh Tempo', $csv);
        $this->assertStringContainsString('Charger dan tas tercatat', $csv);
        $this->assertStringContainsString('Charger diserahkan', $csv);
        $this->assertStringContainsString('2026-10-20 09:30:00', $csv);
    }

    public function test_admin_cannot_assign_a_division_detail_not_in_that_divisions_sections(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $division = Division::factory()->create(['sections' => ['CEO', 'CTO']]);

        $this->actingAs($admin)->post(route('laptops.store'), [
            'quantity' => 1,
            'units' => [[
                'serial_number' => 'SERIAL-DETAIL02',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad E14',
                'ram' => '16 GB',
                'storage_capacity' => '512 GB',
                'division_id' => $division->id,
                'division_detail' => 'Finance Manager',
                'condition' => 'good',
                'status' => 'available',
            ]],
        ])->assertSessionHasErrors([
            'units.0.division_detail' => 'Rincian harus sesuai dengan deskripsi divisi yang dipilih.',
        ]);

        $this->assertDatabaseCount('laptops', 0);
    }

    public function test_admin_cannot_add_laptops_with_duplicate_serial_numbers(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('laptops.store'), [
            'quantity' => 2,
            'units' => [
                [
                    'serial_number' => 'SERIAL-DUPLICATE',
                    'brand' => 'Lenovo',
                    'model' => 'ThinkPad E14',
                    'ram' => '16 GB',
                    'storage_capacity' => '512 GB',
                    'condition' => 'good',
                    'status' => 'available',
                ],
                [
                    'serial_number' => 'SERIAL-DUPLICATE',
                    'brand' => 'Lenovo',
                    'model' => 'ThinkPad E14',
                    'ram' => '16 GB',
                    'storage_capacity' => '512 GB',
                    'condition' => 'good',
                    'status' => 'available',
                ],
            ],
        ])->assertSessionHasErrors('units.1.serial_number');

        $this->assertDatabaseCount('laptops', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_generated_asset_code_remains_unique_when_it_matches_a_custom_code(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        Laptop::factory()->create(['asset_code' => 'LT-0002']);

        $this->actingAs($admin)->post(route('laptops.store'), [
            'quantity' => 1,
            'units' => [[
                'serial_number' => 'SERIAL-COLLISION01',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad E14',
                'ram' => '16 GB',
                'storage_capacity' => '512 GB',
                'condition' => 'good',
                'status' => 'available',
            ]],
        ])->assertRedirect(route('laptops.index'));

        $this->assertDatabaseHas('laptops', [
            'asset_code' => 'LT-0002-1',
            'serial_number' => 'SERIAL-COLLISION01',
        ]);
    }

    public function test_admin_must_provide_a_serial_number_for_each_laptop(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('laptops.store'), [
            'quantity' => 2,
            'units' => [[
                'serial_number' => 'SERIAL-ONLY01',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad E14',
                'ram' => '16 GB',
                'storage_capacity' => '512 GB',
                'condition' => 'good',
                'status' => 'available',
            ]],
        ])->assertSessionHasErrors('units');

        $this->assertDatabaseCount('laptops', 0);
    }

    public function test_admin_sees_a_clear_error_when_serial_numbers_are_missing(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('laptops.store'), [
            'quantity' => 1,
            'units' => [[
                'brand' => 'Lenovo',
                'model' => 'ThinkPad E14',
                'ram' => '16 GB',
                'storage_capacity' => '512 GB',
                'condition' => 'good',
                'status' => 'available',
            ]],
        ])->assertSessionHasErrors('units.0.serial_number');

        $this->assertDatabaseCount('laptops', 0);
    }

    public function test_admin_can_add_laptop_units_while_editing_an_existing_laptop(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $division = Division::factory()->create(['sections' => ['CEO', 'CTO']]);
        $laptop = Laptop::factory()->create([
            'asset_code' => 'LT-EDIT01',
            'serial_number' => 'SERIAL-EDIT01',
            'brand' => 'Lenovo',
            'model' => 'ThinkPad E14',
            'division_id' => $division->id,
            'division_detail' => 'CEO',
        ]);

        $this->actingAs($admin)->get(route('laptops.edit', $laptop))
            ->assertOk()
            ->assertSee('Jumlah perangkat')
            ->assertSee('SERIAL-EDIT01')
            ->assertSee('"asset_code":"LT-EDIT01"', false)
            ->assertSee('"brand":"Lenovo"', false)
            ->assertSee('"model":"ThinkPad E14"', false)
            ->assertSee('"ram":"16 GB"', false)
            ->assertSee('"storage_capacity":"512 GB"', false)
            ->assertSee('"division_detail":"CEO"', false)
            ->assertSee('CEO')
            ->assertSee('units[__INDEX__][ram]', false)
            ->assertSee('units[__INDEX__][storage_capacity]', false);

        $this->put(route('laptops.update', $laptop), [
            'quantity' => 3,
            'units' => [
                [
                    'asset_code' => 'LT-EDIT01',
                    'serial_number' => 'SERIAL-EDIT01',
                    'brand' => 'Dell',
                    'model' => 'Latitude 5440',
                    'ram' => '32 GB',
                    'storage_capacity' => '1 TB',
                    'division_id' => $division->id,
                    'division_detail' => 'CTO',
                    'condition' => 'good',
                    'status' => 'available',
                ],
                [
                    'serial_number' => 'SERIAL-EDIT02',
                    'brand' => 'HP',
                    'model' => 'ProBook 440',
                    'ram' => '16 GB',
                    'storage_capacity' => '512 GB',
                    'condition' => 'minor_damage',
                    'status' => 'maintenance',
                    'notes' => 'Engsel longgar',
                ],
                [
                    'serial_number' => 'SERIAL-EDIT03',
                    'brand' => 'Apple',
                    'model' => 'MacBook Air',
                    'ram' => '24 GB',
                    'storage_capacity' => '2 TB',
                    'condition' => 'good',
                    'status' => 'inactive',
                    'notes' => 'Cadangan',
                ],
            ],
        ])->assertRedirect(route('laptops.index'))
            ->assertSessionHas('status', 'Data laptop diperbarui dan total 3 perangkat berhasil disimpan.');

        $this->assertDatabaseCount('laptops', 3);
        $this->assertDatabaseHas('laptops', [
            'asset_code' => 'LT-EDIT01',
            'serial_number' => 'SERIAL-EDIT01',
            'brand' => 'Dell',
            'model' => 'Latitude 5440',
            'division_id' => $division->id,
            'division_detail' => 'CTO',
            'ram' => '32 GB',
            'storage_capacity' => '1 TB',
        ]);
        $this->assertDatabaseHas('laptops', [
            'asset_code' => 'LT-0002',
            'serial_number' => 'SERIAL-EDIT02',
            'brand' => 'HP',
            'model' => 'ProBook 440',
            'ram' => '16 GB',
            'storage_capacity' => '512 GB',
            'condition' => 'minor_damage',
            'status' => 'maintenance',
            'notes' => 'Engsel longgar',
        ]);
        $this->assertDatabaseHas('laptops', [
            'asset_code' => 'LT-0003',
            'serial_number' => 'SERIAL-EDIT03',
            'brand' => 'Apple',
            'model' => 'MacBook Air',
            'ram' => '24 GB',
            'storage_capacity' => '2 TB',
            'condition' => 'good',
            'status' => 'inactive',
            'notes' => 'Cadangan',
        ]);
        $this->assertDatabaseCount('activity_logs', 3);
    }

    public function test_admin_cannot_save_laptop_with_ram_or_storage_outside_the_available_choices(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('laptops.store'), [
            'quantity' => 1,
            'units' => [[
                'serial_number' => 'SERIAL-SPEC-INVALID',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad E14',
                'ram' => '10 GB',
                'storage_capacity' => '512 GB',
                'condition' => 'good',
                'status' => 'available',
            ]],
        ])->assertSessionHasErrors([
            'units.0.ram' => 'Pilihan RAM tidak tersedia.',
        ]);

        $this->assertDatabaseCount('laptops', 0);
    }

    public function test_asset_code_migration_shortens_generated_codes_and_can_restore_them(): void
    {
        $migration = require database_path('migrations/2026_10_28_050418_shorten_generated_laptop_asset_codes.php');
        $migration->down();

        $laptop = Laptop::factory()->create([
            'asset_code' => 'LT-01ARZ3NDEKTSV4RRFFQ69G5FAV',
        ]);

        $migration->up();

        $this->assertDatabaseHas('laptops', [
            'id' => $laptop->id,
            'asset_code' => 'LT-0001',
        ]);

        $migration->down();

        $this->assertDatabaseHas('laptops', [
            'id' => $laptop->id,
            'asset_code' => 'LT-01ARZ3NDEKTSV4RRFFQ69G5FAV',
        ]);
    }

    public function test_admin_can_verify_a_laptop_using_its_serial_number(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $laptop = Laptop::factory()->create([
            'asset_code' => 'LT-SCAN01',
            'serial_number' => 'SERIAL-SCAN01',
        ]);

        $this->actingAs($admin)
            ->post(route('laptops.verify'), ['asset_code' => 'SERIAL-SCAN01'])
            ->assertOk()
            ->assertViewHas('laptop', $laptop)
            ->assertSee('LT-SCAN01')
            ->assertSee('Aset ditemukan');
    }

    public function test_admin_can_issue_an_available_laptop_to_an_approved_employee(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $division = Division::factory()->create();
        $employee = User::factory()->create([
            'division_id' => $division->id,
            'role' => 'employee',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $laptop = Laptop::factory()->create(['division_id' => $division->id]);

        $this->actingAs($admin)->get(route('loans.index'))
            ->assertOk()
            ->assertDontSee('Rencana tanggal kembali')
            ->assertDontSee('name="due_date"', false);

        $this->post(route('loans.issue'), [
            'laptop_id' => $laptop->id,
            'user_id' => $employee->id,
            'condition_out' => 'good',
            'handover_notes' => 'Charger included',
        ])->assertRedirect(route('loans.index'));

        $this->assertDatabaseHas('loans', [
            'laptop_id' => $laptop->id,
            'user_id' => $employee->id,
            'status' => 'active',
            'due_date' => null,
        ]);
        $this->assertDatabaseHas('laptops', ['id' => $laptop->id, 'status' => 'borrowed']);
    }

    public function test_return_requires_matching_serial_before_admin_can_approve(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $laptop = Laptop::factory()->create(['status' => 'borrowed']);
        $loan = Loan::factory()->create([
            'laptop_id' => $laptop->id,
            'user_id' => $employee->id,
            'status' => 'return_requested',
            'return_requested_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('loans.approve-return', $loan), [
            'serial_number' => 'WRONG-SERIAL',
            'condition_in' => 'good',
        ])->assertSessionHasErrors('serial_number');

        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'return_requested']);

        $this->post(route('loans.approve-return', $loan), [
            'serial_number' => $laptop->serial_number,
            'condition_in' => 'damaged',
            'return_notes' => 'Perlu pemeriksaan teknisi',
        ])->assertRedirect(route('loans.index'));

        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'returned']);
        $this->assertDatabaseHas('laptops', ['id' => $laptop->id, 'status' => 'maintenance', 'condition' => 'damaged']);
    }

    public function test_employee_can_only_request_return_for_their_own_loan(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $otherEmployee = User::factory()->create([
            'role' => 'employee',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $loan = Loan::factory()->create([
            'user_id' => $otherEmployee->id,
            'status' => 'active',
        ]);

        $this->actingAs($employee)->post(route('loans.request-return', $loan))
            ->assertForbidden();

        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'active']);
    }

    public function test_admin_can_record_asset_inspection_and_service_history(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $laptop = Laptop::factory()->create(['status' => 'available']);

        $this->actingAs($admin)->post(route('laptops.audit', $laptop), [
            'action' => 'send_service',
            'condition' => 'damaged',
            'notes' => 'Layar perlu diperiksa teknisi.',
        ])->assertRedirect(route('laptops.scan'));

        $this->assertDatabaseHas('laptops', [
            'id' => $laptop->id,
            'status' => 'maintenance',
            'condition' => 'damaged',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'laptop.send_service',
            'subject_id' => $laptop->id,
        ]);

        $this->post(route('laptops.audit', $laptop), [
            'action' => 'finish_service',
            'condition' => 'good',
        ])->assertRedirect(route('laptops.scan'));

        $this->assertDatabaseHas('laptops', [
            'id' => $laptop->id,
            'status' => 'available',
            'condition' => 'good',
        ]);
    }

    public function test_admin_can_record_a_loan_return_from_the_asset_scan_action(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $employee = User::factory()->create([
            'role' => 'employee',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $laptop = Laptop::factory()->create([
            'asset_code' => 'LT-RETURN01',
            'serial_number' => 'SERIAL-RETURN01',
            'status' => 'borrowed',
        ]);
        $loan = Loan::factory()->create([
            'laptop_id' => $laptop->id,
            'user_id' => $employee->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->post(route('laptops.verify'), ['asset_code' => $laptop->asset_code])
            ->assertOk()
            ->assertSee('Catat pengembalian');

        $this->post(route('laptops.audit', $laptop), [
            'action' => 'record_return',
            'serial_number' => $laptop->serial_number,
            'condition' => 'minor_damage',
            'notes' => 'Ada goresan di bagian casing.',
        ])->assertRedirect(route('laptops.scan'));

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'returned',
            'condition_in' => 'minor_damage',
            'return_notes' => 'Ada goresan di bagian casing.',
            'verified_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('laptops', [
            'id' => $laptop->id,
            'status' => 'maintenance',
            'condition' => 'minor_damage',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'loan.return_approved',
            'subject_id' => $loan->id,
        ]);
    }

    public function test_admin_cannot_record_a_return_without_a_matching_serial_number(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
            'is_active' => true,
        ]);
        $laptop = Laptop::factory()->create(['status' => 'borrowed']);
        $loan = Loan::factory()->create([
            'laptop_id' => $laptop->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post(route('laptops.audit', $laptop), [
            'action' => 'record_return',
            'serial_number' => 'WRONG-SERIAL',
            'condition' => 'good',
        ])->assertSessionHasErrors([
            'serial_number' => 'Nomor seri tidak cocok dengan data aset. Pengembalian belum dapat dicatat.',
        ]);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('laptops', [
            'id' => $laptop->id,
            'status' => 'borrowed',
        ]);
    }

    public function test_employee_cannot_open_admin_inventory_pages(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'is_approved' => true,
            'is_active' => true,
        ]);

        $this->actingAs($employee)->get(route('laptops.index'))->assertForbidden();
    }
}
