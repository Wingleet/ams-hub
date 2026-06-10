import { useState, useEffect } from "react";
import { Zap } from "lucide-react";
import { applicationService } from "../services/applicationService";
import { API_URL } from "../services/apiClient";
import { useAuth } from "../hooks/useAuth";
import type { Application } from "../types";

const Home = () => {
  const { user } = useAuth();
  const [allApplications, setAllApplications] = useState<Application[]>([]);
  const [subscribedApplications, setSubscribedApplications] = useState<
    Application[]
  >([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const loadApplications = async () => {
      setIsLoading(true);
      setError(null);

      try {
        const organizationId = user?.organization?.id;
        const result =
          await applicationService.getMyApplications(organizationId);

        if (result.success && result.data) {
          const apps = Array.isArray(result.data) ? result.data : [];
          const subscribed = apps.filter((app) => app.isSubscribed);
          const available = apps.filter((app) => !app.isSubscribed);

          setSubscribedApplications(subscribed);
          setAllApplications(available);
        } else {
          setError(result.message || "Failed to load applications");
        }
      } catch (err) {
        console.error("Error loading applications:", err);
        setError("An unexpected error occurred while loading applications");
        setSubscribedApplications([]);
        setAllApplications([]);
      }

      setIsLoading(false);
    };

    if (user?.organization?.id) {
      loadApplications();
    }
  }, [user?.organization?.id]);

  const decodeHTML = (html: string) => {
    const textarea = document.createElement("textarea");
    textarea.innerHTML = html;
    return textarea.value;
  };

  return (
    <>
      {/* Organization Desactivated Warning */}
      {user?.organization && !user.organization.isActive && (
        <div className="mb-6 p-4 bg-red-500/15 border border-red-500/40 rounded-lg flex items-start gap-4">
          <svg
            className="w-6 h-6 text-red-400 flex-shrink-0 mt-0.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={2}
              d="M12 9v2m0 4v2m0 0a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <div className="flex-1">
            <h3 className="text-red-400 font-semibold mb-1">
              Organization Desactivated
            </h3>
            <p className="text-red-300 text-sm">
              Your organization has been desactivated. Please contact support
              for more information.
            </p>
          </div>
        </div>
      )}

      {/* Error Message */}
      {error && (
        <div className="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400">
          {error}
        </div>
      )}

      {/* Loading State */}
      {isLoading && (
        <div className="flex items-center justify-center py-12">
          <div className="text-slate-400">Loading applications...</div>
        </div>
      )}

      {!isLoading && (
        <>
          {/* My Applications Section */}
          <section className="mb-16">
            <div className="mb-6">
              <div className="flex items-center gap-3 mb-2">
                <h2 className="text-2xl font-medium text-white tracking-tight">
                  My Applications
                </h2>
              </div>
            </div>

            {/* Empty State */}
            {subscribedApplications.length === 0 ? (
              <div className="text-center py-12 bg-slate-800/30 border border-slate-700 rounded-lg">
                <p className="text-slate-400 text-lg mb-2">
                  No subscriptions yet
                </p>
                <p className="text-slate-500">
                  Subscribe to applications below to see them here
                </p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {subscribedApplications.map((application) => {
                  return (
                    <div
                      key={application.id}
                      className="bg-slate-800/50 border border-slate-700 rounded-lg p-6 hover:border-slate-600 transition-colors flex flex-col"
                    >
                      <div className="flex items-start justify-between mb-4">
                        <div className="flex items-center gap-4">
                          {application.iconUrl ? (
                            <img
                              src={application.iconUrl}
                              alt={`${application.name} icon`}
                              className="w-12 h-12 rounded-lg object-cover"
                              onError={(e) => {
                                e.currentTarget.style.display = "none";
                              }}
                            />
                          ) : (
                            <div className="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                              <span className="text-white font-bold text-xl">
                                {application.name.charAt(0).toUpperCase()}
                              </span>
                            </div>
                          )}
                          <div>
                            <h3 className="text-lg font-semibold text-white">
                              {application.name}
                            </h3>
                            <p className="text-sm text-slate-400">Subscribed</p>
                          </div>
                        </div>
                        <span
                          className={`text-xs px-2 py-1 rounded-full whitespace-nowrap ${
                            application.isActive
                              ? "bg-green-500/20 text-green-400"
                              : "bg-red-500/20 text-red-400"
                          }`}
                        >
                          {application.isActive ? "Active" : "Inactive"}
                        </span>
                      </div>

                      {application.description && (
                        <p className="text-sm text-slate-400 mb-4">
                          {decodeHTML(application.description)}
                        </p>
                      )}

                      <div className="flex items-end justify-start pt-4 border-t border-slate-700 mt-auto">
                        {application.url ? (
                          application.isActive ? (
                            <a
                              href={`${API_URL}/sso/authorize?application_id=${application.id}`}
                              className="inline-flex items-center gap-3 text-white transition-all duration-300 uppercase text-xs font-normal tracking-wider h-8 px-8 rounded-sm bg-gradient-to-r from-slate-700 to-blue-600 border-0 overflow-hidden"
                              style={{
                                clipPath:
                                  "polygon(0px 0px, 0px 0px, 100% 0px, 100% 0px, 100% calc(100% - 15px), calc(100% - 15px) 100%, 15px 100%, 0px 100%)",
                              }}
                              onMouseOver={(e) => {
                                e.currentTarget.style.paddingRight = "35px";
                                e.currentTarget.style.paddingLeft = "35px";
                              }}
                              onMouseOut={(e) => {
                                e.currentTarget.style.paddingRight = "32px";
                                e.currentTarget.style.paddingLeft = "32px";
                              }}
                            >
                              Visit Application
                              <span className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-neutral-900 text-white">
                                <svg
                                  xmlns="http://www.w3.org/2000/svg"
                                  width="24"
                                  height="24"
                                  viewBox="0 0 24 24"
                                  fill="none"
                                  stroke="currentColor"
                                  strokeWidth="2"
                                  strokeLinecap="round"
                                  strokeLinejoin="round"
                                  className="h-3.5 w-3.5"
                                >
                                  <path d="M5 12h14" />
                                  <path d="m12 5 7 7-7 7" />
                                </svg>
                              </span>
                            </a>
                          ) : (
                            <span className="text-sm text-slate-500 cursor-not-allowed">
                              This application has been disabled
                            </span>
                          )
                        ) : (
                          <span className="text-sm text-slate-500">
                            No URL provided
                          </span>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </section>

          {/* Available Applications Section */}
          <section>
            <div className="mb-6">
              <div className="flex items-center gap-3 mb-2">
                <h2 className="text-2xl font-medium text-white tracking-tight">
                  Available Applications
                </h2>
              </div>
            </div>

            {/* Empty State */}
            {allApplications.length === 0 ? (
              <div className="text-center py-12 bg-slate-800/30 border border-slate-700 rounded-lg">
                <Zap className="w-12 h-12 text-slate-500 mx-auto mb-4" />
                <p className="text-slate-400 text-lg">
                  All applications subscribed!
                </p>
              </div>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {allApplications.map((application) => {
                  return (
                    <div
                      key={application.id}
                      className="bg-slate-800/50 border border-slate-700 rounded-lg p-6 hover:border-slate-600 transition-colors flex flex-col"
                    >
                      <div className="flex items-start justify-between mb-4">
                        <div className="flex items-center gap-4 flex-1">
                          {application.iconUrl ? (
                            <img
                              src={application.iconUrl}
                              alt={`${application.name} icon`}
                              className="w-12 h-12 rounded-lg object-cover"
                              onError={(e) => {
                                e.currentTarget.style.display = "none";
                              }}
                            />
                          ) : (
                            <div className="w-12 h-12 rounded-lg bg-linear-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                              <span className="text-white font-bold text-xl">
                                {application.name.charAt(0).toUpperCase()}
                              </span>
                            </div>
                          )}
                          <div>
                            <h3 className="text-lg font-semibold text-white">
                              {application.name}
                            </h3>
                          </div>
                        </div>
                        <span className="text-xs px-2 py-1 rounded-full whitespace-nowrap bg-amber-500/20 text-amber-400">
                          Not subscribed
                        </span>
                      </div>

                      {application.description && (
                        <p className="text-sm text-slate-400 mb-4">
                          {decodeHTML(application.description)}
                        </p>
                      )}

                      <div className="flex items-center justify-center pt-4 border-t border-slate-700 mt-auto">
                        {application.url ? (
                          <span className="text-sm text-slate-500 cursor-not-allowed">
                            Contact your admin to subscribe
                          </span>
                        ) : (
                          <span className="text-sm text-slate-500">
                            You are not subscribed
                          </span>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </section>
        </>
      )}
    </>
  );
};

export default Home;
