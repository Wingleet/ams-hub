// Auto-détection de l'URL de l'API selon l'environnement
// Si VITE_API_URL est défini ET non vide, on l'utilise
// Sinon, on utilise l'origine actuelle (localhost ou domaine staging)
const getApiUrl = () => {
  const envUrl = import.meta.env.VITE_API_URL;
  // Si la variable est définie et non vide, on l'utilise
  if (envUrl && envUrl.trim() !== "") {
    return envUrl;
  }
  // Sinon, auto-détection basée sur l'origine
  return typeof window !== "undefined"
    ? window.location.origin
    : "http://localhost";
};

export const API_URL = getApiUrl();

let isRefreshing = false;
let failedQueue: Array<{
  resolve: (value?: unknown) => void;
  reject: (reason?: unknown) => void;
}> = [];

const processQueue = (error: Error | null = null) => {
  failedQueue.forEach((prom) => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve();
    }
  });

  failedQueue = [];
};

/**
 * Wrapper around fetch with automatic token refresh handling
 */
export const apiFetch = async (
  endpoint: string,
  options?: RequestInit,
): Promise<Response> => {
  const url = endpoint.startsWith("http") ? endpoint : `${API_URL}${endpoint}`;

  // First attempt
  let response = await fetch(url, {
    ...options,
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      ...options?.headers,
    },
  });

  // If 401 and it's not an auth or refresh request
  if (
    response.status === 401 &&
    !endpoint.includes("/auth/login") &&
    !endpoint.includes("/auth/refresh") &&
    !endpoint.includes("/auth/me")
  ) {
    if (!isRefreshing) {
      isRefreshing = true;

      try {
        // Attempt to refresh the token
        const refreshResponse = await fetch(`${API_URL}/api/auth/refresh`, {
          method: "POST",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
          },
        });

        if (refreshResponse.ok) {
          // Token refreshed successfully, process the queue
          processQueue();

          // Retry the original request
          response = await fetch(url, {
            ...options,
            credentials: "include",
            headers: {
              "Content-Type": "application/json",
              ...options?.headers,
            },
          });
        } else {
          // Refresh failed, disconnect the user
          processQueue(new Error("Session expired"));
          // Only redirect if not already on /login
          if (window.location.pathname !== "/login") {
            window.location.href = "/login";
          }
        }
      } catch (error) {
        processQueue(error as Error);
        // Only redirect if not already on /login
        if (window.location.pathname !== "/login") {
          window.location.href = "/login";
        }
      } finally {
        isRefreshing = false;
      }
    } else {
      // Another request is already refreshing the token
      // Queue this request
      return new Promise((resolve, reject) => {
        failedQueue.push({ resolve, reject });
      }).then(() => {
        // Retry the request once the token is refreshed
        return fetch(url, {
          ...options,
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
            ...options?.headers,
          },
        });
      }) as Promise<Response>;
    }
  }

  return response;
};
