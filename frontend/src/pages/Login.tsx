import { useNavigate } from "@tanstack/react-router";
import { useState } from "react";
import { authService } from "../services/authService";
import { useAuthStore } from "../store/authStore";
import type { LoginFormData, FormErrors } from "../types";

const Login = () => {
  const navigate = useNavigate();
  const { setUser } = useAuthStore();
  const [formData, setFormData] = useState<LoginFormData>({
    username: "",
    password: "",
    rememberMe: false,
  });
  const [errors, setErrors] = useState<FormErrors>({});
  const [isLoading, setIsLoading] = useState(false);
  const [generalError, setGeneralError] = useState("");
  const [showAdminLink, setShowAdminLink] = useState(false);

  const validateForm = (): boolean => {
    const newErrors: FormErrors = {};

    if (!formData.username) newErrors.username = "Username is required";

    if (!formData.password) newErrors.password = "Password is required";

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setGeneralError("");

    if (!validateForm()) return;

    setIsLoading(true);

    try {
      const response = await authService.login({
        username: formData.username,
        password: formData.password,
        rememberMe: formData.rememberMe,
      });

      if (response.success && response.user) {
        setUser(response.user);
        navigate({ to: "/" });
      } else {
        setGeneralError(response.message || "Login failed");
        // Check if it's an admin trying to access user portal
        setShowAdminLink(
          !!(
            response.message?.includes("Admin") ||
            response.message?.includes("admin portal")
          ),
        );
        if (response.errors) {
          setErrors(response.errors);
        }
      }
    } catch (error) {
      setGeneralError("An error occurred during login");
    } finally {
      setIsLoading(false);
    }
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value, type, checked } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: type === "checkbox" ? checked : value,
    }));
    // Clear error when user starts typing
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: "" }));
    }
  };

  return (
    <div className="flex flex-col items-center justify-center min-h-[60vh]">
      <div className="text-center mb-8">
        <img
          src="../ams_aircraft_management_system_logo.jpeg"
          alt="AMS Logo"
          className="h-24 w-24 rounded-2xl object-cover mx-auto mb-6 shadow-lg shadow-blue-900/30"
        />
        <h1 className="text-4xl font-medium text-white tracking-tight mb-4">
          AMS APPS HUB
        </h1>
        <p className="text-lg text-slate-500 mb-8">
          Please sign in to access your hub
        </p>

        <div className="w-full max-w-md">
          {generalError && (
            <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
              <p>{generalError}</p>
              {showAdminLink && (
                <p className="mt-2 text-xs">
                  If you have admin privileges, please{" "}
                  <a
                    href="/admin"
                    className="underline hover:text-red-300 transition-colors"
                  >
                    access the admin portal
                  </a>
                  .
                </p>
              )}
            </div>
          )}

          <form onSubmit={handleSubmit}>
            <div className="form-group">
              <label className="form-label">Username</label>
              <input
                type="text"
                name="username"
                value={formData.username}
                onChange={handleChange}
                className="form-input"
                placeholder="johndoe or john.doe@example.com"
              />
              {errors.username && (
                <p className="form-error">{errors.username}</p>
              )}
            </div>

            <div className="form-group">
              <label className="form-label">Password</label>
              <input
                type="password"
                name="password"
                value={formData.password}
                onChange={handleChange}
                className="form-input"
                placeholder="••••••••"
              />
              {errors.password && (
                <p className="form-error">{errors.password}</p>
              )}
            </div>

            <div className="flex items-center justify-between mb-6">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  name="rememberMe"
                  checked={formData.rememberMe}
                  onChange={handleChange}
                  className="w-4 h-4 rounded border-slate-700 bg-slate-800 text-blue-600 focus:ring-2 focus:ring-blue-500 focus:ring-offset-0"
                />
                <span className="text-sm text-slate-400">Remember me</span>
              </label>
            </div>

            <button type="submit" disabled={isLoading} className="btn-primary">
              {isLoading ? "Signing In..." : "Sign In"}
            </button>
          </form>
        </div>
      </div>
    </div>
  );
};

export default Login;
