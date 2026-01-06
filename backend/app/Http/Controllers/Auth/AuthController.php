<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and account
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'account_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create account (tenant)
        $account = Account::create([
            'name' => $request->account_name,
            'plan' => 'free',
            'status' => 'active',
        ]);

        // Create user
        $user = User::create([
            'account_id' => $account->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Create token for auto-login
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Conta criada com sucesso!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account' => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'plan' => $account->plan,
                ],
            ],
        ], 201);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        // Check if account is active
        if (!$user->account->isActive()) {
            return response()->json([
                'message' => 'Conta suspensa ou cancelada.',
            ], 403);
        }

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'account' => [
                    'id' => $user->account->id,
                    'name' => $user->account->name,
                    'plan' => $user->account->plan,
                ],
            ],
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user()->load('account');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mfa_enabled' => $user->mfa_enabled,
            'account' => [
                'id' => $user->account->id,
                'name' => $user->account->name,
                'plan' => $user->account->plan,
                'status' => $user->account->status,
            ],
        ]);
    }
}
