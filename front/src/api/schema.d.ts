// Ce fichier est généré automatiquement par openapi-typescript
// Ne pas modifier manuellement

declare namespace Components {
  namespace Schemas {
    export interface TestResponse {
      message?: string;
    }
  }
}

declare namespace Paths {
  export interface GetApiTest {
    get: {
      responses: {
        200: {
          content: {
            'application/json': Components.Schemas.TestResponse;
          };
        };
      };
    };
  };
}

export interface ApiClient {
  getApiTest: (options?: { headers?: Record<string, string> }) => Promise<Components.Schemas.TestResponse>;
}
