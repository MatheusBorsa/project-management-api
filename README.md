# API de Gerenciamento de Projetos para Designers

## Tecnologias Utilizadas

- PHP
- Laravel
- Docker
- Git
- PostgreSQL

## Funcionalidades da API

API RESTful para gerenciamento de clientes, tarefas e fluxos de trabalho, com autenticação, controle de acesso por plano e integração com pagamentos.

### Autenticação e Usuário
- Registro e login de usuários
- Autenticação via Laravel Sanctum
- Logout de usuários autenticados
- Recuperação de dados do usuário autenticado

### Dashboard
- Dashboard geral com dados do usuário
- Dashboard premium com funcionalidades exclusivas (controle por middleware)

### Gerenciamento de Clientes
- CRUD completo de clientes
- Gerenciamento de usuários vinculados a clientes
- Atualização e remoção de usuários de um cliente
- Listagem de tarefas associadas a clientes

### Convites
- Envio de convites para usuários participarem de clientes
- Reenvio e cancelamento de convites
- Aceite e recusa de convites via token
- Consulta pública de convites por token

### Tarefas
- CRUD completo de tarefas
- Associação de tarefas a clientes
- Atualização de status de tarefas
- Visualização de tarefas em calendário semanal
- Acesso individual a tarefas

### Artes e Revisões (Premium)
- Upload, atualização e remoção de artes vinculadas a tarefas
- Sistema de revisão de artes
- Comentários em artes
- Listagem de comentários por arte
- Acesso restrito a usuários com plano premium

### Billing e Assinaturas
- Integração com Stripe
- Criação de sessão de checkout
- Acesso ao portal de billing
- Consulta de status da assinatura
- Cancelamento de assinatura
- Processamento de webhooks do Stripe

### Segurança e Controle de Acesso
- Autenticação baseada em token
- Middleware para controle de acesso premium
- Separação de rotas públicas e protegidas
- Processamento seguro de webhooks

## Instalação e Execução

### 1 - Clonar o repositório
```bash
git clone https://github.com/MatheusBorsa/api-gerenciamento-projetos.git
cd api-gerenciamento-projetos
```

### 2 - Instalar dependências
```bash
composer install
```

### 3 - Configurar o ambiente

Copie o arquivo de exemplo:

```bash
cp .env.example .env
```

Configure as variáveis de banco de dados:
```bash
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pgsql
DB_USERNAME=pgsql
DB_PASSWORD=pgsql
```
### 4 - Gere a chave da aplicação
```bash
php artisan key:generate
```

### 5 - Inicie o docker
```bash
docker-compose up -d
```

### 6 - Execute as migrações e inicie o projeto
```bash
php artisan migrate
php artisan serve
```

## Testes
```bash
php artisan test
```
## Objetivo do projeto

O objetivo deste projeto é desenvolver uma aplicação focada no workflow de trabalho de designers e profissionais da indústria criativa, permitindo a criação de artes, o envio para revisão, o processo de aprovação ou solicitação de alterações por parte dos clientes, e o acompanhamento de todo o ciclo de trabalho.

Toda a arquitetura e as funcionalidades do sistema foram pensadas para refletir esse fluxo, garantindo organização, rastreabilidade das revisões e clareza na comunicação entre criadores e clientes.

