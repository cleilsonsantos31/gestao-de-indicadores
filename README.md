# Central de Indicadores para GLPI 11

Plugin para consolidação de indicadores operacionais do GLPI e disponibilidade baseada em triggers do Zabbix.

**Versão registrada:** `0.4.7`  
**Build:** `2026.09.09-r12-safe-recovery-zabbix-parity`  
**Compatibilidade declarada:** GLPI `>= 11.0.0` e `< 12.0.0`  
**Licença:** MIT  
**Autor declarado no plugin:** Cleilson Santos

> A revisão R12 mantém a versão registrada `0.4.7` para evitar que uma simples substituição de arquivos coloque o GLPI no estado “To update / plugin not loaded”.

## O que o plugin faz

A Central de Indicadores reúne dados do GLPI em uma aplicação própria e adiciona uma visão opcional de disponibilidade do Zabbix.

Principais grupos de recursos:

- Dashboard Executivo e Visão Gerencial.
- Backlog atual de tickets.
- Tickets criados e solucionados no período.
- SLA de solução.
- Tickets reabertos por recusa de solução.
- Satisfação e NPS configurável.
- Análises por profissional, unidade, categoria e período.
- Comparações históricas.
- Drill-down com os tickets que compõem cada indicador.
- Exportação CSV e Excel.
- Painel Operacional / TV.
- Catálogo de indicadores, metas e regras.
- Auditoria administrativa.
- Integração com banco Zabbix.
- Disponibilidade/indisponibilidade por trigger.
- Contagem de eventos PROBLEM.
- Detalhamento de períodos de indisponibilidade.
- Mapeamento host Zabbix ↔ localização/equipe GLPI.
- Registro e aprovação de justificativas de indisponibilidade.

## Acesso no GLPI

O menu é registrado em:

**Ferramentas > Central de Indicadores**

Atalhos disponíveis no menu do plugin:

- Central de Indicadores.
- Disponibilidade Zabbix.
- Painel Operacional / TV.
- Administração dos Indicadores, quando o perfil possui direito de configuração.

Rotas principais:

- `/plugins/indicadores/App`
- `/plugins/indicadores/Zabbix`
- `/plugins/indicadores/TV`
- `/plugins/indicadores/Export`
- `/plugins/indicadores/Admin`
- `/plugins/indicadores/Config`

## Telas

### Operacionais

- Dashboard Executivo
- Visão Gerencial
- Em Aberto
- Criados
- Fechados
- Satisfação / NPS
- Promotores
- Neutros
- Detratores
- Histórico de Chamados
- Histórico por Profissional
- Comparações
- Drill-down / Evidências
- Disponibilidade Zabbix
- Eventos / Justificativas
- Alertas e Desvios
- Painel Operacional / TV

### Administração

- Conexão Zabbix
- Catálogo de Indicadores
- Cadastro/Edição de Indicador
- Metas e Regras
- Auditoria
- Mapeamentos GLPI ↔ Zabbix

## Filtros

O backend suporta filtros por:

- período;
- ano;
- mês;
- período personalizado;
- localização/unidade;
- área/equipe atribuída;
- técnico;
- categoria;
- prioridade;
- status;
- tipo do ticket;
- requerente.

A entidade não é escolhida manualmente: o plugin usa a **entidade ativa da sessão** e restringe as consultas de tickets exatamente a `glpi_tickets.entities_id = entidade ativa`.

### Regra importante do backlog

As telas/cards de backlog atual (abertos, atribuídos, não atribuídos, pendentes, fora do prazo e ação agora) **não são recortados pela data de criação**. Isso evita ocultar tickets antigos que continuam abertos.

Os filtros de período são aplicados às métricas de fluxo/resultado, como criados, solucionados, SLA, satisfação, reaberturas e comparações.

## Indicadores nativos

### % Chamados Dentro do SLA (`PRAZO`)

Calculado sobre tickets solucionados que possuem `time_to_resolve` válido:

`tickets com solvedate <= time_to_resolve / tickets solucionados elegíveis * 100`

Meta inicial cadastrada pelo plugin: `>= 90%`.

### % Chamados Reabertos (`REABERTOS`)

Baseado em tickets distintos cuja solução foi recusada pelo solicitante no período, dividido pelos tickets que receberam solução no período.

Meta inicial cadastrada: `< 2%`.

### NPS (`NPS`)

Quando a classificação de satisfação está habilitada:

`(Promotores - Detratores) / total de pesquisas respondidas * 100`

As faixas de Promotor, Neutro e Detrator são configuráveis.

## Tickets que exigem ação

A priorização considera, entre outros fatores:

1. SLA de solução vencido;
2. prioridade mais alta;
3. ticket sem atribuição;
4. aguardando técnico;
5. maior tempo em aberto.

O plugin usa o último acompanhamento público e os atores do ticket para classificar quando a próxima resposta aparenta depender do técnico ou do usuário. Quando não há informação suficiente, não inventa um responsável.

## Zabbix

A integração é opcional e consulta diretamente o banco do Zabbix.

Backends suportados pelo código:

- MySQL/MariaDB: `pdo_mysql` ou fallback `mysqli`;
- PostgreSQL: `pdo_pgsql` ou fallback `pgsql`.

A senha configurada é armazenada usando `GLPIKey`.

### Disponibilidade

A R12 usa a semântica de disponibilidade por **trigger**:

- identifica um item candidato usando as chaves configuradas;
- exige um único trigger habilitado associado ao item;
- obtém o último evento anterior ao início do período;
- processa os eventos do trigger no período;
- acumula tempo em `OK` e `PROBLEM`;
- disponibilidade = `OK / (OK + PROBLEM) * 100`;
- indisponibilidade = `PROBLEM / (OK + PROBLEM) * 100`;
- downtime = tempo do trigger em `PROBLEM`;
- falhas = quantidade de eventos `PROBLEM` (`value=1`) detectados no período.

Se houver zero ou múltiplos triggers habilitados para o item selecionado, o plugin retorna **N/D** em vez de escolher arbitrariamente um trigger.

Justificativas do GLPI são evidências/auditoria e **não reduzem** o downtime oficial exibido.

A configuração de horário operacional foi preservada por compatibilidade, mas não altera o indicador oficial de disponibilidade da R12.

Veja [docs/ZABBIX.md](docs/ZABBIX.md).

## Permissões

O plugin não cria um perfil próprio. Ele usa os direitos nativos do GLPI:

- `ticket READ`: visualizar a Central, TV, relatórios e exportações comuns;
- `ticket UPDATE`: registrar justificativas de indisponibilidade;
- `config READ`: visualizar recursos administrativos;
- `config UPDATE`: alterar indicadores, metas, mapeamentos, configuração Zabbix e aprovar/reprovar justificativas.

Em termos de perfis usuais:

- **Post-Only / Self-Service:** normalmente não é o perfil indicado para a Central completa; precisa ter os direitos nativos exigidos acima.
- **Technician:** normalmente atende à visualização operacional; a capacidade de justificar depende de `ticket UPDATE`.
- **Super-Admin:** possui o conjunto necessário para administração/configuração.

Os direitos efetivos do perfil sempre prevalecem sobre o nome do perfil.

## Instalação

Veja o guia completo em [docs/INSTALLATION.md](docs/INSTALLATION.md).

Resumo para uma instalação nova:

1. Faça backup do GLPI e do banco.
2. Garanta que a pasta final se chame exatamente `indicadores`.
3. Copie para `GLPI_ROOT/plugins/indicadores`.
4. Abra **Configurar > Plugins** no GLPI.
5. Instale o plugin.
6. Ative o plugin.
7. Limpe o cache se necessário.
8. Acesse **Ferramentas > Central de Indicadores**.

## Atualização da R12 sem perder dados

Se já existe uma instalação `0.4.7`, não desinstale o plugin.

1. Faça backup.
2. Substitua somente os arquivos da pasta `plugins/indicadores`.
3. Mantenha o nome da pasta exatamente `indicadores`.
4. Execute:
   ```sh
   sh plugins/indicadores/tools/r12-safe-recover.sh
   ```
5. Confirme que `plugin:list` mostra o plugin habilitado.
6. Reinicie PHP-FPM/container caso exista OPcache persistente.

O script R12 **não executa uninstall, install, update nem DROP TABLE**.

## Atenção ao desinstalar

O método de desinstalação deste plugin remove suas tabelas próprias e seus valores de configuração. Portanto, **não use desinstalação/reinstalação como procedimento normal de atualização ou recuperação**.

Antes de desinstalar, faça backup das tabelas `glpi_plugin_indicadores_*`.

## Estrutura de dados própria

O plugin cria/usa:

- `glpi_plugin_indicadores_indicators`
- `glpi_plugin_indicadores_goals`
- `glpi_plugin_indicadores_zabbix_exclusions`
- `glpi_plugin_indicadores_zabbix_mappings`
- `glpi_plugin_indicadores_audits`

Detalhes em [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## Documentação

- [Instalação e atualização](docs/INSTALLATION.md)
- [Configuração e uso](docs/CONFIGURATION.md)
- [Funcionalidades](docs/FEATURES.md)
- [Integração e cálculo Zabbix](docs/ZABBIX.md)
- [Arquitetura](docs/ARCHITECTURE.md)
- [Permissões](docs/PERMISSIONS.md)
- [Solução de problemas](docs/TROUBLESHOOTING.md)
- [Segurança](SECURITY.md)
- [Changelog](CHANGELOG.md)
- [Notas da R12](RELEASE_NOTES_R12.md)

## Diagnóstico rápido

Na raiz do GLPI:

```sh
sh plugins/indicadores/tools/diagnose-plugin-state.sh
```

Para recuperar somente o estado de carregamento da R12:

```sh
sh plugins/indicadores/tools/r12-safe-recover.sh
```

Se aparecer:

`Plugin 'indicadores' is not loaded`

não abra rotas `/plugins/indicadores/...` até o plugin voltar a aparecer como habilitado no `plugin:list`.

## Desenvolvimento

Arquivos principais:

- `setup.php`: metadados, compatibilidade e hooks.
- `hook.php`: instalação, atualização de estruturas próprias, seeds e uninstall.
- `src/Controller/`: rotas HTTP.
- `src/Service/`: consultas, filtros, métricas, analytics, administração e Zabbix.
- `templates/`: interface Twig.
- `public/`: CSS e JavaScript.
- `tools/`: scripts de diagnóstico/recuperação.
- `docs/`: documentação técnica e operacional.

## Licença

MIT. Consulte [LICENSE](LICENSE).
