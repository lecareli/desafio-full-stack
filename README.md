# Wallet App (Desafio Full Stack)

Aplicação web (Laravel 12) de carteira digital com autenticação e extrato de transações.

## Visão geral

O sistema permite que usuários:

- Criem conta e façam login/logout.
- Visualizem o saldo da carteira e o extrato de movimentações.
- Realizem **depósito**, **transferência** e **retirada**.
- Realizem **estorno** de **depósitos** e **transferências**, respeitando regras de autorização e saldo.

Todas as operações relevantes geram registros em **transactions** e logs em **audit_logs**/**error_logs** para auditoria e rastreabilidade.

## Modelo de dados (domínio)

- **User** (`users`)
  - `id` é UUID.
  - `is_active` controla se o usuário pode logar.
- **Wallet** (`wallets`)
  - 1 carteira por usuário (`user_id`).
  - `balance_cents` armazena saldo em centavos (inteiro) para evitar problemas de ponto flutuante.
  - `currency` (default `BRL`).
- **Transaction** (`transactions`)
  - `type`: `DEPOSIT`, `TRANSFER`, `WITHDRAW`, `REVERSAL` (`app/Enums/TransactionTypeEnum.php`).
  - `status`: `POSTED`, `REVERSED`, `FAILED` (`app/Enums/TransactionStatusEnum.php`).
  - `amount_cents` armazena valor em centavos (inteiro).
  - `from_wallet_id` e `to_wallet_id` indicam o fluxo do dinheiro (podem ser `null` em depósito/retirada).
  - `reversal_of_id` referencia a transação original quando `type=REVERSAL`.
- **AuditLog** (`audit_logs`)
  - Loga eventos de negócio (ex.: `deposit_posted`, `transfer_posted`, `logout`, etc).
  - Guarda contexto de request (ip, user-agent, request_id).
- **ErrorLog** (`error_logs`)
  - Loga erros/exceções com contexto, além de dados do request.

## Fluxos e regras de negócio

### 1) Autenticação

- **Registro** cria o usuário, autentica a sessão e escreve `audit_logs.action=register`.
- **Login**
  - Se credenciais inválidas: lança `AuthenticationException` e escreve `error_logs` com nível `WARNING`.
  - Se usuário inativo (`is_active=false`): registra `audit_logs.action=login_denied_inactive`, força logout e lança `AuthenticationException`.
- **Rotas de carteira** são protegidas por `auth` e guests são redirecionados para `auth.view.login` (configurado em `bootstrap/app.php`).

### 2) Carteira (Wallet)

O sistema garante a existência de uma carteira por usuário via `WalletService::getOrCreateWallet()`.

### 3) Depósito

Implementação: `app/Services/Wallet/DepositService.php`.

- O valor recebido do formulário é convertido para centavos (aceita formatos como `150`, `150,00`, `1.234,56`, `R$ 50,00`).
- Atualiza o saldo da carteira dentro de transação de banco (`DB::transaction`) com `lockForUpdate()` para evitar condições de corrida.
- Cria uma `transactions` com `type=DEPOSIT` e `status=POSTED`.
- Cria um log em `audit_logs` (`action=deposit_posted`).

### 4) Transferência

Implementação: `app/Services/Wallet/TransferService.php`.

- Regras:
  - Destinatário deve existir e não pode ser o próprio usuário.
  - Remetente deve possuir saldo suficiente.
- Para evitar deadlock/conflito:
  - As duas wallets são travadas (`lockForUpdate`) em ordem consistente (por `id`).
- Cria uma `transactions` com `type=TRANSFER` e `status=POSTED`.
- Cria um log em `audit_logs` (`action=transfer_posted`).

### 5) Retirada

Implementação: `app/Services/Wallet/WithdrawService.php`.

- Regras:
  - Usuário deve possuir saldo suficiente.
- Atualiza o saldo com `lockForUpdate()` dentro de `DB::transaction`.
- Cria uma `transactions` com `type=WITHDRAW` e `status=POSTED`.
- Cria um log em `audit_logs` (`action=withdraw_posted`).

### 6) Estorno (reversão)

Implementação: `app/Services/Wallet/ReverseTransactionService.php`.

- Só permite estornar transações `DEPOSIT` ou `TRANSFER`.
- Apenas o usuário que criou a transação (`created_by`) pode estornar.
- Se a transação original já estiver `REVERSED`, lança `AlreadyReversedException`.
- **Idempotência:** se já existir uma transação `REVERSAL` com `reversal_of_id` apontando para a original, ela é retornada.
- No estorno:
  - É criada uma nova transação `REVERSAL` (com `reversal_of_id` preenchido).
  - A transação original é marcada como `REVERSED`.
  - Os saldos são ajustados (depósito: subtrai da carteira; transferência: devolve do destinatário para o remetente).

## Interface e rotas

Arquivos de rotas:

- `routes/auth.php`
- `routes/wallet.php`

Rotas principais (web):

- `GET /` → tela de login
- `GET /register` / `POST /register`
- `GET /login` / `POST /login`
- `POST /logout`
- `GET /wallet` (saldo + extrato)
- `POST /wallet/deposit`
- `POST /wallet/transfer`
- `POST /wallet/withdraw`
- `POST /wallet/transactions/{transaction}/reverse`

## Como rodar localmente

### Pré-requisitos

- PHP 8.4+
- Composer
- Node.js (para Vite/Tailwind, se você for mexer em assets)
- PostgreSQL (config padrão em `.env`)

### Setup rápido

1) Instale dependências e rode migrations:

```bash
composer install
php artisan migrate
```

2) Rode o servidor:

```bash
php artisan serve
```

Opcional: script de desenvolvimento (server + queue + logs + vite):

```bash
composer run dev
```

## Testes

Este projeto usa **Pest**.

```bash
php artisan test
```

Cobertura atual inclui:

- Unit tests para services (`tests/Unit/Services/*`)
- Feature tests para rotas (`tests/Feature/Auth/*`, `tests/Feature/Wallet/*`)

## Estrutura (pontos de entrada)

- Controllers: `app/Http/Controllers/Auth/AuthController.php`, `app/Http/Controllers/WalletController.php`
- Validação: `app/Http/Requests/Auth/*`, `app/Http/Requests/Wallet/*`
- Services: `app/Services/Auth/AuthService.php`, `app/Services/Wallet/*`, `app/Services/Logging/*`
- Models: `app/Models/*`
