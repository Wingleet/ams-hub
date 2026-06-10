# 📚 Complete SSO Guide

## ✅ Implementation completed

### Database

- Table `sso_code` : Temporary codes (64 characters) valid for 30 seconds
- Table `application` : New fields `sso_secret` and `sso_callback_url`

### Backend (Symfony)

- `SsoController.php` :
  - `GET /sso/authorize?application_id={id}` → generates code + redirect
  - `POST /sso/verify` → validates secret/code + returns user + JWT
- `SsoCode` Entity & Repository
- `SsoCleanupCommand` : `php bin/console app:sso:cleanup`

### Frontend (React)

- Home.tsx : "Visit Application" links → `/sso/authorize?application_id={id}`

### Security

- `security.yaml` : SSO firewall with JWT authentication
- `JwtAuthenticator` : Support `/sso/*` routes + login redirect on error

## 🧪 How to test

### 1. Login to the Hub

```
URL: http://localhost/
Email: admin1@admin.com
Password: admin123
```

### 2. Navigate to Home and Click "Visit Application"

- Frontend triggers: `GET /sso/authorize?application_id=1`
- Hub backend generates code + redirects to callback URL with code parameter
- SSO_App receives code at `/callback?code=<code>`

### 3. Test with cURL

**Verify a code:**

```bash
curl -X POST "http://localhost/sso/verify" \
  -H "Content-Type: application/json" \
  -d '{
    "code": "CODE_HERE",
    "application_id": 1,
    "sso_secret": "a0d2564005e60a4775f4ad5713b360afde573eeb6f659b33799f6621e14caea2"
  }' | jq
```

## 🔐 Application configuration

For external applications integrating with the SSO Hub:

```env
SSO_HUB_URL=http://localhost
SSO_SECRET=a0d2564005e60a4775f4ad5713b360afde573eeb6f659b33799f6621e14caea2
SSO_CALLBACK_URL=http://your-app:port/auth/callback
```

## ⚠️ To do on application side

1. **Callback page** (`/auth/callback`) :
   - Get `code` from URL
   - Send to backend: `POST /api/auth/sso { code }`

2. **Backend endpoint** (`POST /api/auth/sso`) :
   - Call Hub: `POST http://localhost/sso/verify { code, application_id, sso_secret }`
   - Create local session on success

## 🔒 Security

- ✅ Single-use codes + 30s expiration
- ✅ Secret never exposed in frontend
- ✅ Cryptographically secure generation
- ✅ Authentication required for `/sso/authorize`
- ✅ `/sso/verify` public (server-to-server)

## 📊 Useful SQL queries

```sql
-- View generated codes
SELECT id, code, expires_at, used_at FROM sso_code ORDER BY created_at DESC;

-- Expired unused codes
SELECT COUNT(*) FROM sso_code WHERE expires_at < NOW();
```

## 🔄 Automatic cleanup (optional)

```bash
# Add to cron
0 2 * * * cd /path/to/backend && php bin/console app:sso:cleanup
```

---

✅ **The Hub SSO system is operational!**
Each application must implement client-side endpoints.
