'use client';

import React, { useState, useMemo } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useUser, useDoc, useFirestore, useMemoFirebase } from '@/firebase';
import { doc } from 'firebase/firestore';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import { 
    ArrowLeft, 
    Bus, 
    Info, 
    ShieldCheck, 
    Users, 
    ArrowRight,
    MapPin,
    Clock,
    Zap,
    Armchair,
    Activity,
    LayoutGrid,
    Building2,
    Phone,
    Navigation,
    CheckCircle2,
} from 'lucide-react';
import SeatMap from '@/components/bus/seat-map';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';
import { format } from 'date-fns';

type Trip = {
    id: string;
    origin: string;
    destination: string;
    price: number;
    totalSeats: number;
    occupancy: number;
    status: string;
    departureAt: any;
    busNumber?: string;
    busClass?: 'classic' | 'vip' | '75' | '65' | '50' | '30' | '18' | string;
    bookedSeats?: string[];
    pendingSeats?: string[];
    stationId?: string;
    stationName?: string;
    stationLocation?: string;
    agencyId?: string;
};

const BOOKING_FEE_PER_SEAT = 500;

export default function BusSeatBookingPage() {
    const params = useParams();
    const router = useRouter();
    const { user, isUserLoading } = useUser();
    const firestore = useFirestore();
    const { toast } = useToast();
    const tripId = params.tripId as string;

    const [selectedSeats, setSelectedSeats] = useState<string[]>([]);

    const tripRef = useMemoFirebase(() => {
        if (!firestore || !tripId) return null;
        return doc(firestore, 'trips', tripId);
    }, [firestore, tripId]);

    const { data: trip, isLoading: isTripLoading } = useDoc<Trip>(tripRef);

    const handleSeatToggle = (seatId: string) => {
        setSelectedSeats(prev => 
            prev.includes(seatId) 
                ? prev.filter(s => s !== seatId) 
                : [...prev, seatId]
        );
    };

    const handleContinue = () => {
        if (selectedSeats.length === 0) {
            toast({ variant: 'destructive', title: 'Manifest Required', description: 'Please select at least one seat to proceed.' });
            return;
        }
        
        const fareFare = selectedSeats.length * (trip?.price || 0);
        const bookingFee = selectedSeats.length * BOOKING_FEE_PER_SEAT;
        const params = new URLSearchParams({
            type: 'Bus',
            fare: String(fareFare),
            seats: selectedSeats.join(','),
            tripId,
        });
        if (trip?.stationId) params.set('stationId', trip.stationId);
        if (trip?.stationName) params.set('stationName', trip.stationName);
        if (trip?.stationLocation) params.set('stationLocation', trip.stationLocation);
        router.push(`/checkout?${params.toString()}`);
    };

    if (isUserLoading || isTripLoading) return <LoadingSpinner />;
    if (!trip) return <div className="p-20 text-center uppercase font-black opacity-20">Trip Node Expired</div>;

    const departureAt = trip.departureAt?.toDate ? trip.departureAt.toDate() : (trip.departureAt?.seconds ? new Date(trip.departureAt.seconds * 1000) : new Date());
    const bookedCount = trip.bookedSeats?.length || 0;
    const pendingCount = trip.pendingSeats?.length || 0;
    const remaining = trip.totalSeats - bookedCount - pendingCount;

    const fareFare = selectedSeats.length * trip.price;
    const bookingFee = selectedSeats.length * BOOKING_FEE_PER_SEAT;
    const totalPayable = fareFare + bookingFee;

    return (
        <div className="flex h-svh flex-col bg-background font-display w-full border-x border-border/50 shadow-2xl animate-in fade-in duration-1000 overflow-hidden">
            <header className="sticky top-0 z-50 bg-background/90 backdrop-blur-xl border-b border-border/50 px-6 pt-12 pb-4 shrink-0">
                <div className="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div className="flex items-center justify-between w-full md:w-auto">
                        <div className="flex items-center gap-4">
                            <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50 active:scale-90 transition-all" onClick={() => router.back()}>
                                <ArrowLeft size={20} />
                            </Button>
                            <div className="flex flex-col">
                                <h1 className="text-[10px] font-black uppercase tracking-[0.3em] text-foreground">Manifest Select</h1>
                                <span className="text-[8px] font-black text-primary uppercase tracking-widest mt-1 opacity-60">ID: #{trip.id.substring(0,8)}</span>
                            </div>
                        </div>
                        <Badge className={cn(
                            "text-[8px] font-black uppercase h-6 px-3 border-none shadow-lg md:hidden",
                            trip.busClass === 'vip' ? "bg-amber-500 text-white shadow-amber-500/20" : "bg-secondary text-foreground"
                        )}>
                            {trip.busClass || 'Classic'} Node
                        </Badge>
                    </div>
                    
                    <div className="flex-1 max-w-3xl flex items-center justify-between bg-card border border-border/50 rounded-[2rem] p-4 shadow-sm group">
                        <div className="flex items-center gap-4">
                            <div className="size-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all duration-500 shadow-sm border border-primary/10">
                                <Bus size={24} />
                            </div>
                            <div className="flex flex-col">
                                <div className="flex items-center gap-2">
                                    <span className="text-xl font-black uppercase tracking-tighter text-foreground">{trip.origin.split(' ')[0]}</span>
                                    <ArrowRight className="size-4 text-primary" />
                                    <span className="text-xl font-black uppercase tracking-tighter text-primary">{trip.destination.split(' ')[0]}</span>
                                </div>
                                <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mt-1 opacity-60">{format(departureAt, 'MMMM dd, yyyy • p')}</p>
                            </div>
                        </div>
                        <Badge className={cn(
                            "hidden md:flex text-[9px] font-black uppercase h-7 px-4 border-none shadow-lg rounded-xl",
                            trip.busClass === 'vip' ? "bg-amber-500 text-white shadow-amber-500/20" : "bg-secondary text-foreground"
                        )}>
                            {trip.busClass || 'Classic'} Service Tier
                        </Badge>
                    </div>
                </div>
            </header>

            <main className="flex-1 max-w-7xl mx-auto w-full p-6 md:p-10 lg:p-12 pb-40 grid grid-cols-1 lg:grid-cols-12 gap-12 items-start overflow-y-auto no-scrollbar">
                <section className="lg:col-span-4 space-y-6 lg:sticky lg:top-0">

                    {/* Boarding Station Card */}
                    {trip.stationName ? (
                        <div className="p-6 rounded-[2rem] bg-gradient-to-br from-primary/10 to-primary/5 border border-primary/20 shadow-inner relative overflow-hidden">
                            <div className="absolute top-0 right-0 size-20 bg-primary/10 rounded-full blur-2xl" />
                            <div className="flex items-start gap-4 relative z-10">
                                <div className="size-12 rounded-2xl bg-primary/15 flex items-center justify-center text-primary shrink-0 border border-primary/20">
                                    <Building2 size={22} />
                                </div>
                                <div className="space-y-1 min-w-0">
                                    <p className="text-[9px] font-black text-primary uppercase tracking-[0.3em]">Boarding Station</p>
                                    <p className="text-base font-black text-foreground uppercase tracking-tighter leading-tight">{trip.stationName}</p>
                                    {trip.stationLocation && (
                                        <div className="flex items-center gap-1.5 mt-1">
                                            <MapPin size={11} className="text-primary shrink-0" />
                                            <p className="text-[11px] text-muted-foreground font-bold truncate">{trip.stationLocation}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="mt-4 flex items-center gap-2 px-3 py-2 rounded-xl bg-primary/10 border border-primary/10">
                                <CheckCircle2 size={13} className="text-green-500 shrink-0" />
                                <p className="text-[10px] font-black text-foreground uppercase tracking-widest">
                                    Present your ticket code at this station
                                </p>
                            </div>
                        </div>
                    ) : (
                        <div className="p-5 rounded-[2rem] bg-secondary/30 border border-border/50 flex items-center gap-4 shadow-inner">
                            <div className="size-11 rounded-2xl bg-background flex items-center justify-center border border-border/50 text-muted-foreground shadow-sm">
                                <Building2 size={20} />
                            </div>
                            <div>
                                <p className="text-[9px] font-black text-muted-foreground uppercase tracking-widest opacity-60">Boarding Station</p>
                                <p className="text-sm font-black text-foreground">{trip.origin}</p>
                                <p className="text-[10px] text-muted-foreground mt-0.5">Station details not specified</p>
                            </div>
                        </div>
                    )}

                    {/* Seat stats */}
                    <div className="grid grid-cols-1 gap-4">
                        <div className="flex items-center gap-5 p-6 rounded-[2rem] bg-secondary/30 border border-border/50 shadow-inner group hover:border-primary/20 transition-all">
                            <div className="size-12 rounded-2xl bg-background flex items-center justify-center text-muted-foreground group-hover:bg-primary group-hover:text-white transition-all shadow-sm">
                                <Users size={24} />
                            </div>
                            <div className="flex flex-col">
                                <p className="text-[10px] font-black text-muted-foreground uppercase tracking-widest opacity-60">Total Capacity</p>
                                <span className="text-2xl font-black leading-none text-foreground">{trip.totalSeats} Seats</span>
                            </div>
                        </div>
                        <div className="flex items-center gap-5 p-6 rounded-[2rem] bg-amber-500/5 border border-amber-500/10 shadow-inner group hover:border-amber-500/30 transition-all">
                            <div className="size-12 rounded-2xl bg-amber-500/10 flex items-center justify-center text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-all shadow-sm">
                                <Zap size={24} />
                            </div>
                            <div className="flex flex-col">
                                <p className="text-[10px] font-black text-amber-600 uppercase tracking-widest opacity-60">Available Seats</p>
                                <span className="text-2xl font-black leading-none text-amber-600">{remaining} Left</span>
                            </div>
                        </div>
                    </div>

                    {/* Booking fee notice */}
                    <div className="p-5 rounded-[2rem] bg-secondary/30 border border-border/50 space-y-2 shadow-inner">
                        <div className="flex items-center gap-2">
                            <Info size={14} className="text-primary shrink-0" />
                            <p className="text-[10px] font-black uppercase tracking-widest text-foreground">Pricing Breakdown</p>
                        </div>
                        <div className="space-y-1.5 pt-1">
                            <div className="flex justify-between text-xs">
                                <span className="text-muted-foreground font-bold">Fare per seat</span>
                                <span className="font-black">₣{trip.price.toLocaleString()}</span>
                            </div>
                            <div className="flex justify-between text-xs">
                                <span className="text-muted-foreground font-bold">Booking fee</span>
                                <span className="font-black text-primary">₣{BOOKING_FEE_PER_SEAT.toLocaleString()}</span>
                            </div>
                            <div className="h-px bg-border/50 my-1" />
                            <div className="flex justify-between text-sm">
                                <span className="font-black uppercase tracking-widest">Total/seat</span>
                                <span className="font-black text-primary">₣{(trip.price + BOOKING_FEE_PER_SEAT).toLocaleString()}</span>
                            </div>
                        </div>
                    </div>

                    <div className="p-6 rounded-[2.5rem] bg-secondary/30 border border-border/50 flex items-start gap-5 shadow-inner">
                        <div className="size-12 rounded-2xl bg-background flex items-center justify-center border border-border/50 text-primary shadow-sm">
                            <ShieldCheck size={24} />
                        </div>
                        <div className="space-y-1">
                            <h4 className="text-sm font-black uppercase tracking-tighter text-foreground">Integrity Shield</h4>
                            <p className="text-[10px] text-muted-foreground leading-relaxed font-bold uppercase tracking-widest opacity-60">
                                Seats are locked for 5 minutes during settlement. End-to-end encryption active.
                            </p>
                        </div>
                    </div>
                </section>

                <section className="lg:col-span-8 w-full animate-in slide-in-from-right-4 duration-1000 pb-20">
                    <div className="flex items-center justify-between mb-8 px-2">
                        <div className="flex items-center gap-3">
                            <div className="size-10 rounded-xl bg-secondary/50 flex items-center justify-center text-primary border border-border/50">
                                <LayoutGrid size={20} />
                            </div>
                            <h3 className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">Deck Topography</h3>
                        </div>
                        <div className="flex items-center gap-2 text-[10px] font-black uppercase text-green-500 bg-green-500/5 px-4 py-2 rounded-xl border border-green-500/10 animate-pulse">
                            <Activity size={12} /> Grid Sync Active
                        </div>
                    </div>
                    
                    <div className="scale-95 md:scale-100 origin-top flex justify-center">
                        <div className="w-full max-w-md">
                            <SeatMap 
                                busClass={trip.busClass}
                                pricePerSeat={trip.price}
                                bookedSeats={trip.bookedSeats}
                                pendingSeats={trip.pendingSeats}
                                selectedSeats={selectedSeats}
                                onSeatClick={handleSeatToggle}
                            />
                        </div>
                    </div>
                </section>
            </main>

            <footer className={cn(
                "fixed bottom-0 left-0 right-0 p-8 bg-background/95 backdrop-blur-2xl border-t border-border/50 z-50 transition-all duration-700 pb-safe",
                selectedSeats.length > 0 ? "translate-y-0" : "translate-y-full"
            )}>
                <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-8">
                    <div className="flex items-end gap-10">
                        <div className="space-y-1">
                            <span className="text-[10px] font-black text-muted-foreground uppercase tracking-[0.4em] opacity-60 block">Total (incl. fees)</span>
                            <h2 className="text-5xl font-black tracking-tighter text-foreground leading-none">
                                <span className="text-xl font-black text-primary mr-1">₣</span>
                                {totalPayable.toLocaleString()}
                            </h2>
                            {selectedSeats.length > 0 && (
                                <p className="text-[10px] text-muted-foreground">
                                    ₣{fareFare.toLocaleString()} fare + ₣{bookingFee.toLocaleString()} fee
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-2">
                            <span className="text-[9px] font-black text-primary uppercase tracking-[0.2em] block">{selectedSeats.length} Seat{selectedSeats.length > 1 ? 's' : ''} Selected</span>
                            <div className="flex flex-wrap gap-1">
                                {selectedSeats.map(s => <Badge key={s} className="bg-secondary text-foreground border-none text-[8px] font-black h-5 px-2 rounded-lg">{s}</Badge>)}
                            </div>
                        </div>
                    </div>
                    
                    <Button 
                        onClick={handleContinue} 
                        className="w-full md:w-auto h-20 px-12 bg-primary hover:bg-red-600 text-white font-black rounded-[2rem] flex items-center justify-between gap-10 shadow-2xl shadow-primary/40 active:scale-[0.98] transition-all group overflow-hidden relative border border-white/10"
                    >
                        <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        <span className="text-base tracking-[0.2em] uppercase relative z-10">Authorize Manifest</span>
                        <div className="p-3 rounded-2xl bg-white/20 group-hover:translate-x-1 transition-transform border border-white/10 relative z-10">
                            <ArrowRight size={24} strokeWidth={3} />
                        </div>
                    </Button>
                </div>
            </footer>
        </div>
    );
}
