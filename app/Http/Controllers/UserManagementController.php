<?php

namespace App\Http\Controllers;

use App\Models\AccessProfile;
use App\Models\Orgao;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class UserManagementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with(['profile:id,name,slug', 'orgao:id,nome_orgao'])->orderBy('name');

        if (! $request->user()->hasPermission('users.manage')) {
            $query->where('municipio', $request->user()->municipio);
        }

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('municipio', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Siay/Users/Index', [
            'users' => $query->paginate(12)->withQueryString(),
            'filters' => $request->only('search'),
            'profiles' => AccessProfile::orderBy('name')->get(['id', 'name', 'slug']),
            'orgaos' => Orgao::where('ativo', true)->orderBy('nome_orgao')->get(['id', 'nome_orgao', 'municipio']),
            'canManageAll' => $request->user()->hasPermission('users.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->applyMunicipalRestriction($request, $data);

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'active' => $request->boolean('active', true),
        ]);

        AuditLogger::record('user_created', $user, "Usuario {$user->name} criado.");

        return back()->with('success', "Usuario {$user->name} criado com sucesso.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        $this->applyMunicipalRestriction($request, $data);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $before = $user->getOriginal();
        $user->update($data);

        AuditLogger::record('user_updated', $user, "Usuario {$user->name} atualizado.", [
            'before' => $before,
            'after' => $user->fresh()->toArray(),
        ]);

        return back()->with('success', "Usuario {$user->name} atualizado com sucesso.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Voce nao pode desativar o proprio usuario.');
        }

        $user->forceFill(['active' => false])->save();

        AuditLogger::record('user_removed', $user, "Usuario {$request->user()->name} removeu/desativou {$user->name}.");

        return back()->with('success', "Usuario {$user->name} desativado com sucesso.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.($user?->id ?? 'NULL')],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Rules\Password::defaults()],
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:255'],
            'orgao_id' => ['nullable', 'exists:orgaos,id'],
            'access_profile_id' => ['required', 'exists:access_profiles,id'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyMunicipalRestriction(Request $request, array &$data): void
    {
        if ($request->user()->hasPermission('users.manage')) {
            return;
        }

        $data['municipio'] = $request->user()->municipio;
        $profile = AccessProfile::find($data['access_profile_id']);

        abort_if($profile?->slug === 'administrador-estadual', 403, 'Gestor municipal nao pode criar administrador estadual.');
    }
}
