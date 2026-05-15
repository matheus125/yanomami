<?php

namespace App\Http\Controllers;

use App\Models\AccessProfile;
use App\Models\Permission;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccessProfileController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Siay/Permissions/Index', [
            'profiles' => AccessProfile::with('permissions:id,key,module,name')->orderBy('name')->get(),
            'permissions' => Permission::orderBy('module')->orderBy('name')->get()->groupBy('module'),
        ]);
    }

    public function update(Request $request, AccessProfile $profile): RedirectResponse
    {
        $data = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $profile->permissions()->sync($data['permissions'] ?? []);

        AuditLogger::record('profile_permissions_updated', $profile, "Permissoes do perfil {$profile->name} foram atualizadas.", [
            'permissions' => $profile->permissions()->pluck('key')->all(),
        ]);

        return back()->with('success', "Permissoes do perfil {$profile->name} atualizadas com sucesso.");
    }
}
