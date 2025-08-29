<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(20);

        return response()->json($users);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:student,admin',
            'password' => 'sometimes|min:8|confirmed',
        ]);

        $data = $request->only(['name', 'email', 'role']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            // Force logout user if password changed
            $user->tokens()->delete();
            $user->update([
                'active_session_id' => null,
                'device_fingerprint' => null,
            ]);
        }

        $user->update($data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'gender' => 'sometimes|in:male,female',
            'password' => 'sometimes|min:8|confirmed',
        ]);

        // Only get fields that are actually present and not empty
        $data = [];

        if ($request->filled('name')) {
            $data['name'] = $request->name;
        }

        if ($request->filled('email')) {
            $data['email'] = $request->email;
        }

        if ($request->filled('phone')) {
            $data['phone'] = $request->phone;
        }

        if ($request->filled('gender')) {
            $data['gender'] = $request->gender;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            // Force logout user if password changed
            $user->tokens()->delete();
            $user->update([
                'active_session_id' => null,
                'device_fingerprint' => null,
            ]);
        }

        // Only update if there's data to update
        if (!empty($data)) {
            $user->update($data);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh(),
            'updated_fields' => array_keys($data)
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Don't allow deleting the last admin
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json(['message' => 'Cannot delete the last admin user'], 400);
        }

        // Logout user before deletion
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
