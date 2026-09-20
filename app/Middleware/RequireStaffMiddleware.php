<?php
// Master Arena SaaS - Staff, Admin and Superadmin Middleware
// Comments strictly in ASCII only.

namespace App\Middleware;

class RequireStaffMiddleware extends RoleMiddleware
{
    protected array $allowedRoles = ['SUPERADMIN', 'ADMIN', 'FUNCIONARIO'];
}
