<?php
// Master Arena SaaS - Superadmin Only Middleware
// Comments strictly in ASCII only.

namespace App\Middleware;

class RequireSuperadminMiddleware extends RoleMiddleware
{
    protected array $allowedRoles = ['SUPERADMIN'];
}
