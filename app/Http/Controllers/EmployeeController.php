<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    // 🔥 GET all employees (latest first)
    public function index()
    {
        try {
            Log::info('Fetching all employees');

            $employees = Employee::latest()->get();

            return response()->json([
                'status' => true,
                'message' => 'Employees loaded successfully',
                'data' => $employees
            ], 200);

        } catch (\Exception $e) {
            Log::error("Employee index error: ".$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 🔥 GET single employee
    public function show($id)
    {
        try {
            $employee = Employee::findOrFail($id);

            return response()->json([
                'status' => true,
                'message' => 'Employee details loaded',
                'data' => $employee
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Employee not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }


    
    // 🔥 ADD employee
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'phone' => 'required|unique:employees,phone',
                'email' => 'nullable|email',
                'salary' => 'nullable|numeric',
                'given_money' => 'nullable|numeric',
            ]);

            $employee = Employee::create($validated);

            $user = User::create([
                'name' => $validated['name'] ,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => bcrypt('annapoorani'),
                'role' => 'staff',
                'employee_id' => $employee->id
            ]);

            $employee->update([
            'user_id' => $user->id]);
           
            return response()->json([
                'status' => true,
                'message' => 'Employee added successfully',
                'data' => $employee
            ], 201);

        } catch (\Exception $e) {
            Log::error("Employee store error: ".$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Employee creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // 🔥 UPDATE employee


public function update(Request $request, $id)
{
    try {
        DB::beginTransaction();

        $employee = Employee::with('user')->findOrFail($id);

        Log::info('Updating employee', [
            'employee_id' => $id,
            'current' => $employee->toArray()
        ]);

        $validated = $request->validate([
            'name'        => 'nullable|string|max:255',
            'phone'       => 'required|unique:employees,phone,' . $id,
            'email'       => 'nullable|email',
            'salary'      => 'nullable|numeric',
            'given_money' => 'nullable|numeric',
        ]);

        // 🔹 Update Employee
        $employee->update([
            'name'        => $validated['name'] ?? $employee->name,
            'phone'       => $validated['phone'],
            'email'       => $validated['email'] ?? $employee->email,
            'salary'      => $validated['salary'] ?? $employee->salary,
            'given_money' => $validated['given_money'] ?? $employee->given_money,
        ]);

        // 🔹 Update related User (if exists)
        if ($employee->user) {
            $employee->user->update([
                'name'  => $validated['name'] ?? $employee->user->name,
                'email' => $validated['email'] ?? $employee->user->email,
                'phone' => $validated['phone'],
            ]);
        }

        DB::commit();

        return response()->json([
            'status'  => true,
            'message' => 'Employee updated successfully',
            'data'    => $employee->fresh('user'),
        ], 200);

    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('Employee update error', [
            'employee_id' => $id,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'status'  => false,
            'message' => 'Employee update failed',
            'error'   => $e->getMessage(),
        ], 500);
    }
}



    // 🔥 DELETE employee
    public function destroy($id)
{
    try {
        $employee = Employee::findOrFail($id);

        $employee->update([
            'is_active' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Employee deactivated successfully',
        ], 200);

    } catch (\Exception $e) {
        Log::error("Employee delete error: " . $e->getMessage());

        return response()->json([
            'status' => false,
            'message' => 'Delete failed',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}
