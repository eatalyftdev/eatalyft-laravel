'use client';

import React, { useMemo, useState, useEffect } from 'react';
import { useUser, useDoc, useFirestore, useMemoFirebase, useCollection, useAuth } from '@/firebase';
import { useRequireRole } from '@/hooks/use-require-role';
import { doc, collection, query, where, orderBy, limit, getDoc, deleteDoc } from 'firebase/firestore';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import {
    Bus, Calendar, Ticket, Wallet, Search, Bell, ArrowRightLeft, Plus,
    LayoutDashboard, BarChart3, Settings, ChevronRight, Users, Activity,
    Truck, MoreVertical, Megaphone, QrCode, LayoutGrid, ShieldCheck,
    ArrowRight, CheckCircle2, XCircle, Navigation, Loader2, Trash2,
    Clock, Link as LinkIcon
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import Link from 'next/link';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { VerifiedBadge } from '@/components/ui/verified-badge';
import { format } from 'date-fns';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useToast } from '@/hooks/use-toast';
import { sendBusTripNotification } from '@/ai/flows/bus-notification-flow';


type AgencyProfile = { id: string; name: string; isVerified: boolean; logoUrl?: string };
type FleetSummary = { bus75: number; bus65: number; bus50: number; bus30: number; bus18: number };
type BusTrip = {
    id: string; origin: string; destination: string; price: number;
    occupancy: number; totalSeats: number; status: string; departureAt: any;
    busNumber?: string; busClass?: string; bookedSeats?: string[]; pendingSeats?: string[];
};

const StatCard = ({ title, value, trend, icon: Icon, color, subValue }: {
    title: string; value: string; trend: string; icon: any; color: string; subValue?: string;
}) => (
    <div className="flex min-w-[160px] flex-1 flex-col gap-3 rounded-2xl p-5 bg-card border border-border/50 shadow-sm hover:border-primary/30 transition-all duration-500 group">
        <div className="flex items-center justify-between">
            <div className={cn("p-2 rounded-xl bg-secondary group-hover:bg-primary/10 transition-colors", color)}>
                <Icon size={20} />
            </div>
            <span className="text-emerald-500 text-[10px] font-black uppercase tracking-widest bg-emerald-500/10 px-2 py-0.5 rounded-md">{trend}</span>
        </div>
        <div className="space-y-1">
            <p className="text-muted-foreground text-[10px] font-black uppercase tracking-[0.2em]">{title}</p>
            <p className="text-foreground text-2xl font-black tracking-tighter">{value}</p>
            {subValue && <p className="text-[9px] font-bold text-muted-foreground uppercase tracking-widest opacity-60">{subValue}</p>}
        </div>
    </div>
);

export default function AgencyDashboard() {
    const { user, isUserLoading } = useUser();
    const { isChecking } = useRequireRole(['agency', 'agency_manager', 'sub_agent']);
    const auth = useAuth();
    const firestore = useFirestore();
    const { toast } = useToast();
    const [isPinging, setIsPinging] = useState<string | null>(null);
    const [isStatusUpdating, setIsStatusUpdating] = useState<string | null>(null);
    const [resolvedAgencyId, setResolvedAgencyId] = useState<string | null>(null);

    useEffect(() => {
        if (!user || !firestore) return;
        getDoc(doc(firestore, 'sub_agents', user.uid)).then(snap => {
            if (snap.exists()) {
                setResolvedAgencyId(snap.data().managedAgencyId || user.uid);
            } else {
                setResolvedAgencyId(user.uid);
            }
        });
    }, [user, firestore]);

    const agencyRef = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return doc(firestore, 'agencies', resolvedAgencyId);
    }, [resolvedAgencyId, firestore]);

    const fleetHubRef = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return doc(firestore, 'fleet', resolvedAgencyId);
    }, [resolvedAgencyId, firestore]);

    const { data: agency, isLoading: isAgencyLoading } = useDoc<AgencyProfile>(agencyRef);
    const { data: fleetHub, isLoading: isFleetHubLoading } = useDoc<FleetSummary>(fleetHubRef);

    const upcomingTripsQuery = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return query(
            collection(firestore, 'trips'),
            where('agencyId', '==', resolvedAgencyId),
            where('status', 'in', ['scheduled', 'boarding', 'in_progress']),
            orderBy('departureAt', 'asc'),
            limit(10)
        );
    }, [resolvedAgencyId, firestore]);

    const { data: liveTrips, isLoading: isTripsLoading } = useCollection<BusTrip>(upcomingTripsQuery);

    const allTripsQuery = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return query(collection(firestore, 'trips'), where('agencyId', '==', resolvedAgencyId));
    }, [resolvedAgencyId, firestore]);

    const { data: allTrips, isLoading: isAllTripsLoading } = useCollection<BusTrip>(allTripsQuery);

    const { totalFleet, totalRevenue, completedMissions, loadFactor } = useMemo(() => {
        const validTrips = allTrips?.filter(t => t.id !== '_init') || [];
        let revenue = 0, completedCount = 0, totalOcc = 0, totalCap = 0;
        validTrips.forEach(t => {
            totalOcc += (t.occupancy || 0);
            totalCap += (t.totalSeats || 1);
            if (t.status === 'departed' || t.status === 'completed') revenue += (t.occupancy || 0) * (t.price || 0);
            if (t.status === 'completed') completedCount++;
        });
        const fleetCount = fleetHub ? (
            (Number(fleetHub.bus75) || 0) + (Number(fleetHub.bus65) || 0) +
            (Number(fleetHub.bus50) || 0) + (Number(fleetHub.bus30) || 0) + (Number(fleetHub.bus18) || 0)
        ) : 0;
        return { totalFleet: fleetCount, totalRevenue: revenue, completedMissions: completedCount, loadFactor: totalCap > 0 ? Math.round((totalOcc / totalCap) * 100) : 0 };
    }, [fleetHub, allTrips]);

    const handlePingPassengers = async (tripId: string, type: 'boarding' | 'departure') => {
        setIsPinging(`${tripId}_${type}`);
        try {
            const result = await sendBusTripNotification({ tripId, type });
            if (result.success) {
                toast({ title: "Protocol Synced", description: `Dispatched ${type} alerts to passengers.` });
            } else throw new Error("Cluster failure.");
        } catch (error) {
            toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        } finally { setIsPinging(null); }
    };

    const handleTripStatusUpdate = async (tripId: string, status: 'completed' | 'cancelled' | 'in_progress' | 'boarding') => {
        if (!auth?.currentUser) return;
        setIsStatusUpdating(`${tripId}_${status}`);
        try {
            const idToken = await auth.currentUser.getIdToken();
            const res = await fetch('/api/trips/update-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${idToken}` },
                body: JSON.stringify({ tripId, status }),
            });
            const data = await res.json();
            if (!res.ok || !data.success) throw new Error(data.error || 'Failed');
            const labels: Record<string, string> = {
                completed: 'Marked completed. Passengers notified.',
                cancelled: 'Trip cancelled. Passengers notified.',
                in_progress: 'Trip marked as in progress.',
                boarding: 'Boarding started.',
            };
            toast({ title: 'Status Updated', description: labels[status] });
        } catch (error) {
            toast({ title: 'Error', description: (error as Error).message, variant: 'destructive' });
        } finally { setIsStatusUpdating(null); }
    };

    const handleDeleteTrip = async (tripId: string) => {
        if (!firestore) return;
        try {
            await deleteDoc(doc(firestore, 'trips', tripId));
            toast({ title: "Mission Scrapped", description: "Trip removed from the registry." });
        } catch (error) {
            toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        }
    };

    if (isChecking || isAgencyLoading || isTripsLoading || isAllTripsLoading || isFleetHubLoading) return <LoadingSpinner />;
    if (!agency) return null;

    const trips = liveTrips?.filter(t => t.id !== '_init') || [];
    const pendingOrdersCount = trips.filter(t => t.status === 'boarding').length;

    if ((agency as any).status === 'suspended') {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background px-5">
                <div className="max-w-sm text-center space-y-4">
                    <div className="mx-auto w-16 h-16 rounded-2xl bg-red-500/10 flex items-center justify-center">
                        <XCircle size={32} className="text-red-500" />
                    </div>
                    <h2 className="text-2xl font-black">Account Suspended</h2>
                    <p className="text-muted-foreground text-sm">
                        Your agency has been suspended by the administrator. Please contact EataLyft support for assistance.
                    </p>
                    <p className="text-xs text-muted-foreground/60">Agency: {(agency as any).agencyName || agency.name}</p>
                </div>
            </div>
        );
    }

    if ((agency as any).status === 'pending') {
        return (
            <div className="min-h-screen flex items-center justify-center bg-background px-5">
                <div className="max-w-sm text-center space-y-4">
                    <div className="mx-auto w-16 h-16 rounded-2xl bg-amber-500/10 flex items-center justify-center">
                        <Clock size={32} className="text-amber-500 animate-pulse" />
                    </div>
                    <h2 className="text-2xl font-black">Pending Approval</h2>
                    <p className="text-muted-foreground text-sm">
                        Your agency registration is under review. Full panel access will be unlocked once an admin approves your account.
                    </p>
                    <p className="text-xs text-muted-foreground/60">Agency: {(agency as any).agencyName || agency.name}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-background font-display text-foreground selection:bg-primary selection:text-white">
            <main className="flex flex-col relative overflow-x-hidden pb-32">
                <header className="sticky top-0 z-40 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center justify-between shadow-sm">
                    <div className="flex items-center gap-4">
                        <Link href="/agency/profile" className="size-12 rounded-2xl p-0.5 border-2 border-primary/20 relative group active:scale-95 transition-transform">
                            <Avatar className="size-full rounded-xl">
                                <AvatarImage src={agency.logoUrl || user?.photoURL || ''} className="object-cover" />
                                <AvatarFallback className="font-black bg-secondary text-primary">{agency.name[0]}</AvatarFallback>
                            </Avatar>
                            {agency.isVerified && <VerifiedBadge className="absolute -bottom-1 -right-1 size-4 border-2 border-background" />}
                        </Link>
                        <div>
                            <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Fleet Intelligence</span>
                            <h1 className="text-xl font-black uppercase tracking-tighter leading-none">{agency.name}</h1>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button variant="ghost" size="icon" className="relative size-11 rounded-2xl bg-secondary/50 hover:bg-secondary">
                            <Bell size={20} />
                            {pendingOrdersCount > 0 && <span className="absolute top-2.5 right-2.5 size-2 bg-primary rounded-full animate-pulse border-2 border-background" />}
                        </Button>
                    </div>
                </header>

                <div className="p-6 space-y-10 max-w-7xl mx-auto w-full">
                    {/* Stats */}
                    <section>
                        <div className="flex items-center justify-between mb-6 px-1">
                            <h3 className="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em]">Operational Metrics</h3>
                            <Badge variant="outline" className="text-[8px] font-black uppercase tracking-widest border-primary/20 text-primary">Live Hub Sync</Badge>
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                            <Link href="/agency/fleet" className="flex-1 flex">
                                <StatCard title="Active Fleet" value={`${totalFleet} Assets`} subValue="Live Ledger Nodes" trend="Stable" icon={Truck} color="text-primary" />
                            </Link>
                            <StatCard title="Completed Missions" value={`${completedMissions}`} trend="+12.5%" icon={Activity} color="text-blue-500" />
                            <Link href="/agency/wallet" className="flex-1 flex">
                                <StatCard title="Gross Settlement" value={`₣${totalRevenue.toLocaleString()}`} trend="+8.2%" icon={Wallet} color="text-emerald-500" />
                            </Link>
                        </div>
                    </section>

                    <div className="grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">
                        {/* Trips column */}
                        <section className="xl:col-span-8 space-y-6">
                            <div className="flex items-center justify-between px-1">
                                <div>
                                    <h3 className="text-xl font-black uppercase tracking-tighter">Live &amp; Upcoming Missions</h3>
                                    <p className="text-[9px] font-black text-muted-foreground uppercase tracking-widest opacity-60">Scheduled · Boarding · In Progress</p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <Button onClick={() => {}} className="rounded-2xl h-10 px-4 font-black uppercase tracking-widest text-[9px] shadow-xl shadow-primary/20 gap-2" asChild>
                                        <Link href="/agency/trips/new"><Plus size={14} /> New</Link>
                                    </Button>
                                    <Button variant="link" className="text-[10px] font-black text-primary uppercase tracking-[0.2em] group" asChild>
                                        <Link href="/agency/trips">All <ChevronRight className="size-3 ml-1 group-hover:translate-x-1 transition-transform" /></Link>
                                    </Button>
                                </div>
                            </div>

                            <div className="grid gap-6">
                                {trips.length > 0 ? trips.map(trip => {
                                    const booked = trip.bookedSeats?.length || trip.occupancy || 0;
                                    const pending = trip.pendingSeats?.length || 0;
                                    const total = trip.totalSeats || 75;
                                    const open = total - booked - pending;
                                    const percent = (booked / total) * 100;
                                    const isFull = booked >= total;
                                    const departureAt = trip.departureAt?.toDate ? trip.departureAt.toDate() : new Date();

                                    const statusColor = trip.status === 'boarding' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' :
                                        trip.status === 'in_progress' ? 'bg-blue-500/10 text-blue-500 border-blue-500/20' :
                                        'bg-primary/10 text-primary border-primary/20';

                                    return (
                                        <div key={trip.id} className="group relative bg-card border border-border/50 hover:border-primary/30 transition-all duration-500 rounded-[2.5rem] p-6 shadow-sm hover:shadow-xl">
                                            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 mb-8">
                                                <div className="flex items-center gap-5 flex-1">
                                                    <div className="size-14 rounded-3xl bg-secondary flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-sm shrink-0">
                                                        <Bus size={28} />
                                                    </div>
                                                    <div>
                                                        <div className="flex items-center gap-3">
                                                            <span className="text-xl font-black uppercase tracking-tighter">{trip.origin.split(' ')[0]}</span>
                                                            <ArrowRightLeft className="size-4 text-muted-foreground opacity-40" />
                                                            <span className="text-xl font-black uppercase tracking-tighter">{trip.destination.split(' ')[0]}</span>
                                                        </div>
                                                        <div className="flex items-center gap-2 mt-1.5">
                                                            <Badge variant="outline" className={cn("text-[9px] font-black uppercase h-5 px-2", statusColor)}>
                                                                {trip.status.replace('_', ' ')}
                                                            </Badge>
                                                            <Badge variant="outline" className="text-[9px] font-black uppercase h-5 px-2 border-primary/20 text-primary bg-primary/5">
                                                                #{trip.busNumber || 'TBD'}
                                                            </Badge>
                                                            <span className="text-[9px] font-bold text-muted-foreground uppercase opacity-60">
                                                                {format(departureAt, 'PPP · p')}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Seat counters */}
                                                <div className="flex items-center gap-6 bg-secondary/30 px-6 py-3 rounded-2xl border border-border/50 shadow-inner shrink-0">
                                                    <div className="text-center">
                                                        <p className="text-lg font-black text-primary leading-none">{booked}</p>
                                                        <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-1">Booked</p>
                                                    </div>
                                                    <div className="text-center">
                                                        <p className="text-lg font-black text-amber-500 leading-none">{pending}</p>
                                                        <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-1">Pending</p>
                                                    </div>
                                                    <div className="text-center">
                                                        <p className="text-lg font-black leading-none">{open}</p>
                                                        <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-1">Open</p>
                                                    </div>
                                                </div>

                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50 shrink-0">
                                                            <MoreVertical size={20} />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end" className="w-64 rounded-3xl p-3 border-border/50 shadow-2xl">
                                                        <DropdownMenuLabel className="text-[10px] font-black uppercase tracking-widest px-3 py-2 text-muted-foreground">Mission Control</DropdownMenuLabel>

                                                        <DropdownMenuItem asChild>
                                                            <Link href={`/agency/trips/${trip.id}/seats`} className="rounded-2xl py-3 gap-3 font-black text-xs uppercase cursor-pointer hover:bg-primary/5">
                                                                <div className="size-9 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center shrink-0"><LayoutGrid size={16} /></div>
                                                                Manifest Manager
                                                            </Link>
                                                        </DropdownMenuItem>

                                                        <DropdownMenuItem onSelect={() => handlePingPassengers(trip.id, 'boarding')} className="rounded-2xl py-3 gap-3 font-black text-xs uppercase" disabled={!!isPinging}>
                                                            <div className="size-9 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center shrink-0"><Megaphone size={16} /></div>
                                                            Dispatch Boarding Alert
                                                        </DropdownMenuItem>

                                                        <DropdownMenuItem onSelect={() => handlePingPassengers(trip.id, 'departure')} className="rounded-2xl py-3 gap-3 font-black text-xs uppercase text-primary" disabled={!!isPinging}>
                                                            <div className="size-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0"><Clock size={16} /></div>
                                                            Confirm Fleet Departure
                                                        </DropdownMenuItem>

                                                        {['scheduled', 'boarding'].includes(trip.status) && (
                                                            <DropdownMenuItem onSelect={() => handleTripStatusUpdate(trip.id, 'in_progress')} className="rounded-2xl py-3 gap-3 font-black text-xs uppercase text-blue-600" disabled={!!isStatusUpdating}>
                                                                <div className="size-9 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center shrink-0">
                                                                    {isStatusUpdating === `${trip.id}_in_progress` ? <Loader2 size={16} className="animate-spin" /> : <Navigation size={16} />}
                                                                </div>
                                                                Mark Departed
                                                            </DropdownMenuItem>
                                                        )}

                                                        {trip.status === 'in_progress' && (
                                                            <DropdownMenuItem onSelect={() => handleTripStatusUpdate(trip.id, 'completed')} className="rounded-2xl py-3 gap-3 font-black text-xs uppercase text-teal-600" disabled={!!isStatusUpdating}>
                                                                <div className="size-9 rounded-xl bg-teal-500/10 text-teal-500 flex items-center justify-center shrink-0">
                                                                    {isStatusUpdating === `${trip.id}_completed` ? <Loader2 size={16} className="animate-spin" /> : <CheckCircle2 size={16} />}
                                                                </div>
                                                                Mark Completed
                                                            </DropdownMenuItem>
                                                        )}

                                                        {!['completed', 'cancelled'].includes(trip.status) && (
                                                            <DropdownMenuItem onSelect={() => handleTripStatusUpdate(trip.id, 'cancelled')} className="rounded-2xl py-3 gap-3 font-black text-xs uppercase text-red-600" disabled={!!isStatusUpdating}>
                                                                <div className="size-9 rounded-xl bg-red-500/10 text-red-500 flex items-center justify-center shrink-0">
                                                                    {isStatusUpdating === `${trip.id}_cancelled` ? <Loader2 size={16} className="animate-spin" /> : <XCircle size={16} />}
                                                                </div>
                                                                Cancel Trip
                                                            </DropdownMenuItem>
                                                        )}

                                                        <DropdownMenuSeparator className="my-2" />
                                                        <DropdownMenuItem onSelect={() => handleDeleteTrip(trip.id)} className="rounded-2xl py-3 gap-3 font-black text-xs uppercase text-destructive">
                                                            <div className="size-9 rounded-xl bg-destructive/10 text-destructive flex items-center justify-center shrink-0"><Trash2 size={16} /></div>
                                                            Abort Mission Node
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            </div>

                                            {/* Load factor bar */}
                                            <div className="space-y-3">
                                                <div className="flex justify-between items-end px-1">
                                                    <div className="flex items-center gap-2">
                                                        <Users size={12} className="text-muted-foreground" />
                                                        <span className="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Load Factor ({percent.toFixed(0)}%)</span>
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        {isFull && <Badge className="bg-emerald-500 text-white font-black text-[8px] h-4 uppercase border-none px-1.5">MANIFEST FULL</Badge>}
                                                        <span className="text-[10px] font-black uppercase tracking-tighter">{booked}/{total} <span className="opacity-40">Secured</span></span>
                                                    </div>
                                                </div>
                                                <div className="w-full h-3 bg-secondary rounded-full overflow-hidden shadow-inner border border-border/20 p-0.5">
                                                    <div
                                                        className={cn("h-full rounded-full transition-all [transition-duration:2000ms] ease-out", isFull ? "bg-emerald-500" : "bg-primary shadow-[0_0_15px_rgba(242,13,13,0.4)]")}
                                                        style={{ width: `${percent}%` }}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    );
                                }) : (
                                    <div className="py-20 text-center flex flex-col items-center gap-4 opacity-30 grayscale border-2 border-dashed border-border/50 rounded-[2rem]">
                                        <Calendar size={48} className="text-muted-foreground" />
                                        <p className="text-[10px] font-black uppercase tracking-[0.3em]">No upcoming missions</p>
                                    </div>
                                )}
                            </div>
                        </section>

                        {/* Right sidebar */}
                        <div className="xl:col-span-4 space-y-8">
                            <section className="bg-card rounded-[2.5rem] border border-border/50 shadow-sm overflow-hidden group">
                                <div className="p-6 bg-secondary/30 border-b border-border/10 flex items-center justify-between">
                                    <h3 className="text-[10px] font-black uppercase tracking-[0.3em]">Fleet Composition</h3>
                                    <div className="size-8 rounded-xl bg-background flex items-center justify-center border border-border/50 text-primary">
                                        <Truck size={16} />
                                    </div>
                                </div>
                                <div className="p-6 space-y-4">
                                    {[
                                        { label: '75-Seater Coach', count: fleetHub?.bus75 || 0, color: 'bg-primary' },
                                        { label: '65-Seater VIP', count: fleetHub?.bus65 || 0, color: 'bg-amber-500' },
                                        { label: '50-Seater Liner', count: fleetHub?.bus50 || 0, color: 'bg-blue-500' },
                                        { label: '30-Seater Urban', count: fleetHub?.bus30 || 0, color: 'bg-emerald-500' },
                                        { label: '18-Seater Van', count: fleetHub?.bus18 || 0, color: 'bg-indigo-500' },
                                    ].map(type => (
                                        <div key={type.label} className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className={cn("size-2 rounded-full", type.color)} />
                                                <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">{type.label}</span>
                                            </div>
                                            <span className="text-sm font-black text-foreground">{type.count}</span>
                                        </div>
                                    ))}
                                    <Button variant="outline" className="w-full h-12 rounded-xl mt-4 font-black uppercase tracking-widest text-[9px] border-border/50 hover:bg-primary hover:text-white transition-all shadow-sm" asChild>
                                        <Link href="/agency/fleet">Audit Fleet Ledger</Link>
                                    </Button>
                                </div>
                            </section>

                            <section className="bg-[#1c2434] rounded-[2.5rem] p-8 text-white shadow-2xl relative overflow-hidden group">
                                <div className="absolute top-0 right-0 p-10 opacity-10 rotate-12 transition-transform [transition-duration:2000ms] group-hover:rotate-0">
                                    <ShieldCheck size={200} />
                                </div>
                                <div className="relative z-10">
                                    <div className="size-12 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center mb-6 border border-white/10 shadow-xl">
                                        <Activity size={24} className="text-primary" />
                                    </div>
                                    <h4 className="text-2xl font-black uppercase tracking-tighter">NODE COMPLIANCE</h4>
                                    <p className="text-sm font-medium text-white/60 mt-2 leading-relaxed uppercase tracking-widest opacity-80">
                                        Efficiency: {loadFactor}% load factor across all corridors.
                                    </p>
                                    <Button variant="secondary" className="mt-8 w-full h-14 rounded-xl bg-white text-primary font-black uppercase tracking-widest text-[10px] shadow-xl" asChild>
                                        <Link href="/agency/insights">Performance Review</Link>
                                    </Button>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </main>
            
        </div>
    );
}
