# Travel Order API

REST API para gerenciamento de pedidos de viagem corporativa, construída com **Laravel 12** e **Laravel Sanctum**.

## Funcionalidades

- Autenticação por token (registro / login / logout)
- Criação e listagem de pedidos de viagem (visível apenas pelo proprietário)
- Aprovar ou cancelar pedidos (somente por usuário que não é o dono — o dono não pode alterar o status do próprio pedido)
- Regra de negócio: pedidos cancelados não podem ser reativados; pedidos aprovados ainda podem ser cancelados
- Notificação por e-mail ao dono do pedido em toda mudança de status
- Filtros por status, destino, e intervalos de data de ida e volta
- Paginação configurável via parâmetro `per_page`

---

## Requisitos

- PHP 8.2+
- Composer
- Docker + Docker Compose (para Sail / MySQL)

---

## Configuração

### Com Docker (recomendado)

```bash
# Copiar o arquivo de ambiente
cp .env.example .env

# Iniciar os containers
./vendor/bin/sail up -d

# Instalar dependências (dentro do container)
./vendor/bin/sail composer install

# Gerar a chave da aplicação e rodar as migrations
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

### Sem Docker (SQLite)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

---

## Executando os Testes

```bash
# Todos os testes
php artisan test

# Com Sail
./vendor/bin/sail artisan test

# Suite específica
php artisan test tests/Feature/TravelOrder/
php artisan test tests/Feature/Auth/
```

---

## Autenticação

Todos os endpoints de pedido de viagem exigem um token Bearer obtido em `/api/register` ou `/api/login`.

```
Authorization: Bearer <token>
```

---

## Endpoints

### Auth

#### Registro

```http
POST /api/register
Content-Type: application/json

{
  "name": "Test Jr",
  "email": "test@test.com",
  "password": "secret123",
  "password_confirmation": "secret123"
}
```

Resposta `201`:
```json
{ "user": { ... }, "token": "<plaintext-token>" }
```

#### Login

```http
POST /api/login
Content-Type: application/json

{ "email": "test@test.com", "password": "password123" }
```

Resposta `200`:
```json
{ "user": { ... }, "token": "<plaintext-token>" }
```

#### Logout

```http
DELETE /api/logout
Authorization: Bearer <token>
```

Resposta `200`:
```json
{ "message": "Logged out." }
```

---

### Pedidos de Viagem

#### Criar

```http
POST /api/travel-orders
Authorization: Bearer <token>
Content-Type: application/json

{
  "requester_name": "Test Jr",
  "destination": "Belo Horizonte",
  "departure_date": "2026-05-01",
  "return_date": "2026-05-10"
}
```

Resposta `201` — novo pedido com `status: "requested"`.

#### Listar (com filtros opcionais)

```http
GET /api/travel-orders?status=approved&destination=paris&departure_from=2026-04-01&departure_to=2026-06-01
Authorization: Bearer <token>
```

Parâmetros de query (todos opcionais):

| Parâmetro        | Tipo    | Descrição                              |
|------------------|---------|----------------------------------------|
| `status`         | string  | `requested`, `approved`, `cancelled`  |
| `destination`    | string  | Correspondência parcial               |
| `departure_from` | date    | Data de ida mais antiga               |
| `departure_to`   | date    | Data de ida mais recente              |
| `return_from`    | date    | Data de volta mais antiga             |
| `return_to`      | date    | Data de volta mais recente            |
| `per_page`       | integer | Resultados por página (padrão: 15)    |

A resposta é paginada (`data`, `links`, `meta`).

#### Buscar por ID

```http
GET /api/travel-orders/{id}
Authorization: Bearer <token>
```

Somente o dono do pedido pode visualizá-lo (403 para outros).

#### Atualizar Status

```http
PATCH /api/travel-orders/{id}/status
Authorization: Bearer <token>
Content-Type: application/json

{ "status": "approved" }
```

- `status` deve ser `approved` ou `cancelled`
- Somente um usuário que **não** é dono do pedido pode alterar o status (403 para o dono)
- Transições inválidas retornam 422

---

## Regras de Autorização

| Ação                    | Quem pode realizar                                        |
|-------------------------|-----------------------------------------------------------|
| Criar pedido de viagem  | Qualquer usuário autenticado (torna-se o dono)           |
| Visualizar pedido       | Somente o dono do pedido                                 |
| Listar pedidos          | Qualquer usuário autenticado (vê apenas os próprios)     |
| Aprovar / Cancelar      | Qualquer usuário autenticado **exceto** o dono do pedido |

Essa separação garante que o solicitante não possa aprovar seu próprio pedido de viagem.

## Transições de Status

```
solicitado → aprovado  ✅
solicitado → cancelado ✅
aprovado  → cancelado ✅
cancelado → qualquer  ❌
```

---
