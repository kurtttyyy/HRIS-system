<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user() && in_array(strtolower(trim($request->user()->role)), ['admin', 'administrator'], true)
            && $request->user()->hasAdminPermission('manage_admins'), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        $search = trim((string) $request->query('search', ''));
        $showDeleted = $request->query('section') === 'deleted';
        $deletedCount = User::onlyTrashed()->count();
        $accounts = User::with('employee')->when($showDeleted, fn ($query) => $query->onlyTrashed())->when($search !== '', function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                foreach (['first_name', 'last_name', 'email'] as $field) {
                    $query->orWhere($field, 'like', '%'.$search.'%');
                }
                $query->orWhere('id', $search)->orWhereHas('employee', fn ($q) => $q->where('employee_id', 'like', '%'.$search.'%'));
                $terms = preg_split('/\s+/', $search);
                if (count($terms) > 1) {
                    $query->orWhere(function ($names) use ($terms) {
                        foreach ($terms as $term) {
                            $names->where(fn ($name) => $name->where('first_name', 'like', '%'.$term.'%')->orWhere('last_name', 'like', '%'.$term.'%'));
                        }
                    });
                }
            });
        })->orderBy('last_name')->paginate(15)->withQueryString();
        return response()->view('Admin.accounts', compact('accounts', 'search', 'showDeleted', 'deletedCount'))->header('Cache-Control', 'no-store');
    }

    public function verify(Request $request, User $account)
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['admin_password' => 'required|string']);
        if (!Hash::check($data['admin_password'], $request->user()->password)) {
            return response()->json(['message' => 'The administrator password is incorrect.'], 422);
        }
        $token = Str::random(64);
        $request->session()->put('account_edit.'.$account->id, ['token' => $token, 'admin' => $request->user()->id, 'expires' => now()->addMinutes(5)->timestamp]);
        return response()->json(['token' => $token, 'account' => [
            'email' => $account->email,
            'id_number' => $account->employee?->employee_id ?: 'Account #'.$account->id,
            'password_hash' => $account->getRawOriginal('password'),
        ]])->header('Cache-Control', 'no-store');
    }

    public function update(Request $request, User $account)
    {
        $this->authorizeAdmin($request);
        $grant = $request->session()->get('account_edit.'.$account->id);
        abort_unless($grant && $grant['admin'] === $request->user()->id && $grant['expires'] >= now()->timestamp
            && hash_equals($grant['token'], (string) $request->input('edit_token')), 403, 'Verify your administrator password again.');
        $data = $request->validate(['password' => 'required|string|min:8|confirmed']);
        $account->forceFill(['password' => Hash::make($data['password']), 'remember_token' => Str::random(60), 'temporary_pin' => null])->save();
        $request->session()->forget('account_edit.'.$account->id);
        return response()->json(['message' => 'Password updated successfully.', 'password_hash' => $account->getRawOriginal('password')])->header('Cache-Control', 'no-store');
    }

    public function destroy(Request $request, User $account)
    {
        $this->authorizeAdmin($request);
        abort_if($account->id === $request->user()->id, 422, 'You cannot delete your own account.');
        $account->delete();
        return redirect()->route('admin.accountManagement.index', array_filter(['tab_session' => $request->input('tab_session'), 'search' => $request->input('search')]))
            ->with('success', 'Account deleted. The record is retained and can no longer sign in.');
    }

    public function restore(Request $request, string $account)
    {
        $this->authorizeAdmin($request);
        $deletedAccount = User::onlyTrashed()->findOrFail($account);
        $deletedAccount->restore();
        $request->session()->forget('account_edit.'.$deletedAccount->id);
        return redirect()->route('admin.accountManagement.index', array_filter([
            'tab_session' => $request->input('tab_session'), 'section' => 'deleted', 'search' => $request->input('search'),
        ]))->with('success', 'Account restored successfully. It is available in Staff accounts with its previous password and account status.');
    }
}
