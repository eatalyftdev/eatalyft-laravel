'use client';

import React, { useState, useEffect, useMemo, useRef } from 'react';
import { useRouter } from 'next/navigation';
import { useFirestore } from '@/firebase';
import {
    collection, query, where, orderBy, onSnapshot, Timestamp, getDoc, doc, getDocs,
} from 'firebase/firestore';
import { useFirebaseUser } from '@/hooks/useFirebaseUser';
import { BusTrip } from '@/lib/types/bus';
import { formatXAF, formatTime, cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Bus, Bell, MapPin, ArrowUpDown, ArrowRight,
    Search, Bookmark, CreditCard, History,
    Wifi, Wind, Zap, Clock, Users, AlertCircle, RefreshCw,
    ChevronRight, Navigation, X,
} from 'lucide-react';
import Link from 'next/link';

// ── Types ─────────────────────────────────────────────────────────────────────

type Destination = { id: string; name: string; country: string; isActive: boolean };

// ── Helpers ───────────────────────────────────────────────────────────────────

const AMENITY_ICONS: Record<string, React.ReactNode> = {
    AC: <Wind className="size-3" />,
    WiFi: <Wifi className="size-3" />,
    'USB Charging': <Zap className="size-3" />,
};

const BUS_CLASS_COLORS: Record<string, string> = {
    VIP:       'bg-amber-500 text-white',
    Executive: 'bg-violet-600 text-white',
    Standard:  'bg-primary text-white',
};

function greeting(name?: string | null) {
    const h = new Date().getHours();
    const tod = h < 12 ? 'morning' : h < 17 ? 'afternoon' : 'evening';
    return `Good ${tod}${name ? `, ${name.split(' ')[0]}` : ''}! 👋`;
}

function formatDep(trip: BusTrip) {
    return formatTime(trip.departureTime ?? '');
}

function timeUntil(trip: BusTrip): string {
    const today = new Date().toISOString().split('T')[0];
    if (trip.departureDate !== today) return trip.departureDate === new Date(Date.now() + 86_400_000).toISOString().split('T')[0] ? 'Tomorrow' : trip.departureDate;
    const [hh, mm] = (trip.departureTime ?? '00:00').split(':').map(Number);
    const depMs = new Date().setHours(hh, mm, 0, 0);
    const diff = depMs - Date.now();
    if (diff <= 0) return 'Departing';
    const mins = Math.floor(diff / 60000);
    return mins < 60 ? `${mins} min` : `${Math.floor(mins / 60)}h ${mins % 60}m`;
}

// ── Skeleton ──────────────────────────────────────────────────────────────────

function TripSkeleton() {
    return (
        <div className="flex items-center gap-3 bg-card rounded-2xl border border-border p-4 animate-pulse">
            <div className="size-14 rounded-xl bg-muted shrink-0" />
            <div className="flex-1 space-y-2">
                <div className="h-3.5 bg-muted rounded w-2/3" />
                <div className="h-3 bg-muted rounded w-1/3" />
                <div className="h-3 bg-muted rounded w-1/2" />
            </div>
            <div className="text-right space-y-2">
                <div className="h-5 bg-muted rounded w-14" />
                <div className="h-3 bg-muted rounded w-16" />
            </div>
        </div>
    );
}

// ── Town Picker ───────────────────────────────────────────────────────────────

function TownPicker({
    value, onChange, placeholder, destinations, icon,
}: {
    value: string;
    onChange: (v: string) => void;
    placeholder: string;
    destinations: Destination[];
    icon: React.ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const [q, setQ] = useState('');
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function handleClick(e: MouseEvent) {
            if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
        }
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, []);

    const filtered = useMemo(() => {
        if (!q) return destinations;
        return destinations.filter(d => d.name.toLowerCase().includes(q.toLowerCase()));
    }, [destinations, q]);

    const selected = destinations.find(d => d.name === value);

    return (
        <div ref={ref} className="relative flex-1">
            <button
                type="button"
                onClick={() => { setOpen(o => !o); setQ(''); }}
                className="w-full flex items-center gap-2 rounded-2xl px-3 py-2.5 text-left transition-colors hover:bg-muted/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
            >
                <span className="shrink-0">{icon}</span>
                <span className={cn('flex-1 text-sm truncate', value ? 'text-foreground font-semibold' : 'text-muted-foreground')}>
                    {selected?.name ?? placeholder}
                </span>
                {value && (
                    <button
                        type="button"
                        onClick={e => { e.stopPropagation(); onChange(''); }}
                        className="p-0.5 rounded-full hover:bg-muted"
                    >
                        <X className="size-3.5 text-muted-foreground" />
                    </button>
                )}
            </button>

            {open && (
                <div className="absolute top-full left-0 right-0 z-50 mt-2 overflow-hidden rounded-2xl border border-border/50 bg-popover/95 shadow-2xl shadow-black/15 ring-1 ring-black/5 backdrop-blur-xl">
                    <div className="border-b border-border/50 bg-muted/30 p-2.5">
                        <div className="flex h-11 items-center gap-2 rounded-xl border border-border/50 bg-background px-3 shadow-sm focus-within:border-primary/30 focus-within:ring-2 focus-within:ring-primary/10">
                            <Search className="size-4 shrink-0 text-muted-foreground" />
                            <input
                                autoFocus
                                value={q}
                                onChange={e => setQ(e.target.value)}
                                placeholder="Search town..."
                                className="flex-1 bg-transparent text-sm font-medium outline-none placeholder:text-muted-foreground"
                            />
                        </div>
                    </div>
                    <div className="max-h-52 overflow-y-auto">
                        {filtered.length === 0 ? (
                            <p className="text-xs text-muted-foreground text-center py-4">No towns found</p>
                        ) : (
                            filtered.map(d => (
                                <button
                                    key={d.id}
                                    type="button"
                                    onClick={() => { onChange(d.name); setOpen(false); }}
                                    className={cn(
                                        'w-full flex items-center gap-3 px-4 py-3 text-sm hover:bg-muted transition-colors text-left',
                                        value === d.name && 'bg-primary/5 text-primary font-semibold'
                                    )}
                                >
                                    <MapPin className="size-3.5 text-muted-foreground shrink-0" />
                                    <span className="flex-1">{d.name}</span>
                                    {d.country && <span className="text-xs text-muted-foreground">{d.country}</span>}
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

// ── Trip Card ─────────────────────────────────────────────────────────────────

function TripCard({ trip, onSelect }: { trip: BusTrip; onSelect: (t: BusTrip) => void }) {
    const booked = trip.availableSeats === 0;
    const classColor = BUS_CLASS_COLORS[trip.busType ?? 'Standard'] ?? BUS_CLASS_COLORS.Standard;
    const eta = timeUntil(trip);
    const isToday = trip.departureDate === new Date().toISOString().split('T')[0];
    const etaIsTime = !['Tomorrow', 'Departing'].includes(eta) && !eta.includes('-');

    return (
        <button
            type="button"
            disabled={booked}
            onClick={() => !booked && onSelect(trip)}
            className={cn(
                'w-full flex items-center gap-3 bg-card rounded-2xl border border-border p-4 text-left transition-all duration-200',
                !booked && 'hover:border-primary/40 hover:shadow-md active:scale-[0.99]',
                booked && 'opacity-60 cursor-not-allowed'
            )}
        >
            {/* Route badge */}
            <div className={cn('size-14 rounded-xl flex flex-col items-center justify-center shrink-0 gap-0.5', classColor)}>
                <Bus className="size-5" />
                {trip.busNumber && (
                    <span className="text-[9px] font-black uppercase tracking-wider leading-none opacity-90">
                        {trip.busNumber.substring(0, 6)}
                    </span>
                )}
            </div>

            {/* Info */}
            <div className="flex-1 min-w-0">
                <p className="font-black text-sm leading-tight truncate">
                    {trip.from} <ArrowRight className="size-3 inline text-primary" /> {trip.to}
                </p>
                <p className="text-xs text-muted-foreground font-medium mt-0.5 truncate">
                    {trip.busCompany ?? 'Bus Company'}
                </p>
                <div className="flex items-center gap-2 mt-1.5">
                    <div className="flex items-center gap-1">
                        {(trip.amenities ?? []).slice(0, 2).map(a => (
                            <span key={a} title={a} className="text-muted-foreground">
                                {AMENITY_ICONS[a] ?? <Clock className="size-3" />}
                            </span>
                        ))}
                    </div>
                    <span className={cn(
                        'text-[10px] font-bold px-2 py-0.5 rounded-full border',
                        trip.availableSeats === 0
                            ? 'text-red-600 bg-red-500/10 border-red-500/20'
                            : trip.availableSeats <= 4
                                ? 'text-amber-600 bg-amber-500/10 border-amber-500/20'
                                : 'text-green-600 bg-green-500/10 border-green-500/20'
                    )}>
                        {booked ? 'Sold Out' : trip.availableSeats <= 4 ? `${trip.availableSeats} left` : `${trip.availableSeats} seats`}
                    </span>
                </div>
            </div>

            {/* ETA + price */}
            <div className="text-right shrink-0">
                {isToday && etaIsTime ? (
                    <>
                        <p className="text-primary font-black text-base leading-tight">{eta}</p>
                        <p className="text-[10px] text-muted-foreground font-medium">{formatDep(trip)}</p>
                    </>
                ) : (
                    <>
                        <p className="text-foreground font-black text-sm leading-tight">{formatDep(trip)}</p>
                        <p className="text-[10px] text-muted-foreground font-medium">{eta}</p>
                    </>
                )}
                <p className="text-[10px] font-black text-primary mt-1">{formatXAF(trip.baseFare ?? 0)}</p>
            </div>
        </button>
    );
}

// ── Quick Action ──────────────────────────────────────────────────────────────

function QuickAction({ icon, label, href, onClick }: {
    icon: React.ReactNode;
    label: string;
    href?: string;
    onClick?: () => void;
}) {
    const cls = 'flex flex-col items-center gap-2 group';
    const inner = (
        <>
            <div className="size-14 rounded-2xl bg-primary/8 border border-primary/15 flex items-center justify-center text-primary transition-all duration-200 group-hover:bg-primary group-hover:text-white group-hover:border-primary group-hover:shadow-lg group-hover:shadow-primary/30">
                {icon}
            </div>
            <span className="text-[10px] font-black uppercase tracking-wider text-center text-muted-foreground group-hover:text-foreground leading-tight">
                {label}
            </span>
        </>
    );
    if (href) return <Link href={href} className={cls}>{inner}</Link>;
    return <button type="button" onClick={onClick} className={cls}>{inner}</button>;
}

// ── Main Page ─────────────────────────────────────────────────────────────────

export default function BusPage() {
    const router = useRouter();
    const db = useFirestore();
    const { user } = useFirebaseUser();

    const [trips, setTrips] = useState<BusTrip[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [agencyNames, setAgencyNames] = useState<Record<string, string>>({});
    const [destinations, setDestinations] = useState<Destination[]>([]);
    const [from, setFrom] = useState('');
    const [to, setTo] = useState('');

    // ── Load destinations for town picker ─────────────────────────────────
    useEffect(() => {
        if (!db) return;
        getDocs(query(collection(db, 'destinations'), where('isActive', '==', true), orderBy('order', 'asc')))
            .then(snap => {
                setDestinations(snap.docs.map(d => ({ id: d.id, ...d.data() } as Destination)));
            })
            .catch(() => {
                // Fallback: load all without filter
                getDocs(collection(db, 'destinations'))
                    .then(snap => setDestinations(snap.docs.map(d => ({ id: d.id, ...d.data() } as Destination))));
            });
    }, [db]);

    // ── Live subscription to upcoming trips ────────────────────────────────
    useEffect(() => {
        if (!db) return;
        setLoading(true);
        const start = new Date();
        start.setHours(0, 0, 0, 0);
        const q = query(
            collection(db, 'trips'),
            where('departureAt', '>=', Timestamp.fromDate(start)),
            orderBy('departureAt', 'asc')
        );

        const unsub = onSnapshot(q, (snap) => {
            const mapped: BusTrip[] = snap.docs.map((d) => {
                const data = d.data();
                const depAt = data.departureAt?.toDate
                    ? data.departureAt.toDate()
                    : data.departureAt?.seconds
                        ? new Date(data.departureAt.seconds * 1000)
                        : new Date();
                const booked = data.bookedSeats?.length ?? data.occupancy ?? 0;
                const available = Math.max(0, (data.totalSeats ?? 0) - booked);
                const busClass = data.busClass ?? 'classic';
                const busType = busClass === 'vip' ? 'VIP'
                    : (busClass === '75' || busClass === '65' ? 'Executive' : 'Standard');
                return {
                    id: d.id,
                    from: data.origin ?? '',
                    to: data.destination ?? '',
                    departureDate: depAt.toISOString().split('T')[0],
                    departureTime: depAt.toTimeString().substring(0, 5),
                    arrivalTime: '',
                    totalSeats: data.totalSeats ?? 0,
                    availableSeats: available,
                    seats: [],
                    baseFare: data.price ?? 0,
                    status: data.status ?? 'scheduled',
                    createdBy: data.agencyId ?? '',
                    createdAt: data.createdAt ?? null,
                    updatedAt: data.updatedAt ?? null,
                    amenities: data.amenities ?? [],
                    busType,
                    busNumber: data.busNumber ?? '',
                    busCompany: data.agencyId ?? '',
                } as BusTrip;
            });

            const active = mapped.filter(t => t.status !== 'cancelled' && t.status !== 'completed');
            setTrips(active);
            setLoading(false);
            setError(null);
        }, () => {
            setError('Failed to load trips. Check your connection.');
            setLoading(false);
        });

        return () => unsub();
    }, [db]);

    // ── Resolve agency names ───────────────────────────────────────────────
    useEffect(() => {
        if (!db || trips.length === 0) return;
        const missing = Array.from(new Set(
            trips.map(t => t.createdBy).filter(id => id && !(id in agencyNames))
        ));
        if (missing.length === 0) return;
        let cancelled = false;
        Promise.all(
            missing.map(async (aid) => {
                try {
                    const snap = await getDoc(doc(db, 'agencies', aid));
                    return [aid, snap.exists() ? (snap.data()?.name ?? aid) : aid] as const;
                } catch {
                    return [aid, aid] as const;
                }
            })
        ).then((entries) => {
            if (cancelled) return;
            setAgencyNames(prev => {
                const next = { ...prev };
                for (const [k, v] of entries) next[k] = v;
                return next;
            });
        });
        return () => { cancelled = true; };
    }, [db, trips, agencyNames]);

    const handleSelectTrip = (trip: BusTrip) => router.push(`/trips/${trip.id}`);

    const tripsWithNames = useMemo(
        () => trips.map(t => ({ ...t, busCompany: agencyNames[t.createdBy] ?? t.busCompany })),
        [trips, agencyNames]
    );

    // ── Filter by from/to when both selected ─────────────────────────────
    const filteredTrips = useMemo(() => {
        if (!from && !to) return tripsWithNames;
        return tripsWithNames.filter(t => {
            const matchFrom = !from || t.from?.toLowerCase() === from.toLowerCase();
            const matchTo = !to || t.to?.toLowerCase() === to.toLowerCase();
            return matchFrom && matchTo;
        });
    }, [tripsWithNames, from, to]);

    // ── Group by date ─────────────────────────────────────────────────────
    const grouped = useMemo(() => {
        const today = new Date().toISOString().split('T')[0];
        const tomorrow = new Date(Date.now() + 86_400_000).toISOString().split('T')[0];
        return {
            today: filteredTrips.filter(t => t.departureDate === today),
            tomorrow: filteredTrips.filter(t => t.departureDate === tomorrow),
            later: filteredTrips.filter(t => t.departureDate > tomorrow),
        };
    }, [filteredTrips]);

    const swapLocations = () => { const tmp = from; setFrom(to); setTo(tmp); };

    const searching = from || to;
    const sectionLabel = searching
        ? (from && to ? `${from} → ${to}` : from ? `From ${from}` : `To ${to}`)
        : 'Live Trips';

    return (
        <div className="min-h-screen bg-[#f8f8fc] dark:bg-background font-display pb-32">

            {/* ── Top bar ──────────────────────────────────────────────── */}
            <div className="bg-white dark:bg-card border-b border-border/60 pt-safe sticky top-0 z-30">
                <div className="max-w-lg mx-auto px-5 py-3 flex items-center justify-between">
                    <div className="flex items-center gap-1">
                        <span className="text-2xl font-black tracking-tight text-foreground">Eata</span>
                        <span className="text-2xl font-black tracking-tight text-primary">Lyft</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href="/history/bus" className="relative p-2 rounded-xl hover:bg-muted transition-colors">
                            <Bell className="size-5 text-foreground" />
                            {tripsWithNames.length > 0 && (
                                <span className="absolute top-1.5 right-1.5 size-2 rounded-full bg-primary" />
                            )}
                        </Link>
                    </div>
                </div>
            </div>

            <div className="max-w-lg mx-auto px-5">

                {/* ── Greeting ─────────────────────────────────────────── */}
                <div className="pt-6 pb-4">
                    <h2 className="font-black text-xl text-foreground leading-tight">
                        {greeting(user?.displayName)}
                    </h2>
                    <p className="text-sm text-muted-foreground mt-0.5 font-medium">
                        Where do you want to travel today?
                    </p>
                </div>

                {/* ── Search panel ─────────────────────────────────────── */}
                <div className="bg-white dark:bg-card rounded-3xl border border-border shadow-sm px-5 py-4 mb-5 relative">
                    <div className="flex items-center gap-3 py-1">
                        <div className="size-2.5 rounded-full bg-primary shrink-0" />
                        <TownPicker
                            value={from}
                            onChange={setFrom}
                            placeholder="Current location / Origin"
                            destinations={destinations}
                            icon={<Navigation className="size-4 text-primary" />}
                        />
                        <button
                            type="button"
                            title="Use current location"
                            className="p-2 rounded-xl hover:bg-muted transition-colors text-muted-foreground hover:text-primary"
                        >
                            <Navigation className="size-4" />
                        </button>
                    </div>

                    {/* Dotted divider */}
                    <div className="ml-[17px] my-1 h-6 border-l-2 border-dashed border-border" />

                    <div className="flex items-center gap-3 py-1">
                        <MapPin className="size-4 text-primary shrink-0 ml-0.5" />
                        <TownPicker
                            value={to}
                            onChange={setTo}
                            placeholder="Where to?"
                            destinations={destinations}
                            icon={<MapPin className="size-4 text-primary" />}
                        />
                    </div>

                    {/* Swap button */}
                    <button
                        type="button"
                        onClick={swapLocations}
                        className="absolute right-5 top-1/2 -translate-y-1/2 size-10 rounded-full bg-primary shadow-lg shadow-primary/30 flex items-center justify-center text-white hover:bg-primary/90 transition-all active:scale-95"
                        title="Swap origin and destination"
                    >
                        <ArrowUpDown className="size-4" />
                    </button>
                </div>

                {/* ── Quick actions ─────────────────────────────────────── */}
                <div className="bg-white dark:bg-card rounded-3xl border border-border shadow-sm px-5 py-5 mb-6">
                    <div className="grid grid-cols-4 gap-2">
                        <QuickAction
                            icon={<Search className="size-5" />}
                            label="Plan a Trip"
                            onClick={() => { setFrom(''); setTo(''); }}
                        />
                        <QuickAction
                            icon={<Bookmark className="size-5" />}
                            label="Saved Trips"
                            href="/profile"
                        />
                        <QuickAction
                            icon={<CreditCard className="size-5" />}
                            label="My Passes"
                            href="/profile"
                        />
                        <QuickAction
                            icon={<History className="size-5" />}
                            label="Trip History"
                            href="/history/bus"
                        />
                    </div>
                </div>

                {/* ── Trips section ─────────────────────────────────────── */}
                <div className="flex items-center justify-between mb-3">
                    <div>
                        <h3 className="font-black text-base text-foreground">{sectionLabel}</h3>
                        {!loading && filteredTrips.length > 0 && (
                            <p className="text-xs text-muted-foreground font-medium mt-0.5">
                                {filteredTrips.length} trip{filteredTrips.length !== 1 ? 's' : ''} available
                            </p>
                        )}
                    </div>
                    {searching && (
                        <button
                            type="button"
                            onClick={() => { setFrom(''); setTo(''); }}
                            className="text-xs font-bold text-primary hover:underline flex items-center gap-1"
                        >
                            <X className="size-3" /> Clear
                        </button>
                    )}
                    {!searching && filteredTrips.length > 0 && (
                        <button
                            type="button"
                            className="text-xs font-bold text-primary hover:underline flex items-center gap-1"
                        >
                            View All <ChevronRight className="size-3.5" />
                        </button>
                    )}
                </div>

                {/* Loading */}
                {loading && (
                    <div className="space-y-3">
                        {[1, 2, 3].map(i => <TripSkeleton key={i} />)}
                    </div>
                )}

                {/* Error */}
                {error && !loading && (
                    <div className="flex flex-col items-center gap-4 py-16 text-center">
                        <AlertCircle className="size-10 text-destructive opacity-60" />
                        <p className="text-sm font-bold text-muted-foreground">{error}</p>
                        <Button variant="outline" onClick={() => window.location.reload()} size="sm" className="rounded-xl gap-2">
                            <RefreshCw className="size-4" /> Try Again
                        </Button>
                    </div>
                )}

                {/* Trips */}
                {!loading && !error && (
                    <>
                        {filteredTrips.length === 0 ? (
                            <div className="flex flex-col items-center gap-5 py-16 text-center">
                                <div className="size-20 rounded-[2rem] bg-primary/5 border-2 border-dashed border-primary/20 flex items-center justify-center">
                                    <Bus className="size-10 text-primary opacity-40" />
                                </div>
                                <div>
                                    <p className="font-black text-lg uppercase tracking-tighter">
                                        {searching ? 'No Trips Found' : 'No Upcoming Trips'}
                                    </p>
                                    <p className="text-xs font-bold text-muted-foreground uppercase tracking-widest mt-1">
                                        {searching
                                            ? 'Try a different route or date'
                                            : 'Agencies add trips daily — check back soon'}
                                    </p>
                                </div>
                                {searching && (
                                    <Button variant="outline" onClick={() => { setFrom(''); setTo(''); }} size="sm" className="rounded-xl">
                                        Show all trips
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {grouped.today.length > 0 && (
                                    <>
                                        <p className="text-[10px] font-black uppercase tracking-[0.25em] text-muted-foreground px-1 pt-2">
                                            Today · {new Date().toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' })}
                                        </p>
                                        {grouped.today.map(t => <TripCard key={t.id} trip={t} onSelect={handleSelectTrip} />)}
                                    </>
                                )}
                                {grouped.tomorrow.length > 0 && (
                                    <>
                                        <p className="text-[10px] font-black uppercase tracking-[0.25em] text-muted-foreground px-1 pt-2">
                                            Tomorrow · {new Date(Date.now() + 86_400_000).toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' })}
                                        </p>
                                        {grouped.tomorrow.map(t => <TripCard key={t.id} trip={t} onSelect={handleSelectTrip} />)}
                                    </>
                                )}
                                {grouped.later.length > 0 && (
                                    <>
                                        <p className="text-[10px] font-black uppercase tracking-[0.25em] text-muted-foreground px-1 pt-2">
                                            Upcoming
                                        </p>
                                        {grouped.later.map(t => <TripCard key={t.id} trip={t} onSelect={handleSelectTrip} />)}
                                    </>
                                )}
                            </div>
                        )}
                    </>
                )}

                {/* ── Promo banner ──────────────────────────────────────── */}
                <div className="mt-8 rounded-3xl overflow-hidden bg-gradient-to-br from-primary to-red-800 p-6 relative">
                    <div className="relative z-10">
                        <p className="text-white font-black text-lg leading-tight">
                            Travel more,<br />pay less
                        </p>
                        <p className="text-white/70 text-xs font-medium mt-1 mb-4">
                            Book early and save up to 20% on intercity trips.
                        </p>
                        <Link
                            href="/history/bus"
                            className="inline-flex items-center gap-1.5 bg-white text-primary rounded-xl px-4 py-2 text-xs font-black uppercase tracking-wider hover:bg-white/90 transition-colors"
                        >
                            My Bookings <ArrowRight className="size-3.5" />
                        </Link>
                    </div>
                    {/* Decorative bus illustration */}
                    <div className="absolute right-4 bottom-0 opacity-20 pointer-events-none select-none">
                        <Bus className="size-28 text-white" />
                    </div>
                </div>

            </div>
        </div>
    );
}
