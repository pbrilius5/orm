# TESTER.md - API Testing Guide

This guide provides comprehensive instructions for testing the ADR API endpoints using curl, Postman, or any HTTP client.

## Prerequisites

- PHP server running on `http://localhost:8080`
- Database initialized with fixtures (optional but recommended for testing with existing data)

## Base Configuration

| Setting | Value |
|---------|-------|
| Base URL | `http://localhost:8080` |
| Content-Type | `application/json` |
| Accept | `application/json`, `application/hal+json` |

---

## Endpoints Overview

| # | Method | Endpoint | Description |
|---|--------|----------|-------------|
| 1 | GET | `/health` | Health check |
| 2 | GET | `/api/users` | List all users |
| 3 | POST | `/api/users` | Create new user |
| 4 | GET | `/api/users/{id}` | Show single user |
| 5 | PUT | `/api/users/{id}` | Update user (full replacement) |
| 6 | PATCH | `/api/users/{id}` | Update user (partial) |
| 7 | DELETE | `/api/users/{id}` | Delete user |
| 8 | GET | `/api/groups` | List all groups |
| 9 | POST | `/api/groups` | Create new group |
| 10 | GET | `/api/groups/{id}` | Show single group |
| 11 | PUT | `/api/groups/{id}` | Update group (full replacement) |
| 12 | PATCH | `/api/groups/{id}` | Update group (partial) |
| 13 | DELETE | `/api/groups/{id}` | Delete group |
| 14 | GET | `/manifest.json` | PWA manifest |

---

## Specimen Requests

### 1. Health Check

**Request:**
```bash
curl -X GET http://localhost:8080/health
```

**Response:**
```json
{
  "_links": {
    "self": {
      "href": "/health"
    }
  },
  "status": "ok",
  "timestamp": "2026-04-03T12:00:00+00:00"
}
```

---

### User Endpoints

#### 2. List All Users

**Request:**
```bash
curl -X GET http://localhost:8080/api/users
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "email": "admin@example.com",
      "gamification_roles": ["ROLE_WIZARD", "ROLE_ARCHITECT"],
      "created_at": "2026-01-01T00:00:00+00:00",
      "_links": {
        "self": { "href": "/api/users/1" },
        "update": { "href": "/api/users/1" },
        "delete": { "href": "/api/users/1" }
      }
    }
  ],
  "_links": {
    "self": { "href": "/api/users" },
    "create": { "href": "/api/users" }
  },
  "meta": {
    "total": 1,
    "count": 1
  }
}
```

---

#### 3. Create User (Specimen)

**Request:**
```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{
    "email": "tester@example.com",
    "password": "securePass123",
    "gamification_roles": ["ROLE_WIZARD"]
  }'
```

**Response:**
```json
{
  "data": {
    "id": 2,
    "email": "tester@example.com",
    "gamification_roles": ["ROLE_WIZARD"],
    "created_at": "2026-04-03T12:00:00+00:00",
    "_links": {
      "self": { "href": "/api/users/2" },
      "update": { "href": "/api/users/2" },
      "delete": { "href": "/api/users/2" }
    }
  }
}
```

**Validation Error Specimen (Invalid Email):**
```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"email": "not-an-email", "password": "secret123"}'
```

```json
{
  "_errors": [
    {
      "status": 422,
      "title": "Validation Error",
      "detail": "Email must be a valid email address"
    }
  ]
}
```

**Validation Error Specimen (Missing Required Fields):**
```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{}'
```

```json
{
  "_errors": [
    {
      "status": 422,
      "title": "Validation Error",
      "detail": "Email is required"
    },
    {
      "status": 422,
      "title": "Validation Error", 
      "detail": "Password is required"
    }
  ]
}
```

---

#### 4. Show Single User

**Request:**
```bash
curl -X GET http://localhost:8080/api/users/1
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "email": "admin@example.com",
    "gamification_roles": ["ROLE_WIZARD", "ROLE_ARCHITECT"],
    "created_at": "2026-01-01T00:00:00+00:00",
    "_links": {
      "self": { "href": "/api/users/1" },
      "update": { "href": "/api/users/1" },
      "delete": { "href": "/api/users/1" }
    }
  }
}
```

---

#### 5. Update User (PUT - Full Replacement)

**Request:**
```bash
curl -X PUT http://localhost:8080/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{
    "email": "updated@example.com",
    "password": "newpassword456",
    "gamification_roles": ["ROLE_GAME_MASTER"]
  }'
```

---

#### 6. Update User (PATCH - Partial)

**Request:**
```bash
curl -X PATCH http://localhost:8080/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"gamification_roles": ["ROLE_WIZARD", "ROLE_GAME_MASTER"]}'
```

---

#### 7. Delete User

**Request:**
```bash
curl -X DELETE http://localhost:8080/api/users/1
```

**Response:** `204 No Content`

---

### Group Endpoints

#### 8. List All Groups

**Request:**
```bash
curl -X GET http://localhost:8080/api/groups
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Administrators",
      "description": "Admin group",
      "created_at": "2026-01-01T00:00:00+00:00",
      "_links": {
        "self": { "href": "/api/groups/1" },
        "update": { "href": "/api/groups/1" },
        "delete": { "href": "/api/groups/1" }
      }
    }
  ],
  "_links": {
    "self": { "href": "/api/groups" },
    "create": { "href": "/api/groups" }
  },
  "meta": {
    "total": 1,
    "count": 1
  }
}
```

---

#### 9. Create Group (Specimen)

**Request:**
```bash
curl -X POST http://localhost:8080/api/groups \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Developers",
    "description": "Development team group"
  }'
```

**Response:**
```json
{
  "data": {
    "id": 2,
    "name": "Developers",
    "description": "Development team group",
    "created_at": "2026-04-03T12:00:00+00:00",
    "_links": {
      "self": { "href": "/api/groups/2" },
      "update": { "href": "/api/groups/2" },
      "delete": { "href": "/api/groups/2" }
    }
  }
}
```

**Validation Error Specimen (Name Too Short):**
```bash
curl -X POST http://localhost:8080/api/groups \
  -H "Content-Type: application/json" \
  -d '{"name": "A"}'
```

```json
{
  "_errors": [
    {
      "status": 422,
      "title": "Validation Error",
      "detail": "Name must be between 2 and 255 characters"
    }
  ]
}
```

---

#### 10. Show Single Group

**Request:**
```bash
curl -X GET http://localhost:8080/api/groups/1
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Administrators",
    "description": "Admin group",
    "created_at": "2026-01-01T00:00:00+00:00",
    "_links": {
      "self": { "href": "/api/groups/1" },
      "update": { "href": "/api/groups/1" },
      "delete": { "href": "/api/groups/1" }
    }
  }
}
```

---

#### 11. Update Group (PUT - Full Replacement)

**Request:**
```bash
curl -X PUT http://localhost:8080/api/groups/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Super Administrators",
    "description": "Updated description"
  }'
```

---

#### 12. Update Group (PATCH - Partial)

**Request:**
```bash
curl -X PATCH http://localhost:8080/api/groups/1 \
  -H "Content-Type: application/json" \
  -d '{"description": "New description"}'
```

---

#### 13. Delete Group

**Request:**
```bash
curl -X DELETE http://localhost:8080/api/groups/1
```

**Response:** `204 No Content`

---

#### 14. PWA Manifest

**Request:**
```bash
curl -X GET http://localhost:8080/manifest.json
```

**Response:**
```json
{
  "name": "Oryx ORM App",
  "short_name": "OryxApp",
  "description": "Full-stack ORM with ADR pattern",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#4A90E2",
  "icons": [
    { "src": "/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

---

## Postman Collection

Import the following JSON into Postman for quick testing:

```json
{
  "info": {
    "name": "Oryx ORM ADR API",
    "description": "API testing collection for Oryx ORM ADR endpoints",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:8080",
      "type": "string"
    }
  ],
  "item": [
    {
      "name": "Health",
      "item": [
        {
          "name": "Health Check",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/health"
          }
        }
      ]
    },
    {
      "name": "Users",
      "item": [
        {
          "name": "List Users",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/users"
          }
        },
        {
          "name": "Create User",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/users",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\"email\": \"newuser@example.com\", \"password\": \"password123\", \"gamification_roles\": [\"ROLE_WIZARD\"]}"
            }
          }
        },
        {
          "name": "Show User",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/users/1"
          }
        },
        {
          "name": "Update User (PUT)",
          "request": {
            "method": "PUT",
            "url": "{{base_url}}/api/users/1",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\"email\": \"updated@example.com\", \"password\": \"newpass\", \"gamification_roles\": [\"ROLE_ARCHITECT\"]}"
            }
          }
        },
        {
          "name": "Update User (PATCH)",
          "request": {
            "method": "PATCH",
            "url": "{{base_url}}/api/users/1",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\"gamification_roles\": [\"ROLE_WIZARD\"]}"
            }
          }
        },
        {
          "name": "Delete User",
          "request": {
            "method": "DELETE",
            "url": "{{base_url}}/api/users/1"
          }
        }
      ]
    },
    {
      "name": "Groups",
      "item": [
        {
          "name": "List Groups",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/groups"
          }
        },
        {
          "name": "Create Group",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/groups",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\"name\": \"New Group\", \"description\": \"Group description\"}"
            }
          }
        },
        {
          "name": "Show Group",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/api/groups/1"
          }
        },
        {
          "name": "Update Group (PUT)",
          "request": {
            "method": "PUT",
            "url": "{{base_url}}/api/groups/1",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\"name\": \"Updated Group\", \"description\": \"Updated description\"}"
            }
          }
        },
        {
          "name": "Update Group (PATCH)",
          "request": {
            "method": "PATCH",
            "url": "{{base_url}}/api/groups/1",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\"description\": \"Partial update\"}"
            }
          }
        },
        {
          "name": "Delete Group",
          "request": {
            "method": "DELETE",
            "url": "{{base_url}}/api/groups/1"
          }
        }
      ]
    },
    {
      "name": "Manifest",
      "item": [
        {
          "name": "PWA Manifest",
          "request": {
            "method": "GET",
            "url": "{{base_url}}/manifest.json"
          }
        }
      ]
    }
  ]
}
```

### Importing Postman Collection

1. Open Postman
2. Click **Import** button
3. Select **Raw text** tab
4. Paste the JSON above
5. Click **Import**
6. Set `base_url` variable to your server URL

---

## Console Commands (Tactician)

The application now uses Command/Handler pattern. Console commands are executed via `bin/console`:

### User Commands

```bash
# List all users
bin/console user:list

# Create user
bin/console user:create --email=user@example.com --password=secret

# Show user
bin/console user:show <id>

# Update user
bin/console user:update <id> --email=new@example.com

# Delete user
bin/console user:delete <id>

# Interactive user management
bin/console user:manage
```

### Group Commands

```bash
# List all groups
bin/console group:list

# Create group
bin/console group:create --name="New Group" --description="Description"

# Show group
bin/console group:show <id>

# Update group
bin/console group:update <id> --name="Updated Name"

# Delete group
bin/console group:delete <id>
```

### Database Commands

```bash
# Create database
bin/console oryx:db:create

# Load fixtures
bin/console oryx:fixtures:load

# Generate proxies
bin/console orm:generate:proxies
```

### Testing Console Commands

```bash
# List all available commands
bin/console list

# Get help for specific command
bin/console user:create --help
```

---

## Common Test Scenarios

### Test Validation - Required Fields Missing
```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{}'
```

### Test Validation - Invalid Email Format
```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"email": "invalid-email"}'
```

### Test 404 - Resource Not Found
```bash
curl -X GET http://localhost:8080/api/users/99999
```

### Test 405 - Method Not Allowed
```bash
curl -X DELETE http://localhost:8080/api/users
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Connection refused | Ensure PHP server is running: `php -S localhost:8080 -t public` |
| 404 on all endpoints | Check .htaccess or routing configuration |
| Validation errors not showing | Ensure `Content-Type: application/json` header is set |
| Database errors | Run migrations and seed data: `php bin/console orm:fixture:load` |