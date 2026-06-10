import { createRoot } from "react-dom/client";
import { RouterProvider, createRouter } from "@tanstack/react-router";
import { QueryClientProvider, QueryClient } from "@tanstack/react-query";
import "./index.css";
import { routeTree } from "./routeTree.gen";

const queryClient = new QueryClient();

const router = createRouter({ routeTree });

const rootElement = document.getElementById("root")!;
const root = createRoot(rootElement);

root.render(
  <QueryClientProvider client={queryClient}>
    <RouterProvider router={router} />
  </QueryClientProvider>
);
