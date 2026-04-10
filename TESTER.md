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
| 1 | GET | `/api/health` | Health check |
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
| 14 | POST | `/api/groups` | Create DeveloperGroup |
| 15 | POST | `/api/groups` | Create DesignerGroup |
| 16 | POST | `/api/groups` | Create TesterGroup |
| 17 | PATCH | `/api/users/{id}` | Change user group |
| 18 | PATCH | `/api/users/{id}` | Remove user from groups |
| 19 | GET | `/manifest.json` | PWA manifest |

---

## Specimen Requests

### 1. Health Check

**Request:**
```bash
curl -X GET http://localhost:8080/api/health
```

**Response:**
```json
{
  "_links": {
    "self": {
      "href": "/api/health"
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
  "_links": {
    "self": { "href": "/users" }
  },
  "_embedded": {
    "users": [
      {
        "id": "8821f533-4028-4695-aa95-0c0fb6633c87",
        "email": "laila58@example.org",
        "workGroupName": "Designers",
        "role": "ROLE_ARCHITECT",
        "createdAt": { "date": "2026-04-08 16:05:12.000000", "timezone_type": 3, "timezone": "UTC" }
      }
    ]
  },
  "_meta": {
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
    "work_group": "developer",
    "gamification_roles": "ROLE_WIZARD"
  }'
```

**Response:**
```json
{
  "_links": {
    "self": { "href": "/api/users/d005be55-76c1-4bc9-9670-27eb63d1d9c1" },
    "collection": { "href": "/api/users" }
  },
  "user": {
    "id": "d005be55-76c1-4bc9-9670-27eb63d1d9c1",
    "email": "tester@example.com",
    "workGroupName": "Developers",
    "role": "ROLE_WIZARD",
    "createdAt": { "date": "2026-04-09 11:16:57.000000", "timezone_type": 3, "timezone": "UTC" }
  }
}
```

**Validation Error Specimen (Invalid Email):**
```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"email": "not-an-email", "password": "secret123", "work_group": "developer"}'
```

```json
{
  "_error": {
    "status": 422,
    "title": "Unprocessable Entity",
    "detail": "Validation failed",
    "errors": [
      {
        "field": "email",
        "message": "Email must be a valid email address"
      }
    ]
  }
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
  "_error": {
    "status": 422,
    "title": "Unprocessable Entity",
    "detail": "Validation failed",
    "errors": [
      {
        "field": "email",
        "message": "Email is required"
      },
      {
        "field": "password",
        "message": "Password is required"
      },
      {
        "field": "work_group",
        "message": "Work group is required"
      }
    ]
  }
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
    "role": "ROLE_WIZARD",
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
    "work_group": "developer",
    "gamification_roles": "ROLE_GAME_MASTER"
  }'
```

---

#### 6. Update User (PATCH - Partial)

**Request:**
```bash
curl -X PATCH http://localhost:8080/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"gamification_roles": "ROLE_ARCHITECT"}'
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
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "name": "Developers",
      "description": "Development team",
      "created_at": "2026-01-01T00:00:00+00:00",
      "_links": {
        "self": { "href": "/api/groups/550e8400-e29b-41d4-a716-446655440000" },
        "update": { "href": "/api/groups/550e8400-e29b-41d4-a716-446655440000" },
        "delete": { "href": "/api/groups/550e8400-e29b-41d4-a716-446655440000" }
      }
    },
    {
      "id": "660e8400-e29b-41d4-a716-446655440000",
      "name": "Designers",
      "description": "Design team",
      "created_at": "2026-01-01T00:00:00+00:00",
      "_links": {
        "self": { "href": "/api/groups/660e8400-e29b-41d4-a716-446655440000" },
        "update": { "href": "/api/groups/660e8400-e29b-41d4-a716-446655440000" },
        "delete": { "href": "/api/groups/660e8400-e29b-41d4-a716-446655440000" }
      }
    }
  ],
  "_links": {
    "self": { "href": "/api/groups" },
    "create": { "href": "/api/groups" }
  },
  "meta": {
    "total": 2,
    "count": 2
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
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Developers",
    "description": "Development team group",
    "created_at": "2026-04-03T12:00:00+00:00",
    "_links": {
      "self": { "href": "/api/groups/550e8400-e29b-41d4-a716-446655440000" },
      "update": { "href": "/api/groups/550e8400-e29b-41d4-a716-446655440000" },
      "delete": { "href": "/api/groups/550e8400-e29b-41d4-a716-446655440000" }
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
  "_error": {
    "status": 422,
    "title": "Unprocessable Entity",
    "detail": "Validation failed",
    "errors": [
      {
        "field": "name",
        "message": "Name must be between 2 and 255 characters"
      }
    ]
  }
}
```

---

#### 10. Show Single Group

**Request:**
```bash
curl -X GET http://localhost:8080/api/groups/5c5bb3dc-65e2-4ac4-8244-cfa7921c09aa
```

**Response:**
```json
{
  "_links": {
    "self": { "href": "/api/groups/5c5bb3dc-65e2-4ac4-8244-cfa7921c09aa" },
    "collection": { "href": "/api/groups" }
  },
  "group": {
    "id": "5c5bb3dc-65e2-4ac4-8244-cfa7921c09aa",
    "name": "Developers",
    "description": null,
    "createdAt": { "date": "2026-04-08 16:05:12.000000", "timezone_type": 3, "timezone": "UTC" }
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
curl -X DELETE http://localhost:8080/api/groups/550e8400-e29b-41d4-a716-446655440000
```

**Response:** `204 No Content`

---

### Group STI (Single Table Inheritance)

Groups use STI. The `type` parameter in POST requests determines the group class:

| Type | Class |
|------|-------|
| `developer` | `DeveloperGroup` |
| `designer` | `DesignerGroup` |
| `tester` | `TesterGroup` |

Note: The base `Group` class is hidden from API responses.

#### 14. Create DeveloperGroup

**Request:**
```bash
curl -X POST http://localhost:8080/api/groups \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Developers",
    "description": "Development team",
    "type": "developer"
  }'
```

**Response:**
```json
{
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "name": "Developers",
    "description": "Development team",
    "created_at": "2026-04-03T12:00:00+00:00",
    "_links": {
      "self": { "href": "/api/groups/550e8400-e29b-41d4-a716-446655440000" },
      "update": { "href": "/api/groups/550e8400-e29b-41d4-a716-446655440000" },
      "delete": { "href": "/api/groups/550e8400-e29b-41d4-a716-446655440000" }
    }
  }
}
```

#### 15. Create DesignerGroup

**Request:**
```bash
curl -X POST http://localhost:8080/api/groups \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Designers",
    "description": "Design team",
    "type": "designer"
  }'
```

#### 16. Create TesterGroup

**Request:**
```bash
curl -X POST http://localhost:8080/api/groups \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Testers",
    "description": "QA team",
    "type": "tester"
  }'
```

---

### UserGroup (Many-to-Many)

Users can belong to multiple work groups via the `UserGroup` join entity. Use `work_group` discriminator:

| Discriminator | Class |
|---------------|-------|
| `developer` | DeveloperGroup (rank: 3) |
| `designer` | DesignerGroup (rank: 2) |
| `tester` | TesterGroup (rank: 1) |

#### 17. Change User's Work Group

**Request (PATCH):**
```bash
curl -X PATCH http://localhost:8080/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{
    "work_group": "designer"
  }'
```

#### 18. Remove User from Work Group

**Request (PATCH):**
```bash
curl -X PATCH http://localhost:8080/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{
    "work_group": ""
  }'
```

---

#### 19. PWA Manifest

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
            "url": "{{base_url}}/api/health"
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
              "raw": "{\"email\": \"newuser@example.com\", \"password\": \"password123\", \"work_group\": \"developer\", \"gamification_roles\": \"ROLE_WIZARD\"}"
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
              "raw": "{\"email\": \"updated@example.com\", \"password\": \"newpass\", \"work_group\": \"designer\", \"gamification_roles\": \"ROLE_ARCHITECT\"}"
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
              "raw": "{\"gamification_roles\": \"ROLE_WIZARD\"}"
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
        },
        {
          "name": "Create DeveloperGroup",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/groups",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\"name\": \"Developers\", \"description\": \"Development team\", \"type\": \"developer\"}"
            }
          }
        },
        {
          "name": "Create DesignerGroup",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/groups",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\"name\": \"Designers\", \"description\": \"Design team\", \"type\": \"designer\"}"
            }
          }
        },
        {
          "name": "Create TesterGroup",
          "request": {
            "method": "POST",
            "url": "{{base_url}}/api/groups",
            "header": [{"key": "Content-Type", "value": "application/json"}],
            "body": {
              "mode": "raw",
              "raw": "{\"name\": \"Testers\", \"description\": \"QA team\", \"type\": \"tester\"}"
            }
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

## Validation Error Format

All validation errors now follow a consistent format using the centralized FormProcessor service. When validation fails, you'll receive a 422 Unprocessable Entity response with this structure:

```json
{
  "_error": {
    "status": 422,
    "title": "Unprocessable Entity",
    "detail": "Validation failed",
    "errors": [
      {
        "field": "field_name",
        "message": "Validation error message"
      }
    ]
  }
}
```

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

### Test Validation - Short Password
```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "123", "work_group": "developer"}'
```

### Test Validation - Invalid Work Group
```bash
curl -X POST http://localhost:8080/api/users \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "secure123", "work_group": "invalid_group"}'
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
