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
      email: "thomas@bailletced.org",
      isOrganizer: false,
    });
  }),
  // POST /auth/link-request: always 202 with the same message (F02-T15)
  http.post("http://localhost:8080/auth/link-request", () => {
    return HttpResponse.json(
      {
        message:
          "Si un compte correspond à cette adresse, un lien de connexion vient de vous être envoyé.",
      },
      { status: 202 },
    );
  }),
  // POST /auth/register: always 202 with the same message (F07-T01)
  http.post("http://localhost:8080/auth/register", () => {
    return HttpResponse.json(
      {
        message:
          "Un lien de connexion vient de vous être envoyé à cette adresse.",
      },
      { status: 202 },
    );
  }),
  // POST /auth/login: opens the session and returns the target (F02-T08)
  http.post("http://localhost:8080/auth/login", () => {
    return HttpResponse.json({ target: "/my-space" });
  }),
  // POST /auth/logout: destroys the session (F02-T10)
  http.post("http://localhost:8080/auth/logout", () => {
    return new HttpResponse(null, { status: 200 });
  }),
  // GET /api/editions/current: no public edition by default (F08-T02) —
  // tests showing an open edition override this handler with server.use()
  http.get("http://localhost:8080/api/editions/current", () => {
    return HttpResponse.json(
      {
        "@context": "/api/contexts/Error",
        "@type": "hydra:Error",
        title: "An error occurred",
        detail: "Le modèle est introuvable.",
      },
      { status: 404 },
    );
  }),
  // GET /api/editions/{id}/slots: an empty grid by default (F08-T04) —
  // tests showing slots override this handler with server.use()
  http.get("http://localhost:8080/api/editions/:id/slots", () => {
    return HttpResponse.json({
      "@context": "/api/contexts/PublicEditionSlots",
      "@id": "/api/editions/current/slots",
      "@type": "PublicEditionSlots",
      id: "01936f2e-7c4a-7b1e-8f2a-3c4d5e6f7a8b",
      slots: [],
    });
  }),
];
