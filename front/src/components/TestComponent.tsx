import { useState, useEffect } from 'react';

export interface TestComponentProps {
  message?: string;
}

export function TestComponent({ message = 'Hello' }: TestComponentProps) {
  const [data, setData] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    async function fetchData() {
      setLoading(true);
      try {
        const response = await fetch('http://localhost:8080/api/test');
        const result = await response.json();
        setData(result.message);
      } catch (error) {
        setData('Error');
      } finally {
        setLoading(false);
      }
    }

    fetchData();
  }, []);

  return (
    <div data-testid="test-component">
      <h2>{message}</h2>
      {loading ? (
        <p>Loading...</p>
      ) : (
        <p data-testid="api-message">{data}</p>
      )}
    </div>
  );
}
