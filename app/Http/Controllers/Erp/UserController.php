<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function fetchUser()
    {
        $users = User::all();

        return response()->json($users);
    }

    /**
     * Search users for dropdown/autocomplete
     */
    public function searchUser(Request $request)
    {
        $query = User::query();
        
        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('last_name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
            });
        }
        
        $users = $query->orderBy('first_name')->limit(10)->get();
        
        $results = $users->map(function($user) {
            return [
                'id' => $user->id,
                'text' => $user->first_name . ' ' . $user->last_name . ' (' . $user->email . ')'
            ];
        });
        
        return response()->json([
            'results' => $results
        ]);
    }
}
