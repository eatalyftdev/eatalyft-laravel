
'use client';

import React, { useState, useEffect, useMemo } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useDoc, useFirestore, useMemoFirebase, useCollection } from '@/firebase';
import { doc, updateDoc, serverTimestamp, arrayUnion, arrayRemove, collection, query, where, getDoc } from 'firebase/firestore';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import { 
    ArrowLeft, 
    LayoutGrid, 
    Save, 
    ShieldCheck, 
    Users, 
    Armchair, 
    Info,
    Activity,
    Clock,
    User,
    ChevronRight,
    MapPin
} from 'lucide-react';
import SeatMap from '@/components/bus/seat-map';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

type Trip = {
    id: string;
    origin: string;
    destination: string;
    totalSeats: number;
    occupancy: number;
    price: number;
    bookedSeats?: string[];
    pendingSeats?: string[];
    status: string;
    busNumber?: string;
    busClass?: 'classic' | 'vip';
};

type Booking = {
    id: string;
    customerName: string;
    seatNumber: string;
    status: string;
    userId: string;
};

const PassengerIdentity = ({ booking }: { booking: Booking }) => {
    const firestore = useFirestore();
    const [profile, setProfile] = useState<{ name: string; avatar: string } | null>(null);

    useEffect(() => {
        if (!firestore || !booking.userId) return;
        
        const fetchProfile = async () => {
            try {
                const userRef = doc(firestore, 'users', booking.userId);
                const userSnap = await getDoc(userRef);
                if (userSnap.exists()) {
                    const data = userSnap.data();
                    setProfile({
                        name: `${data.firstName} ${data.lastName || ''}`,
                        avatar: data.photoURL || ''
                    });
                } else {
                    // Fallback to name in booking if user doc missing
                    setProfile({ name: booking.customerName || 'Passenger', avatar: '' });
                }
            } catch (e) {
                setProfile({ name: booking.customerName || 'Passenger', avatar: '' });
            }
        };

        fetchProfile();
    }, [firestore, booking.userId, booking.customerName]);

    if (!profile) {
        return (
            <div className="flex items-center gap-3 animate-pulse">
                <div className="size-9 rounded-xl bg-secondary"></div>
                <div className="space-y-2">
                    <div className="h-3 w-24 bg-secondary rounded"></div>
                    <div className="h-2 w-12 bg-secondary rounded"></div>
                </div>
            </div>
        );
    }

    return (
        <div className="flex items-center gap-3">
            <Avatar className="size-9 rounded-xl border border-primary/10">
                <AvatarImage src={profile.avatar} />
                <AvatarFallback className="font-black bg-primary/10 text-primary text-xs uppercase">
                    {profile.name.charAt(0)}
                </AvatarFallback>
            </Avatar>
            <div>
                <p className="text-xs font-black uppercase tracking-tighter text-foreground truncate max-w-[120px]">
                    {profile.name}
                </p>
                <p className="text-[9px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5">
                    Seat: <span className="text-primary">{booking.seatNumber}</span>
                </p>
            </div>
        </div>
    );
};

export default function AgencySeatManagementPage() {
    const params = useParams();
    const router = useRouter();
    const firestore = useFirestore();
    const { toast } = useToast();
    const tripId = params.tripId as string;

    const [isSaving, setIsSaving] = useState(false);

    const tripRef = useMemoFirebase(() => {
        if (!firestore || !tripId) return null;
        return doc(firestore, 'trips', tripId);
    }, [firestore, tripId]);

    const bookingsQuery = useMemoFirebase(() => {
        if (!firestore || !tripId) return null;
        return query(collection(firestore, 'bus'), where('tripId', '==', tripId));
    }, [firestore, tripId]);

    const { data: trip, isLoading } = useDoc<Trip>(tripRef);
    const { data: bookings, isLoading: isBookingsLoading } = useCollection<Booking>(bookingsQuery);

    const handleSeatToggle = async (seatId: string) => {
        if (!trip || !tripRef) return;

        const isCurrentlyPending = trip.pendingSeats?.includes(seatId);
        const isCurrentlyBooked = trip.bookedSeats?.includes(seatId);

        if (isCurrentlyBooked) {
            toast({ variant: 'destructive', title: "Seat Locked", description: "This seat has been officially booked by a passenger." });
            return;
        }

        try {
            if (isCurrentlyPending) {
                await updateDoc(tripRef, {
                    pendingSeats: arrayRemove(seatId),
                    updatedAt: serverTimestamp()
                });
                toast({ title: "Seat Released", description: `Seat ${seatId} is now available.` });
            } else {
                await updateDoc(tripRef, {
                    pendingSeats: arrayUnion(seatId),
                    updatedAt: serverTimestamp()
                });
                toast({ title: "Seat Reserved", description: `Seat ${seatId} is now marked as pending.` });
            }
        } catch (error) {
            toast({ variant: 'destructive', title: "Update Failed", description: (error as Error).message });
        }
    };

    if (isLoading || isBookingsLoading) return <LoadingSpinner />;
    if (!trip) return <div className="p-20 text-center uppercase font-black text-muted-foreground">Mission Node Expired</div>;

    const bookedCount = trip.bookedSeats?.length || 0;
    const pendingCount = trip.pendingSeats?.length || 0;
    const remaining = trip.totalSeats - bookedCount - pendingCount;

    return (
        <div className="min-h-screen bg-background text-foreground font-display selection:bg-primary selection:text-white">
            <header className="sticky top-0 z-50 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center justify-between">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50" onClick={() => router.back()}>
                        <ArrowLeft size={20} />
                    </Button>
                    <div>
                        <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Operational Manifest</span>
                        <h1 className="text-xl font-black uppercase tracking-tighter leading-none">Seat Management</h1>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <Badge variant="outline" className="h-10 px-4 rounded-xl border-primary/20 text-primary font-black uppercase tracking-widest text-[10px] bg-primary/5">
                        Bus #{trip.busNumber || 'TBD'}
                    </Badge>
                    <Badge className={cn(
                        "h-10 px-4 rounded-xl font-black uppercase tracking-widest text-[10px]",
                        trip.busClass === 'vip' ? "bg-amber-500 text-white" : "bg-secondary text-foreground"
                    )}>
                        {trip.busClass || 'Classic'}
                    </Badge>
                </div>
            </header>

            <main className="max-w-7xl mx-auto p-6 md:p-10 lg:p-12 pb-32 flex flex-col lg:grid lg:grid-cols-12 gap-12 items-start">
                
                <section className="w-full lg:col-span-4 space-y-8 lg:sticky lg:top-28">
                    <div className="space-y-2">
                        <h2 className="text-3xl font-black uppercase tracking-tight leading-none">{trip.origin.split(' ')[0]} ➞ {trip.destination.split(' ')[0]}</h2>
                        <p className="text-xs font-bold text-muted-foreground uppercase tracking-widest opacity-60">Verified Inter-City Corridor</p>
                    </div>

                    <div className="grid grid-cols-1 gap-4">
                        <div className="p-6 rounded-[2rem] bg-card border border-border/50 shadow-sm flex items-center justify-between group">
                            <div className="flex items-center gap-4">
                                <div className="size-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-500">
                                    <Users size={20} />
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-[9px] font-black text-muted-foreground uppercase tracking-widest">Total Capacity</span>
                                    <span className="text-2xl font-black tracking-tighter">{trip.totalSeats} Seats</span>
                                </div>
                            </div>
                        </div>

                        <div className="p-6 rounded-[2rem] bg-card border border-border/50 shadow-sm flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <div className="size-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                    <Armchair size={20} />
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-[9px] font-black text-muted-foreground uppercase tracking-widest">Confirmed Manifest</span>
                                    <span className="text-2xl font-black tracking-tighter text-primary">{bookedCount} Booked</span>
                                </div>
                            </div>
                        </div>

                        <div className="p-6 rounded-[2rem] bg-card border border-border/50 shadow-sm flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <div className="size-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500">
                                    <Activity size={20} />
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-[9px] font-black text-muted-foreground uppercase tracking-widest">Node Availability</span>
                                    <span className="text-2xl font-black tracking-tighter text-amber-600">{remaining} Left</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="bg-card rounded-[2.5rem] border border-border/50 shadow-sm overflow-hidden">
                        <div className="p-6 bg-secondary/30 border-b border-border/10 flex items-center justify-between">
                            <h3 className="text-[10px] font-black uppercase tracking-[0.3em] text-foreground">Passenger Roster</h3>
                            <Badge variant="outline" className="text-[8px] font-black uppercase">{bookings?.filter(b => b.id !== '_init').length || 0} Nodes</Badge>
                        </div>
                        <div className="divide-y divide-border/10 max-h-[400px] overflow-y-auto no-scrollbar">
                            {bookings?.filter(b => b.id !== '_init').map((booking) => (
                                <div key={booking.id} className="p-4 flex items-center justify-between group hover:bg-secondary/20 transition-all">
                                    <PassengerIdentity booking={booking} />
                                    <Badge variant="secondary" className="text-[8px] font-black uppercase h-4">{booking.status}</Badge>
                                </div>
                            ))}
                            {(!bookings || bookings.filter(b => b.id !== '_init').length === 0) && (
                                <div className="p-10 text-center space-y-2 opacity-30">
                                    <User className="size-8 mx-auto text-muted-foreground" />
                                    <p className="text-[9px] font-black uppercase tracking-[0.2em]">Manifest Empty</p>
                                </div>
                            )}
                        </div>
                    </div>
                </section>

                <section className="lg:col-span-8 w-full animate-in fade-in slide-in-from-right-4 duration-1000">
                    <div className="flex items-center justify-between mb-8 px-2">
                        <h3 className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">Live Manifest Map</h3>
                        <div className="flex items-center gap-2">
                            <span className="size-2 rounded-full bg-green-500 animate-pulse"></span>
                            <span className="text-[9px] font-black uppercase tracking-widest text-green-500">Real-time Sync Active</span>
                        </div>
                    </div>
                    
                    <div className="scale-90 md:scale-100 origin-top">
                        <SeatMap 
                            busClass={trip.busClass}
                            bookedSeats={trip.bookedSeats} 
                            pendingSeats={trip.pendingSeats}
                            pricePerSeat={trip.price}
                            onSeatClick={handleSeatToggle}
                            isAdmin={true}
                        />
                    </div>
                </section>

            </main>
        </div>
    );
}
