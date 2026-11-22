'use client';

import React, { useState, useEffect, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { useUser, useFirestore, useMemoFirebase, useCollection, useAuth } from '@/firebase';
import { doc, collection, query, where, getDoc, deleteDoc, serverTimestamp } from 'firebase/firestore';
import { addDocumentNonBlocking } from '@/firebase/non-blocking-updates';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { useToast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';
import {
    ArrowLeft, Plus, Users, MapPin, Phone, Mail, Copy, CheckCircle2,
    Trash2, ShieldCheck, Clock, LayoutDashboard, Activity, QrCode,
    Bus, Settings, UserCheck, Building2, Link2, Lock, Zap,
    MoreVertical, Globe, Navigation
} from 'lucide-react';
import Link from 'next/link';

import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

// Station doc stored at agencies/{agencyId}/stations/{stationId}
type StationDoc = {
    id: string;
    agencyId?: string;
    name: string;
    location?: string;
    phoneNumber?: string;
    managerName?: string;
    managerIds?: string[];
    createdAt?: any;
};

// Sub-agent user account
type SubAgent = {
    id: string;
    name?: string;
    displayName?: string;
    email?: string;
    phoneNumber?: string;
    stationName?: string;
    role: string;
};

// Pending invite in agencyInvites collection
type Invite = {
    id: string;
    managerName: string;
    email: string;
    phoneNumber?: string;
    stationName: string;
    status: 'pending' | 'accepted';
};

type AgencyProfile = { id: string; name: string };
type CreateMode = 'station' | 'invite';

export default function AgencyStationsPage() {
    const { user, isUserLoading } = useUser();
    const auth = useAuth();
    const firestore = useFirestore();
    const router = useRouter();
    const { toast } = useToast();

    const [resolvedAgencyId, setResolvedAgencyId] = useState<string | null>(null);
    const [agency, setAgency] = useState<AgencyProfile | null>(null);
    const [isCreateOpen, setIsCreateOpen] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [isDeletingId, setIsDeletingId] = useState<string | null>(null);
    const [createMode, setCreateMode] = useState<CreateMode>('station');
    const [copiedId, setCopiedId] = useState<string | null>(null);
    const [form, setForm] = useState({
        name: '', location: '', phoneNumber: '', managerName: '',
        // For invite mode
        email: '', password: '',
    });

    useEffect(() => {
        if (!user || !firestore) return;
        const resolve = async () => {
            // Every read below can throw (denied/missing doc, offline). Each
            // layer falls through to the next so the page can NEVER strand
            // on the splash screen — the uid fallback always resolves.
            try {
                // Owner path: agency_managers doc exists.
                const mgrSnap = await getDoc(doc(firestore, 'agency_managers', user.uid));
                if (mgrSnap.exists()) {
                    setResolvedAgencyId(user.uid);
                    try {
                        const aSnap = await getDoc(doc(firestore, 'agencies', user.uid));
                        if (aSnap.exists()) setAgency({ ...(aSnap.data() as Omit<AgencyProfile, 'id'>), id: user.uid });
                    } catch { /* profile is best-effort */ }
                    return;
                }
            } catch { /* no read access — fall through */ }
            try {
                // Sub-agent path (or legacy owner without a managers doc).
                const subSnap = await getDoc(doc(firestore, 'sub_agents', user.uid));
                const aid = subSnap.exists() ? (subSnap.data().managedAgencyId || user.uid) : user.uid;
                setResolvedAgencyId(aid);
                try {
                    const aSnap = await getDoc(doc(firestore, 'agencies', aid));
                    if (aSnap.exists()) setAgency({ ...(aSnap.data() as Omit<AgencyProfile, 'id'>), id: aid });
                } catch { /* profile is best-effort */ }
                return;
            } catch { /* fall through to the guarantee */ }
            setResolvedAgencyId(user.uid);
        };
        resolve();
    }, [user, firestore]);

    // ── Primary source: agencies/{agencyId}/stations subcollection ──────────
    const stationsQuery = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return collection(firestore, 'agencies', resolvedAgencyId, 'stations');
    }, [resolvedAgencyId, firestore]);

    // ── Sub-agent user accounts (for manager linking) ────────────────────────
    const subAgentsQuery = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return query(
            collection(firestore, 'sub_agents'),
            where('managedAgencyId', '==', resolvedAgencyId)
        );
    }, [resolvedAgencyId, firestore]);

    // ── Pending invites ───────────────────────────────────────────────────────
    const invitesQuery = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return query(
            collection(firestore, 'agencyInvites'),
            where('agencyId', '==', resolvedAgencyId)
        );
    }, [resolvedAgencyId, firestore]);

    const { data: rawStations, isLoading: isStationsLoading } = useCollection<StationDoc>(stationsQuery);
    const { data: rawAgents,   isLoading: isAgentsLoading   } = useCollection<SubAgent>(subAgentsQuery);
    const { data: rawInvites,  isLoading: isInvitesLoading  } = useCollection<Invite>(invitesQuery);

    const stations       = useMemo(() => rawStations?.filter(s => s.id !== '_init') ?? [], [rawStations]);
    const subAgents      = useMemo(() => rawAgents?.filter(a => a.id !== '_init') ?? [], [rawAgents]);
    const pendingInvites = useMemo(() => rawInvites?.filter(i => i.id !== '_init' && i.status === 'pending') ?? [], [rawInvites]);

    // Build a map of stationName -> subAgent for enrichment
    const agentByStation = useMemo(() => {
        const map: Record<string, SubAgent> = {};
        subAgents.forEach(a => { if (a.stationName) map[a.stationName.toLowerCase()] = a; });
        return map;
    }, [subAgents]);

    const resetForm = () => setForm({ name: '', location: '', phoneNumber: '', managerName: '', email: '', password: '' });

    // Create station record in agencies/{agencyId}/stations via API
    const handleCreateStation = async () => {
        if (!resolvedAgencyId || !auth?.currentUser) return;
        if (!form.name) {
            toast({ variant: 'destructive', title: 'Missing Fields', description: 'Station name is required.' });
            return;
        }
        // Firebase Auth project policy: minimum 8 characters. Catch it here
        // so the manager sees guidance instead of a server round-trip failure.
        if (form.email && form.password && form.password.length < 8) {
            toast({ variant: 'destructive', title: 'Weak Password', description: 'Manager password must contain at least 8 characters.' });
            return;
        }
        setIsSaving(true);
        try {
            const idToken = await auth.currentUser!.getIdToken();
            const res = await fetch('/api/agency/stations', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${idToken}` },
                body: JSON.stringify({
                    agencyId: resolvedAgencyId,
                    name: form.name,
                    location: form.location,
                    phoneNumber: form.phoneNumber,
                    managerName: form.managerName,
                }),
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Server error');

            // If email + password provided, also create a sub-manager account
            if (form.email && form.password) {
                const res2 = await fetch('/api/agency/create-sub-manager', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${idToken}` },
                    body: JSON.stringify({
                        email: form.email,
                        password: form.password,
                        name: form.managerName || form.name,
                        agencyId: resolvedAgencyId,
                        stationName: form.name,
                        phoneNumber: form.phoneNumber,
                    }),
                });
                const data2 = await res2.json();
                if (!res2.ok || !data2.success) {
                    const friendly = data2.error === 'WEAK_PASSWORD'
                        ? 'Manager password must contain at least 8 characters.'
                        : data2.error === 'EMAIL_IN_USE'
                            ? 'This manager email is already registered.'
                            : data2.error === 'INVALID_EMAIL'
                                ? 'This manager email address is invalid.'
                                : (data2.error || 'Manager account creation failed');
                    throw new Error(friendly);
                }
            }

            toast({ title: 'Station Added', description: `${form.name} is now in your network.` });
            resetForm();
            setIsCreateOpen(false);
        } catch (err: any) {
            toast({ variant: 'destructive', title: 'Failed', description: err.message });
        } finally { setIsSaving(false); }
    };

    // Invite flow — creates invite doc; manager signs up via link
    const handleCreateInvite = async () => {
        if (!resolvedAgencyId || !firestore) return;
        if (!form.name || !form.email) {
            toast({ variant: 'destructive', title: 'Missing Fields', description: 'Station name and manager email are required.' });
            return;
        }
        setIsSaving(true);
        try {
            await addDocumentNonBlocking(collection(firestore, 'agencyInvites'), {
                agencyId: resolvedAgencyId, agencyName: agency?.name || '',
                managerName: form.managerName || form.name,
                email: form.email,
                phoneNumber: form.phoneNumber,
                stationName: form.name,
                status: 'pending',
                createdAt: serverTimestamp(),
            });
            toast({ title: 'Invite Created', description: `Share the invite link with ${form.managerName || form.name}.` });
            resetForm();
            setIsCreateOpen(false);
        } catch (err: any) {
            toast({ variant: 'destructive', title: 'Error', description: err.message });
        } finally { setIsSaving(false); }
    };

    const handleCreate = () => createMode === 'station' ? handleCreateStation() : handleCreateInvite();

    const handleDeleteStation = async (stationId: string) => {
        if (!resolvedAgencyId || !auth?.currentUser) return;
        setIsDeletingId(stationId);
        try {
            const idToken = await auth.currentUser!.getIdToken();
            await fetch(`/api/agency/stations?agencyId=${resolvedAgencyId}&stationId=${stationId}`, {
                method: 'DELETE',
                headers: { 'Authorization': `Bearer ${idToken}` },
            });
            toast({ title: 'Station Removed' });
        } catch (err: any) {
            toast({ variant: 'destructive', title: 'Error', description: err.message });
        } finally { setIsDeletingId(null); }
    };

    const handleCopyLink = (inviteId: string) => {
        const link = `${window.location.origin}/signup/agency/join?invite=${inviteId}`;
        navigator.clipboard.writeText(link);
        setCopiedId(inviteId);
        setTimeout(() => setCopiedId(null), 3000);
        toast({ title: 'Link Copied', description: 'Share with the station manager.' });
    };

    const handleDeleteInvite = async (inviteId: string) => {
        if (!firestore) return;
        try {
            await deleteDoc(doc(firestore, 'agencyInvites', inviteId));
            toast({ title: 'Invite Revoked' });
        } catch (err: any) {
            toast({ variant: 'destructive', title: 'Error', description: err.message });
        }
    };

    if (isUserLoading || !resolvedAgencyId || isStationsLoading || isAgentsLoading || isInvitesLoading) return <LoadingSpinner />;

    return (
        <div className="min-h-screen bg-background font-display text-foreground">
            <main className="flex flex-col pb-32 overflow-x-hidden">
                <header className="sticky top-0 z-40 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center justify-between gap-4 shadow-sm">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50 lg:hidden" onClick={() => router.back()}>
                            <ArrowLeft size={20} />
                        </Button>
                        <div>
                            <p className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">{agency?.name || 'Agency'}</p>
                            <h1 className="text-xl font-black uppercase tracking-tighter leading-none">Station Network</h1>
                        </div>
                    </div>
                    <Button onClick={() => setIsCreateOpen(true)} className="rounded-2xl h-11 px-5 font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20 gap-2">
                        <Plus size={16} /> Add Station
                    </Button>
                </header>

                <div className="p-6 space-y-8 max-w-4xl mx-auto w-full">

                    {/* Stats row */}
                    <div className="grid grid-cols-3 gap-4">
                        {[
                            { label: 'Total Stations',   value: stations.length,       icon: MapPin,       color: 'text-primary',       bg: 'bg-primary/10' },
                            { label: 'Active Managers',  value: subAgents.length,      icon: UserCheck,    color: 'text-emerald-500',   bg: 'bg-emerald-500/10' },
                            { label: 'Pending Invites',  value: pendingInvites.length, icon: Clock,        color: 'text-amber-500',     bg: 'bg-amber-500/10' },
                        ].map(stat => (
                            <div key={stat.label} className="bg-card border border-border/50 rounded-[2rem] p-5 shadow-sm">
                                <div className={cn("size-10 rounded-2xl flex items-center justify-center mb-3", stat.bg)}>
                                    <stat.icon size={20} className={stat.color} />
                                </div>
                                <p className="text-2xl font-black">{stat.value}</p>
                                <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5">{stat.label}</p>
                            </div>
                        ))}
                    </div>

                    {/* Station cards */}
                    {stations.length > 0 && (
                        <section>
                            <div className="flex items-center justify-between mb-4 px-1">
                                <h2 className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">All Stations</h2>
                                <Badge variant="outline" className="text-[8px] font-black text-primary border-primary/20">{stations.length} nodes</Badge>
                            </div>
                            <div className="space-y-3">
                                {stations.map(station => {
                                    // Try to find linked sub-agent account by stationName match
                                    const linkedAgent = agentByStation[station.name?.toLowerCase() ?? ''];

                                    return (
                                        <div key={station.id} className="bg-card border border-border/50 rounded-[2rem] p-5 hover:border-primary/30 transition-all group">
                                            {/* Header row */}
                                            <div className="flex items-start gap-4 mb-4">
                                                <div className="size-14 rounded-2xl bg-primary/10 border border-primary/20 flex items-center justify-center shrink-0">
                                                    <MapPin size={22} className="text-primary" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-black text-base uppercase tracking-tighter">{station.name}</p>
                                                    {station.location && (
                                                        <div className="flex items-center gap-1.5 mt-0.5">
                                                            <Navigation size={10} className="text-muted-foreground shrink-0" />
                                                            <p className="text-[9px] font-bold text-muted-foreground uppercase tracking-widest truncate">{station.location}</p>
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-2 shrink-0">
                                                    <Badge className={cn(
                                                        "font-black text-[8px] uppercase h-5",
                                                        linkedAgent
                                                            ? "bg-emerald-500/10 text-emerald-600 border-emerald-500/30"
                                                            : "bg-secondary text-muted-foreground border-border/50"
                                                    )}>
                                                        {linkedAgent ? 'Active' : 'No Manager'}
                                                    </Badge>
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="icon" className="size-9 rounded-xl bg-secondary/50">
                                                                <MoreVertical size={14} />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end" className="w-52 rounded-2xl p-2">
                                                            <DropdownMenuItem asChild>
                                                                <Link href={`/agency/trips?stationId=${station.id}`} className="rounded-xl py-3 gap-3 font-bold text-xs uppercase cursor-pointer">
                                                                    <Bus size={14} className="text-primary" /> View Station Trips
                                                                </Link>
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem
                                                                onSelect={() => handleDeleteStation(station.id)}
                                                                className="rounded-xl py-3 gap-3 font-bold text-xs uppercase cursor-pointer text-destructive"
                                                                disabled={isDeletingId === station.id}
                                                            >
                                                                <Trash2 size={14} />
                                                                {isDeletingId === station.id ? 'Removing…' : 'Remove Station'}
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </div>
                                            </div>

                                            {/* Info grid */}
                                            <div className="grid grid-cols-2 gap-2 mb-3">
                                                {(station.managerName || linkedAgent) && (
                                                    <div className="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-secondary/50 border border-border/30">
                                                        <UserCheck size={11} className="text-primary shrink-0" />
                                                        <p className="text-[9px] font-bold text-muted-foreground uppercase tracking-widest truncate">
                                                            {station.managerName || linkedAgent?.name || linkedAgent?.displayName || '—'}
                                                        </p>
                                                    </div>
                                                )}
                                                {(station.phoneNumber || linkedAgent?.phoneNumber) && (
                                                    <div className="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-secondary/50 border border-border/30">
                                                        <Phone size={11} className="text-primary shrink-0" />
                                                        <p className="text-[9px] font-bold text-muted-foreground uppercase tracking-widest truncate">
                                                            {station.phoneNumber || linkedAgent?.phoneNumber}
                                                        </p>
                                                    </div>
                                                )}
                                                {linkedAgent?.email && (
                                                    <div className="flex items-center gap-2 px-3 py-2.5 rounded-xl bg-secondary/50 border border-border/30 col-span-2">
                                                        <Mail size={11} className="text-primary shrink-0" />
                                                        <p className="text-[9px] font-bold text-muted-foreground uppercase tracking-widest truncate">{linkedAgent.email}</p>
                                                    </div>
                                                )}
                                            </div>

                                            {/* Actions */}
                                            <div className="flex gap-2 pt-3 border-t border-border/30">
                                                <Link
                                                    href={`/agency/trips?stationId=${station.id}`}
                                                    className="flex-1 flex items-center justify-center gap-2 h-9 rounded-2xl bg-primary/10 border border-primary/20 text-primary text-[9px] font-black uppercase tracking-widest transition-all hover:bg-primary/20 active:scale-95"
                                                >
                                                    <Bus size={12} /> Station Trips
                                                </Link>
                                                {!linkedAgent && (
                                                    <button
                                                        onClick={() => { setForm(f => ({ ...f, name: station.name })); setIsCreateOpen(true); }}
                                                        className="flex-1 flex items-center justify-center gap-2 h-9 rounded-2xl bg-secondary/50 border border-border/50 text-muted-foreground text-[9px] font-black uppercase tracking-widest transition-all hover:bg-secondary active:scale-95"
                                                    >
                                                        <UserCheck size={12} /> Assign Manager
                                                    </button>
                                                )}
                                                {linkedAgent && (
                                                    <div className="flex-1 flex items-center justify-center gap-2 h-9 px-3 rounded-2xl bg-emerald-500/5 border border-emerald-500/20">
                                                        <CheckCircle2 size={11} className="text-emerald-500" />
                                                        <p className="text-[9px] font-black text-emerald-600 uppercase tracking-widest">Manager Active</p>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </section>
                    )}

                    {/* Sub-agents without a station doc */}
                    {(() => {
                        const unlinked = subAgents.filter(a => !stations.some(s => s.name?.toLowerCase() === a.stationName?.toLowerCase()));
                        if (unlinked.length === 0) return null;
                        return (
                            <section>
                                <div className="flex items-center gap-2 mb-4 px-1">
                                    <h2 className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">Linked Managers</h2>
                                    <Badge variant="outline" className="text-[8px] font-black text-muted-foreground border-border/50">{unlinked.length}</Badge>
                                </div>
                                <div className="space-y-3">
                                    {unlinked.map(agent => (
                                        <div key={agent.id} className="bg-card border border-border/50 rounded-[2rem] p-5 flex items-center gap-4">
                                            <div className="size-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0">
                                                <UserCheck size={20} className="text-emerald-500" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="font-black text-sm uppercase tracking-tighter">{agent.name || agent.displayName || '—'}</p>
                                                <p className="text-[10px] font-bold text-primary uppercase tracking-widest mt-0.5">{agent.stationName || '—'}</p>
                                                {agent.email && <p className="text-[9px] text-muted-foreground uppercase tracking-widest mt-0.5 opacity-60">{agent.email}</p>}
                                            </div>
                                            <Badge className="bg-emerald-500/10 text-emerald-500 border-emerald-500/20 font-black text-[8px] uppercase h-5">Active</Badge>
                                        </div>
                                    ))}
                                </div>
                            </section>
                        );
                    })()}

                    {/* Pending invites */}
                    {pendingInvites.length > 0 && (
                        <section>
                            <div className="flex items-center justify-between mb-4 px-1">
                                <h2 className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">Pending Invites</h2>
                                <Badge variant="outline" className="text-[8px] font-black text-amber-500 border-amber-500/20">{pendingInvites.length} Awaiting</Badge>
                            </div>
                            <div className="space-y-3">
                                {pendingInvites.map(invite => (
                                    <div key={invite.id} className="bg-card border border-border/50 rounded-[2rem] p-5">
                                        <div className="flex items-start gap-4 mb-4">
                                            <div className="size-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center shrink-0">
                                                <Clock size={20} className="text-amber-500" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="font-black text-sm uppercase tracking-tighter">{invite.managerName}</p>
                                                <div className="flex items-center gap-1.5 mt-0.5">
                                                    <MapPin size={10} className="text-primary" />
                                                    <p className="text-[10px] font-bold text-primary uppercase tracking-widest">{invite.stationName}</p>
                                                </div>
                                                <p className="text-[9px] text-muted-foreground uppercase tracking-widest mt-0.5 opacity-60">{invite.email}</p>
                                            </div>
                                            <Badge className="bg-amber-500/10 text-amber-500 border-amber-500/20 font-black text-[8px] uppercase h-5 shrink-0">Pending</Badge>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button variant="outline" className="flex-1 h-10 rounded-2xl font-black text-[9px] uppercase tracking-widest gap-2 border-primary/20" onClick={() => handleCopyLink(invite.id)}>
                                                {copiedId === invite.id ? <CheckCircle2 size={13} className="text-emerald-500" /> : <Copy size={13} />}
                                                {copiedId === invite.id ? 'Copied!' : 'Copy Invite Link'}
                                            </Button>
                                            <Button variant="outline" size="icon" className="size-10 rounded-2xl border-destructive/20 text-destructive hover:bg-destructive hover:text-white" onClick={() => handleDeleteInvite(invite.id)}>
                                                <Trash2 size={14} />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}

                    {/* Empty state */}
                    {stations.length === 0 && pendingInvites.length === 0 && subAgents.length === 0 && (
                        <div className="py-40 text-center flex flex-col items-center gap-6 opacity-30 grayscale border-2 border-dashed border-border/50 rounded-[3rem]">
                            <Building2 size={64} />
                            <div className="space-y-2">
                                <h3 className="text-2xl font-black uppercase tracking-tighter">No Stations Yet</h3>
                                <p className="text-xs font-bold text-muted-foreground uppercase tracking-widest max-w-xs mx-auto leading-relaxed">
                                    Add stations to your network and assign managers to operate them independently.
                                </p>
                            </div>
                            <Button variant="outline" className="rounded-xl px-10 h-14 font-black uppercase tracking-widest text-[10px] border-border" onClick={() => setIsCreateOpen(true)}>
                                Add First Station
                            </Button>
                        </div>
                    )}

                    {/* Security note */}
                    <div className="p-5 rounded-[2rem] bg-secondary/30 border border-border/50 flex items-start gap-3">
                        <ShieldCheck size={16} className="text-emerald-500 mt-0.5 shrink-0" />
                        <p className="text-[9px] font-bold text-muted-foreground leading-relaxed uppercase tracking-wide">
                            <span className="text-foreground">Add Station</span> — creates the station record and optionally a manager account instantly.{' '}
                            <span className="text-foreground">Invite Link</span> — creates an invite; the manager sets their own credentials when they sign up.
                        </p>
                    </div>
                </div>
            </main>

            {/* Create / Invite dialog */}
            <Dialog open={isCreateOpen} onOpenChange={v => { setIsCreateOpen(v); if (!v) resetForm(); }}>
                <DialogContent className="rounded-[2.5rem] border-border/50 max-w-md mx-4 p-8 max-h-[90vh] overflow-y-auto">
                    <DialogHeader className="mb-4">
                        <DialogTitle className="font-black text-xl uppercase tracking-tighter">Add Station</DialogTitle>
                        <DialogDescription className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">
                            Create a station and optionally assign a manager.
                        </DialogDescription>
                    </DialogHeader>

                    {/* Mode toggle */}
                    <div className="flex bg-secondary/50 rounded-2xl p-1 mb-5">
                        {([
                            { key: 'station', label: 'Direct Add', icon: Zap },
                            { key: 'invite',  label: 'Invite Link', icon: Link2 },
                        ] as { key: CreateMode; label: string; icon: any }[]).map(({ key, label, icon: Icon }) => (
                            <button
                                key={key}
                                onClick={() => setCreateMode(key)}
                                className={cn(
                                    "flex-1 flex items-center justify-center gap-2 h-10 rounded-xl font-black text-[9px] uppercase tracking-widest transition-all",
                                    createMode === key ? "bg-background shadow-sm text-foreground" : "text-muted-foreground hover:text-foreground"
                                )}
                            >
                                <Icon size={12} /> {label}
                            </button>
                        ))}
                    </div>

                    <div className="space-y-4">
                        {/* Always shown */}
                        {[
                            { field: 'name',        label: 'Station Name *',      placeholder: 'e.g. Douala Central',       icon: MapPin,  type: 'text' },
                            { field: 'location',    label: 'Location / Address',  placeholder: 'e.g. Commercial Ave, Bali', icon: Globe,   type: 'text' },
                            { field: 'phoneNumber', label: 'Station Phone',       placeholder: '+237 6XX XXX XXX',          icon: Phone,   type: 'tel' },
                            { field: 'managerName', label: 'Manager Name',        placeholder: 'e.g. Jean Dupont',          icon: Users,   type: 'text' },
                        ].map(({ field, label, placeholder, icon: Icon, type }) => (
                            <div key={field} className="space-y-2">
                                <Label className="text-[9px] font-black uppercase tracking-widest">{label}</Label>
                                <div className="relative">
                                    <Icon size={14} className="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground" />
                                    <Input type={type} className="pl-10 h-12 rounded-2xl bg-secondary/30 border-border/50 font-bold text-sm" placeholder={placeholder}
                                        value={(form as any)[field]} onChange={e => setForm(p => ({ ...p, [field]: e.target.value }))} />
                                </div>
                            </div>
                        ))}

                        {/* Manager account fields — direct only */}
                        {createMode === 'station' && (
                            <>
                                <div className="pt-2 pb-1">
                                    <p className="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Manager Login (optional)</p>
                                    <p className="text-[8px] text-muted-foreground mt-0.5 uppercase tracking-wide">Fill in to create a login account for this station manager.</p>
                                </div>
                                {[
                                    { field: 'email',    label: 'Manager Email',    placeholder: 'manager@email.com',   icon: Mail, type: 'email' },
                                    { field: 'password', label: 'Temp Password',    placeholder: 'Min 8 characters',    icon: Lock, type: 'password' },
                                ].map(({ field, label, placeholder, icon: Icon, type }) => (
                                    <div key={field} className="space-y-2">
                                        <Label className="text-[9px] font-black uppercase tracking-widest">{label}</Label>
                                        <div className="relative">
                                            <Icon size={14} className="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground" />
                                            <Input type={type} className="pl-10 h-12 rounded-2xl bg-secondary/30 border-border/50 font-bold text-sm" placeholder={placeholder}
                                                value={(form as any)[field]} onChange={e => setForm(p => ({ ...p, [field]: e.target.value }))}
                                                minLength={field === 'password' ? 8 : undefined}
                                                aria-describedby={field === 'password' ? 'submgr-pw-hint' : undefined} />
                                        </div>
                                        {field === 'password' && form.password.length > 0 && form.password.length < 8 && (
                                            <p id="submgr-pw-hint" className="text-[9px] font-bold uppercase tracking-widest text-destructive">
                                                {form.password.length}/8 characters minimum
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </>
                        )}

                        {/* Invite mode — just needs email */}
                        {createMode === 'invite' && (
                            <div className="space-y-2">
                                <Label className="text-[9px] font-black uppercase tracking-widest">Manager Email *</Label>
                                <div className="relative">
                                    <Mail size={14} className="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground" />
                                    <Input type="email" className="pl-10 h-12 rounded-2xl bg-secondary/30 border-border/50 font-bold text-sm" placeholder="manager@email.com"
                                        value={form.email} onChange={e => setForm(p => ({ ...p, email: e.target.value }))} />
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="flex gap-3 mt-6">
                        <Button variant="outline" className="flex-1 h-12 rounded-2xl font-black uppercase tracking-widest text-[10px]"
                            onClick={() => { setIsCreateOpen(false); resetForm(); }}>Cancel</Button>
                        <Button className="flex-1 h-12 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20"
                            onClick={handleCreate} disabled={isSaving}>
                            {isSaving ? 'Saving…' : createMode === 'station' ? 'Add Station' : 'Generate Invite'}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            
        </div>
    );
}
