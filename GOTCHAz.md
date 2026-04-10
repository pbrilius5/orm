# GOTCHAS

Bugs/*todoz*, related **AI**.

## TESTER

### PUT atsakyme null vietoje rolės vartotojui

```shell
curl -X PUT http://localhost:8080/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{
    "email": "updated@example.com",
    "password": "newpassword456",
    "work_group": "developer",
    "gamification_roles": "ROLE_GAME_MASTER"
  }'
```

```json
{
    "_links": {
        "self": {
            "href": "/user/6b8f829b-a498-489a-bde3-e94aed34f2f0"
        }
    },
    "user": {
        "id": "6b8f829b-a498-489a-bde3-e94aed34f2f0",
        "email": "updated@example.com",
        "workGroupName": "Developers",
        "role": null,
        "createdAt": {
            "date": "2026-04-10 07:23:30.000000",
            "timezone_type": 3,
            "timezone": "UTC"
        }
    }
}
```

```shell
[2026-04-10T07:24:56.301474+00:00] app.DEBUG: Incoming request {"method":"PUT","uri":"http://localhost:8080/api/users/6b8f829b-a498-489a-bde3-e94aed34f2f0","ip":"::1","user_agent":"PostmanRuntime/7.49.1"} {"app_env":"dev","app_debug":true,"php_version":"8.4.11","script_name":"/index.php","memory_usage":"2 MB","file":"/home/povilasb/.src/orm-develop/src/Kernel.php","line":197,"class":"App\\Kernel","callType":"->","function":"handle","uid":"50c0f5e"}
[2026-04-10T07:24:56.305589+00:00] app.DEBUG: SecurityMiddleware processing request {"method":"PUT","uri":"/api/users/6b8f829b-a498-489a-bde3-e94aed34f2f0","ip":"::1"} {"app_env":"dev","app_debug":true,"php_version":"8.4.11","script_name":"/index.php","memory_usage":"2 MB","file":"/home/povilasb/.src/orm-develop/src/Middleware/SecurityMiddleware.php","line":26,"class":"App\\Middleware\\SecurityMiddleware","callType":"->","function":"process","uid":"50c0f5e"}
[2026-04-10T07:24:56.306369+00:00] app.DEBUG: CorsMiddleware processing request {"method":"PUT","origin":"(none)","uri":"/api/users/6b8f829b-a498-489a-bde3-e94aed34f2f0"} {"app_env":"dev","app_debug":true,"php_version":"8.4.11","script_name":"/index.php","memory_usage":"2 MB","file":"/home/povilasb/.src/orm-develop/src/Middleware/CorsMiddleware.php","line":40,"class":"App\\Middleware\\CorsMiddleware","callType":"->","function":"process","uid":"50c0f5e"}
[2026-04-10T07:24:56.307049+00:00] app.DEBUG: RateLimitMiddleware checking rate limit {"ip":"::1","uri":"/api/users/6b8f829b-a498-489a-bde3-e94aed34f2f0","method":"PUT","limit":100,"window":60} {"app_env":"dev","app_debug":true,"php_version":"8.4.11","script_name":"/index.php","memory_usage":"2 MB","file":"/home/povilasb/.src/orm-develop/src/Middleware/RateLimitMiddleware.php","line":59,"class":"App\\Middleware\\RateLimitMiddleware","callType":"->","function":"process","uid":"50c0f5e"}
[2026-04-10T07:24:56.307827+00:00] app.DEBUG: RateLimitMiddleware allowing request {"ip":"::1","uri":"/api/users/6b8f829b-a498-489a-bde3-e94aed34f2f0","method":"PUT","count":1,"limit":100,"remaining":99} {"app_env":"dev","app_debug":true,"php_version":"8.4.11","script_name":"/index.php","memory_usage":"2 MB","file":"/home/povilasb/.src/orm-develop/src/Middleware/RateLimitMiddleware.php","line":152,"class":"App\\Middleware\\RateLimitMiddleware","callType":"->","function":"process","uid":"50c0f5e"}
[2026-04-10T07:24:56.308560+00:00] app.DEBUG: CsrfMiddleware processing request {"method":"PUT","path":"/api/users/6b8f829b-a498-489a-bde3-e94aed34f2f0","exempt":true} {"app_env":"dev","app_debug":true,"php_version":"8.4.11","script_name":"/index.php","memory_usage":"2 MB","file":"/home/povilasb/.src/orm-develop/src/Middleware/CsrfMiddleware.php","line":42,"class":"App\\Middleware\\CsrfMiddleware","callType":"->","function":"process","uid":"50c0f5e"}
[2026-04-10T07:24:56.809888+00:00] app.DEBUG: CorsMiddleware adding CORS headers {"method":"PUT","origin":"(none)","status_code":200} {"app_env":"dev","app_debug":true,"php_version":"8.4.11","script_name":"/index.php","memory_usage":"2 MB","file":"/home/povilasb/.src/orm-develop/src/Middleware/CorsMiddleware.php","line":56,"class":"App\\Middleware\\CorsMiddleware","callType":"->","function":"process","uid":"50c0f5e"}
[2026-04-10T07:24:56.810695+00:00] app.DEBUG: SecurityMiddleware finished processing {"method":"PUT","uri":"/api/users/6b8f829b-a498-489a-bde3-e94aed34f2f0","status_code":200} {"app_env":"dev","app_debug":true,"php_version":"8.4.11","script_name":"/index.php","memory_usage":"2 MB","file":"/home/povilasb/.src/orm-develop/src/Middleware/SecurityMiddleware.php","line":34,"class":"App\\Middleware\\SecurityMiddleware","callType":"->","function":"process","uid":"50c0f5e"}
```
