<?php
// Master Arena SaaS - Base Migration Class
// Comments strictly in ASCII only.

namespace Database;

use PDO;

abstract class Migration
{
    // Execute the migration forward
    abstract public function up(PDO $pdo): void;

    // Revert the migration backwards
    abstract public function down(PDO $pdo): void;
}
