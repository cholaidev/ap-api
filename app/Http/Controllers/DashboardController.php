<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary()
    {
        $totalSales = Delivery::sum('total');
        $totalExpenses = Expense::sum('amount');
        $totalPayments = Payment::sum('amount');
        $profit = $totalPayments - $totalExpenses;

        $totalCustomers = Customer::count();
        $totalVendors = Vendor::count();
        $totalStaff = User::where('role', 'staff')->count();

        return response()->json([
            'total_customers' => $totalCustomers,
            'total_vendors' => $totalVendors,
            'total_staff' => $totalStaff,
            'total_sales' => $totalSales,
            'total_expenses' => $totalExpenses,
            'total_payments' => $totalPayments,
            'profit' => $profit,
        ]);
    }
}

