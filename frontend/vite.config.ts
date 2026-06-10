import { defineConfig, loadEnv } from "vite";
import react from "@vitejs/plugin-react";
import tailwindcss from "@tailwindcss/vite";
import { tanstackRouter } from "@tanstack/router-plugin/vite";

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Load env file based on `mode` in the current working directory.
  const env = loadEnv(mode, process.cwd(), "");

  return {
    plugins: [
      tanstackRouter({
        target: "react",
        autoCodeSplitting: true,
      }),
      react(),
      tailwindcss(),
    ],
    define: {
      // Explicitly define environment variables
      "import.meta.env.VITE_API_URL": JSON.stringify(
        env.VITE_API_URL || process.env.VITE_API_URL || "http://localhost",
      ),
    },
    server: {
      port: 5173,
      host: true,
      strictPort: true,
      allowedHosts: ["localhost", "staging-ams-apps-hub.wingleetdev.com"],
      watch: {
        usePolling: true,
      },
    },
    test: {
      globals: true,
      environment: "jsdom",
      setupFiles: "./src/test/setup.ts",
      css: true,
      coverage: {
        provider: "v8",
        reporter: ["text", "json", "html"],
        exclude: [
          "node_modules/",
          "src/test/",
          "**/*.test.ts",
          "**/*.test.tsx",
          "src/routeTree.gen.ts",
          "src/main.tsx",
          "src/routes/",
          "**/*.d.ts",
          "src/components/",
          "src/pages/",
          "src/App.tsx",
        ],
        include: [
          "src/services/**/*.{ts,tsx}",
          "src/store/**/*.{ts,tsx}",
          "src/hooks/**/*.{ts,tsx}",
        ],
        all: true,
        lines: 80,
        functions: 80,
        branches: 80,
        statements: 80,
      },
    },
  };
});
