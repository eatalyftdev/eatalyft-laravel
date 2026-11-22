'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';

export default function SubAccountsRedirect() {
    const router = useRouter();
    useEffect(() => { router.replace('/agency/stations'); }, [router]);
    return null;
}
