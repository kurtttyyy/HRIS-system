<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | Northeastern College HRMS</title>
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

        <h2 class="mb-2 text-3xl font-bold text-gray-900">Reset Password</h2>
        <p class="mb-8 text-gray-500">Enter your account email and we will send a password reset link.</p>

        @if (session('status'))
            <div class="mb-5 rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->has('email'))
            <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first('email') }}
            </div>
        @endif

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

        <p class="mt-8 text-center text-sm text-gray-500">
            Remember your sign-in details?
            <a href="{{ route('login_display') }}" class="font-semibold text-green-700 hover:underline">Sign in</a>
        </p>
    </section>
</main>
</body>
</html>
