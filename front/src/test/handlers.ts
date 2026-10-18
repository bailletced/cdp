import { http, HttpResponse } from "msw";

// MSW handlers for the tests. The absolute URL matches the test base URL
// of the API client (http://localhost:8080, the dev stack).
export const handlers = [
  // GET /api/me: authenticated priant
  http.get("http://localhost:8080/api/me", () => {
    return HttpResponse.json({
      "@context": "/api/contexts/Me",
      "@id": "/api/me",
      "@type": "Me",
      email: "thomas@example.com",
      isOrganizer: false,
    });
  }),
];
