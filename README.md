# Project Management API for Designers

## Technologies Used

- PHP
- Laravel
- Docker
- Git
- PostgreSQL

## API Features

RESTful API for managing clients, tasks, and workflows, with authentication, plan-based access control, and payment integration.

### Authentication and User
- User registration and login
- Authentication via Laravel Sanctum
- Logout for authenticated users
- Retrieval of authenticated user data

### Dashboard
- General dashboard with user data
- Premium dashboard with exclusive features (controlled via middleware)

### Client Management
- Complete CRUD for clients
- Management of users linked to clients
- Update and removal of users from a client
- Listing of tasks associated with clients

### Invitations
- Sending invitations for users to participate in clients
- Resending and canceling invitations
- Accepting and declining invitations via token
- Public query of invitations by token

### Tasks
- Complete CRUD for tasks
- Association of tasks with clients
- Task status updates
- Viewing tasks in weekly calendar
- Individual access to tasks

### Artwork and Revisions (Premium)
- Upload, update, and removal of artwork linked to tasks
- Artwork review system
- Comments on artwork
- Listing comments by artwork
- Restricted access to users with premium plan

### Billing and Subscriptions
- Stripe integration
- Checkout session creation
- Access to billing portal
- Subscription status query
- Subscription cancellation
- Stripe webhook processing

### Security and Access Control
- Token-based authentication
- Middleware for premium access control
- Separation of public and protected routes
- Secure webhook processing

## Installation and Execution

### 1 - Clone the repository
```bash
git clone https://github.com/MatheusBorsa/project-management-api.git
cd project-management-api
```

### 2 - Install Dependencies
```bash
composer install
```

### 3 - Configure the environment

Copy the example file:

```bash
cp .env.example .env
```

Configure the database variables:
```bash
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pgsql
DB_USERNAME=pgsql
DB_PASSWORD=pgsql
```
### 4 - Generate the application key
```bash
php artisan key:generate
```

### 5 - Start docker
```bash
docker-compose up -d
```

### 6 - Run the migrations and start the project
```bash
php artisan migrate
php artisan serve
```

## Tests
```bash
php artisan test
```
## Project Objective

The objective of this project is to develop an application focused on the workflow of designers and creative industry professionals, allowing the creation of artwork, sending for review, the approval process or change requests from clients, and tracking of the entire work cycle.

All the architecture and system functionalities were designed to reflect this flow, ensuring organization, traceability of revisions, and clarity in communication between creators and clients.
