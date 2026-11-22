'use client';

import React, { Suspense } from 'react';
import { SubdomainLogin } from '@/components/auth/SubdomainLogin';
import LoadingSpinner from '@/components/loading-spinner';

export default function AgencyLoginPage() {
  return (
    <Suspense fallback={<LoadingSpinner />}>
      <SubdomainLogin roleKey="agency" />
    </Suspense>
  );
}