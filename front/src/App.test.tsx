import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Root } from './components/Root';

describe('Root component', () => {
  it('renders the title', () => {
    render(<Root.Component />);
    expect(screen.getByText('Chaîne de prière - CDP')).toBeInTheDocument();
  });
});
