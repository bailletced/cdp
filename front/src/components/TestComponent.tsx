import { useEffect, useState } from "react";
import { apiClient } from "../api/client";

export interface TestComponentProps {
  message?: string;
}

export function TestComponent({ message = "Hello" }: TestComponentProps) {
  const [email, setEmail] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    async function fetchMe() {
      setLoading(true);
      try {
        const { data, error } = await apiClient.GET("/api/me");

        if (error) {
          throw error;
        }

        setEmail(data?.email ?? null);
      } catch {
        setEmail("Erreur");
      } finally {
        setLoading(false);
      }
    }

    fetchMe();
  }, []);

  return (
    <div data-testid="test-component">
      <h2>{message}</h2>
      {loading ? <p>Chargement…</p> : <p data-testid="api-message">{email}</p>}
    </div>
  );
}
