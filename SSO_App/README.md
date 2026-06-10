# Simple Authentication Application with SSO

Simple JavaScript application with a registration system, local login and SSO authentication from a centralized Hub.

## Features

- ✅ User registration (local)
- ✅ Secure login (local)
- ✅ **SSO Authentication from Hub**
- ✅ Protected home page
- ✅ SQLite database
- ✅ Password hashing with bcrypt
- ✅ JWT-based stateless authentication

## Technologies used

- Node.js
- Express
- SQLite3
- bcrypt
- jsonwebtoken (JWT)
- dotenv

## Installation

```bash
npm install
```

## Configuration

Create a `.env` file at the root of the project with the following variables:

```env
# SSO Configuration
SSO_HUB_URL=http://localhost:8000
SSO_SECRET=your_secret_shared_with_hub

# Server Configuration
PORT=3000
```

**Important:**

- The `SSO_SECRET` must match the `sso_secret` configured in the Hub for this application
- The `SSO_HUB_URL` must point to the SSO Hub URL
- Authentication is now stateless using JWT tokens

## Startup

```bash
npm start
```

The application will be accessible at http://localhost:3000

## Available pages

- `/register.html` - Registration page (local)
- `/login.html` - Login page (local)
- `/auth/callback` - Callback page for SSO authentication
- `/home.html` - Home page (protected)
- `/` - Redirects to login or home based on login status

## Project structure

```
SSO_Apps/
├── server.js          # Express server and API
├── package.json       # Dependencies
├── .env              # Environment variables (do not version)
├── .gitignore        # Files to ignore
├── database.db       # SQLite database (created automatically)
└── public/          # Static files
    ├── register.html  # Registration page
    ├── login.html     # Login page
    ├── callback.html  # SSO callback page
    ├── home.html      # Home page
    └── style.css      # CSS styles
```

## SSO authentication flow

1. **User clicks on the application in the Hub**
   - Hub generates a temporary SSO code
   - Redirects to: `http://localhost:3000/auth/callback?code=abc123`

2. **Callback page receives the code**
   - Gets code from URL
   - Sends code to backend: `POST /api/auth/sso`

3. **Backend verifies the code**
   - Calls Hub: `POST {SSO_HUB_URL}/sso/verify`
   - Sends: `{ code, application_id, sso_secret }`

4. **Hub validates and responds**
   - Verifies code (not expired, not used, secret valid)
   - Returns user info + JWT
   - Invalidates code

5. **Application creates session**
   - Creates or gets local user
   - Stores SSO info in session
   - Redirects to home page

## API Endpoints

### POST /api/register

Register a new user (local)

```json
{
  "username": "username",
  "password": "password"
}
```

### POST /api/login

Login a user (local)

```json
{
  "username": "username",
  "password": "password"
}
```

### POST /api/auth/sso

SSO authentication (called by callback page)

```json
{
  "code": "temporary_code_from_hub"
}
```

**Logic:**

1. Receives SSO code from frontend
2. Calls Hub to verify code
3. Creates or gets local user
4. Creates session with SSO info
5. Returns success and redirect

### POST /api/logout

Logout current user

### GET /api/user

Gets information for logged-in user (requires authentication)

**Response for SSO user:**

```json
{
  "id": 1,
  "username": "jean@example.com",
  "authMethod": "SSO",
  "ssoUser": {
    "id": 123,
    "email": "jean@example.com",
    "firstname": "Jean",
    "lastname": "Dupont",
    "role": ["ROLE_USER"],
    "organization_id": 5
  }
}
```

## Security

- Passwords are hashed with bcrypt (10 rounds)
- Sessions are secure with express-session
- Home page is protected by authentication middleware
- User input validation
- **SSO:** Secure communication with Hub via shared secret
- **SSO:** SSO codes expire in 30 seconds
- **SSO:** Single use of codes (invalidation after use)
- **SSO:** SSO secret is never exposed on frontend

## Hub configuration

For this application to work with SSO Hub, configure in Hub:

1. **sso_callback_url** : `http://localhost:3000/auth/callback`
2. **sso_secret** : Same secret as in your `.env`
3. **application_id** : Application ID in Hub

## SSO testing

To test complete SSO flow:

1. Make sure SSO Hub is started and accessible
2. Configure `.env` file correctly
3. Start application: `npm start`
4. In Hub, click on application link
5. You should be automatically logged in to application

**Scenarios to test:**

- ✅ Normal SSO login from Hub
- ✅ Expired code (wait 30+ seconds)
- ✅ Reused code (reload callback page)
- ✅ Wrong secret in `.env`
- ✅ Hub unavailable
