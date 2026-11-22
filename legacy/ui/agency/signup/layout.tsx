import { buildMetadata } from '@/lib/seo-config';
import type { ReactNode } from 'react';

export const metadata = buildMetadata('signup_agency');

export default function SignupAgencyLayout({ children }: { children: ReactNode }) {
  return <>{children}</>;
}
