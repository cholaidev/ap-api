<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    // 🔥 GET ALL EMPLOYEES
    public function index()
    {
        try {
            return response()->json([
                'status'  => true,
                'message' => 'Employees loaded successfully',
                'data'    => Employee::latest()->get(),
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Employee index error', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Unable to fetch employees',
            ], 500);
        }
    }

    // 🔥 GET SINGLE EMPLOYEE
    public function show($id)
    {
        try {
            return response()->json([
                'status'  => true,
                'message' => 'Employee details loaded',
                'data'    => Employee::with('user')->findOrFail($id),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Employee not found',
            ], 404);
        }
    }

    // 🔥 CREATE EMPLOYEE
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // ✅ BASIC VALIDATION
            $validated = $request->validate([
                'name'        => 'nullable|string|max:255',
                'phone'       => 'required|string',
                'salary'      => 'nullable|numeric',
                'given_money' => 'nullable|numeric',
            ]);

            // ❌ PHONE EXISTS IN USERS
            if (User::where('phone', $validated['phone'])->exists()) {
                throw ValidationException::withMessages([
                    'phone' => ['This phone number already exists in users.'],
                ]);
            }

            // ❌ PHONE EXISTS IN EMPLOYEES
            if (Employee::where('phone', $validated['phone'])->exists()) {
                throw ValidationException::withMessages([
                    'phone' => ['This phone number already exists in employees.'],
                ]);
            }

            // ✅ CREATE EMPLOYEE
            $employee = Employee::create([
                'name'        => $validated['name'] ?? null,
                'phone'       => $validated['phone'],
                'salary'      => $validated['salary'] ?? 0,
                'given_money' => $validated['given_money'] ?? 0,
                'is_active'   => 1,
            ]);

            // ✅ CREATE USER
            $user = User::create([
                'name'        => $validated['name'] ?? 'Staff',
                'phone'       => $validated['phone'],
                'password'    => bcrypt('annapoorani'),
                'role'        => 'staff',
                'employee_id' => $employee->id,
                'is_active'   => 1,
            ]);

            $employee->update(['user_id' => $user->id]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Employee added successfully',
                'data'    => $employee->load('user'),
            ], 201);
        }
        // ✅ VALIDATION ERROR (422)
        catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }
        // ❌ SERVER ERROR (500)
        catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Employee store error', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Employee creation failed',
            ], 500);
        }
    }

    // 🔥 UPDATE EMPLOYEE
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $employee = Employee::with('user')->findOrFail($id);

            $validated = $request->validate([
                'name'        => 'nullable|string|max:255',
                'phone'       => 'required|string',
                'salary'      => 'nullable|numeric',
                'given_money' => 'nullable|numeric',
            ]);

            // ❌ PHONE EXISTS IN USERS (EXCEPT CURRENT)
            if (
                User::where('phone', $validated['phone'])
                    ->where('employee_id', '!=', $id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'phone' => ['This phone number already exists in users.'],
                ]);
            }

            // ❌ PHONE EXISTS IN EMPLOYEES (EXCEPT CURRENT)
            if (
                Employee::where('phone', $validated['phone'])
                    ->where('id', '!=', $id)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'phone' => ['This phone number already exists in employees.'],
                ]);
            }

            // ✅ UPDATE EMPLOYEE
            $employee->update([
                'name'        => $validated['name'] ?? $employee->name,
                'phone'       => $validated['phone'],
                'salary'      => $validated['salary'] ?? $employee->salary,
                'given_money' => $validated['given_money'] ?? $employee->given_money,
            ]);

            // ✅ UPDATE USER
            if ($employee->user) {
                $employee->user->update([
                    'name'  => $validated['name'] ?? $employee->user->name,
                    'phone' => $validated['phone'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Employee updated successfully',
                'data'    => $employee->fresh('user'),
            ], 200);
        }
        catch (ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }
        catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Employee update error', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Employee update failed',
            ], 500);
        }
    }

    // 🔥 DELETE (SOFT)
    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            Log::info('delete employee hitted');

            $employee = Employee::findOrFail($id);

            $employee->update(['is_active' => 0]);

            

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Employee deactivated successfully',
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Employee delete error', ['error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Employee delete failed',
            ], 500);
        }
    }
}
