<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password or PIN | Northeastern College HRMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #14532d 0%, #15803d 45%, #4ade80 100%);
        }
    </style>
</head>
<body>
<main class="min-h-screen flex items-center justify-center px-6 py-8">
    <section class="w-full max-w-md rounded-3xl bg-white p-10 shadow-2xl">
        <div class="mb-8 flex items-center gap-3">
            <div class="h-12 w-12 overflow-hidden rounded-xl bg-white ring-1 ring-slate-100">
                <img src="/images/logo.webp" alt="Northeastern College Logo" class="h-full w-full object-contain">
            </div>
            <div>
                <h1 class="text-lg font-bold text-gray-900">Northeastern College</h1>
                <p class="text-sm text-gray-500">HR Management System</p>
            </div>
        </div>

        @php($recoveryType = request()->query('type'))

        @if (!in_array($recoveryType, ['password', 'pin'], true))
            <h2 class="mb-2 text-3xl font-bold text-gray-900">What do you want to reset?</h2>
            <p class="mb-8 text-gray-500">Choose the account credential you need help with.</p>

            <div class="space-y-4">
                <a href="{{ route('password.request', ['type' => 'password']) }}" class="group flex items-center gap-4 rounded-2xl border border-gray-200 p-5 transition hover:border-green-600 hover:bg-green-50">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-green-100 text-xl text-green-700">&#128274;</span>
                    <span>
                        <strong class="block text-gray-900 group-hover:text-green-800">Reset password</strong>
                        <span class="mt-1 block text-sm text-gray-500">Receive a secure password reset link by email.</span>
                    </span>
                </a>

                <a href="{{ route('password.request', ['type' => 'pin']) }}" class="group flex items-center gap-4 rounded-2xl border border-gray-200 p-5 transition hover:border-green-600 hover:bg-green-50">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-green-100 text-xl font-bold text-green-700">#</span>
                    <span>
                        <strong class="block text-gray-900 group-hover:text-green-800">Reset PIN</strong>
                        <span class="mt-1 block text-sm text-gray-500">Get help restoring access to your PIN.</span>
                    </span>
                </a>
            </div>
        @elseif ($recoveryType === 'password')
            <h2 class="mb-2 text-3xl font-bold text-gray-900">Reset Password</h2>
            <p class="mb-8 text-gray-500">Enter your account email and we will send a password reset link.</p>

        @if ($recoveryType === 'password' && session('status'))
            <div class="mb-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($recoveryType === 'password' && $errors->has('email'))
            <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first('email') }}
            </div>
        @endif

        @if ($recoveryType === 'password')
        <form class="space-y-6" method="POST" action="{{ route('password.email') }}">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="john@example.com" class="mt-2 w-full rounded-xl border border-gray-200 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-600">
            </div>

            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-green-800 via-green-600 to-green-800 py-3 font-semibold text-white transition hover:opacity-95">
                Send Reset Link
            </button>
        </form>
        @endif
        @else
            <h2 class="mb-2 text-3xl font-bold text-gray-900">Reset PIN</h2>
            <p class="mb-6 text-gray-500">For your security, PIN resets are handled by the HR administrator.</p>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-800">
                Please contact HR and ask them to reset your account PIN. Be ready to confirm your employee information.
            </div>
        @endif

        @if (in_array($recoveryType, ['password', 'pin'], true))
            <p class="mt-6 text-center text-sm">
                <a href="{{ route('password.request') }}" class="font-semibold text-green-700 hover:underline">Choose another option</a>
            </p>
        @endif

        <p class="mt-8 text-center text-sm text-gray-500">
            Remember your sign-in details?
            <a href="{{ route('login_display') }}" class="font-semibold text-green-700 hover:underline">Sign in</a>
        </p>
    </section>
</main>
</body>
</html>
