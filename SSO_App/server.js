require("dotenv").config();
const express = require("express");
const jwt = require("jsonwebtoken");
const bcrypt = require("bcrypt");
const sqlite3 = require("sqlite3").verbose();
const path = require("path");
const fetch = require("node-fetch");

const app = express();
const PORT = process.env.PORT || 3000;

// SQLite database configuration
const db = new sqlite3.Database("./database.db", (err) => {
  if (err) {
    console.error("Error connecting to database:", err);
  } else {
    initDatabase();
  }
});

// Create users table if it doesn't exist
function initDatabase() {
  db.run(
    `
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT UNIQUE NOT NULL,
      password TEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `,
    (err) => {
      if (err) {
        console.error("Error creating table:", err);
      }
    },
  );
}

// Middleware
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static("public"));

// Middleware to check authentication - Validate JWT from Hub
function requireAuth(req, res, next) {
  const token = req.headers.authorization?.split(" ")[1];

  if (!token) {
    console.log("[requireAuth] No token provided");
    return res.status(401).json({ error: "No authentication token provided" });
  }
  try {
    // Verify JWT from Hub (without secret - just decode and check structure)
    // In production, you should verify the signature using the Hub's public key
    const decoded = jwt.decode(token);
    console.log("[requireAuth] Decoded token:", decoded);

    if (!decoded) {
      return res.status(401).json({ error: "Invalid token" });
    }

    // Accept both 'sub' (standard JWT) and 'user_id' (Hub's JWT format)
    if (!decoded.sub && !decoded.user_id) {
      console.log("[requireAuth] No sub or user_id in token");
      return res.status(401).json({ error: "Invalid token" });
    }

    req.user = decoded;
    next();
  } catch (error) {
    console.log("[requireAuth] Token validation error:", error);
    return res.status(401).json({ error: "Token validation failed" });
  }
}

// Routes for HTML pages
app.get("/", (req, res) => {
  res.redirect("/login.html");
});

app.get("/home.html", (req, res) => {
  res.sendFile(path.join(__dirname, "public", "home.html"));
});

// Route for SSO callback
app.get("/auth/callback", (req, res) => {
  res.sendFile(path.join(__dirname, "public", "callback.html"));
});

// API - Registration
app.post("/api/register", async (req, res) => {
  const { username, password } = req.body;

  if (!username || !password) {
    return res.status(400).json({ error: "Username and password required" });
  }

  if (password.length < 6) {
    return res
      .status(400)
      .json({ error: "Password must contain at least 6 characters" });
  }

  try {
    // Hash the password
    const hashedPassword = await bcrypt.hash(password, 10);

    // Insert into database
    db.run(
      "INSERT INTO users (username, password) VALUES (?, ?)",
      [username, hashedPassword],
      function (err) {
        if (err) {
          if (err.message.includes("UNIQUE constraint failed")) {
            return res
              .status(400)
              .json({ error: "This username already exists" });
          }
          return res.status(500).json({ error: "Registration error" });
        }

        // Generate JWT token for local users
        const token = jwt.sign(
          {
            sub: this.lastID,
            username: username,
            authMethod: "local",
          },
          "local-secret", // For local auth, using a fixed secret
          { expiresIn: "24h" },
        );

        res.json({
          success: true,
          message: "Registration successful",
          token: token,
          redirect: "/home.html",
        });
      },
    );
  } catch (error) {
    res.status(500).json({ error: "Server error" });
  }
});

// API - Login
app.post("/api/login", (req, res) => {
  const { username, password } = req.body;

  if (!username || !password) {
    return res.status(400).json({ error: "Username and password required" });
  }

  db.get(
    "SELECT * FROM users WHERE username = ?",
    [username],
    async (err, user) => {
      if (err) {
        return res.status(500).json({ error: "Server error" });
      }

      if (!user) {
        return res.status(401).json({ error: "Invalid username or password" });
      }

      try {
        // Verify password
        const match = await bcrypt.compare(password, user.password);

        if (match) {
          // Generate JWT token for local users
          const token = jwt.sign(
            {
              sub: user.id,
              username: user.username,
              authMethod: "local",
            },
            "local-secret",
            { expiresIn: "24h" },
          );

          res.json({
            success: true,
            message: "Login successful",
            token: token,
            redirect: "/home.html",
          });
        } else {
          res.status(401).json({ error: "Invalid username or password" });
        }
      } catch (error) {
        res.status(500).json({ error: "Server error" });
      }
    },
  );
});

// API - Logout
app.post("/api/logout", (req, res) => {
  // In stateless JWT architecture, logout is handled client-side by removing the token
  res.json({ success: true, message: "Logout successful" });
});

// API - SSO Authentication
app.post("/api/auth/sso", async (req, res) => {
  const { code } = req.body;

  if (!code) {
    return res.status(400).json({
      success: false,
      error: "SSO code required",
    });
  }

  // SSO configuration from environment variables
  const SSO_HUB_URL = process.env.SSO_HUB_URL;
  const SSO_SECRET = process.env.SSO_SECRET;

  if (!SSO_HUB_URL || !SSO_SECRET) {
    console.error("Missing SSO configuration in .env");
    return res.status(500).json({
      success: false,
      error: "SSO configuration not available",
    });
  }

  try {
    // Call the Hub to verify the code
    console.log(`[SSO] Verifying code with ${SSO_HUB_URL}`);

    const response = await fetch(`${SSO_HUB_URL}/sso/verify`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        code: code,
        sso_secret: SSO_SECRET,
      }),
    });

    console.log(`[SSO] Hub response: status=${response.status}`);

    // Check the Content-Type of the response
    const contentType = response.headers.get("content-type");
    if (!contentType || !contentType.includes("application/json")) {
      const text = await response.text();
      console.error(
        `[SSO] Hub did not return JSON. Content-Type: ${contentType}`,
      );
      console.error(`[SSO] Response received: ${text.substring(0, 200)}`);
      return res.status(500).json({
        success: false,
        error: "Hub returned invalid response format",
      });
    }

    const data = await response.json();
    console.log(`[SSO] Data received:`, data);

    if (!response.ok || !data.success) {
      console.error(`[SSO] Hub error:`, data.error);
      return res.status(400).json({
        success: false,
        error: data.error || "Code SSO invalide",
      });
    }

    const ssoUser = data.user;
    if (!ssoUser || !ssoUser.email) {
      console.error("[SSO] No user info in response");
      return res.status(400).json({
        success: false,
        error: "No user information returned",
      });
    }

    // Check if user already exists in our local database
    db.get(
      "SELECT * FROM users WHERE username = ?",
      [ssoUser.email],
      async (err, existingUser) => {
        if (err) {
          console.error("Database error:", err);
          return res.status(500).json({
            success: false,
            error: "Server error",
          });
        }

        let userId;
        let username;

        if (existingUser) {
          userId = existingUser.id;
          username = existingUser.username;
        } else {
          // Create a new local user
          // Generate a random password (it will never be used)
          const randomPassword = require("crypto")
            .randomBytes(32)
            .toString("hex");
          const hashedPassword = await bcrypt.hash(randomPassword, 10);

          await new Promise((resolve, reject) => {
            db.run(
              "INSERT INTO users (username, password) VALUES (?, ?)",
              [ssoUser.email, hashedPassword],
              function (err) {
                if (err) {
                  console.error("User creation error:", err);
                  reject(err);
                } else {
                  userId = this.lastID;
                  username = ssoUser.email;
                  resolve();
                }
              },
            );
          });
        }

        // Return the JWT from Hub directly (stateless approach)
        // The Hub's JWT contains all necessary user info and is already signed
        console.log(
          `[SSO] Sending response with token: ${data.jwt.substring(0, 30)}...`,
        );
        res.json({
          success: true,
          message: "SSO login successful",
          token: data.jwt, // Use the Hub's JWT directly
          redirect: "/home.html",
        });
      },
    );
  } catch (error) {
    console.error("[SSO] Error during SSO verification:", error);
    return res.status(500).json({
      success: false,
      error: "Error connecting to SSO Hub: " + error.message,
    });
  }
});

// API - Get logged-in user information
app.get("/api/user", requireAuth, (req, res) => {
  // Handle both 'sub' (local JWT) and 'user_id' (Hub's JWT format)
  const response = {
    id: req.user.sub || req.user.user_id,
    username: req.user.username || req.user.email,
    authMethod: req.user.authMethod || "SSO",
  };

  console.log("[/api/user] Returning user data:", response);
  res.json(response);
});

// Server startup
app.listen(PORT, () => {
  console.log(`Server started on http://localhost:${PORT}`);
});

// Clean shutdown
process.on("SIGINT", () => {
  db.close((err) => {
    if (err) {
      console.error("Database close error:", err);
    } else {
      console.log("Database closed");
    }
    process.exit(0);
  });
});
