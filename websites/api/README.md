# PHP Backend API

A simple PHP backend API for user authentication and supplier management using SQLite database.

## Prerequisites

- Docker
- Docker Compose

## Project Structure



````

## Quick Start

1. Clone or create the project structure:
```bash
mkdir -p api/{config,controllers,models,middleware,utils}
````

2. Copy all PHP files into their respective directories

3. Make the database directory writable:

```bash
chmod 777 api/
```

4. Start Docker containers:

```bash
docker-compose up -d
```

The API will be available at `http://localhost:8080/api/`

## API Endpoints

### Authentication

- `POST /api/login`

```json
{
  "email": "user@localauthority.gov.uk",
  "password": "password123"
}
```

- `POST /api/register`

```json
{
  "fullName": "Test User",
  "email": "user@localauthority.gov.uk",
  "authority": "Test Authority",
  "password": "password123"
}
```

### Suppliers

- `GET /api/suppliers/pending` - Get all pending suppliers
- `POST /api/suppliers/{id}/decision` - Update supplier status

```json
{
  "decision": "approved",
  "rejectionReason": null
}
```

## Testing the API

Using cURL:

1. Register a new user:

```bash
curl -X POST http://localhost:8080/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "fullName": "Test User",
    "email": "test@localauthority.gov.uk",
    "authority": "Test Authority",
    "password": "password123"
  }'
```

2. Login:

```bash
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@localauthority.gov.uk",
    "password": "password123"
  }'
```

3. Get pending suppliers:

```bash
curl -X GET http://localhost:8080/api/suppliers/pending
```

## Implementation Notes

- Uses SQLite database for data storage
- Session-based authentication
- JSON responses
- Basic error handling with HTTP status codes

## Security Notes

For production use, implement:

1. Password hashing
2. Input validation
3. CSRF protection
4. Rate limiting
5. HTTPS
6. Proper CORS policies
7. Environment variables for sensitive data

## Troubleshooting

1. Permission errors:

   - Check database directory permissions
   - View PHP logs: `docker-compose logs php`

2. API accessibility:

   - Check Docker containers: `docker-compose ps`
   - Verify port 8080 is free
   - Check container logs

3. Database issues:
   - SQLite database creates automatically
   - Verify file permissions
