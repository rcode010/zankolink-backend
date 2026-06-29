<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'is_two_factor_enabled' => $this->is_two_factor_enabled,

            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->values();
            }),

            'scopes' => $this->whenLoaded('userScopes', function () {
                return $this->userScopes->map(fn ($scope) => [
                    'user_scope_id' => $scope->id,
                    'role_id' => $scope->role_id,
                    'role_name' => $scope->role?->name,
                    'scope_type' => $scope->scope_type,
                    'scope_id' => $scope->scope_id,
                ])->values();
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
