<?php

declare(strict_types=1);

namespace App\Models;

use DirectoryTree\Authorization\Permission as DirectoryPermission;
use DirectoryTree\Authorization\Traits\ClearsCachedPermissions;
use DirectoryTree\Authorization\Traits\HasRoles;
use DirectoryTree\Authorization\Traits\HasUsers;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class Permission extends DirectoryPermission
{
    use ClearsCachedPermissions;
    use HasRoles;
    use HasUsers;
    use HasUuids;
}
