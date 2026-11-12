# Segurança

## Credenciais do Zabbix

A senha é armazenada pelo `ConfigService` usando `GLPIKey`.

Recomendações:

- use uma conta de banco exclusiva para o plugin;
- conceda somente leitura às tabelas Zabbix necessárias;
- restrinja a origem de rede ao servidor/container GLPI;
- não versione credenciais em arquivos;
- faça rotação periódica da senha.

## Permissões GLPI

A administração usa `config READ/UPDATE` e as visões operacionais usam `ticket READ/UPDATE`.

Revise os direitos de cada perfil.

## Atualização

Nunca use uninstall/reinstall como atalho de atualização em produção. O uninstall remove tabelas próprias do plugin e suas configurações.

## Relato de vulnerabilidade

Não publique credenciais, dumps de banco, tokens, chaves GLPI ou informações sensíveis de tickets em issues públicas.

Ao abrir um issue, use dados anonimizados e inclua somente os logs necessários.
