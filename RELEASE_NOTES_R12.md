# R12 SAFE RECOVERY — Release Notes

**Versão registrada:** 0.4.7  
**Build:** 2026.09.09-r12-safe-recovery-zabbix-parity

## Objetivo

Recuperar o carregamento seguro no GLPI 11, restaurar os datasets GLPI/Zabbix e manter a disponibilidade alinhada à máquina de estados de trigger usada pelo relatório do Zabbix.

## Principais correções

- mantém os datasets GLPI necessários pela interface;
- restaura a listagem de hosts da configuração Zabbix;
- corrige a navegação para telas que exigem contexto backend;
- evita escolha arbitrária quando um item possui múltiplos triggers;
- disponibilidade baseada em tempo OK/PROBLEM;
- falhas baseadas em eventos PROBLEM;
- justificativas não adulteram o indicador Zabbix;
- agregados ponderados pelo tempo calculável;
- pré-carga em lote de itens, triggers e eventos;
- cache por requisição de dados administrativos e Zabbix;
- contagens simples usam COUNT no banco;
- recuperação segura do estado “plugin not loaded”.

## Compatibilidade

Não altera a versão registrada além de 0.4.7 e não introduz uma mudança de schema específica da R12.

## Atualização segura

```sh
sh plugins/indicadores/tools/r12-safe-recover.sh
```

O script não executa uninstall/install/update forçado.
