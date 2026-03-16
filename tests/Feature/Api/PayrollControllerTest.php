<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayrollControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat Role Admin (Sesuaikan dengan sistem role kamu)
        Role::findOrCreate('admin', 'api');
        Role::findOrCreate('hr', 'api');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Buat data Employee dummy
        $this->employee = Employee::factory()->create();
    }

    /** @test */
    public function admin_can_list_all_payrolls()
    {
        Payroll::factory()->count(3)->create(['employee_id' => $this->employee->id]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/payrolls');

        $response->assertStatus(200)
                 ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function admin_can_finalize_payroll_status()
    {
        $payroll = Payroll::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => Payroll::STATUS_DRAFT
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/payrolls/{$payroll->uuid}/finalize");

        $response->assertStatus(200);
        $this->assertDatabaseHas('payrolls', [
            'uuid' => $payroll->uuid,
            'status' => Payroll::STATUS_FINALIZED
        ]);
    }

    /** @test */
    public function admin_can_void_payroll_with_reason()
    {
        $payroll = Payroll::factory()->create(['employee_id' => $this->employee->id]);

        Sanctum::actingAs($this->admin);

        $response = $this->putJson("/api/payrolls/{$payroll->uuid}/void", [
            'note' => 'Kesalahan input data tunjangan'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('payrolls', [
            'uuid' => $payroll->uuid,
            'status' => Payroll::STATUS_VOIDED,
            'void_note' => 'Kesalahan input data tunjangan'
        ]);
    }

    /** @test */
    // public function user_can_download_generated_slip()
    // {
    //     Storage::fake('private');
    //     $path = 'slips/slip-test.pdf';
    //     Storage::put($path, 'content');

    //     $payroll = Payroll::factory()->create([
    //         'employee_id' => $this->employee->id,
    //         'status' => Payroll::STATUS_FINALIZED,
    //         'slip_path' => 'private/' . $path
    //     ]);

    //     Sanctum::actingAs($this->admin);

    //     $response = $this->get("/api/payrolls/{$payroll->uuid}/download-slip");

    //     $response->assertStatus(200);
    // }
}
