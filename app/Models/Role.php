<?php

declare(strict_types=1);

namespace App\Models;

use DirectoryTree\Authorization\Role as DirectoryRole;
use DirectoryTree\Authorization\Traits\ManagesPermissions;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class Role extends DirectoryRole
{
    use HasUuids;
    use ManagesPermissions;
}
