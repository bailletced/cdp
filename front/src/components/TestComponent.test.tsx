import { describe, expect, it, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import { TestComponent } from './TestComponent';
import { server } from '../test/setup';

describe('TestComponent', () => {
  it('renders with default message', () => {
    render(<TestComponent />);
    expect(screen.getByText('Hello')).toBeInTheDocument();
  });

  it('renders with custom message', () => {
    render(<TestComponent message="Custom" />);
    expect(screen.getByText('Custom')).toBeInTheDocument();
  });

  it('fetches data from API using MSW', async () => {
    render(<TestComponent message="API Test" />);

    // Attendre que les données soient chargées
    await waitFor(() => {
      expect(screen.getByTestId('api-message')).toHaveTextContent('Test successful');
    });
  });

  it('has correct test id', () => {
    render(<TestComponent />);
    expect(screen.getByTestId('test-component')).toBeInTheDocument();
  });
});
