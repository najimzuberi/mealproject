<?php

namespace App\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Activated = 'activated';
    case Deactivated = 'deactivated';
    case Login = 'login';
    case Logout = 'logout';
    case PermissionChanged = 'permission_changed';
    case RoleAssigned = 'role_assigned';
    case RoleRemoved = 'role_removed';
}
