<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'permissions', 'invitation_token', 'invitation_expires_at', 'must_set_password'])]
#[Hidden(['password', 'remember_token', 'invitation_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_EDITOR = 'editor';

    public const ROLE_VIEWER = 'viewer';

    public const PERM_MANAGE_CHECKLISTS = 'manage_checklists';

    public const PERM_MANAGE_SHIPMENTS = 'manage_shipments';

    public const PERM_CREATE_DOCUMENTS = 'create_documents';

    public const PERM_EDIT_DOCUMENTS = 'edit_documents';

    public const PERM_DELETE_DOCUMENTS = 'delete_documents';

    public const PERM_RESTORE_VERSIONS = 'restore_versions';

    public const AVAILABLE_PERMISSIONS = [
        self::PERM_MANAGE_CHECKLISTS => [
            'name' => 'Manage Checklist Templates',
            'description' => 'Create, edit, modify, and delete document checklist templates and task items.',
            'category' => 'Checklists',
        ],
        self::PERM_MANAGE_SHIPMENTS => [
            'name' => 'Manage Shipment Trackers',
            'description' => 'Create shipment orders, modify order details, and toggle lifecycle milestone progress.',
            'category' => 'Shipments',
        ],
        self::PERM_CREATE_DOCUMENTS => [
            'name' => 'Create Documents',
            'description' => 'Create new Proforma Invoices and Commercial Invoices with automated calculations.',
            'category' => 'Documents',
        ],
        self::PERM_EDIT_DOCUMENTS => [
            'name' => 'Edit Documents & Packing',
            'description' => 'Edit document line items, packages, diameters, and carrier shipment costs.',
            'category' => 'Documents',
        ],
        self::PERM_DELETE_DOCUMENTS => [
            'name' => 'Delete Documents',
            'description' => 'Permanently remove document records and associated version history.',
            'category' => 'Documents',
        ],
        self::PERM_RESTORE_VERSIONS => [
            'name' => 'Restore Document Versions',
            'description' => 'Rollback or forward-restore historic document snapshots from version history.',
            'category' => 'Documents',
        ],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
            'must_set_password' => 'boolean',
            'password' => 'hashed',
            'permissions' => 'array',
        ];
    }

    public function hasValidInvitation(): bool
    {
        return ! empty($this->invitation_token)
            && $this->invitation_expires_at !== null
            && $this->invitation_expires_at->isFuture();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEditor(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_EDITOR]);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $perms = $this->permissions ?? [];

        return in_array($permission, $perms, true);
    }

    public function canManageChecklists(): bool
    {
        return $this->isAdmin() || $this->hasPermission(self::PERM_MANAGE_CHECKLISTS);
    }

    public function canManageShipments(): bool
    {
        return $this->isAdmin() || $this->isEditor() || $this->hasPermission(self::PERM_MANAGE_SHIPMENTS);
    }

    public function canCreateDocuments(): bool
    {
        return $this->isAdmin() || $this->isEditor() || $this->hasPermission(self::PERM_CREATE_DOCUMENTS);
    }

    public function canEditDocuments(): bool
    {
        return $this->isAdmin() || $this->isEditor() || $this->hasPermission(self::PERM_EDIT_DOCUMENTS);
    }

    public function canEdit(): bool
    {
        return $this->canEditDocuments();
    }

    public function canDeleteDocuments(): bool
    {
        return $this->isAdmin() || $this->hasPermission(self::PERM_DELETE_DOCUMENTS);
    }

    public function canRestoreVersions(): bool
    {
        return $this->isAdmin() || $this->isEditor() || $this->hasPermission(self::PERM_RESTORE_VERSIONS);
    }
}
