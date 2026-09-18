# MASTER ARENA — Sistema SaaS de Agendamento e Gestão de Arenas

Sistema profissional SaaS para gerenciamento de complexos esportivos, quadras e arenas (Beach Tennis, Vôlei de Areia, Futebol Society, Futevôlei, Quadras Poliesportivas).

Desenvolvido por **MasterDev Solutions**.

## Arquitetura
* **Backend / API:** PHP 8.1+ / 8.3+, MySQL 8+ / MariaDB 10.4+, PDO, REST JSON
* **Frontend Web:** HTML5, CSS3, Tailwind CSS, JavaScript Fetch API
* **Desktop App:** C# .NET Windows Forms
* **Multi-Arena SaaS:** Isolamento estrito por `arena_id`

## Regra Fundamental
```text
SITE WEB -> API PHP -> MYSQL
DESKTOP C# -> API PHP -> MYSQL
```
O frontend e o cliente desktop nunca acessam o banco de dados diretamente.
