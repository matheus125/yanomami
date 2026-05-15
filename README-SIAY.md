# SIAY - Sistema Integrado de Acompanhamento Yanomami

Projeto Laravel 12 + Vue 3/Inertia criado a partir do documento de arquitetura.

## Acesso local

- URL do servidor embutido: `http://127.0.0.1:8000`
- Usuario inicial: `admin@siay.test`
- Senha inicial: `password`

## Comandos principais

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

## Modulos implementados

- Dashboard com indicadores de casos, risco, pendencias, encaminhamentos e alertas.
- Casos com numero unico, sigilo, responsavel, municipio, comunidade e pessoas vinculadas.
- Pessoas, familias, comunidades, municipios e orgaos.
- Atendimentos, encaminhamentos, alertas, casa de transito e anexos.
- Usuarios com perfil, orgao, municipio, status ativo/inativo e registro de remocao/desativacao.
- Tela de permissoes por perfil.
- Auditoria de atividades e tabela de erros do sistema.

## Seguranca inicial

- Cadastro publico foi desativado.
- Login usa o throttle padrao do Laravel Breeze.
- Usuarios inativos nao autenticam.
- Rotas internas exigem permissao do perfil.
- Logs registram usuario, acao, registro afetado, IP e agente do navegador.
