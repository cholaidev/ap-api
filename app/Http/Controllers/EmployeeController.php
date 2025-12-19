<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
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
            $employee = Employee::findOrFail($id);

            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'phone' => 'required|unique:employees,phone,' . $id,
                'email' => 'nullable|email',
                'salary' => 'nullable|numeric',
                'given_money' => 'nullable|numeric',
            ]);

            $employee->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Employee updated successfully',
                'data' => $employee
            ]);

        } catch (\Exception $e) {
            Log::error("Employee update error: ".$e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Employee update failed',
                'error' => $e->getMessage()
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
