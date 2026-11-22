'use client';

import React, { useState, useMemo, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useUser, useDoc, useAuth, useFirestore, useMemoFirebase, useCollection } from '@/firebase';
import { collection, query, where, orderBy, deleteDoc, doc, getDoc } from 'firebase/firestore';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import { 
    Bus, 
    ArrowLeft, 
    Plus, 
    ChevronRight,
    Users,
    TrendingUp,
    MoreVertical,
    Megaphone,
    LogOut,
    LayoutGrid,
    Trash2,
    Calendar,
    ArrowRightLeft,
    Clock,
    Activity,
    Settings,
    LayoutDashboard,
    BarChart3,
    QrCode,
    CheckCircle2,
    XCircle,
    PlayCircle,
    Navigation,
    Loader2,
    FileText
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import Link from 'next/link';
import { format } from 'date-fns';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useToast } from '@/hooks/use-toast';
import { sendBusTripNotification } from '@/ai/flows/bus-notification-flow';


type BusTrip = {
    id: string;
    origin: string;
    destination: string;
    price: number;
    occupancy: number;
    totalSeats: number;
    status: string;
    departureAt: any;
    busNumber?: string;
    busClass?: string;
    bookedSeats?: string[];
    pendingSeats?: string[];
};

export default function AgencyMissionsPage() {
    const { user, isUserLoading } = useUser();
    const auth = useAuth();
    const firestore = useFirestore();
    const router = useRouter();
    const { toast } = useToast();
    const [isPinging, setIsPinging] = useState<string | null>(null);
    const [isStatusUpdating, setIsStatusUpdating] = useState<string | null>(null);
    const [resolvedAgencyId, setResolvedAgencyId] = useState<string | null>(null);

    useEffect(() => {
        if (!user || !firestore) return;
        const resolveId = async () => {
            const subSnap = await getDoc(doc(firestore, 'sub_agents', user.uid));
            if (subSnap.exists()) {
                setResolvedAgencyId(subSnap.data().managedAgencyId || user.uid);
            } else {
                setResolvedAgencyId(user.uid);
            }
        };
        resolveId();
    }, [user, firestore]);

    const tripsQuery = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return query(
            collection(firestore, 'trips'),
            where('agencyId', '==', resolvedAgencyId),
            orderBy('departureAt', 'desc')
        );
    }, [resolvedAgencyId, firestore]);

    const { data: allTrips, isLoading: isTripsLoading } = useCollection<BusTrip>(tripsQuery);

    const handlePingPassengers = async (tripId: string, type: 'boarding' | 'departure') => {
        setIsPinging(`${tripId}_${type}`);
        try {
            const result = await sendBusTripNotification({ tripId, type });
            if (result.success) {
                toast({ title: "Protocol Synced", description: `Dispatched ${type} alerts to passengers.` });
            } else {
                throw new Error("Communication node failure.");
            }
        } catch (error) {
toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        } finally {
            setIsPinging(null);
        }
    };

    const handleDeleteTrip = async (tripId: string) => {
        if (!firestore) return;
        try {
            await deleteDoc(doc(firestore, 'trips', tripId));
            toast({ title: "Mission Scrapped", description: "The trip has been removed from the registry." });
        } catch (error) {
toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        }
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
            const statusLabels: Record<string, string> = {
                completed: 'Trip marked as completed. Passengers emailed.',
                cancelled: 'Trip cancelled. Passengers notified.',
                in_progress: 'Trip marked as in progress.',
                boarding: 'Boarding started.',
            };
            toast({ title: 'Status Updated', description: statusLabels[status] });
        } catch (error) {
            toast({ title: 'Error', description: (error as Error).message, variant: 'destructive' });
        } finally {
            setIsStatusUpdating(null);
        }
    };

    if (isUserLoading || isTripsLoading) return <LoadingSpinner />;

    const trips = allTrips?.filter(t => t.id !== '_init') || [];

    return (
        <div className="min-h-screen bg-background font-display text-foreground selection:bg-primary selection:text-white">
            <main className="flex flex-col relative overflow-x-hidden pb-32">
                <header className="sticky top-0 z-40 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center justify-between shadow-sm">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50 lg:hidden" onClick={() => router.back()}>
                            <ArrowLeft size={20} />
                        </Button>
                        <div>
                            <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Operational Ledger</span>
                            <h1 className="text-xl font-black uppercase tracking-tighter leading-none">Global Missions</h1>
                        </div>
                    </div>
                    <Button onClick={() => router.push('/agency/trips/new')} className="rounded-2xl h-11 px-6 font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20 gap-2">
                        <Plus size={16} /> Schedule Mission
                    </Button>
                </header>

                <div className="p-6 space-y-8 max-w-6xl mx-auto w-full">
                    {trips.length > 0 ? (
                        <div className="grid gap-6">
                            {trips.map(trip => {
                                const booked = trip.bookedSeats?.length || 0;
                                const pending = trip.pendingSeats?.length || 0;
                                const total = trip.totalSeats || 75;
                                const open = total - booked - pending;
                                const percent = (booked / total) * 100;
                                const isFull = booked >= total;
                                const departureAt = trip.departureAt?.toDate ? trip.departureAt.toDate() : new Date();

                                return (
                                    <div key={trip.id} className="group relative bg-card border border-border/50 hover:border-primary/30 transition-all duration-500 rounded-[2.5rem] p-8 shadow-sm hover:shadow-xl">
                                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-8 mb-10">
                                            <div className="flex items-center gap-6 flex-1">
                                                <div className="size-16 rounded-3xl bg-secondary flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-sm">
                                                    <Bus size={32} />
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-4">
                                                        <span className="text-2xl font-black uppercase tracking-tighter">{trip.origin.split(' ')[0]}</span>
                                                        <ArrowRightLeft className="size-5 text-muted-foreground opacity-40" />
                                                        <span className="text-2xl font-black uppercase tracking-tighter">{trip.destination.split(' ')[0]}</span>
                                                    </div>
                                                    <div className="flex items-center gap-3 mt-2">
                                                        <Badge variant="outline" className="text-[10px] font-black uppercase tracking-widest border-primary/20 text-primary px-2 h-5 bg-primary/5">
                                                            Bus #{trip.busNumber || 'TBD'}
                                                        </Badge>
                                                        <span className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest opacity-60">
                                                            {format(departureAt, 'PPP • p')}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-10 bg-secondary/30 px-8 py-4 rounded-3xl border border-border/50 shadow-inner">
                                                <div className="text-center">
                                                    <p className="text-xl font-black text-primary leading-none">{booked}</p>
                                                    <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-1.5">Booked</p>
                                                </div>
                                                <div className="text-center">
                                                    <p className="text-xl font-black text-amber-500 leading-none">{pending}</p>
                                                    <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-1.5">Pending</p>
                                                </div>
                                                <div className="text-center">
                                                    <p className="text-xl font-black text-foreground leading-none">{open}</p>
                                                    <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-1.5">Open</p>
                                                </div>
                                            </div>

                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon" className="rounded-2xl size-12 bg-secondary/50">
                                                        <MoreVertical size={24} />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end" className="w-64 rounded-3xl p-3 border-border/50 shadow-2xl">
                                                    <DropdownMenuLabel className="text-[10px] font-black uppercase tracking-widest px-3 py-2 text-muted-foreground">Mission Control</DropdownMenuLabel>
                                                    
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/agency/trips/${trip.id}/seats`} className="rounded-2xl py-4 gap-4 font-black text-xs uppercase cursor-pointer hover:bg-primary/5 transition-all">
                                                            <div className="size-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center">
                                                                <LayoutGrid size={18} />
                                                            </div>
                                                            Manifest Manager
                                                        </Link>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/agency/trips/${trip.id}/manifest`} className="rounded-2xl py-4 gap-4 font-black text-xs uppercase cursor-pointer hover:bg-primary/5 transition-all">
                                                            <div className="size-10 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                                                                <FileText size={18} />
                                                            </div>
                                                            Passenger Manifest
                                                        </Link>
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem onSelect={() => handlePingPassengers(trip.id, 'boarding')} className="rounded-2xl py-4 gap-4 font-black text-xs uppercase cursor-pointer" disabled={!!isPinging}>
                                                        <div className="size-10 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center">
                                                            <Megaphone size={18} />
                                                        </div>
                                                        Dispatch Boarding Alert
                                                    </DropdownMenuItem>

                                                    <DropdownMenuItem onSelect={() => handlePingPassengers(trip.id, 'departure')} className="rounded-2xl py-4 gap-4 font-black text-xs uppercase cursor-pointer text-primary" disabled={!!isPinging}>
                                                        <div className="size-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                                                            <Clock size={18} />
                                                        </div>
                                                        Confirm Fleet Departure
                                                    </DropdownMenuItem>

                                                    {['scheduled', 'boarding'].includes(trip.status) && (
                                                        <DropdownMenuItem onSelect={() => handleTripStatusUpdate(trip.id, 'in_progress')} className="rounded-2xl py-4 gap-4 font-black text-xs uppercase cursor-pointer text-blue-600" disabled={!!isStatusUpdating}>
                                                            <div className="size-10 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center">
                                                                {isStatusUpdating === `${trip.id}_in_progress` ? <Loader2 size={18} className="animate-spin" /> : <Navigation size={18} />}
                                                            </div>
                                                            Mark Departed
                                                        </DropdownMenuItem>
                                                    )}

                                                    {trip.status === 'in_progress' && (
                                                        <DropdownMenuItem onSelect={() => handleTripStatusUpdate(trip.id, 'completed')} className="rounded-2xl py-4 gap-4 font-black text-xs uppercase cursor-pointer text-teal-600" disabled={!!isStatusUpdating}>
                                                            <div className="size-10 rounded-xl bg-teal-500/10 text-teal-500 flex items-center justify-center">
                                                                {isStatusUpdating === `${trip.id}_completed` ? <Loader2 size={18} className="animate-spin" /> : <CheckCircle2 size={18} />}
                                                            </div>
                                                            Mark Completed
                                                        </DropdownMenuItem>
                                                    )}

                                                    {!['completed', 'cancelled'].includes(trip.status) && (
                                                        <DropdownMenuItem onSelect={() => handleTripStatusUpdate(trip.id, 'cancelled')} className="rounded-2xl py-4 gap-4 font-black text-xs uppercase cursor-pointer text-red-600" disabled={!!isStatusUpdating}>
                                                            <div className="size-10 rounded-xl bg-red-500/10 text-red-500 flex items-center justify-center">
                                                                {isStatusUpdating === `${trip.id}_cancelled` ? <Loader2 size={18} className="animate-spin" /> : <XCircle size={18} />}
                                                            </div>
                                                            Cancel Trip
                                                        </DropdownMenuItem>
                                                    )}

                                                    <DropdownMenuSeparator className="my-2" />
                                                    
                                                    <DropdownMenuItem onSelect={() => handleDeleteTrip(trip.id)} className="rounded-2xl py-4 gap-4 font-black text-xs uppercase cursor-pointer text-destructive">
                                                        <div className="size-10 rounded-xl bg-destructive/10 text-destructive flex items-center justify-center">
                                                            <Trash2 size={18} />
                                                        </div>
                                                        Abort Mission Node
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>

                                        <div className="space-y-4">
                                            <div className="flex justify-between items-end px-1">
                                                <div className="flex items-center gap-2">
                                                    <Users size={14} className="text-muted-foreground" />
                                                    <span className="text-[10px] font-black text-muted-foreground uppercase tracking-widest">Load Factor ({percent.toFixed(0)}%)</span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    {isFull && <Badge className="bg-emerald-500 text-white font-black text-[8px] h-4 uppercase border-none px-1.5">MANIFEST FULL</Badge>}
                                                    <span className="text-[10px] font-black uppercase tracking-tighter">{booked}/{total} <span className="opacity-40">Seats Secured</span></span>
                                                </div>
                                            </div>
                                            <div className="w-full h-3 bg-secondary rounded-full overflow-hidden shadow-inner border border-border/20 p-0.5">
                                                <div 
                                                    className={cn(
                                                        "h-full rounded-full transition-all [transition-duration:2000ms] ease-out",
                                                        isFull ? "bg-emerald-500" : "bg-primary shadow-[0_0_15px_rgba(242,13,13,0.4)]"
                                                    )} 
                                                    style={{ width: `${percent}%` }}
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="py-40 text-center flex flex-col items-center gap-6 opacity-30 grayscale border-2 border-dashed border-border/50 rounded-[3rem]">
                            <Calendar size={64} className="text-muted-foreground" />
                            <div className="space-y-2">
                                <h3 className="text-2xl font-black uppercase tracking-tighter">No Missions Scheduled</h3>
                                <p className="text-xs font-bold text-muted-foreground uppercase tracking-widest max-w-xs mx-auto leading-relaxed">
                                    Your operational log is empty. Initialize a new trip node to begin manifest collection.
                                </p>
                            </div>
                            <Button variant="outline" className="rounded-xl px-10 h-14 font-black uppercase tracking-widest text-[10px] border-border" onClick={() => router.push('/agency/trips/new')}>
                                Register Mission
                            </Button>
                        </div>
                    )}
                </div>
            </main>

            
        </div>
    );
}
