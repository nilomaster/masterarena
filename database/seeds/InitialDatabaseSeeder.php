<?php
// Master Arena SaaS - Initial Database Seeder
// Comments strictly in ASCII only.

namespace Database\Seeds;

use PDO;

class InitialDatabaseSeeder
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function run(): void
    {
        echo "====================================================\n";
        echo " MASTER ARENA - Seeding Initial Database Records\n";
        echo "====================================================\n";

        $this->seedPlans();
        $arenaId = $this->seedArena();
        $this->seedSubscription($arenaId);
        $this->seedUsers($arenaId);
        $modalidadeIds = $this->seedModalidades($arenaId);
        $quadraIds = $this->seedQuadras($arenaId, $modalidadeIds);
        $this->seedHorariosAndValores($arenaId, $quadraIds, $modalidadeIds);
        $clienteId = $this->seedClients($arenaId);
        $this->seedCouponsAndPromotions($arenaId);

        echo "Database seeding completed successfully.\n";
    }

    // Seed SaaS subscription plans
    private function seedPlans(): void
    {
        echo "Seeding plans ... ";
        $plans = [
            [
                'nome' => 'Plano Start',
                'slug' => 'plano-start',
                'descricao' => 'Ideal para arenas de pequeno porte com ate 2 quadras.',
                'preco_mensal' => 99.00,
                'limite_quadras' => 2,
                'limite_usuarios' => 3,
                'limite_agendamentos' => 300,
            ],
            [
                'nome' => 'Plano Pro',
                'slug' => 'plano-pro',
                'descricao' => 'Para arenas de medio porte em expansao com ate 6 quadras.',
                'preco_mensal' => 199.00,
                'limite_quadras' => 6,
                'limite_usuarios' => 10,
                'limite_agendamentos' => 1500,
            ],
            [
                'nome' => 'Plano Premium',
                'slug' => 'plano-premium',
                'descricao' => 'Complexos esportivos de grande porte sem limites.',
                'preco_mensal' => 349.00,
                'limite_quadras' => 20,
                'limite_usuarios' => 50,
                'limite_agendamentos' => 10000,
            ],
        ];

        $stmt = $this->pdo->prepare("INSERT INTO `planos` 
            (`nome`, `slug`, `descricao`, `preco_mensal`, `limite_quadras`, `limite_usuarios`, `limite_agendamentos`) 
            VALUES (:nome, :slug, :descricao, :preco_mensal, :limite_quadras, :limite_usuarios, :limite_agendamentos)
            ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `preco_mensal` = VALUES(`preco_mensal`);");

        foreach ($plans as $plan) {
            $stmt->execute($plan);
        }
        echo "[OK]\n";
    }

    // Seed demonstration arena
    private function seedArena(): int
    {
        echo "Seeding demo arena ... ";
        $stmt = $this->pdo->prepare("SELECT `id` FROM `arenas` WHERE `slug` = :slug");
        $stmt->execute([':slug' => 'arena-master-beach']);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            echo "[OK (Exists: #{$existingId})]\n";
            return (int)$existingId;
        }

        $insertStmt = $this->pdo->prepare("INSERT INTO `arenas` 
            (`slug`, `nome_arena`, `nome_fantasia`, `cnpj`, `telefone`, `whatsapp`, `email`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`, `instagram`, `facebook`, `site`, `google_maps_url`, `descricao`, `status`) 
            VALUES 
            (:slug, :nome_arena, :nome_fantasia, :cnpj, :telefone, :whatsapp, :email, :endereco, :numero, :bairro, :cidade, :estado, :cep, :instagram, :facebook, :site, :google_maps_url, :descricao, 'ATIVO')");

        $insertStmt->execute([
            ':slug' => 'arena-master-beach',
            ':nome_arena' => 'Arena Master Beach & Sports',
            ':nome_fantasia' => 'Master Arena Clube',
            ':cnpj' => '12.345.678/0001-90',
            ':telefone' => '1134567890',
            ':whatsapp' => '5511999998888',
            ':email' => 'contato@masterarena.com.br',
            ':endereco' => 'Av. das Nacoes Unidas',
            ':numero' => '1200',
            ':bairro' => 'Brooklin',
            ':cidade' => 'Sao Paulo',
            ':estado' => 'SP',
            ':cep' => '04578-000',
            ':instagram' => '@masterarenaclube',
            ':facebook' => 'fb.com/masterarenaclube',
            ':site' => 'https://masterarena.com.br',
            ':google_maps_url' => 'https://maps.google.com/?q=Master+Arena+Clube',
            ':descricao' => 'A melhor estrutura para esportes de areia e quadras society da regiao.',
        ]);

        $arenaId = (int)$this->pdo->lastInsertId();
        echo "[OK (#{$arenaId})]\n";
        return $arenaId;
    }

    // Seed arena subscription
    private function seedSubscription(int $arenaId): void
    {
        echo "Seeding arena subscription ... ";
        $planStmt = $this->pdo->prepare("SELECT `id`, `limite_quadras`, `limite_usuarios`, `limite_agendamentos` FROM `planos` WHERE `slug` = 'plano-pro'");
        $planStmt->execute();
        $plan = $planStmt->fetch();

        if (!$plan) {
            echo "[SKIPPED]\n";
            return;
        }

        $checkStmt = $this->pdo->prepare("SELECT `id` FROM `assinaturas` WHERE `arena_id` = :arena_id");
        $checkStmt->execute([':arena_id' => $arenaId]);
        if ($checkStmt->fetchColumn()) {
            echo "[OK (Exists)]\n";
            return;
        }

        $subStmt = $this->pdo->prepare("INSERT INTO `assinaturas` 
            (`arena_id`, `plano_id`, `data_inicio`, `data_expiracao`, `status`, `limite_quadras`, `limite_usuarios`, `limite_agendamentos`) 
            VALUES 
            (:arena_id, :plano_id, CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 1 YEAR), 'ATIVA', :limite_quadras, :limite_usuarios, :limite_agendamentos)");

        $subStmt->execute([
            ':arena_id' => $arenaId,
            ':plano_id' => $plan['id'],
            ':limite_quadras' => $plan['limite_quadras'],
            ':limite_usuarios' => $plan['limite_usuarios'],
            ':limite_agendamentos' => $plan['limite_agendamentos'],
        ]);
        echo "[OK]\n";
    }

    // Seed default administrative and staff users
    private function seedUsers(int $arenaId): void
    {
        echo "Seeding users ... ";
        $users = [
            [
                'arena_id' => null, // Superadmin is global across SaaS
                'nome' => 'Super Admin MasterDev',
                'email' => 'superadmin@masterarena.com.br',
                'senha' => 'Admin@123456',
                'telefone' => '11999990000',
                'perfil' => 'SUPERADMIN',
            ],
            [
                'arena_id' => $arenaId,
                'nome' => 'Administrador da Arena',
                'email' => 'admin@masterarena.com.br',
                'senha' => 'Arena@123456',
                'telefone' => '11999991111',
                'perfil' => 'ADMIN',
            ],
            [
                'arena_id' => $arenaId,
                'nome' => 'Atendente da Arena',
                'email' => 'atendente@masterarena.com.br',
                'senha' => 'Staff@123456',
                'telefone' => '11999992222',
                'perfil' => 'FUNCIONARIO',
            ],
        ];

        $stmt = $this->pdo->prepare("INSERT INTO `usuarios` 
            (`arena_id`, `nome`, `email`, `senha_hash`, `telefone`, `perfil`, `status`) 
            VALUES (:arena_id, :nome, :email, :senha_hash, :telefone, :perfil, 'ATIVO')
            ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`), `perfil` = VALUES(`perfil`), `senha_hash` = VALUES(`senha_hash`);");

        foreach ($users as $u) {
            $stmt->execute([
                ':arena_id' => $u['arena_id'],
                ':nome' => $u['nome'],
                ':email' => $u['email'],
                ':senha_hash' => password_hash($u['senha'], PASSWORD_BCRYPT),
                ':telefone' => $u['telefone'],
                ':perfil' => $u['perfil'],
            ]);
        }
        echo "[OK]\n";
    }

    // Seed sports modalities
    private function seedModalidades(int $arenaId): array
    {
        echo "Seeding sports modalities ... ";
        $modalidades = [
            ['nome' => 'Beach Tennis', 'descricao' => 'Quadra de areia oficial para Beach Tennis', 'icone' => 'sports_tennis'],
            ['nome' => 'Volei de Areia', 'descricao' => 'Quadra de areia com rede oficial de volei', 'icone' => 'sports_volleyball'],
            ['nome' => 'Futevolei', 'descricao' => 'Quadra de areia para futevolei', 'icone' => 'sports_soccer'],
            ['nome' => 'Futebol Society', 'descricao' => 'Campo sintetico com iluminacao LED', 'icone' => 'sports_soccer'],
        ];

        $stmt = $this->pdo->prepare("INSERT INTO `modalidades` (`arena_id`, `nome`, `descricao`, `icone`, `ativo`) 
            VALUES (:arena_id, :nome, :descricao, :icone, 1)
            ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);");

        $idMap = [];
        foreach ($modalidades as $m) {
            $stmt->execute([
                ':arena_id' => $arenaId,
                ':nome' => $m['nome'],
                ':descricao' => $m['descricao'],
                ':icone' => $m['icone'],
            ]);

            $idStmt = $this->pdo->prepare("SELECT `id` FROM `modalidades` WHERE `arena_id` = :arena_id AND `nome` = :nome");
            $idStmt->execute([':arena_id' => $arenaId, ':nome' => $m['nome']]);
            $idMap[$m['nome']] = (int)$idStmt->fetchColumn();
        }
        echo "[OK]\n";
        return $idMap;
    }

    // Seed arena courts
    private function seedQuadras(int $arenaId, array $modalidadeIds): array
    {
        echo "Seeding courts ... ";
        $courts = [
            ['nome' => 'Quadra 01 - Beach Tennis Central', 'modalidade' => 'Beach Tennis', 'capacidade' => 4, 'valor' => 80.00],
            ['nome' => 'Quadra 02 - Volei de Areia', 'modalidade' => 'Volei de Areia', 'capacidade' => 6, 'valor' => 70.00],
            ['nome' => 'Quadra 03 - Futevolei', 'modalidade' => 'Futevolei', 'capacidade' => 4, 'valor' => 70.00],
            ['nome' => 'Quadra 04 - Society Master', 'modalidade' => 'Futebol Society', 'capacidade' => 14, 'valor' => 160.00],
        ];

        $stmt = $this->pdo->prepare("INSERT INTO `quadras` 
            (`arena_id`, `modalidade_id`, `nome`, `descricao`, `capacidade`, `valor_padrao`, `status`) 
            VALUES (:arena_id, :modalidade_id, :nome, :descricao, :capacidade, :valor_padrao, 'ATIVO')");

        $courtIds = [];
        foreach ($courts as $c) {
            $checkStmt = $this->pdo->prepare("SELECT `id` FROM `quadras` WHERE `arena_id` = :arena_id AND `nome` = :nome");
            $checkStmt->execute([':arena_id' => $arenaId, ':nome' => $c['nome']]);
            $existingId = $checkStmt->fetchColumn();

            if ($existingId) {
                $courtIds[] = (int)$existingId;
                continue;
            }

            $modId = $modalidadeIds[$c['modalidade']] ?? 1;
            $stmt->execute([
                ':arena_id' => $arenaId,
                ':modalidade_id' => $modId,
                ':nome' => $c['nome'],
                ':descricao' => 'Espaco equipado com iluminacao profissional e vestiarios.',
                ':capacidade' => $c['capacidade'],
                ':valor_padrao' => $c['valor'],
            ]);
            $courtIds[] = (int)$this->pdo->lastInsertId();
        }
        echo "[OK]\n";
        return $courtIds;
    }

    // Seed standard operating hours and pricing
    private function seedHorariosAndValores(int $arenaId, array $courtIds, array $modalidadeIds): void
    {
        echo "Seeding operating hours and pricing ... ";
        // Hours configuration: Monday (1) to Sunday (0), 07:00 to 23:00
        $horarioStmt = $this->pdo->prepare("INSERT INTO `horarios` 
            (`arena_id`, `quadra_id`, `dia_semana`, `hora_inicio`, `hora_fim`, `duracao_minutos`, `intervalo_minutos`, `ativo`) 
            VALUES (:arena_id, NULL, :dia_semana, '07:00:00', '23:00:00', 60, 0, 1)");

        for ($day = 0; $day <= 6; $day++) {
            $checkStmt = $this->pdo->prepare("SELECT `id` FROM `horarios` WHERE `arena_id` = :arena_id AND `dia_semana` = :dia_semana AND `quadra_id` IS NULL");
            $checkStmt->execute([':arena_id' => $arenaId, ':dia_semana' => $day]);
            if (!$checkStmt->fetchColumn()) {
                $horarioStmt->execute([
                    ':arena_id' => $arenaId,
                    ':dia_semana' => $day,
                ]);
            }
        }

        // Pricing rules: Mon-Fri daytime (07:00 - 18:00) = R$ 60,00; Mon-Fri prime (18:00 - 23:00) = R$ 90,00
        $valStmt = $this->pdo->prepare("INSERT INTO `valores_horarios` 
            (`arena_id`, `quadra_id`, `modalidade_id`, `dia_semana`, `hora_inicio`, `hora_fim`, `valor`) 
            VALUES (:arena_id, NULL, NULL, :dia_semana, :hora_inicio, :hora_fim, :valor)");

        // Weekdays (1 to 5)
        for ($day = 1; $day <= 5; $day++) {
            $checkVal = $this->pdo->prepare("SELECT `id` FROM `valores_horarios` WHERE `arena_id` = :arena_id AND `dia_semana` = :dia_semana");
            $checkVal->execute([':arena_id' => $arenaId, ':dia_semana' => $day]);
            if (!$checkVal->fetchColumn()) {
                // Daytime slot
                $valStmt->execute([
                    ':arena_id' => $arenaId,
                    ':dia_semana' => $day,
                    ':hora_inicio' => '07:00:00',
                    ':hora_fim' => '18:00:00',
                    ':valor' => 60.00,
                ]);
                // Prime night slot
                $valStmt->execute([
                    ':arena_id' => $arenaId,
                    ':dia_semana' => $day,
                    ':hora_inicio' => '18:00:00',
                    ':hora_fim' => '23:00:00',
                    ':valor' => 90.00,
                ]);
            }
        }
        echo "[OK]\n";
    }

    // Seed test clients
    private function seedClients(int $arenaId): int
    {
        echo "Seeding client ... ";
        $checkStmt = $this->pdo->prepare("SELECT `id` FROM `clientes` WHERE `arena_id` = :arena_id AND `whatsapp` = :whatsapp");
        $checkStmt->execute([':arena_id' => $arenaId, ':whatsapp' => '5511988887777']);
        $existingId = $checkStmt->fetchColumn();

        if ($existingId) {
            echo "[OK (Exists: #{$existingId})]\n";
            return (int)$existingId;
        }

        $stmt = $this->pdo->prepare("INSERT INTO `clientes` 
            (`arena_id`, `nome`, `telefone`, `whatsapp`, `cpf`, `email`, `observacao`) 
            VALUES 
            (:arena_id, :nome, :telefone, :whatsapp, :cpf, :email, :observacao)");

        $stmt->execute([
            ':arena_id' => $arenaId,
            ':nome' => 'Carlos Eduardo Silva',
            ':telefone' => '11988887777',
            ':whatsapp' => '5511988887777',
            ':cpf' => '123.456.789-00',
            ':email' => 'carlos.silva@gmail.com',
            ':observacao' => 'Cliente assiduo de Beach Tennis.',
        ]);

        $clientId = (int)$this->pdo->lastInsertId();
        echo "[OK (#{$clientId})]\n";
        return $clientId;
    }

    // Seed coupons and promotions
    private function seedCouponsAndPromotions(int $arenaId): void
    {
        echo "Seeding coupons and promotions ... ";
        // Coupons
        $coupons = [
            [
                'codigo' => 'BEMVINDO10',
                'descricao' => 'Cupom de boas-vindas com 10% de desconto',
                'tipo' => 'PERCENTUAL',
                'valor' => 10.00,
                'data_inicio' => date('Y-m-d'),
                'data_fim' => date('Y-m-d', strtotime('+1 year')),
                'limite_uso' => 100,
                'limite_por_cliente' => 1,
            ],
            [
                'codigo' => 'MASTER20',
                'descricao' => 'Desconto fixo de R$ 20,00 na primeira reserva',
                'tipo' => 'VALOR_FIXO',
                'valor' => 20.00,
                'data_inicio' => date('Y-m-d'),
                'data_fim' => date('Y-m-d', strtotime('+1 year')),
                'limite_uso' => 50,
                'limite_por_cliente' => 1,
            ],
        ];

        $couponStmt = $this->pdo->prepare("INSERT INTO `cupons` 
            (`arena_id`, `codigo`, `descricao`, `tipo`, `valor`, `data_inicio`, `data_fim`, `limite_uso`, `limite_por_cliente`, `ativo`) 
            VALUES (:arena_id, :codigo, :descricao, :tipo, :valor, :data_inicio, :data_fim, :limite_uso, :limite_por_cliente, 1)
            ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `ativo` = 1;");

        foreach ($coupons as $c) {
            $couponStmt->execute(array_merge([':arena_id' => $arenaId], [
                ':codigo' => $c['codigo'],
                ':descricao' => $c['descricao'],
                ':tipo' => $c['tipo'],
                ':valor' => $c['valor'],
                ':data_inicio' => $c['data_inicio'],
                ':data_fim' => $c['data_fim'],
                ':limite_uso' => $c['limite_uso'],
                ':limite_por_cliente' => $c['limite_por_cliente'],
            ]));
        }

        // Promotion
        $checkPromo = $this->pdo->prepare("SELECT `id` FROM `promocoes` WHERE `arena_id` = :arena_id AND `nome` = :nome");
        $checkPromo->execute([':arena_id' => $arenaId, ':nome' => 'QUARTA DO BEACH']);
        if (!$checkPromo->fetchColumn()) {
            $promoStmt = $this->pdo->prepare("INSERT INTO `promocoes` 
                (`arena_id`, `nome`, `descricao`, `quadra_id`, `modalidade_id`, `dia_semana`, `hora_inicio`, `hora_fim`, `valor_promocional`, `data_inicio`, `data_fim`, `ativo`) 
                VALUES 
                (:arena_id, 'QUARTA DO BEACH', 'Preco especial de R$ 50 para quadras de areia nas tardes de quarta', NULL, NULL, 3, '14:00:00', '17:00:00', 50.00, CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 6 MONTH), 1)");
            $promoStmt->execute([':arena_id' => $arenaId]);
        }

        echo "[OK]\n";
    }
}
