# R12 SAFE RECOVERY — erro `Plugin indicadores is not loaded`

O erro exibido pelo GLPI em `PluginsRouterListener.php` acontece **antes** de qualquer
Controller do plugin ser executado. No GLPI 11, uma URL `/plugins/indicadores/...`
só é roteada quando o plugin está presente na lista interna de plugins carregados.

Este build mantém a versão registrada **0.4.7** para não disparar atualização de
schema por mudança artificial de versão.

## Recuperação sem tocar nos dados

Na raiz do GLPI:

```bash
sh plugins/indicadores/tools/r12-safe-recover.sh
```

O script:
1. valida o nome exato da pasta (`indicadores`);
2. valida a sintaxe de todos os PHP;
3. exibe o estado reconhecido pelo GLPI;
4. tenta apenas `plugin:enable indicadores`;
5. limpa cache;
6. confirma o estado final.

Ele **não executa** uninstall, install, update ou DROP TABLE.

Se a habilitação falhar, consulte:
- `files/_log/php-errors.log`
- `files/_log/sql-errors.log`

Não execute `plugin:install --force` às cegas: primeiro confirme a versão registrada
e o motivo do estado do plugin.
