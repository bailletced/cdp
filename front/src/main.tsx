import { QueryClientProvider } from "@tanstack/react-query";
import { RouterProvider } from "@tanstack/react-router";
import React from "react";
import ReactDOM from "react-dom/client";
import { queryClient } from "./api/queryClient";
import { Notifications } from "./components/Notifications";
import { initSentry } from "./config/sentry";
import "./index.css";
import { router } from "./router";

// Initialise Sentry en production (F03-T20).
initSentry();

const rootElement = document.getElementById("root");

if (!rootElement) {
  throw new Error("Root element #root not found");
}

ReactDOM.createRoot(rootElement).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
      <Notifications />
    </QueryClientProvider>
  </React.StrictMode>,
);
