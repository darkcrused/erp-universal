<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * List users in the current tenant.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::where('tenant_id', tenant()->getTenantKey())
            ->with('roles')
            ->paginate($request->input('per_page', 20));

        return response()->json($users);
    }

    /**
     * Create a new user within the tenant.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'locale' => 'nullable|string|in:pt_BR,en,es',
            'role' => 'nullable|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user = User::create([
            'tenant_id' => tenant()->getTenantKey(),
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'locale' => $request->input('locale', 'pt_BR'),
        ]);

        if ($request->role) {
            $user->assignRole($request->role);
        } else {
            $user->assignRole('user');
        }

        return response()->json([
            'message' => __('core.user_created'),
            'user' => $this->userResponse($user),
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a specific user.
     */
    public function show(string $id): JsonResponse
    {
        $user = User::where('tenant_id', tenant()->getTenantKey())
            ->with('roles')
            ->findOrFail($id);

        return response()->json(['user' => $this->userResponse($user)]);
    }

    /**
     * Update a user.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::where('tenant_id', tenant()->getTenantKey())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'locale' => 'nullable|string|in:pt_BR,en,es',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->update($validator->validated());

        return response()->json([
            'message' => __('core.user_updated'),
            'user' => $this->userResponse($user),
        ]);
    }

    /**
     * Delete (soft-delete) a user.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = User::where('tenant_id', tenant()->getTenantKey())->findOrFail($id);

        if ($user->id === request()->user()->id) {
            return response()->json(['message' => __('core.cannot_delete_self')], Response::HTTP_FORBIDDEN);
        }

        $user->delete();

        return response()->json(['message' => __('core.user_deleted')]);
    }

    /**
     * Update a user's role.
     */
    public function updateRole(Request $request, string $id): JsonResponse
    {
        $user = User::where('tenant_id', tenant()->getTenantKey())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'role' => 'required|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => __('core.role_updated'),
            'user' => $this->userResponse($user),
        ]);
    }

    /**
     * Invite a user via email (placeholder).
     */
    public function invite(Request $request, string $id): JsonResponse
    {
        $user = User::where('tenant_id', tenant()->getTenantKey())->findOrFail($id);

        // TODO: send invitation email
        // Mail::to($user)->send(new UserInvitation($user));

        return response()->json([
            'message' => __('core.invitation_sent', ['email' => $user->email]),
        ]);
    }

    /**
     * List available roles.
     */
    public function roles(): JsonResponse
    {
        $roles = Role::all()->map(fn (Role $role): array => [
            'id' => $role->id,
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'permissions_count' => $role->permissions->count(),
        ]);

        return response()->json(['roles' => $roles]);
    }

    /**
     * List available permissions.
     */
    public function permissions(): JsonResponse
    {
        $permissions = \Spatie\Permission\Models\Permission::all()->pluck('name');

        return response()->json(['permissions' => $permissions]);
    }

    private function userResponse(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
            'is_active' => $user->is_active,
            'roles' => $user->getRoleNames(),
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
