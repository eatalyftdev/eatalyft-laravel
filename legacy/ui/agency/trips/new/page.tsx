'use client';

import React, { useState, useEffect, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import { useUser, useFirestore, useCollection, useMemoFirebase, useDoc } from '@/firebase';
import { collection, query, where, doc, serverTimestamp, Timestamp, getDoc } from 'firebase/firestore';
import { addDocumentNonBlocking, setDocumentNonBlocking } from '@/firebase/non-blocking-updates';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { 
    ArrowLeft as ArrowLeftIcon,
    MapPin,
    Calendar as CalendarIcon,
    Clock,
    Bus,
    CheckCircle2,
    Zap,
    CircleDot,
    Hash,
    Star,
    LayoutGrid,
    ChevronRight,
    ArrowRight,
    Activity,
    Globe,
    ShieldAlert,
    Building2
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/hooks/use-toast';
import LoadingSpinner from '@/components/loading-spinner';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import SeatMap from '@/components/bus/seat-map';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';

const tripSchema = z.object({
  origin: z.string().min(1, "Origin is required"),
  destination: z.string().min(1, "Destination is required"),
  departureDate: z.string().min(1, "Date is required"),
  departureTime: z.string().min(1, "Time is required"),
  price: z.coerce.number().min(100, "Minimum price is ₣100"),
  totalSeats: z.coerce.number().min(1, "Seats required"),
  busNumber: z.string().min(1, "Bus number is required"),
  busClass: z.string(),
});

type TripFormValues = z.infer<typeof tripSchema>;

type VehicleAsset = {
    id: string;
    vehicleDetails: {
        model: string;
        licensePlate: string;
        type: string;
        seater?: number;
    };
    firstName: string;
    status: string;
};

type AgencyProfile = {
    id: string;
    name: string;
    operatingCities: string[];
};

type StationOption = {
    id: string;
    name: string;
    location?: string;
};

export default function ScheduleTripPage() {
    const { user, isUserLoading } = useUser();
    const firestore = useFirestore();
    const router = useRouter();
    const { toast } = useToast();
    const [isSaving, setIsSaving] = useState(false);
    const [selectedVehicleId, setSelectedVehicleId] = useState<string | null>(null);
    const [resolvedAgencyId, setResolvedAgencyId] = useState<string | null>(null);
    const [stations, setStations] = useState<StationOption[]>([]);
    const [selectedStationId, setSelectedStationId] = useState<string>('');

    useEffect(() => {
        if (!user || !firestore) return;
        const resolveId = async () => {
            const subSnap = await getDoc(doc(firestore, 'sub_agents', user.uid));
            if (subSnap.exists()) {
                const aid = subSnap.data().managedAgencyId || user.uid;
                setResolvedAgencyId(aid);
                // Load stations for this agency
                const { getDocs, collection: col } = await import('firebase/firestore');
                const stationsSnap = await getDocs(col(firestore, 'agencies', aid, 'stations'));
                const loadedStations: StationOption[] = [];
                stationsSnap.forEach(s => {
                    if (s.id !== '_init') {
                        const d = s.data();
                        loadedStations.push({ id: s.id, name: d.name || s.id, location: d.location || d.city || '' });
                    }
                });
                setStations(loadedStations);
                if (loadedStations.length > 0) setSelectedStationId(loadedStations[0].id);
            } else {
                setResolvedAgencyId(user.uid);
            }
        };
        resolveId();
    }, [user, firestore]);

    const agencyRef = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return doc(firestore, 'agencies', resolvedAgencyId);
    }, [resolvedAgencyId, firestore]);

    const { data: agency, isLoading: isAgencyLoading } = useDoc<AgencyProfile>(agencyRef);

    const vehiclesQuery = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        // Fetch from fleet root path and strictly filter for VERIFIED AVAILABLE assets
        return query(
            collection(firestore, 'fleet', resolvedAgencyId, 'assets'),
            where('status', '==', 'available')
        );
    }, [resolvedAgencyId, firestore]);

    const { data: rawVehicles, isLoading: isVehiclesLoading } = useCollection<VehicleAsset>(vehiclesQuery);
    
    const vehicles = useMemo(() => {
        return rawVehicles?.filter(v => v.id !== '_init') || [];
    }, [rawVehicles]);

    const form = useForm<TripFormValues>({
        resolver: zodResolver(tripSchema),
        defaultValues: {
            origin: '',
            destination: '',
            departureDate: new Date().toISOString().split('T')[0],
            departureTime: '08:30',
            price: 8500,
            totalSeats: 75,
            busNumber: '',
            busClass: 'classic'
        }
    });

    useEffect(() => {
        if ((agency?.operatingCities?.length ?? 0) > 0 && agency) {
            const cities = agency.operatingCities;
            form.setValue('origin', cities[0]);
            form.setValue('destination', cities[1] || cities[0]);
        }
    }, [agency, form]);

    const handleVehicleSelect = (v: any) => {
        setSelectedVehicleId(v.id);
        form.setValue('busClass', v.vehicleDetails.type || 'classic');
        form.setValue('totalSeats', v.vehicleDetails.seater || (v.vehicleDetails.type === 'vip' ? 65 : 75));
        if (v.vehicleDetails.licensePlate !== 'TBD') {
            form.setValue('busNumber', v.vehicleDetails.licensePlate);
        }
    };

    const onSubmit = async (data: TripFormValues) => {
        if (!resolvedAgencyId || !firestore || !selectedVehicleId) {
            toast({ variant: 'destructive', title: 'Asset Required', description: 'Please select a verified available vehicle node.' });
            return;
        }

        setIsSaving(true);
        const selectedVehicle = vehicles.find(v => v.id === selectedVehicleId);
        const selectedStation = stations.find(s => s.id === selectedStationId);

        try {
            const tripCol = collection(firestore, 'trips');
            const [year, month, day] = data.departureDate.split('-').map(Number);
            const [hours, minutes] = data.departureTime.split(':').map(Number);
            const departureAt = new Date(year, month - 1, day, hours, minutes);

            const tripData = {
                agencyId:        resolvedAgencyId,
                origin:          data.origin,
                destination:     data.destination,
                departureAt:     Timestamp.fromDate(departureAt),
                price:           Number(data.price),
                totalSeats:      Number(data.totalSeats),
                occupancy:       0,
                status:          'scheduled',
                vehicleId:       selectedVehicleId,
                busNumber:       data.busNumber,
                busClass:        data.busClass,
                vehicleDetails: {
                    model: selectedVehicle?.vehicleDetails.model,
                    plate: data.busNumber,
                },
                // Station snapshot — denormalized for performance
                stationId:       selectedStation?.id       || '',
                stationName:     selectedStation?.name     || '',
                stationLocation: selectedStation?.location || '',
                createdAt:       serverTimestamp(),
            };

            const docRef = await addDocumentNonBlocking(tripCol, tripData);
            if (docRef) {
                setDocumentNonBlocking(docRef, { id: docRef.id }, { merge: true });
            }

            toast({ title: "Mission Synchronized", description: `Trip ${data.busNumber} has been deployed to the network.` });
            router.push('/agency/trips');
        } catch (error) {
toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        } finally {
            setIsSaving(false);
        }
    };

    if (isUserLoading || isVehiclesLoading || isAgencyLoading) return <LoadingSpinner />;

    const operatingCities = agency?.operatingCities || [];

    return (
        <div className="min-h-screen bg-background text-foreground font-display text-foreground flex flex-col animate-in fade-in duration-700">
            <header className="sticky top-0 z-50 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4">
                <div className="max-w-4xl mx-auto flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" className="rounded-full size-11 bg-secondary/50" onClick={() => router.back()}>
                            <ArrowLeftIcon size={20} />
                        </Button>
                        <div>
                            <h1 className="text-xl font-black uppercase tracking-tighter">Initialize Mission</h1>
                            <p className="text-[10px] font-bold text-primary uppercase tracking-widest mt-0.5">Fleet Dispatch Protocol</p>
                        </div>
                    </div>
                    <Badge variant="outline" className="h-10 px-4 rounded-xl border-primary/20 text-primary font-black uppercase tracking-widest text-[10px] hidden sm:flex">
                        Secure Node: AM-04
                    </Badge>
                </div>
            </header>

            <main className="max-w-4xl mx-auto w-full p-6 pb-40 grid grid-cols-1 lg:grid-cols-12 gap-10">
                <div className="lg:col-span-7 space-y-10">
                    <Form {...form}>
                        <form onSubmit={form.handleSubmit(onSubmit)} id="trip-form" className="space-y-10">
                            <section className="space-y-4">
                                <h2 className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">Route Intelligence</h2>
                                <div className="bg-card rounded-[2.5rem] p-8 shadow-2xl border border-border/50 space-y-10 relative overflow-hidden group">
                                    <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-primary/20 to-transparent"></div>
                                    
                                    {/* Visual Connector */}
                                    <div className="absolute left-[3.25rem] top-24 bottom-32 w-px border-l-2 border-dashed border-border/50 z-0"></div>
                                    
                                    {/* Origin Node */}
                                    <div className="flex items-center gap-6 relative z-10">
                                        <div className="size-12 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shadow-lg border border-primary/20 shrink-0">
                                            <CircleDot size={22} className="animate-pulse" />
                                        </div>
                                        <div className="flex-1">
                                            <Label className="text-[9px] font-black text-primary uppercase tracking-[0.3em] mb-2 block ml-1">Departure Terminal</Label>
                                            <div className="relative group/select">
                                                {operatingCities.length > 0 ? (
                                                    <select 
                                                        className="w-full bg-secondary/30 border border-transparent focus:border-primary/30 rounded-2xl h-14 px-5 font-black text-sm uppercase tracking-widest outline-none appearance-none cursor-pointer shadow-inner transition-all hover:bg-secondary/50"
                                                        {...form.register('origin')}
                                                    >
                                                        {operatingCities.map(city => (
                                                            <option key={`origin-${city}`} value={city}>{city}</option>
                                                        ))}
                                                    </select>
                                                ) : (
                                                    <div className="h-14 flex items-center px-5 rounded-2xl bg-secondary/30 border border-dashed border-border/50 text-[10px] font-bold uppercase text-muted-foreground">
                                                        No jurisdictions configured
                                                    </div>
                                                )}
                                                <ChevronRight className="absolute right-4 top-1/2 -translate-y-1/2 size-4 text-muted-foreground rotate-90 pointer-events-none" />
                                            </div>
                                        </div>
                                    </div>

                                    {/* Destination Node */}
                                    <div className="flex items-center gap-6 relative z-10">
                                        <div className="size-12 rounded-2xl bg-secondary flex items-center justify-center text-foreground border border-border/50 shrink-0 shadow-sm transition-transform group-hover:scale-105">
                                            <MapPin size={22} />
                                        </div>
                                        <div className="flex-1">
                                            <Label className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.3em] mb-2 block ml-1">Target Corridor</Label>
                                            <div className="relative group/select">
                                                {operatingCities.length > 0 ? (
                                                    <select 
                                                        className="w-full bg-secondary/30 border border-transparent focus:border-primary/30 rounded-2xl h-14 px-5 font-black text-sm uppercase tracking-widest outline-none appearance-none cursor-pointer shadow-inner transition-all hover:bg-secondary/50"
                                                        {...form.register('destination')}
                                                    >
                                                        {operatingCities.map(city => (
                                                            <option key={`dest-${city}`} value={city}>{city}</option>
                                                        ))}
                                                    </select>
                                                ) : (
                                                    <div className="h-14 flex items-center px-5 rounded-2xl bg-secondary/30 border border-dashed border-border/50 text-[10px] font-bold uppercase text-muted-foreground">
                                                        No jurisdictions configured
                                                    </div>
                                                )}
                                                <ChevronRight className="absolute right-4 top-1/2 -translate-y-1/2 size-4 text-muted-foreground rotate-90 pointer-events-none" />
                                            </div>
                                        </div>
                                    </div>

                                    {/* Schedule Sync */}
                                    <div className="grid grid-cols-2 gap-4 pt-8 border-t border-border/10">
                                        <div className="space-y-2">
                                            <Label className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.3em] ml-1">Launch Date</Label>
                                            <div className="flex items-center gap-3 bg-secondary/30 border border-transparent focus-within:border-primary/20 h-14 px-4 rounded-2xl shadow-inner transition-all">
                                                <CalendarIcon className="text-primary size-5" />
                                                <input 
                                                    className="bg-transparent border-none p-0 focus:ring-0 text-xs font-black uppercase w-full outline-none" 
                                                    type="date" 
                                                    {...form.register('departureDate')} 
                                                />
                                            </div>
                                        </div>
                                        <div className="space-y-2">
                                            <Label className="text-[9px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">Dispatch Time</Label>
                                            <div className="flex items-center gap-3 bg-secondary/30 border border-transparent focus-within:border-primary/20 h-14 px-4 rounded-2xl shadow-inner transition-all">
                                                <Clock className="text-primary size-5" />
                                                <input 
                                                    className="bg-transparent border-none p-0 focus:ring-0 text-xs font-black w-full outline-none" 
                                                    type="time" 
                                                    {...form.register('departureTime')} 
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {operatingCities.length === 0 && (
                                        <div className="pt-4 flex items-center gap-3 text-amber-500 animate-in fade-in duration-500">
                                            <Globe size={14} className="animate-pulse" />
                                            <p className="text-[9px] font-black uppercase tracking-widest">Configure operating cities in your profile first.</p>
                                        </div>
                                    )}
                                </div>
                            </section>

                            {/* Station Selection */}
                            <section className="space-y-4">
                                <h2 className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">Departure Station</h2>
                                <div className="bg-card rounded-[2rem] p-6 shadow-sm border border-border/50 space-y-4">
                                    {stations.length > 0 ? (
                                        <div className="grid grid-cols-1 gap-3">
                                            {stations.map(station => (
                                                <button
                                                    type="button"
                                                    key={station.id}
                                                    onClick={() => setSelectedStationId(station.id)}
                                                    className={cn(
                                                        "flex items-center gap-4 p-4 rounded-2xl border-2 transition-all text-left",
                                                        selectedStationId === station.id
                                                            ? "bg-primary/5 border-primary shadow-sm"
                                                            : "bg-secondary/20 border-border/50 hover:border-primary/30"
                                                    )}
                                                >
                                                    <div className={cn("size-10 rounded-xl flex items-center justify-center shrink-0 transition-colors",
                                                        selectedStationId === station.id ? "bg-primary text-white" : "bg-secondary text-muted-foreground"
                                                    )}>
                                                        <Building2 size={18} />
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="font-black text-sm uppercase tracking-tighter truncate">{station.name}</p>
                                                        {station.location && (
                                                            <p className="text-[9px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5 opacity-70 truncate">
                                                                <MapPin size={8} className="inline mr-1" />{station.location}
                                                            </p>
                                                        )}
                                                    </div>
                                                    {selectedStationId === station.id && <CheckCircle2 size={18} className="text-primary shrink-0" />}
                                                </button>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="py-10 flex flex-col items-center gap-3 opacity-40 border-2 border-dashed border-border/50 rounded-2xl">
                                            <Building2 size={28} className="text-muted-foreground" />
                                            <p className="text-[9px] font-black uppercase tracking-widest text-center">No stations configured yet.<br/>Trip will not be station-locked.</p>
                                        </div>
                                    )}
                                </div>
                            </section>

                            <section className="space-y-4">
                                <div className="flex items-center justify-between px-1">
                                    <h2 className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">Fleet Asset Assignment</h2>
                                    <Badge variant="outline" className="text-[8px] font-black text-primary border-primary/20">VERIFIED NODES ONLY</Badge>
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {vehicles.map((v: any) => (
                                        <div key={v.id} onClick={() => handleVehicleSelect(v)} className={cn("p-5 rounded-[2rem] border-2 transition-all cursor-pointer relative overflow-hidden group", selectedVehicleId === v.id ? "bg-primary/5 border-primary shadow-xl shadow-primary/10" : "bg-card border-border/50 hover:border-primary/30")}>
                                            <div className="flex justify-between items-start mb-4">
                                                <div className={cn("size-10 rounded-xl flex items-center justify-center transition-colors", selectedVehicleId === v.id ? "bg-primary text-white" : "bg-secondary text-muted-foreground")}>
                                                    {v.vehicleDetails.type === 'vip' ? <Star size={20} className="fill-current" /> : <Bus size={20} />}
                                                </div>
                                                <Badge variant="outline" className="text-[8px] font-black uppercase tracking-widest border-border/50">
                                                    {v.vehicleDetails.seater} SEATS
                                                </Badge>
                                            </div>
                                            <div>
                                                <h4 className="font-black text-sm uppercase tracking-tighter truncate">{v.vehicleDetails.model}</h4>
                                                <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mt-1 opacity-60">{v.vehicleDetails.licensePlate}</p>
                                            </div>
                                            {selectedVehicleId === v.id && <div className="absolute top-4 right-4 text-primary animate-in zoom-in duration-300"><CheckCircle2 size={20} /></div>}
                                        </div>
                                    ))}
                                    {vehicles.length === 0 && !isVehiclesLoading && (
                                        <div className="col-span-full py-16 text-center opacity-40 grayscale border-2 border-dashed border-border/50 rounded-[2.5rem] bg-secondary/10 flex flex-col items-center gap-4">
                                            <ShieldAlert size={32} className="text-primary" />
                                            <div className="space-y-1">
                                                <p className="text-[10px] font-black uppercase tracking-widest">No Verified Assets</p>
                                                <p className="text-[8px] font-bold uppercase tracking-tighter px-10 leading-relaxed">Please ensure your fleet nodes are verified by administration in the Fleet Audit terminal.</p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </section>

                            <section className="space-y-4">
                                <h2 className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground ml-1">Asset Visibility</h2>
                                <div className="bg-card rounded-[2rem] p-8 shadow-sm border border-border/50 space-y-6">
                                    <div className="space-y-2">
                                        <Label className="text-[9px] font-black uppercase tracking-widest text-muted-foreground ml-1 mb-2 block">Bus Number (Public ID)</Label>
                                        <div className="relative group">
                                            <Hash className="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                            <Input required className="h-14 pl-12 rounded-2xl bg-secondary/50 border-none font-black text-base uppercase tracking-widest shadow-inner focus-visible:ring-1 focus-visible:ring-primary/20" placeholder="e.g. BUS-01 or LT-123" {...form.register('busNumber')} />
                                        </div>
                                        <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest ml-1">This ID reflects on the passenger's digital boarding pass.</p>
                                    </div>
                                    
                                    <div className="grid grid-cols-2 gap-4 pt-2">
                                        <div className="space-y-2">
                                            <Label className="text-[9px] font-black uppercase tracking-widest text-muted-foreground ml-1">Ticket Price (₣)</Label>
                                            <Input type="number" className="h-14 rounded-2xl bg-secondary/50 border-none font-black text-xl shadow-inner focus-visible:ring-1 focus-visible:ring-primary/20" {...form.register('price')} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label className="text-[9px] font-black uppercase tracking-widest text-muted-foreground ml-1">Total Capacity</Label>
                                            <Input type="number" readOnly className="h-14 rounded-2xl bg-secondary/10 border-none font-black text-xl shadow-inner opacity-60" {...form.register('totalSeats')} />
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </form>
                    </Form>
                </div>

                <div className="lg:col-span-5 space-y-8">
                    <div className="sticky top-28 space-y-8">
                        <section className="space-y-4">
                            <div className="flex items-center justify-between px-1">
                                <h2 className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">Manifest Intelligence</h2>
                                <div className="flex items-center gap-2">
                                    <span className="size-2 rounded-full bg-green-500 animate-pulse"></span>
                                    <span className="text-[9px] font-black uppercase tracking-widest text-green-500">Live Syncing</span>
                                </div>
                            </div>
                            
                            <div className="bg-card rounded-[2.5rem] p-1 border border-border/50 shadow-2xl overflow-hidden min-h-[500px]">
                                <div className="p-6 bg-secondary/30 border-b border-border/10 flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="size-10 rounded-xl bg-background flex items-center justify-center border border-border/50 text-primary">
                                            <LayoutGrid size={20} />
                                        </div>
                                        <div>
                                            <p className="text-[9px] font-black text-muted-foreground uppercase tracking-widest opacity-60">Layout Preview</p>
                                            <h3 className="text-sm font-black uppercase tracking-tighter">
                                                {selectedVehicleId ? `${form.watch('busClass').toUpperCase()} DECK` : 'AWAITING ASSET'}
                                            </h3>
                                        </div>
                                    </div>
                                    <Badge className="bg-primary/10 text-primary border-primary/20 text-[9px] font-black uppercase">
                                        {form.watch('totalSeats')} Seats
                                    </Badge>
                                </div>
                                
                                <div className="p-6 py-10 scale-[0.85] origin-top">
                                    <SeatMap 
                                        busClass={form.watch('busClass') as any} 
                                        pricePerSeat={form.watch('price')} 
                                        onSeatClick={() => {}} 
                                        isAdmin={true} 
                                    />
                                </div>
                            </div>
                        </section>

                        <div className="p-6 rounded-[2rem] bg-primary text-white shadow-xl shadow-primary/20 relative overflow-hidden group">
                            <div className="relative z-10">
                                <div className="size-10 rounded-xl bg-white/20 flex items-center justify-center mb-4">
                                    <Activity className="size-6" />
                                </div>
                                <h4 className="font-black text-sm uppercase tracking-widest">Protocol Guard</h4>
                                <p className="text-[10px] font-medium text-white/80 mt-2 leading-relaxed uppercase tracking-widest">
                                    Missions are immutable once published. All manifest changes are synchronized with the user cluster instantly.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <footer className="fixed bottom-0 left-0 right-0 p-6 bg-background/90 backdrop-blur-xl border-t border-border/50 z-50 shadow-2xl">
                <div className="max-w-4xl mx-auto flex items-center justify-between gap-6">
                    <div className="hidden sm:flex items-center gap-4">
                        <div className="size-12 rounded-2xl bg-secondary flex items-center justify-center text-muted-foreground border border-border/50">
                            <Activity className="size-6" />
                        </div>
                        <div className="flex flex-col">
                            <span className="text-[10px] font-black text-muted-foreground uppercase tracking-widest">System Node</span>
                            <span className="text-xs font-black uppercase tracking-tighter">Eatalyft-Travey-01</span>
                        </div>
                    </div>
                    
                    <Button 
                        form="trip-form"
                        type="submit"
                        disabled={isSaving || !selectedVehicleId || operatingCities.length === 0}
                        className="w-full sm:w-auto h-16 px-12 bg-primary hover:bg-red-600 text-white font-black rounded-2xl shadow-2xl shadow-primary/40 flex items-center justify-center gap-4 transition-all active:scale-[0.98] group relative overflow-hidden"
                    >
                        <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        <Zap size={20} className="relative z-10 group-hover:scale-110 group-hover:rotate-12 transition-transform" />
                        <span className="text-sm uppercase tracking-[0.2em] relative z-10">{isSaving ? 'SYNCHRONIZING...' : 'INITIALIZE MISSION'}</span>
                        <ArrowRight size={20} className="relative z-10 group-hover:translate-x-1 transition-transform" />
                    </Button>
                </div>
            </footer>
        </div>
    );
}
