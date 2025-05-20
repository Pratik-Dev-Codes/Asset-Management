<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestController extends Controller
{
    public function testRole()
    {
        // Get the authenticated user
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Not authenticated'], 401);
        }

        // Check if the user has the 'admin' role using the User model
        if ($user->hasRole('admin')) {
            return response()->json([
                'message' => 'You have the admin role!',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                ],
            ]);
        }

        return response()->json([
            'message' => 'You do not have the admin role.',
            'user_roles' => $user->getRoleNames(),
        ], 403);
    }
}
