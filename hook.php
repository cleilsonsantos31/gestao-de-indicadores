<?php

declare(strict_types=1);

function plugin_indicadores_install(): bool
{
    global $DB;

    $migration = new Migration(470);

    if (!$DB->tableExists('glpi_plugin_indicadores_indicators')) {
        $DB->doQuery(
            "CREATE TABLE `glpi_plugin_indicadores_indicators` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `code` varchar(100) NOT NULL,
                `name` varchar(255) NOT NULL,
                `description` text NULL,
                `category` varchar(100) NULL,
                `unit` varchar(50) NULL,
                `target_value` decimal(18,4) NULL,
                `target_operator` varchar(20) NULL,
                `direction` varchar(30) NOT NULL DEFAULT 'INFORMATIVO',
                `formula` text NULL,
                `formula_status` varchar(30) NOT NULL DEFAULT 'PENDENTE',
                `periodicity` varchar(30) NULL,
                `responsible` varchar(255) NULL,
                `decimals` tinyint unsigned NOT NULL DEFAULT 2,
                `color` varchar(30) NULL,
                `is_active` tinyint NOT NULL DEFAULT 1,
                `sort_order` int NOT NULL DEFAULT 0,
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uniq_code` (`code`),
                KEY `is_active` (`is_active`),
                KEY `sort_order` (`sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
        );
    }

    if (!$DB->tableExists('glpi_plugin_indicadores_goals')) {
        $DB->doQuery(
            "CREATE TABLE `glpi_plugin_indicadores_goals` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `plugin_indicadores_indicators_id` int unsigned NOT NULL,
                `target_value` decimal(18,4) NULL,
                `target_operator` varchar(20) NULL,
                `attention_min` decimal(18,4) NULL,
                `attention_max` decimal(18,4) NULL,
                `start_date` date NOT NULL,
                `end_date` date NULL,
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `indicator` (`plugin_indicadores_indicators_id`),
                KEY `validity` (`start_date`, `end_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
        );
    }

    if (!$DB->tableExists('glpi_plugin_indicadores_zabbix_exclusions')) {
        $DB->doQuery(
            "CREATE TABLE `glpi_plugin_indicadores_zabbix_exclusions` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `entities_id` int unsigned NOT NULL,
                `zabbix_hostid` bigint unsigned NOT NULL,
                `outage_key` varchar(190) NOT NULL,
                `start_at` datetime NOT NULL,
                `end_at` datetime NOT NULL,
                `reason` varchar(100) NOT NULL,
                `justification` text NOT NULL,
                `approved` tinyint NOT NULL DEFAULT 0,
                `users_id` int unsigned NOT NULL,
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `entity_host` (`entities_id`, `zabbix_hostid`),
                KEY `period` (`start_at`, `end_at`),
                KEY `approved` (`approved`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
        );
    }


    if (!$DB->tableExists('glpi_plugin_indicadores_zabbix_mappings')) {
        $DB->doQuery(
            "CREATE TABLE `glpi_plugin_indicadores_zabbix_mappings` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `entities_id` int unsigned NOT NULL,
                `zabbix_hostid` bigint unsigned NOT NULL,
                `zabbix_host_name` varchar(255) NOT NULL,
                `locations_id` int unsigned NOT NULL DEFAULT 0,
                `groups_id` int unsigned NOT NULL DEFAULT 0,
                `resource_type` varchar(100) NULL,
                `is_monitored` tinyint NOT NULL DEFAULT 1,
                `date_creation` timestamp NULL DEFAULT NULL,
                `date_mod` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `entity_host` (`entities_id`, `zabbix_hostid`),
                KEY `location` (`locations_id`),
                KEY `group_id` (`groups_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
        );
    }

    if (!$DB->tableExists('glpi_plugin_indicadores_audits')) {
        $DB->doQuery(
            "CREATE TABLE `glpi_plugin_indicadores_audits` (
                `id` int unsigned NOT NULL AUTO_INCREMENT,
                `entities_id` int unsigned NOT NULL,
                `users_id` int unsigned NOT NULL,
                `action` varchar(100) NOT NULL,
                `object_type` varchar(100) NOT NULL,
                `object_id` varchar(190) NULL,
                `old_value` longtext NULL,
                `new_value` longtext NULL,
                `ip` varchar(64) NULL,
                `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `entity_date` (`entities_id`, `date_creation`),
                KEY `user_date` (`users_id`, `date_creation`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC"
        );
    }


    if ($DB->tableExists('glpi_plugin_indicadores_indicators')) {
        if (!$DB->fieldExists('glpi_plugin_indicadores_indicators', 'decimals')) {
            $DB->doQuery(
                "ALTER TABLE `glpi_plugin_indicadores_indicators`
                 ADD `decimals` tinyint unsigned NOT NULL DEFAULT 2 AFTER `responsible`"
            );
        }

        if (!$DB->fieldExists('glpi_plugin_indicadores_indicators', 'color')) {
            $DB->doQuery(
                "ALTER TABLE `glpi_plugin_indicadores_indicators`
                 ADD `color` varchar(30) NULL AFTER `decimals`"
            );
        }
    }

    $migration->executeMigration();

    seed_plugin_indicadores_indicators();

    // Mantém os indicadores nativos sincronizados com as fórmulas realmente
    // executadas pelo plugin. As metas personalizadas não são sobrescritas.
    if ($DB->tableExists('glpi_plugin_indicadores_indicators')) {
        $builtin_updates = [
            'PRAZO' => [
                'name' => '% Chamados Dentro do SLA',
                'description' => 'Percentual de tickets solucionados dentro ou exatamente no prazo do SLA de solução, entre os tickets solucionados que possuem data limite de solução válida.',
                'direction' => 'MAIOR É MELHOR',
                'target_value' => 90,
                'target_operator' => '>=',
                'formula' => '(% Chamados Dentro do SLA) = (Quantidade de tickets com solvedate menor ou igual a time_to_resolve ÷ Quantidade de tickets solucionados com time_to_resolve válido) × 100',
                'formula_status' => 'VALIDADA',
            ],
            'REABERTOS' => [
                'name' => '% Chamados Reabertos',
                'description' => 'Percentual de tickets cuja solução foi recusada pelo solicitante, entre os tickets que receberam solução no período.',
                'direction' => 'MENOR É MELHOR',
                'target_value' => 2,
                'target_operator' => '<',
                'formula' => '(% Chamados Reabertos) = (Quantidade de tickets distintos com solução recusada no período ÷ Quantidade de tickets distintos que receberam solução no período) × 100',
                'formula_status' => 'VALIDADA',
            ],
            'NPS' => [
                'name' => 'NPS',
                'description' => 'Net Promoter Score calculado somente quando as faixas de Promotor, Neutro e Detrator estiverem habilitadas na configuração.',
                'direction' => 'MAIOR É MELHOR',
                'formula' => 'NPS = ((Quantidade de Promotores - Quantidade de Detratores) ÷ Total de pesquisas respondidas) × 100',
                'formula_status' => 'VALIDADA',
            ],
        ];

        foreach ($builtin_updates as $code => $values) {
            $DB->update(
                'glpi_plugin_indicadores_indicators',
                $values + ['date_mod' => date('Y-m-d H:i:s')],
                ['code' => $code]
            );
        }
    }

    // Normaliza apenas metas legadas criadas pelas versões que usavam
    // a semântica incorreta "% fora do SLA". Metas personalizadas diferentes
    // desses valores não são sobrescritas.
    if (
        $DB->tableExists('glpi_plugin_indicadores_indicators')
        && $DB->tableExists('glpi_plugin_indicadores_goals')
    ) {
        $builtinGoalFixes = [
            'PRAZO' => [
                'legacy_values' => [10.0],
                'legacy_operators' => ['<=', '<'],
                'target_value' => 90,
                'target_operator' => '>=',
            ],
            'REABERTOS' => [
                'legacy_values' => [90.0, 10.0],
                'legacy_operators' => ['>=', '>'],
                'target_value' => 2,
                'target_operator' => '<',
            ],
        ];

        foreach ($builtinGoalFixes as $code => $fix) {
            $indicatorIterator = $DB->request([
                'SELECT' => ['id'],
                'FROM' => 'glpi_plugin_indicadores_indicators',
                'WHERE' => ['code' => $code],
                'LIMIT' => 1,
            ]);

            $indicatorId = 0;
            foreach ($indicatorIterator as $indicatorRow) {
                $indicatorId = (int) $indicatorRow['id'];
                break;
            }

            if ($indicatorId <= 0) {
                continue;
            }

            $goalIterator = $DB->request([
                'FROM' => 'glpi_plugin_indicadores_goals',
                'WHERE' => [
                    'plugin_indicadores_indicators_id' => $indicatorId,
                ],
            ]);

            foreach ($goalIterator as $goal) {
                $value = $goal['target_value'] !== null ? (float) $goal['target_value'] : null;
                $operator = (string) ($goal['target_operator'] ?? '');

                if (
                    ($value !== null && in_array($value, $fix['legacy_values'], true))
                    || in_array($operator, $fix['legacy_operators'], true)
                ) {
                    $DB->update(
                        'glpi_plugin_indicadores_goals',
                        [
                            'target_value' => $fix['target_value'],
                            'target_operator' => $fix['target_operator'],
                            'date_mod' => date('Y-m-d H:i:s'),
                        ],
                        ['id' => (int) $goal['id']]
                    );
                }
            }
        }
    }

    $defaults = [
        'zabbix_enabled' => 0,
        'zabbix_driver' => 'mysql',
        'zabbix_host' => '',
        'zabbix_port' => 3306,
        'zabbix_database' => 'zabbix',
        'zabbix_user' => '',
        'zabbix_password' => '',
        'zabbix_ping_keys' => 'icmpping,agent.ping,zabbix[host,agent,available]',
        'zabbix_refresh_seconds' => 60,
        'satisfaction_classification_enabled' => 0,
        'satisfaction_promoter_min' => 5,
        'satisfaction_neutral_min' => 4,
        'satisfaction_detractor_max' => 3,
    ];
    $current = Config::getConfigurationValues('plugin:indicadores');
    $missing = array_diff_key($defaults, $current);
    if ($missing !== []) {
        Config::setConfigurationValues('plugin:indicadores', $missing);
    }

    return true;
}

function seed_plugin_indicadores_indicators(): void
{
    global $DB;

    $rows = [
        [
            'code' => 'PRAZO',
            'name' => '% Chamados Dentro do SLA',
            'description' => 'Percentual de tickets solucionados dentro ou exatamente no prazo do SLA de solução entre os tickets solucionados que possuem data limite de solução.',
            'category' => 'SLA',
            'unit' => '%',
            'target_value' => 90,
            'target_operator' => '>=',
            'direction' => 'MAIOR É MELHOR',
            'formula' => '(% Chamados Dentro do SLA) = (Quantidade de tickets com solvedate menor ou igual a time_to_resolve ÷ Quantidade de tickets solucionados com time_to_resolve válido) × 100',
            'formula_status' => 'VALIDADA',
            'periodicity' => 'Mensal',
            'responsible' => '',
            'is_active' => 1,
            'sort_order' => 10,
        ],
        [
            'code' => 'REABERTOS',
            'name' => '% Chamados Reabertos',
            'description' => 'Percentual de tickets cuja solução foi recusada pelo solicitante, entre os tickets que receberam solução no período.',
            'category' => 'Qualidade',
            'unit' => '%',
            'target_value' => 2,
            'target_operator' => '<',
            'direction' => 'MENOR É MELHOR',
            'formula' => '(% Chamados Reabertos) = (Quantidade de tickets distintos com solução recusada no período ÷ Quantidade de tickets distintos que receberam solução no período) × 100',
            'formula_status' => 'VALIDADA',
            'periodicity' => 'Mensal',
            'responsible' => '',
            'is_active' => 1,
            'sort_order' => 20,
        ],
        [
            'code' => 'NPS',
            'name' => 'NPS',
            'description' => 'Indicador NPS conforme metodologia configurada no indicador.',
            'category' => 'Satisfação',
            'unit' => '%',
            'target_value' => 90,
            'target_operator' => '>=',
            'direction' => 'MAIOR É MELHOR',
            'formula' => 'NPS = ((Quantidade de Promotores - Quantidade de Detratores) ÷ Total de pesquisas respondidas) × 100',
            'formula_status' => 'VALIDADA',
            'periodicity' => 'Mensal',
            'responsible' => '',
            'is_active' => 1,
            'sort_order' => 30,
        ],
    ];

    foreach ($rows as $row) {
        $exists = $DB->request([
            'FROM' => 'glpi_plugin_indicadores_indicators',
            'WHERE' => ['code' => $row['code']],
            'LIMIT' => 1,
        ]);

        if (count($exists) === 0) {
            $row['date_creation'] = date('Y-m-d H:i:s');
            $row['date_mod'] = date('Y-m-d H:i:s');
            $DB->insert('glpi_plugin_indicadores_indicators', $row);
        }
    }
}

function plugin_indicadores_uninstall(): bool
{
    global $DB;

    foreach ([
        'glpi_plugin_indicadores_audits',
        'glpi_plugin_indicadores_zabbix_mappings',
        'glpi_plugin_indicadores_zabbix_exclusions',
        'glpi_plugin_indicadores_goals',
        'glpi_plugin_indicadores_indicators',
    ] as $table) {
        if ($DB->tableExists($table)) {
            $DB->doQuery("DROP TABLE `$table`");
        }
    }

    $config = new Config();
    $values = Config::getConfigurationValues('plugin:indicadores');
    if (!empty($values)) {
        $config->deleteConfigurationValues('plugin:indicadores', array_keys($values));
    }

    return true;
}
