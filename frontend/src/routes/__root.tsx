import { createRootRoute } from "@tanstack/react-router";
import { Layout } from "../components/Layout";
import { useAuthStore } from "../store/authStore";

export const Route = createRootRoute({
  component: Layout,
  beforeLoad: () => {
    const state = useAuthStore.getState();
    return { isAuthenticated: state.isAuthenticated };
  },
});
