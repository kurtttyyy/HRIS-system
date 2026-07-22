<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Account | Northeastern College HRMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-green-950 via-green-700 to-emerald-400 px-6 py-10">
<main class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-md items-center">
    <section class="w-full rounded-3xl bg-white p-8 shadow-2xl sm:p-10">
        <div class="mb-7 flex items-center gap-3">
            <img src="/images/logo.webp" alt="Northeastern College Logo" class="h-12 w-12 rounded-xl object-contain ring-1 ring-slate-100">
            <div>
                <h1 class="font-bold text-slate-900">Northeastern College</h1>
                <p class="text-sm text-slate-500">Employee account activation</p>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        @if (!$pinVerified)
            <h2 class="text-2xl font-black text-slate-900">Verify your account</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Enter the Employee ID and temporary PIN provided by HR.</p>
            <form method="POST" action="{{ route('account.activation.submit', [], false) }}" class="mt-7 space-y-5">
                @csrf
                <input type="hidden" name="activation_step" value="verify">
                <div>
                    <label class="text-sm font-semibold text-slate-700">Employee ID</label>
                    <input name="employee_id" value="{{ old('employee_id') }}" required autocomplete="username" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Temporary PIN</label>
                    <input type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" name="temporary_pin" required autocomplete="one-time-code" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 tracking-[0.35em] outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <button class="w-full rounded-xl bg-green-700 py-3 font-bold text-white hover:bg-green-800">Verify PIN</button>
            </form>
        @else
            <h2 class="text-2xl font-black text-slate-900">Create your password</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">Use at least 8 characters. Saving will activate and approve your account.</p>
            <form method="POST" action="{{ route('account.activation.submit', [], false) }}" class="mt-7 space-y-5">
                @csrf
                <input type="hidden" name="activation_step" value="complete">
                <div>
                    <label class="text-sm font-semibold text-slate-700">New password</label>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <div>
                    <label class="text-sm font-semibold text-slate-700">Confirm password</label>
                    <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-green-600">
                </div>
                <button class="w-full rounded-xl bg-green-700 py-3 font-bold text-white hover:bg-green-800">Save and Activate Account</button>
            </form>
        @endif

        <p class="mt-7 text-center text-sm"><a href="{{ route('login_display') }}" class="font-semibold text-green-700 hover:underline">Back to sign in</a></p>
    </section>
</main>

@if (session('activation_already_completed'))
    <div id="activation-complete-alert" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-5 backdrop-blur-sm" role="alertdialog" aria-modal="true" aria-labelledby="activation-complete-title">
        <div class="w-full max-w-sm rounded-3xl bg-white p-7 text-center shadow-2xl">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-3xl text-emerald-700">✓</div>
            <h2 id="activation-complete-title" class="mt-5 text-2xl font-black text-slate-900">Account already activated</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">Your temporary PIN has already been used and cannot be used again. Sign in with the password you created, or use Forgot Password if you need a new one.</p>
            <a href="{{ route('login_display', [], false) }}" class="mt-6 block w-full rounded-xl bg-green-700 py-3 font-bold text-white transition hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-200">Go to sign in</a>
            <button type="button" onclick="document.getElementById('activation-complete-alert').remove()" class="mt-3 w-full py-2 text-sm font-semibold text-slate-500 hover:text-slate-700">Close</button>
        </div>
    </div>
@endif
</body>
</html>
