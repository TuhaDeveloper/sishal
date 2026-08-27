<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\SalesTarget;
use App\Models\User;
use App\Services\BonusCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class BonusCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_under_target_gives_zero_bonus()
    {
        $suffix = uniqid();

        // 1. Create a Branch
        $branch = Branch::create([
            'name' => 'Test Branch Alpha ' . $suffix,
            'code' => 'TBA' . $suffix,
            'location' => 'Test Location',
            'contact_info' => '01700000000',
            'status' => 'active'
        ]);

        // 2. Create Users and Employees
        $userA = User::create([
            'first_name' => 'Employee',
            'last_name' => 'One',
            'email' => 'emp1_' . $suffix . '@test.com',
            'password' => bcrypt('password'),
        ]);
        $employeeA = Employee::create([
            'user_id' => $userA->id,
            'branch_id' => $branch->id,
            'phone' => '017' . rand(10000000, 99999999),
            'salary' => 10000.00,
            'status' => 'active'
        ]);

        $userB = User::create([
            'first_name' => 'Employee',
            'last_name' => 'Two',
            'email' => 'emp2_' . $suffix . '@test.com',
            'password' => bcrypt('password'),
        ]);
        $employeeB = Employee::create([
            'user_id' => $userB->id,
            'branch_id' => $branch->id,
            'phone' => '017' . rand(10000000, 99999999),
            'salary' => 20000.00,
            'status' => 'active'
        ]);

        // 3. Create a Sales Target for the branch
        $target = SalesTarget::create([
            'branch_id' => $branch->id,
            'target_quantity' => 100.00,
            'incentive_amount' => 6000.00,
            'commission_per_extra_sale' => 50.00,
            'period_type' => 'monthly',
            'period_month' => 'May',
            'period_year' => 2026,
            'status' => 'active',
            'created_by' => $userA->id,
        ]);

        // Mock POS sales with less than 100 units
        // We will insert direct records into pos and pos_items
        $posSaleId = DB::table('pos')->insertGetId([
            'sale_number' => 'POS-' . $suffix . '-1',
            'branch_id' => $branch->id,
            'sold_by' => $userA->id,
            'sale_date' => '2026-05-15',
            'sub_total' => 5000,
            'total_amount' => 5000,
            'status' => 'delivered',
        ]);

        DB::table('pos_items')->insert([
            'pos_sale_id' => $posSaleId,
            'product_id' => 9999, // dummy product id
            'quantity' => 80.00,
            'unit_price' => 50,
            'total_price' => 4000,
        ]);

        // 4. Calculate Achievement
        $service = new BonusCalculationService();
        $achievementA = $service->calculateAchievementForEmployee($employeeA->id, 'May', 2026);
        $achievementB = $service->calculateAchievementForEmployee($employeeB->id, 'May', 2026);

        // 5. Assertions
        $this->assertTrue($achievementA['has_target']);
        $this->assertEquals(80.00, $achievementA['achieved_amount']);
        $this->assertEquals(0, $achievementA['bonus_amount']);
        $this->assertEquals(0, $achievementB['bonus_amount']);
        
        $freshTarget = SalesTarget::find($target->id);
        $this->assertEquals('active', $freshTarget->status);
        $this->assertEquals(0, $freshTarget->total_achieved_bonus);
    }

    public function test_over_target_gives_incentive_and_commission_distributed_by_salary_share()
    {
        $suffix = uniqid();

        // 1. Create a Branch
        $branch = Branch::create([
            'name' => 'Test Branch Beta ' . $suffix,
            'code' => 'TBB' . $suffix,
            'location' => 'Test Location 2',
            'contact_info' => '01700000009',
            'status' => 'active'
        ]);

        // 2. Create Users and Employees
        $userA = User::create([
            'first_name' => 'Employee',
            'last_name' => 'One',
            'email' => 'beta1_' . $suffix . '@test.com',
            'password' => bcrypt('password'),
        ]);
        $employeeA = Employee::create([
            'user_id' => $userA->id,
            'branch_id' => $branch->id,
            'phone' => '017' . rand(10000000, 99999999),
            'salary' => 10000.00,
            'status' => 'active'
        ]);

        $userB = User::create([
            'first_name' => 'Employee',
            'last_name' => 'Two',
            'email' => 'beta2_' . $suffix . '@test.com',
            'password' => bcrypt('password'),
        ]);
        $employeeB = Employee::create([
            'user_id' => $userB->id,
            'branch_id' => $branch->id,
            'phone' => '017' . rand(10000000, 99999999),
            'salary' => 20000.00,
            'status' => 'active'
        ]);

        // 3. Create a Sales Target for the branch
        $target = SalesTarget::create([
            'branch_id' => $branch->id,
            'target_quantity' => 100.00,
            'incentive_amount' => 6000.00,
            'commission_per_extra_sale' => 50.00,
            'period_type' => 'monthly',
            'period_month' => 'May',
            'period_year' => 2026,
            'status' => 'active',
            'created_by' => $userA->id,
        ]);

        // Mock POS sales with more than 100 units: 120 units
        $posSaleId = DB::table('pos')->insertGetId([
            'sale_number' => 'POS-' . $suffix . '-2',
            'branch_id' => $branch->id,
            'sold_by' => $userA->id,
            'sale_date' => '2026-05-15',
            'sub_total' => 10000,
            'total_amount' => 10000,
            'status' => 'delivered',
        ]);

        DB::table('pos_items')->insert([
            'pos_sale_id' => $posSaleId,
            'product_id' => 9999, // dummy product id
            'quantity' => 120.00,
            'unit_price' => 50,
            'total_price' => 6000,
        ]);

        // 4. Calculate Achievement
        $service = new BonusCalculationService();
        $achievementA = $service->calculateAchievementForEmployee($employeeA->id, 'May', 2026);
        $achievementB = $service->calculateAchievementForEmployee($employeeB->id, 'May', 2026);

        // 5. Assertions
        // Total expected branch bonus = 6000 (incentive) + 20 extra units * 50 = 7000 total bonus.
        // Total salary of active branch employees = 10000 + 20000 = 30000.
        // Employee A share = 10000 / 30000 * 7000 = 2333.3333...
        // Employee B share = 20000 / 30000 * 7000 = 4666.6666...
        $this->assertTrue($achievementA['has_target']);
        $this->assertEquals(120.00, $achievementA['achieved_amount']);
        $this->assertEquals(7000.00, $achievementA['bonus_data']['total_branch_bonus']);
        $this->assertEquals(2333.3333333333335, $achievementA['bonus_amount']);
        $this->assertEquals(4666.666666666667, $achievementB['bonus_amount']);

        $freshTarget = SalesTarget::find($target->id);
        $this->assertEquals('achieved', $freshTarget->status);
        $this->assertEquals(7000.00, $freshTarget->total_achieved_bonus);
    }

    public function test_sales_returns_are_deducted_from_achievement()
    {
        $suffix = uniqid();

        $branch = Branch::create([
            'name' => 'Test Branch Gamma ' . $suffix,
            'code' => 'TBG' . $suffix,
            'location' => 'Test Location Gamma',
            'contact_info' => '01700000010',
            'status' => 'active'
        ]);

        $user = User::create([
            'first_name' => 'Gamma',
            'last_name' => 'Emp',
            'email' => 'gamma_' . $suffix . '@test.com',
            'password' => bcrypt('password'),
        ]);
        $employee = Employee::create([
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'phone' => '017' . rand(10000000, 99999999),
            'salary' => 15000.00,
            'status' => 'active'
        ]);

        $target = SalesTarget::create([
            'branch_id' => $branch->id,
            'target_quantity' => 100.00,
            'incentive_amount' => 5000.00,
            'commission_per_extra_sale' => 50.00,
            'period_type' => 'monthly',
            'period_month' => 'May',
            'period_year' => 2026,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        // 110 units sold
        $posSaleId = DB::table('pos')->insertGetId([
            'sale_number' => 'POS-' . $suffix . '-3',
            'branch_id' => $branch->id,
            'sold_by' => $user->id,
            'sale_date' => '2026-05-10',
            'sub_total' => 11000,
            'total_amount' => 11000,
            'status' => 'delivered',
        ]);

        DB::table('pos_items')->insert([
            'pos_sale_id' => $posSaleId,
            'product_id' => 9999,
            'quantity' => 110.00,
            'unit_price' => 50,
            'total_price' => 5500,
        ]);

        // 20 units returned by customer in the same month
        $saleReturnId = DB::table('sale_returns')->insertGetId([
            'pos_sale_id' => $posSaleId,
            'return_to_type' => 'branch',
            'return_to_id' => $branch->id,
            'return_date' => '2026-05-15',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sale_return_items')->insert([
            'sale_return_id' => $saleReturnId,
            'product_id' => 9999,
            'returned_qty' => 20.00,
            'unit_price' => 50,
            'total_price' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Calculate Achievement: 110 sold - 20 returned = 90 net sold (below target of 100)
        $service = new BonusCalculationService();
        $achievement = $service->calculateAchievementForEmployee($employee->id, 'May', 2026);

        $this->assertEquals(90.00, $achievement['achieved_amount']);
        $this->assertEquals(0, $achievement['bonus_amount']);

        $freshTarget = SalesTarget::find($target->id);
        $this->assertEquals('active', $freshTarget->status);
        $this->assertEquals(0, $freshTarget->total_achieved_bonus);
    }
}
