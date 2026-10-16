<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Accounts | Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>body{font-family:"Segoe UI",sans-serif}main{margin-left:4rem}aside:hover~main{margin-left:18rem}dialog::backdrop{background:rgba(15,23,42,.6)}dialog{max-height:90vh;overflow:auto}input{color:#0f172a}</style>
</head>
<body class="bg-slate-50 text-slate-800">
@php($tabSession = (string) request('tab_session', ''))
<div class="flex min-h-screen">
    @include('components.adminSideBar')
    <main class="min-w-0 flex-1">
        @include('components.adminHeader.dashboardHeader', ['headerTitle' => 'Accounts', 'headerSubtitle' => 'Manage staff account access and passwords.', 'showThemeToggle' => false])
        <div class="mx-auto max-w-7xl p-4 pt-20 md:p-8">
            <p id="account-success" role="status" @if(!session('success')) hidden @endif class="mb-5 rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('success') }}</p>
            @if($errors->any())<p role="alert" class="mb-5 rounded-xl bg-rose-50 p-4 text-rose-700">{{ $errors->first() }}</p>@endif
            <nav aria-label="Account sections" class="mb-5 flex flex-wrap gap-3">
                <a href="{{ route('admin.accountManagement.index', ['tab_session' => $tabSession, 'search' => $search]) }}" @if(!$showDeleted) aria-current="page" @endif class="rounded-xl border px-4 py-3 text-sm font-semibold {{ !$showDeleted ? 'bg-emerald-800 text-white' : 'bg-white text-slate-600' }}">Staff accounts</a>
                <a href="{{ route('admin.accountManagement.index', ['tab_session' => $tabSession, 'section' => 'deleted', 'search' => $search]) }}" @if($showDeleted) aria-current="page" @endif class="rounded-xl border px-4 py-3 text-sm font-semibold {{ $showDeleted ? 'bg-emerald-800 text-white' : 'bg-white text-slate-600' }}"><i class="fa-solid fa-trash-can-arrow-up mr-2"></i>Deleted accounts ({{ $deletedCount }})</a>
            </nav>
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b p-6">
                    <div><h1 class="text-xl font-bold">{{ $showDeleted ? 'Deleted accounts' : 'Staff accounts' }}</h1><p class="mt-1 text-sm text-slate-500">{{ $accounts->total() }} accounts · {{ $showDeleted ? 'Restore retained accounts here.' : 'Passwords are securely hashed.' }}</p></div>
                    <form method="GET" class="flex flex-wrap gap-2">
                        <input type="hidden" name="tab_session" value="{{ $tabSession }}">
                        @if($showDeleted)<input type="hidden" name="section" value="deleted">@endif
                        <label for="account-search" class="sr-only">Search by name, email or ID number</label>
                        <input id="account-search" name="search" value="{{ $search }}" placeholder="Name, email or ID number" class="w-64 rounded-xl border px-4 py-2">
                        <button class="rounded-xl bg-emerald-800 px-4 py-2 text-white"><i class="fa-solid fa-search mr-2"></i>Search</button>
                        @if($search !== '')<a class="px-3 py-2 text-sm text-slate-600" href="{{ route('admin.accountManagement.index', ['tab_session' => $tabSession, 'section' => $showDeleted ? 'deleted' : null]) }}">Clear</a>@endif
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-slate-500"><tr>@foreach(['Employee', 'ID number', 'Email', 'Role', $showDeleted ? 'Deleted on' : 'Password', 'Actions'] as $heading)<th class="px-6 py-4">{{ $heading }}</th>@endforeach</tr></thead>
                        <tbody class="divide-y">
                        @forelse($accounts as $account)
                            <tr>
                                <td class="px-6 py-4 font-semibold">{{ $account->first_name }} {{ $account->last_name }}</td>
                                <td class="px-6 py-4">{{ $account->employee?->employee_id ?: 'Account #'.$account->id }}</td>
                                <td class="px-6 py-4">{{ $account->email ?: 'No email assigned' }}</td>
                                <td class="px-6 py-4">{{ $account->role }}</td>
                                @if($showDeleted)
                                <td class="px-6 py-4">{{ $account->deleted_at->timezone('Asia/Shanghai')->format('M j, Y g:i A') }}</td>
                                @else
                                <td class="px-6 py-4"><code class="block w-72 break-all rounded-lg bg-slate-50 px-3 py-2 font-mono text-xs leading-relaxed text-slate-600">{{ $account->getRawOriginal('password') }}</code></td>
                                @endif
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($showDeleted)
                                    <button type="button" data-restore data-name="{{ $account->first_name }} {{ $account->last_name }}" data-url="{{ route('admin.accountManagement.restore', $account->id) }}" class="rounded-lg bg-emerald-50 px-4 py-3 text-emerald-700"><i class="fa-solid fa-rotate-left mr-2"></i>Restore</button>
                                    @else
                                    <button type="button" data-edit data-name="{{ $account->first_name }} {{ $account->last_name }}" data-verify="{{ route('admin.accountManagement.verify', ['account' => $account, 'tab_session' => $tabSession]) }}" data-update="{{ route('admin.accountManagement.update', ['account' => $account, 'tab_session' => $tabSession]) }}" aria-label="Edit password for {{ $account->first_name }} {{ $account->last_name }}" title="Edit password" class="rounded-lg bg-emerald-50 p-3 text-emerald-700"><i class="fa-solid fa-pen-to-square"></i></button>
                                    @if($account->id !== auth()->id())
                                    <button type="button" data-delete data-name="{{ $account->first_name }} {{ $account->last_name }}" data-url="{{ route('admin.accountManagement.destroy', $account) }}" aria-label="Delete account for {{ $account->first_name }} {{ $account->last_name }}" title="Delete account" class="ml-2 rounded-lg bg-rose-50 p-3 text-rose-600"><i class="fa-solid fa-trash"></i></button>
                                    @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-12 text-center text-slate-500">{{ $showDeleted && $search === '' ? 'No deleted accounts.' : 'No accounts found. Try another name, email or ID number.' }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t p-5">{{ $accounts->links() }}</div>
            </section>
        </div>
    </main>
</div>
<dialog id="edit-account" class="w-full max-w-md rounded-2xl p-6 shadow-xl">
    <h2 class="text-xl font-bold">Verify administrator password</h2>
    <p id="edit-name" class="my-3 text-sm text-slate-500"></p>
    <p id="edit-error" role="alert" class="mb-3 text-sm text-rose-600"></p>
    <form id="verify-form" class="space-y-4">
        <label class="block text-sm">Your administrator password<input name="admin_password" type="password" autocomplete="current-password" required class="mt-2 w-full rounded-xl border p-3"></label>
        <div class="flex justify-end gap-3"><button type="button" data-close class="rounded-xl border px-4 py-2">Cancel</button><button class="rounded-xl bg-emerald-800 px-4 py-2 text-white">Verify</button></div>
    </form>
    <div id="account-details" hidden class="space-y-4">
        <dl class="space-y-3 text-sm">
            <div><dt class="text-slate-500">ID number</dt><dd id="detail-id" class="mt-1 font-semibold"></dd></div>
            <div><dt class="text-slate-500">Email</dt><dd id="detail-email" class="mt-1 break-all font-semibold"></dd></div>
            <div><dt class="text-slate-500">Stored password hash</dt><dd class="mt-2"><code id="detail-password" class="block break-all rounded-xl bg-slate-50 p-3 font-mono text-xs leading-relaxed text-slate-600"></code></dd></div>
        </dl>
        <p class="text-xs text-slate-500">The original password cannot be recovered from this hash.</p>
        <div class="flex justify-end gap-3"><button type="button" data-close class="rounded-xl border px-4 py-2">Close</button><button type="button" id="change-password" class="rounded-xl bg-emerald-800 px-4 py-2 text-white">Change password</button></div>
    </div>
    <form id="password-form" hidden class="space-y-4">
        <label class="block text-sm">New password<input name="password" type="password" autocomplete="new-password" minlength="8" required class="mt-2 w-full rounded-xl border p-3"></label>
        <p class="text-xs text-slate-500">Use at least 8 characters.</p>
        <label class="block text-sm">Confirm new password<input name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required class="mt-2 w-full rounded-xl border p-3"></label>
        <div class="flex justify-end gap-3"><button type="button" id="password-back" class="rounded-xl border px-4 py-2">Back</button><button class="rounded-xl bg-emerald-800 px-4 py-2 text-white">Save password</button></div>
    </form>
</dialog>
<dialog id="delete-account" class="w-full max-w-md rounded-2xl p-6 shadow-xl">
    <h2 class="text-xl font-bold">Delete this account?</h2>
    <p id="delete-name" class="my-4"></p>
    <p class="mb-6 text-sm text-slate-500">They will lose access to the system. Their account and employee records will be retained.</p>
    <form method="POST" id="delete-form">@csrf @method('DELETE')
        <input type="hidden" name="tab_session" value="{{ $tabSession }}"><input type="hidden" name="search" value="{{ $search }}">
        <div class="flex justify-end gap-3"><button type="button" data-close class="rounded-xl border px-4 py-2">Cancel</button><button class="rounded-xl bg-rose-600 px-4 py-2 text-white">Delete account</button></div>
    </form>
</dialog>
<dialog id="restore-account" class="w-full max-w-md rounded-2xl p-6 shadow-xl">
    <h2 class="text-xl font-bold">Restore this account?</h2>
    <p id="restore-name" class="my-4"></p>
    <p class="mb-6 text-sm text-slate-500">The account will return to Staff accounts with its previous password and account status. Active accounts can sign in again.</p>
    <form method="POST" id="restore-form">@csrf
        <input type="hidden" name="tab_session" value="{{ $tabSession }}"><input type="hidden" name="search" value="{{ $search }}">
        <div class="flex justify-end gap-3"><button type="button" data-close class="rounded-xl border px-4 py-2">Cancel</button><button class="rounded-xl bg-emerald-800 px-4 py-2 text-white">Restore account</button></div>
    </form>
</dialog>
<script>
(() => {
    const edit = document.getElementById('edit-account'), verifyForm = document.getElementById('verify-form'), passwordForm = document.getElementById('password-form'), error = document.getElementById('edit-error');
    const details = document.getElementById('account-details');
    const successNotice = document.getElementById('account-success');
    let successTimer;
    function showSuccess(message) {
        clearTimeout(successTimer);
        successNotice.textContent = message;
        successNotice.hidden = false;
        successTimer = setTimeout(() => { successNotice.hidden = true; successNotice.textContent = ''; }, 5000);
    }
    if (!successNotice.hidden) showSuccess(successNotice.textContent);
    let selected, token, selectedRow;
    document.querySelectorAll('[data-edit]').forEach(button => button.addEventListener('click', () => {
        selected = button.dataset; selectedRow = button.closest('tr'); token = ''; verifyForm.reset(); passwordForm.reset(); verifyForm.hidden = false; passwordForm.hidden = true; details.hidden = true; error.textContent = '';
        edit.querySelector('h2').textContent = 'Verify administrator password'; document.getElementById('edit-name').textContent = 'Editing account: ' + selected.name; edit.showModal();
    }));
    document.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
    edit.addEventListener('close', () => { token = ''; verifyForm.reset(); passwordForm.reset(); document.getElementById('detail-password').textContent = ''; });
    function showDetails() {
        passwordForm.hidden = true; details.hidden = false; error.textContent = ''; edit.querySelector('h2').textContent = 'Account details';
        document.getElementById('change-password').focus();
    }
    document.getElementById('change-password').addEventListener('click', () => {
        details.hidden = true; passwordForm.hidden = false; error.textContent = ''; edit.querySelector('h2').textContent = 'Change account password'; passwordForm.elements.password.focus();
    });
    document.getElementById('password-back').addEventListener('click', () => { passwordForm.reset(); showDetails(); });
    async function submit(form, url, method, extra = {}) {
        const button = form.querySelector('button:not([type="button"])'); button.disabled = true; error.textContent = '';
        try {
            const response = await fetch(url, {method, headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify({...Object.fromEntries(new FormData(form)), ...extra})});
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors ? Object.values(data.errors).flat()[0] : data.message || 'Unable to save. Please try again.');
            return data;
        } catch (e) { error.textContent = e.message; return null; } finally { button.disabled = false; }
    }
    verifyForm.addEventListener('submit', async event => {
        event.preventDefault(); const data = await submit(verifyForm, selected.verify, 'POST');
        if (data) {
            token = data.token; verifyForm.reset(); verifyForm.hidden = true;
            document.getElementById('detail-id').textContent = data.account.id_number;
            document.getElementById('detail-email').textContent = data.account.email || 'No email assigned';
            document.getElementById('detail-password').textContent = data.account.password_hash;
            showDetails();
        }
    });
    passwordForm.addEventListener('submit', async event => {
        event.preventDefault(); const data = await submit(passwordForm, selected.update, 'PATCH', {edit_token: token});
        if (data) { selectedRow.querySelector('code').textContent = data.password_hash; edit.close(); showSuccess(data.message); }
    });
    document.querySelectorAll('[data-delete]').forEach(button => button.addEventListener('click', () => {
        document.getElementById('delete-form').action = button.dataset.url; document.getElementById('delete-name').textContent = 'Are you sure you want to delete the account for ' + button.dataset.name + '?'; document.getElementById('delete-account').showModal();
    }));
    document.querySelectorAll('[data-restore]').forEach(button => button.addEventListener('click', () => {
        document.getElementById('restore-form').action = button.dataset.url;
        document.getElementById('restore-name').textContent = 'Restore the account for ' + button.dataset.name + '?';
        document.getElementById('restore-account').showModal();
    }));
})();
</script>
</body>
</html>
