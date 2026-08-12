# clone_instagram_v2

Clone do Instagram com feed, stories, posts, curtidas, comentários, seguidores e destaques.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Backend | PHP 8.3, Laravel 13, Sanctum (auth via token), Pest (testes) |
| Frontend | HTML, CSS e JavaScript puro (estático, sem build) |
| Banco de dados | MySQL 8.0 |
| Infraestrutura | Docker Compose (nginx, phpMyAdmin) |
| Documentação da API | Swagger (l5-swagger) |

## Estrutura do projeto

```
clone_instagram_v2/
├── app/          # Backend Laravel
│   ├── compose.yaml          # app (8000), mysql (3307), phpmyadmin (8081)
│   ├── app/Http/Controllers/ # Controllers da API
│   ├── app/Models/           # User, Post, Story, Comment, Like, Follow, Highlight
│   ├── app/Services/         # UserService, PostService, etc.
│   ├── routes/api.php        # Rotas da API
│   ├── database/migrations/  # Schema do banco
│   ├── database/seeders/     # Dados de demonstração
│   └── tests/                # Testes Pest
└── frontend/     # Frontend estático
    ├── compose.yaml          # nginx (8080) servindo ./src
    └── src/                  # pages/, js/, css/
```

## Pré-requisitos

- [Docker](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/) (já incluso no Docker Desktop)

## Como rodar

### 1. Backend (porta 8000)

```bash
cd app
docker compose up -d
```

Isso sobe:

- **API Laravel** em `http://localhost:8000`
- **MySQL 8.0** na porta `3307` (credenciais abaixo)
- **phpMyAdmin** em `http://localhost:8081`

### 2. Banco de dados (migrações + dados de exemplo)

```bash
docker compose exec app php artisan migrate --seed
```

Os seeders criam usuários, posts, stories, seguidores, curtidas, comentários e destaques de demonstração.

> Alternativa: `docker compose exec app php artisan migrate` para rodar apenas as migrações.

### 3. Frontend (porta 8080)

```bash
cd ../frontend
docker compose up -d
```

O nginx serve o conteúdo estático de `frontend/src/` direto em `http://localhost:8080` (sem build).

### 4. Acessar

| Serviço | URL |
| --- | --- |
| Aplicação (frontend) | http://localhost:8080/pages/index.html |
| Swagger da API | http://localhost:8000/api/documentation |
| phpMyAdmin | http://localhost:8081 |

## Credenciais do banco

Definidas em `app/compose.yaml`:

| Campo | Valor |
| --- | --- |
| Banco | `instagram` |
| Usuário | `instagram` |
| Senha | `instagram` |
| Root | `root` |

## Configuração (.env)

1. `cd app`
2. `cp .env.example .env`
3. Para rodar dentro do Docker, o `.env` usa os valores abaixo (já configurados):

```
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=instagram
DB_USERNAME=instagram
DB_PASSWORD=instagram
```

> `DB_HOST=mysql` é o nome do serviço no Docker; para rodar localmente (fora do Docker), use `DB_HOST=127.0.0.1` e `DB_PORT=3307`.

## Scripts úteis (backend)

`app/composer.json` define:

- `composer setup` — instala dependências, cria `.env`, gera chave, roda migrações e monta o frontend.
- `composer dev` — roda simultaneamente `artisan serve`, fila, `pail` (logs) e Vite.

Dentro do container:

```bash
docker compose exec app php artisan serve --host=0.0.0.0 --port=8000  # já é o CMD do container
docker compose exec app php artisan storage:link                       # link de uploads (já criado no build)
docker compose exec app php artisan queue:listen                       # fila (stories etc.)
```

## Testes

```bash
cd app
docker compose exec app php artisan test
```

> Nota: os testes de stories (`StoryTest`) podem falhar localmente se a extensão **GD** do PHP não estiver instalada (`imagecreatetruecolor()` indisponível). No container ela já está habilitada.

## Documentação da API (Swagger)

A UI fica em `http://localhost:8000/docs`. Para regenerar as anotações após mudanças nos controllers:

```bash
cd app
docker compose exec app php artisan l5-swagger:generate
```

Cobertura: 26 paths / 37 operações (autenticação, usuários, seguidores, posts, stories, comentários, curtidas, destaques).

## Endpoints principais

| Método | Rota | Descrição |
| --- | --- | --- |
| POST | `/api/register` | Criar conta |
| POST | `/api/login` | Login (retorna token Sanctum) |
| POST | `/api/logout` | Logout |
| GET | `/api/me` | Usuário autenticado |
| GET | `/api/users?search=` | Buscar usuários por username |
| GET | `/api/users/recommended` | Sugestões para seguir |
| POST/DELETE | `/api/users/{user}/follow` | Seguir / deixar de seguir |
| GET/POST | `/api/posts` | Listar / criar posts |
| GET/POST/DELETE | `/api/stories` | Listar / criar / excluir stories |
| GET/POST | `/api/posts/{post}/comments` | Listar / comentar |
| POST/DELETE | `/api/posts/{post}/like` | Curtir / descurtir |
| GET/POST | `/api/highlights` | Destaques de stories |

> Todas as rotas, exceto `register` e `login`, exigem autenticação via header `Authorization: Bearer <token>`.

## Observações

- O frontend é **estático** (sem build): as páginas usam um hash-router (`js/router.js`) e chamam a API via `fetch` apontando para `http://localhost:8000/api`.
- Imagens são servidas de `http://localhost:8000/storage/{path}` (link `storage/app/public`).
- O container do backend roda como usuário `appuser` (UID/GID 1000) para evitar problemas de permissão com arquivos locais.
