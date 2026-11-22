'use client';

import { RouteError } from '@/components/error/route-error';

export default function AgencyError({
  error,
  reset,
}: {
  error: Error & { digest?: string };
  reset: () => void;
}) {
  return <RouteError error={error} reset={reset} homeHref="/agency/dashboard" homeLabel="Agency Dashboard" />;
}
