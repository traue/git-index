# Deploy — api.traue.com.br/disciplinas

Sistema de administração de disciplinas por semestre, com API pública
compatível com o front atual (git.traue.com.br). PHP puro (sem Composer) +
MySQL, pensado para hospedagem compartilhada (cPanel/FTP).

## Estrutura

```
disciplinas/
├── index.php              API pública (GET) — mesmo contrato de sempre
├── config.php              bootstrap (env + conexão)
├── .env                    credenciais reais (você cria, nunca vai pro git)
├── .env.example             modelo do .env
├── .htaccess                bloqueia .env, .sql, .md e listagem de pastas
├── src/                     classes PHP (Env, Database, Auth) — bloqueado por HTTP
├── database/schema.sql      criação das tabelas + seed do semestre 26.1 atual
└── admin/                   painel administrativo (login obrigatório)
    ├── setup.php            criação do 1º usuário (uso único, com token)
    ├── login.php / logout.php
    ├── index.php            semestres + interruptor "active"
    └── disciplinas.php      CRUD de disciplinas por semestre
```

## Passo a passo

1. **Banco de dados** — no cPanel, em "MySQL® Databases", crie um banco e um
   usuário com todos os privilégios sobre ele (o nome final costuma vir
   prefixado, ex: `seuusuario_disciplinas`).

2. **Importe o schema** — abra o phpMyAdmin, selecione o banco criado e
   importe `database/schema.sql`. Isso cria as tabelas e já migra os dados
   atuais do `discs.json` como o semestre `26.1`, publicado (a API volta a
   responder exatamente o que respondia antes da troca).

3. **Backup do que existe hoje** — antes de sobrescrever, baixe uma cópia do
   `index.php` e `discs.json` que já estão em produção. Se algo der errado,
   é o seu rollback imediato.

4. **Suba os arquivos** — envie todo o conteúdo desta pasta para o mesmo
   lugar onde está o `index.php` atual em `api.traue.com.br/disciplinas/`
   (FTP ou File Manager do cPanel), sobrescrevendo o `index.php` antigo.

5. **Configure o `.env`** — copie `.env.example` para `.env` na mesma pasta
   e preencha `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` com as credenciais
   do passo 1. Gere um `SETUP_TOKEN` aleatório e longo (ex: um gerador de
   senha qualquer, 40+ caracteres) — ele evita que outra pessoa cadastre a
   própria conta de admin antes de você.

6. **Confirme as permissões** — se o painel do host permitir, deixe o
   `.env` legível só pelo seu usuário (permissão 640 ou 600). Teste também
   que ele não é acessível por URL: `https://api.traue.com.br/disciplinas/.env`
   deve dar 403/404 (o `.htaccess` já cuida disso, mas vale conferir).

7. **Crie seu usuário admin** — acesse
   `https://api.traue.com.br/disciplinas/admin/setup.php?token=SEU_SETUP_TOKEN`,
   preencha nome/e-mail/senha (mínimo 10 caracteres). Essa página se
   autodesabilita assim que existir 1 usuário no banco — depois disso pode
   apagar `admin/setup.php` do servidor se quiser uma camada extra de
   segurança, mas não é obrigatório.

8. **Teste o login** — vá em `https://api.traue.com.br/disciplinas/admin/`
   e entre com o usuário criado.

9. **Confira a API pública** — `https://api.traue.com.br/disciplinas/` deve
   devolver o mesmo formato de JSON de antes (agora com um campo extra
   `"semestre": "26.1"`). Compare com o `discs.json` antigo que você
   guardou no passo 3.

10. **Aponte o `path` do cookie de sessão** — em `admin/_bootstrap.php`
    há um comentário sobre isso: o valor `'/disciplinas/admin/'` assume que
    `api.traue.com.br` serve diretamente esta pasta. Se a estrutura de
    pastas do seu host for diferente, ajuste esse `path` antes de testar o
    login.

## Uso do dia a dia

- **Novo semestre**: painel → "Novo semestre" → informe o código (ex: `26.2`)
  → ele nasce como **rascunho** (não afeta o front).
- **Cadastrar disciplinas**: dentro do semestre, adicione cada uma com nome,
  tipo (presencial/EaD), turno + dia (só presencial) e o nome do repositório.
- **Publicar**: quando o semestre estiver pronto, clique em "Publicar" —
  isso arquiva automaticamente o semestre publicado anterior (mantendo o
  histórico) e a API pública passa a servir o novo na hora.
- **Ligar/desligar o front**: botão "Ativar/Desativar front" no topo do
  painel — é o mesmo `active` de sempre.
- **Histórico**: semestres arquivados continuam no banco, com todas as
  disciplinas, só não aparecem mais na API pública. Nada é apagado a não
  ser que você exclua manualmente um rascunho.

## Segurança — o que já está coberto

- Senhas com `password_hash`/`password_verify` (nunca texto puro).
- Todas as queries via PDO com parâmetros — sem concatenar SQL.
- CSRF token em todo formulário do admin.
- Bloqueio de força bruta no login (5 tentativas / 15 min por e-mail e por IP).
- Cookie de sessão `HttpOnly` + `Secure` + `SameSite=Lax`, regenerado no login.
- `.env`, `/src/`, `/database/` e `/admin/partials/` bloqueados por `.htaccess`.
- API pública é somente leitura (só `GET`); nenhuma escrita exposta sem login.
- `admin/setup.php` protegido por token e autodesabilitado após o 1º uso.

## O que considerar depois (não bloqueia o uso agora)

- **HTTPS obrigatório**: confirme que `api.traue.com.br` força HTTPS
  (redirect automático) — os cookies `Secure` dependem disso.
- **Backup do banco**: agora que os dados moram no MySQL, vale configurar
  um backup automático do banco no cPanel (a maioria oferece isso pronto).
- **Log de alterações**: hoje o histórico é por semestre; se um dia quiser
  saber "quem mudou o quê e quando" dentro de um semestre, dá pra somar uma
  tabela de auditoria — não implementada agora por não ter sido pedida.
