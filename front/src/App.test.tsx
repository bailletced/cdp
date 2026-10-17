import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Root } from './components/Root';
import { server } from './test/setup';

describe('Root component', () => {
  it('renders the title', () => {
    render(<Root.Component />);
    expect(screen.getByText('Chaîne de prière - CDP')).toBeInTheDocument();
  });

  it('renders without crashing', () => {
    render(<Root.Component />);
    expect(screen.getByRole('heading')).toBeInTheDocument();
  });
});

// Test avec MSW handler
describe('API integration with MSW', () => {
  it('can mock API calls', async () => {
    // Ce test démontre que MSW fonctionne
    const response = await fetch('http://localhost:8080/api/test');
    const data = await response.json();
    
    expect(data.message).toBe('Test successful');
  });
});
