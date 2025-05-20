import { render, screen } from '@testing-library/react';
import { MemoryRouter } from 'react-router-dom';
import AssetList from '../AssetList';

describe('AssetList', () => {
  const assets = [
    { id: 1, name: 'Laptop 1', asset_tag: 'AST-001', status: { name: 'Deployed' } },
    { id: 2, name: 'Monitor', asset_tag: 'AST-002', status: { name: 'Available' } },
  ];

  it('renders a list of assets', () => {
    render(
      <MemoryRouter>
        <AssetList assets={assets} loading={false} />
      </MemoryRouter>
    );

    expect(screen.getByText('Laptop 1')).toBeInTheDocument();
    expect(screen.getByText('AST-001')).toBeInTheDocument();
    expect(screen.getByText('Monitor')).toBeInTheDocument();
  });

  it('shows loading state', () => {
    render(
      <MemoryRouter>
        <AssetList assets={[]} loading={true} />
      </MemoryRouter>
    );

    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });
});
