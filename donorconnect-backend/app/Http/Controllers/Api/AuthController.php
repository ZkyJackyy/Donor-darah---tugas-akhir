<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendVerificationRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\VerifyEmailCode;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        $user = User::create($request->validated());

        $this->generateAndSendVerificationCode($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->created([
            'access_token' => $token,
            'user' => new UserResource($user)
        ], 'Registration successful');
    }

    /**
     * Verifikasi email dengan kode 6 digit yang dikirim ke email.
     */
    public function verifyEmail(VerifyEmailRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (
            $user->email_verification_code !== $request->code
            || !$user->email_verification_code_expires_at
            || now()->greaterThan($user->email_verification_code_expires_at)
        ) {
            return $this->error('Kode tidak valid atau sudah kedaluwarsa', 400);
        }

        $user->email_verified_at = now();
        $user->email_verification_code = null;
        $user->email_verification_code_expires_at = null;
        $user->save();

        return $this->success(new UserResource($user), 'Email berhasil diverifikasi');
    }

    /**
     * Kirim ulang kode verifikasi email.
     */
    public function resendVerificationCode(ResendVerificationRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if ($user->email_verified_at) {
            return $this->error('Email sudah terverifikasi', 400);
        }

        $this->generateAndSendVerificationCode($user);

        return $this->success(null, 'Kode verifikasi telah dikirim ulang');
    }

    private function generateAndSendVerificationCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        $user->update([
            'email_verification_code' => $code,
            'email_verification_code_expires_at' => now()->addMinutes(15),
        ]);

        $user->notify(new VerifyEmailCode($code));
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->unauthorized('Invalid login credentials');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'access_token' => $token,
            'user' => new UserResource($user)
        ], 'Login successful');
    }

    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();
        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Forgot password: generate reset token and send via WA (simplified).
     * Token valid for 15 menit.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();
        $token = Str::random(60);

        // Store token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Dalam produksi, kirim via WA. Untuk sekarang, return token langsung.
        return $this->success([
            'message' => 'Reset token telah dikirim ke nomor WhatsApp Anda',
            'token_hint' => substr($token, 0, 8) . '...',
        ], 'Reset token generated');
    }

    /**
     * Reset password dengan token.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord || !Hash::check($request->token, $resetRecord->token)) {
            return $this->error('Token reset tidak valid atau sudah kedaluwarsa', 400);
        }

        if (now()->diffInMinutes($resetRecord->created_at) > 15) {
            return $this->error('Token reset sudah kedaluwarsa', 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        // Hapus token setelah digunakan
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return $this->success(null, 'Password berhasil diubah');
    }

    /**
     * Change password (user sudah login).
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Password lama salah', 400);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return $this->success(null, 'Password berhasil diubah');
    }
}
