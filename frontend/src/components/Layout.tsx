import { Link, Outlet, useLocation } from "@tanstack/react-router";
import { User, LogOut, Menu, X } from "lucide-react";
import { useAuth } from "../hooks/useAuth";
import { useState } from "react";

export const Layout = () => {
  const { user, isAuthenticated, logout } = useAuth();
  const location = useLocation();
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

  const isLoginPage = location.pathname === "/login";

  const handleSignOut = async () => {
    await logout();
  };

  return (
    <div className="bg-slate-950 text-slate-300 min-h-screen flex flex-col antialiased selection:bg-blue-500/30 selection:text-blue-100">
      {/* Subtle Ambient Background */}
      <div className="fixed inset-0 z-0 pointer-events-none">
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-3xl h-64 bg-blue-900/10 blur-[120px] rounded-full"></div>
      </div>

      {/* Navigation - Hidden on login page */}
      {!isLoginPage && (
        <header className="relative z-20 w-full border-b border-white/5 bg-slate-950/50 backdrop-blur-md">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 h-16 sm:h-20 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <Link
                to="/"
                className="flex items-center gap-3 hover:opacity-80 transition-opacity"
              >
                <img
                  src="/ams_aircraft_management_system_logo.jpeg"
                  alt="AMS Logo"
                  className="h-10 w-10 rounded-lg object-cover shadow-lg shadow-blue-900/20"
                />
                <span className="text-lg sm:text-xl font-medium tracking-tight text-slate-100 hover:text-white transition-colors">
                  AMS APPS HUB
                </span>
              </Link>
            </div>

            {/* Desktop Menu - Hidden on mobile */}
            <div className="hidden md:flex items-center gap-4">
              {isAuthenticated ? (
                <>
                  <div className="flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-slate-200 transition-colors">
                    <User className="w-4 h-4" />
                    {user?.firstName} {user?.lastName}
                  </div>
                  <div className="text-slate-600">|</div>
                  <div className="flex items-center text-sm font-medium text-slate-400 hover:text-slate-200 transition-colors">
                    {user?.organization?.name}
                  </div>
                  <div>
                    <button
                      className="group flex cursor-pointer items-center gap-2 px-4 py-2 rounded-lg border border-red-500/20 bg-red-500/5 text-red-400 hover:bg-red-500/10 hover:border-red-500/30 hover:text-red-300 transition-all duration-200 ease-out"
                      onClick={handleSignOut}
                    >
                      <LogOut className="w-4 h-4" />
                      <span className="text-sm font-medium">Logout</span>
                    </button>
                  </div>
                </>
              ) : null}
            </div>

            {/* Mobile Menu Button - Hidden on desktop */}
            <div className="md:hidden">
              {isAuthenticated && (
                <button
                  onClick={() => setIsMobileMenuOpen(!isMobileMenuOpen)}
                  className="p-2 text-slate-400 hover:text-slate-200 transition-colors"
                  title="Toggle menu"
                >
                  {isMobileMenuOpen ? (
                    <X className="w-6 h-6" />
                  ) : (
                    <Menu className="w-6 h-6" />
                  )}
                </button>
              )}
            </div>
          </div>

          {/* Mobile Menu - Hidden on desktop */}
          {!isLoginPage && isAuthenticated && isMobileMenuOpen && (
            <div className="absolute top-full left-0 right-0 md:hidden z-50 border-b border-white/5 bg-slate-900 shadow-lg">
              <div className="px-4 sm:px-6 py-4 space-y-4">
                <div className="flex items-center gap-2 text-sm font-medium text-slate-400">
                  <User className="w-4 h-4" />
                  <span>
                    {user?.firstName} {user?.lastName}
                  </span>
                </div>
                <div className="h-px bg-slate-700"></div>
                <div className="flex items-center text-sm font-medium text-slate-400">
                  <span className="text-slate-500 mr-2">Organization:</span>
                  {user?.organization?.name}
                </div>
                <div className="h-px bg-slate-700"></div>
                <button
                  className="w-full group flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-red-500/20 bg-red-500/5 text-red-400 hover:bg-red-500/10 hover:border-red-500/30 hover:text-red-300 transition-all duration-200 ease-out"
                  onClick={handleSignOut}
                >
                  <LogOut className="w-4 h-4" />
                  <span className="text-sm font-medium">Logout</span>
                </button>
              </div>
            </div>
          )}
        </header>
      )}

      {/* Main Content */}
      <main className="relative z-10 flex-grow flex flex-col max-w-7xl mx-auto w-full px-4 sm:px-6 py-8 sm:py-12">
        <Outlet />
      </main>

      {/* Footer */}
      <footer className="relative z-10 w-full border-t border-white/5 py-8 mt-auto">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 text-center md:text-left">
          <p className="text-sm sm:text-base text-slate-600">
            &copy; 2025 Production Systems. All rights reserved.
          </p>
        </div>
      </footer>
    </div>
  );
};
