'use client';

/**
 * Agency layout — guard preserved verbatim, chrome unified on PanelShell.
 * Mobile nav is the shared RoleDock rendered by the shell.
 */
import React, { ReactNode, useEffect, useState } from 'react';
import LoadingSpinner from '@/components/loading-spinner';
import { useUser, useAuth } from '@/firebase';
import { useRouter } from 'next/navigation';
import { doc, getDoc } from 'firebase/firestore';
import { useFirestore } from '@/firebase';
import { resolveUserRole } from '@/lib/resolve-user-role';
import { performLogout } from '@/lib/logout';
import { usePathname } from 'next/navigation';
import { PanelShell } from '@/components/shell/PanelShell';
import { agencyPanel } from '@/components/shell/panel-config';

export default function AgencyLayout({ children }: { children: ReactNode }) {
    const { user, isUserLoading } = useUser();
    const auth = useAuth();
    const router = useRouter();
    const pathname = usePathname();
    const firestore = useFirestore();
    const [isCheckingRole, setIsCheckingRole] = useState(true);
    const [isAuthorized, setIsAuthorized] = useState(false);

    const isAuthPage = pathname === '/agency/login' || pathname === '/agency/signup';

    useEffect(() => {
        if (isUserLoading) return;

        if (!user) {
            if (!isAuthPage) {
                router.replace('/agency/login');
            }
            setIsCheckingRole(false);
            return;
        }

        const checkAgencyRole = async () => {
            try {
                const role = await resolveUserRole(user, firestore);
                if (['agency_manager', 'sub_agent', 'agency', 'admin', 'super_admin'].includes(role)) {
                    setIsAuthorized(true);
                } else {
                    if (!isAuthPage) {
                        setIsAuthorized(false);
                        router.replace('/unauthorized');
                    }
                }
            } catch (error) {
                console.error('[Agency] Auth check failed:', error);
                if (!isAuthPage) setIsAuthorized(false);
            } finally {
                setIsCheckingRole(false);
            }
        };

        checkAgencyRole();
    }, [user, isUserLoading, firestore, router, isAuthPage]);

    if (isUserLoading || isCheckingRole) {
        return (
            <div className="flex h-screen w-full items-center justify-center bg-background">
                <LoadingSpinner />
            </div>
        );
    }

    if (isAuthPage) {
        return <main className="bg-background min-h-dvh">{children}</main>;
    }

    if (!isAuthorized) return null;

    const name = user?.displayName || user?.email || 'Agency';
    const logout = () => {
        if (auth) performLogout(auth, '/agency/login');
    };
    const profile = {
        name,
        role: 'Agency',
        avatarUrl: user?.photoURL || undefined,
        links: [{ href: '/agency/profile', label: 'Profile & Settings' }],
        onLogout: logout,
    };

    return (
        <PanelShell
            config={agencyPanel}
            profile={profile}
            sidebarProfile={{ name, role: 'Agency', avatarUrl: user?.photoURL || undefined, onLogout: logout }}
            showCurrency
        >
            {children}
        </PanelShell>
    );
}
