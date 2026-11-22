import { buildMetadata, BREADCRUMB_SCHEMA } from '@/lib/seo-config';
import { JsonLd, ServiceSchema } from '@/components/seo/jsonld';
import type { ReactNode } from 'react';

export const metadata = buildMetadata('bus');

export default function BusLayout({ children }: { children: ReactNode }) {
  const breadcrumb = BREADCRUMB_SCHEMA([
    { name: 'Home', url: 'https://eatalyft.cm' },
    { name: 'Bus Tickets', url: 'https://eatalyft.cm/bus' },
  ]);
  return (
    <>
      <JsonLd data={breadcrumb} />
      <ServiceSchema route="bus" />
      {children}
    </>
  );
}