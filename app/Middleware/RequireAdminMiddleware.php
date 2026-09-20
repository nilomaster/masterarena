<?php
// Master Arena SaaS - Admin and Superadmin Middleware
// Comments strictly in ASCII only.

namespace App\Middleware;

class RequireAdminMiddleware extends RoleMiddleware
{
    protected array $allowedRoles = ['SUPERADMIN', 'ADMIN'];
}
