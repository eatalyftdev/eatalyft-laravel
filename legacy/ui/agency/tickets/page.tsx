'use client';

import React, { useState, useMemo, useEffect, useCallback } from 'react';
import { useUser, useAuth, useFirestore, useCollection, useMemoFirebase } from '@/firebase';
import { doc, getDoc, collection, query, where, orderBy, getDocs } from 'firebase/firestore';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Ticket, Search, Edit3, Save, X, History, ChevronDown, ChevronUp,
    LayoutDashboard, Activity, QrCode, Settings, MapPin, Trash2,
    AlertTriangle, Loader2, DollarSign, User, Phone, Hash, CheckCircle2,
    XCircle, Clock, Filter, BarChart3, Users, ArrowLeft
} from 'lucide-react';
import { cn } from '@/lib/utils';
import Link from 'next/link';
import { format, isAfter, isBefore, parseISO } from 'date-fns';

import { useToast } from '@/hooks/use-toast';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useRouter } from 'next/navigation';

type BusTicket = {
    id: string; customerName: string; from: string; to: string;
    seatNumber: string; amount?: number; price?: number;
    status: string; bookingStatus?: string; paymentStatus?: string;
    checkedIn?: boolean; createdAt?: any; agentId?: string;
    stationId?: string; tripId?: string; deleted?: boolean; phone?: string;
    agencyManagerId?: string;
};

type AuditLog = {
    id: string; actionType: string; performedBy: string; performedByRole: string;
    changes: { field: string; from: any; to: any }[];
    timestamp: any; customerName?: string; stationId?: string;
};

type StationDoc = { id: string; name: string; location?: string };

type BusTrip = { id: string; origin: string; destination: string; departureAt: any; status: string };

const PAYMENT_STATUSES = ['paid', 'pending', 'unpaid'] as const;
const BOOKING_STATUSES = ['reserved', 'confirmed', 'cancelled'] as const;

const paymentBadge = (s?: string) => {
    if (s === 'paid') return 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30';
    if (s === 'unpaid') return 'bg-red-500/10 text-red-600 border-red-500/30';
    return 'bg-amber-500/10 text-amber-600 border-amber-500/30';
};

const bookingBadge = (s?: string) => {
    if (s === 'confirmed') return 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30';
    if (s === 'cancelled') return 'bg-red-500/10 text-red-600 border-red-500/30';
    return 'bg-blue-500/10 text-blue-600 border-blue-500/30';
};

const actionTypeBadge = (type: string) => {
    if (type === 'CREATE') return 'bg-emerald-500/10 text-emerald-600';
    if (type === 'DELETE') return 'bg-red-500/10 text-red-600';
    if (type === 'STATUS_CHANGE') return 'bg-blue-500/10 text-blue-600';
    return 'bg-amber-500/10 text-amber-600';
};

export default function AgencyTicketsPage() {
    const { user, isUserLoading } = useUser();
    const auth = useAuth();
    const firestore = useFirestore();
    const router = useRouter();
    const { toast } = useToast();

    const [agencyId, setAgencyId] = useState<string | null>(null);
    const [isReady, setIsReady] = useState(false);
    const [stations, setStations] = useState<StationDoc[]>([]);
    const [trips, setTrips] = useState<BusTrip[]>([]);
    const [agentNames, setAgentNames] = useState<Record<string, string>>({});

    // Filters
    const [search, setSearch] = useState('');
    const [filterStation, setFilterStation] = useState('all');
    const [filterTrip, setFilterTrip] = useState('all');
    const [filterAgent, setFilterAgent] = useState('all');
    const [filterPayment, setFilterPayment] = useState('all');
    const [filterBooking, setFilterBooking] = useState('all');
    const [filterFrom, setFilterFrom] = useState('');
    const [filterTo, setFilterTo] = useState('');
    const [showFilters, setShowFilters] = useState(false);

    // Edit
    const [editId, setEditId] = useState<string | null>(null);
    const [editForm, setEditForm] = useState({ customerName: '', phone: '', seatNumber: '', paymentStatus: 'pending', bookingStatus: 'reserved' });
    const [isSaving, setIsSaving] = useState(false);

    // Audit
    const [auditTicketId, setAuditTicketId] = useState<string | null>(null);
    const [auditLogs, setAuditLogs] = useState<AuditLog[]>([]);
    const [isAuditLoading, setIsAuditLoading] = useState(false);
    const [globalAuditOpen, setGlobalAuditOpen] = useState(false);
    const [globalAuditLogs, setGlobalAuditLogs] = useState<AuditLog[]>([]);
    const [isGlobalAuditLoading, setIsGlobalAuditLoading] = useState(false);
    const [auditFilterStation, setAuditFilterStation] = useState('all');
    const [auditFilterAgent, setAuditFilterAgent] = useState('all');
    const [auditFilterFrom, setAuditFilterFrom] = useState('');
    const [auditFilterTo, setAuditFilterTo] = useState('');

    // Delete
    const [deleteId, setDeleteId] = useState<string | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    useEffect(() => {
        if (!user || !firestore) return;
        (async () => {
            const subSnap = await getDoc(doc(firestore, 'sub_agents', user.uid));
            {
                const aid = subSnap.exists() ? (subSnap.data().managedAgencyId || user.uid) : user.uid;
                setAgencyId(aid);

                // Load stations
                const stSnap = await getDocs(query(collection(firestore, 'agencies', aid, 'stations')));
                setStations(stSnap.docs.map(d => ({ ...(d.data() as Omit<StationDoc, 'id'>), id: d.id })));

                // Load trips
                const trSnap = await getDocs(query(collection(firestore, 'trips'), where('agencyId', '==', aid), orderBy('departureAt', 'desc')));
                setTrips(trSnap.docs.map(d => ({ ...(d.data() as Omit<BusTrip, 'id'>), id: d.id })));
            }
            setIsReady(true);
        })();
    }, [user, firestore]);

    const ticketsQuery = useMemoFirebase(() => {
        if (!agencyId || !firestore) return null;
        return query(
            collection(firestore, 'bus'),
            where('agencyId', '==', agencyId),
            orderBy('createdAt', 'desc')
        );
    }, [agencyId, firestore]);

    const { data: allTickets, isLoading } = useCollection<BusTicket>(ticketsQuery);

    // Resolve agent names lazily
    useEffect(() => {
        if (!allTickets || !firestore) return;
        const unknownIds = allTickets
            .map(t => t.agentId)
            .filter((id): id is string => !!id && !agentNames[id]);
        const unique = [...new Set(unknownIds)];
        if (unique.length === 0) return;
        Promise.all(unique.map(id => getDoc(doc(firestore, 'users', id)))).then(snaps => {
            const updates: Record<string, string> = {};
            snaps.forEach((s, i) => {
                updates[unique[i]] = s.exists() ? (s.data()!.name || s.data()!.displayName || unique[i].slice(0, 8)) : unique[i].slice(0, 8);
            });
            setAgentNames(prev => ({ ...prev, ...updates }));
        });
    }, [allTickets, firestore]);

    const tickets = useMemo(() => {
        let t = allTickets?.filter(x => x.id !== '_init' && !x.deleted) ?? [];
        if (search.trim()) {
            const q = search.toLowerCase();
            t = t.filter(x =>
                x.customerName?.toLowerCase().includes(q) ||
                x.id.toLowerCase().includes(q) ||
                x.seatNumber?.toLowerCase().includes(q) ||
                x.from?.toLowerCase().includes(q) ||
                x.to?.toLowerCase().includes(q)
            );
        }
        if (filterStation !== 'all') t = t.filter(x => x.stationId === filterStation);
        if (filterTrip !== 'all') t = t.filter(x => x.tripId === filterTrip);
        if (filterAgent !== 'all') t = t.filter(x => x.agentId === filterAgent);
        if (filterPayment !== 'all') t = t.filter(x => x.paymentStatus === filterPayment);
        if (filterBooking !== 'all') t = t.filter(x => (x.bookingStatus || x.status) === filterBooking);
        if (filterFrom) t = t.filter(x => {
            if (!x.createdAt?.toDate) return true;
            return isAfter(x.createdAt.toDate(), parseISO(filterFrom));
        });
        if (filterTo) t = t.filter(x => {
            if (!x.createdAt?.toDate) return true;
            return isBefore(x.createdAt.toDate(), parseISO(filterTo));
        });
        return t;
    }, [allTickets, search, filterStation, filterTrip, filterAgent, filterPayment, filterBooking, filterFrom, filterTo]);

    const uniqueAgents = useMemo(() => {
        const ids = [...new Set((allTickets ?? []).map(t => t.agentId).filter(Boolean) as string[])];
        return ids;
    }, [allTickets]);

    const stats = useMemo(() => {
        const base = allTickets?.filter(x => x.id !== '_init' && !x.deleted) ?? [];
        const paid = base.filter(x => x.paymentStatus === 'paid');
        const revenue = paid.reduce((s, t) => s + Number(t.amount || t.price || 0), 0);
        return { total: base.length, paid: paid.length, revenue };
    }, [allTickets]);

    const openEdit = (ticket: BusTicket) => {
        setEditId(ticket.id);
        setEditForm({
            customerName: ticket.customerName || '',
            phone: ticket.phone || '',
            seatNumber: ticket.seatNumber || '',
            paymentStatus: ticket.paymentStatus || 'pending',
            bookingStatus: ticket.bookingStatus || ticket.status || 'reserved',
        });
    };

    const handleSave = useCallback(async (ticket: BusTicket) => {
        if (!auth?.currentUser) return;
        setIsSaving(true);
        try {
            const idToken = await auth.currentUser.getIdToken();
            const res = await fetch('/api/station/update-ticket', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${idToken}` },
                body: JSON.stringify({
                    ticketId: ticket.id,
                    tripId: ticket.tripId,
                    customerName: editForm.customerName,
                    phone: editForm.phone,
                    seatNumber: editForm.seatNumber,
                    paymentStatus: editForm.paymentStatus,
                    bookingStatus: editForm.bookingStatus,
                }),
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                toast({ variant: 'destructive', title: 'Update Failed', description: data.message || data.error });
                return;
            }
            toast({ title: 'Ticket Updated', description: `${data.changes?.length || 0} change(s) saved.` });
            setEditId(null);
        } catch (err: any) {
            toast({ variant: 'destructive', title: 'Error', description: err.message });
        } finally {
            setIsSaving(false);
        }
    }, [auth, editForm, toast]);

    const handleDelete = useCallback(async (ticketId: string) => {
        if (!auth?.currentUser) return;
        setIsDeleting(true);
        try {
            const idToken = await auth.currentUser.getIdToken();
            const res = await fetch('/api/station/update-ticket', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${idToken}` },
                body: JSON.stringify({ ticketId, deleted: true }),
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                toast({ variant: 'destructive', title: 'Delete Failed', description: data.message || data.error });
                return;
            }
            toast({ title: 'Ticket Removed' });
            setDeleteId(null);
        } catch (err: any) {
            toast({ variant: 'destructive', title: 'Error', description: err.message });
        } finally {
            setIsDeleting(false);
        }
    }, [auth, toast]);

    const loadAuditLogs = useCallback(async (ticketId: string) => {
        if (!firestore) return;
        setIsAuditLoading(true);
        setAuditTicketId(ticketId);
        try {
            const q = query(
                collection(firestore, 'ticketAuditLogs'),
                where('ticketId', '==', ticketId),
                orderBy('timestamp', 'desc')
            );
            const snap = await getDocs(q);
            setAuditLogs(snap.docs.map(d => ({ ...(d.data() as Omit<AuditLog, 'id'>), id: d.id })));
        } catch {
            setAuditLogs([]);
        } finally {
            setIsAuditLoading(false);
        }
    }, [firestore]);

    const loadGlobalAudit = useCallback(async () => {
        if (!firestore || !agencyId) return;
        setIsGlobalAuditLoading(true);
        try {
            const q = query(
                collection(firestore, 'ticketAuditLogs'),
                where('agencyId', '==', agencyId),
                orderBy('timestamp', 'desc')
            );
            const snap = await getDocs(q);
            setGlobalAuditLogs(snap.docs.map(d => ({ ...(d.data() as Omit<AuditLog, 'id'>), id: d.id })));
        } catch {
            setGlobalAuditLogs([]);
        } finally {
            setIsGlobalAuditLoading(false);
        }
    }, [firestore, agencyId]);

    const filteredGlobalAudit = useMemo(() => {
        let logs = globalAuditLogs;
        if (auditFilterStation !== 'all') logs = logs.filter(l => l.stationId === auditFilterStation);
        if (auditFilterAgent !== 'all') logs = logs.filter(l => l.performedBy === auditFilterAgent);
        if (auditFilterFrom) logs = logs.filter(l => l.timestamp?.toDate ? isAfter(l.timestamp.toDate(), parseISO(auditFilterFrom)) : true);
        if (auditFilterTo) logs = logs.filter(l => l.timestamp?.toDate ? isBefore(l.timestamp.toDate(), parseISO(auditFilterTo)) : true);
        return logs;
    }, [globalAuditLogs, auditFilterStation, auditFilterAgent, auditFilterFrom, auditFilterTo]);

    const stationName = (id?: string) => stations.find(s => s.id === id)?.name || id || '—';
    const tripLabel = (id?: string) => {
        const t = trips.find(t => t.id === id);
        return t ? `${t.origin} → ${t.destination}` : id || '—';
    };

    if (isUserLoading || !isReady) return <LoadingSpinner />;

    return (
        <div className="min-h-screen bg-background font-display text-foreground">
            <main className="flex flex-col pb-32 overflow-x-hidden">
                {/* Header */}
                <header className="sticky top-0 z-40 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center gap-4">
                    <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50 lg:hidden" onClick={() => router.back()}>
                        <ArrowLeft size={20} />
                    </Button>
                    <div>
                        <p className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Global Registry</p>
                        <h1 className="text-xl font-black uppercase tracking-tighter leading-none">Ticket Registry</h1>
                    </div>
                    <div className="ml-auto flex items-center gap-2">
                        <Button variant="outline" size="sm" className="rounded-xl font-black text-[8px] uppercase tracking-widest border-primary/20 text-primary hover:bg-primary/10"
                            onClick={() => { setGlobalAuditOpen(true); loadGlobalAudit(); }}>
                            <History size={12} className="mr-1" /> Audit Log
                        </Button>
                        <button onClick={() => setShowFilters(!showFilters)}
                            className={cn("flex items-center gap-1.5 h-9 px-3 rounded-xl font-black text-[8px] uppercase tracking-widest transition-all",
                                showFilters ? "bg-primary text-white" : "bg-secondary/50 text-muted-foreground hover:text-foreground")}>
                            <Filter size={12} /> Filters
                        </button>
                    </div>
                </header>

                <div className="p-6 space-y-5 max-w-5xl mx-auto w-full">
                    {/* Stats bar */}
                    <div className="grid grid-cols-3 gap-3">
                        {[
                            { label: 'Total Tickets', value: stats.total, icon: Ticket, color: 'text-blue-500', bg: 'bg-blue-500/10' },
                            { label: 'Confirmed Paid', value: stats.paid, icon: CheckCircle2, color: 'text-emerald-500', bg: 'bg-emerald-500/10' },
                            { label: 'Revenue (XAF)', value: stats.revenue.toLocaleString(), icon: BarChart3, color: 'text-primary', bg: 'bg-primary/10' },
                        ].map(s => (
                            <div key={s.label} className="bg-card border border-border/50 rounded-[2rem] p-4 shadow-sm">
                                <div className={cn("size-9 rounded-xl flex items-center justify-center mb-2", s.bg)}>
                                    <s.icon size={18} className={s.color} />
                                </div>
                                <p className="text-lg font-black truncate">{s.value}</p>
                                <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5">{s.label}</p>
                            </div>
                        ))}
                    </div>

                    {/* Search */}
                    <div className="relative">
                        <Search size={16} className="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            className="pl-10 h-12 rounded-2xl bg-secondary/30 border-border/50 font-bold text-sm"
                            placeholder="Search by name, seat, route, ID…"
                            value={search} onChange={e => setSearch(e.target.value)}
                        />
                    </div>

                    {/* Filters panel */}
                    {showFilters && (
                        <div className="bg-card border border-border/50 rounded-[2rem] p-5 space-y-4">
                            <p className="text-[9px] font-black text-muted-foreground uppercase tracking-widest">Filter by</p>
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                <div>
                                    <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">Station</label>
                                    <Select value={filterStation} onValueChange={setFilterStation}>
                                        <SelectTrigger className="h-9 rounded-xl bg-secondary/30 border-border/50 text-[10px] font-bold">
                                            <SelectValue placeholder="All" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Stations</SelectItem>
                                            {stations.map(s => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">Trip</label>
                                    <Select value={filterTrip} onValueChange={setFilterTrip}>
                                        <SelectTrigger className="h-9 rounded-xl bg-secondary/30 border-border/50 text-[10px] font-bold">
                                            <SelectValue placeholder="All" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Trips</SelectItem>
                                            {trips.slice(0, 30).map(t => <SelectItem key={t.id} value={t.id}>{t.origin} → {t.destination}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">Agent</label>
                                    <Select value={filterAgent} onValueChange={setFilterAgent}>
                                        <SelectTrigger className="h-9 rounded-xl bg-secondary/30 border-border/50 text-[10px] font-bold">
                                            <SelectValue placeholder="All" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All Agents</SelectItem>
                                            {uniqueAgents.map(id => <SelectItem key={id} value={id}>{agentNames[id] || id.slice(0, 8)}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">Payment</label>
                                    <Select value={filterPayment} onValueChange={setFilterPayment}>
                                        <SelectTrigger className="h-9 rounded-xl bg-secondary/30 border-border/50 text-[10px] font-bold">
                                            <SelectValue placeholder="All" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All</SelectItem>
                                            {PAYMENT_STATUSES.map(s => <SelectItem key={s} value={s}>{s}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">Booking</label>
                                    <Select value={filterBooking} onValueChange={setFilterBooking}>
                                        <SelectTrigger className="h-9 rounded-xl bg-secondary/30 border-border/50 text-[10px] font-bold">
                                            <SelectValue placeholder="All" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All</SelectItem>
                                            {BOOKING_STATUSES.map(s => <SelectItem key={s} value={s}>{s}</SelectItem>)}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">Date From</label>
                                    <Input type="date" className="h-9 rounded-xl bg-secondary/30 border-border/50 text-[10px] font-bold"
                                        value={filterFrom} onChange={e => setFilterFrom(e.target.value)} />
                                </div>
                                <div>
                                    <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">Date To</label>
                                    <Input type="date" className="h-9 rounded-xl bg-secondary/30 border-border/50 text-[10px] font-bold"
                                        value={filterTo} onChange={e => setFilterTo(e.target.value)} />
                                </div>
                            </div>
                            <Button variant="ghost" size="sm" className="h-7 rounded-xl font-black text-[8px] uppercase tracking-widest text-muted-foreground"
                                onClick={() => { setFilterStation('all'); setFilterTrip('all'); setFilterAgent('all'); setFilterPayment('all'); setFilterBooking('all'); setFilterFrom(''); setFilterTo(''); }}>
                                <X size={10} className="mr-1" /> Clear All
                            </Button>
                        </div>
                    )}

                    {/* Count */}
                    <p className="text-[9px] font-black text-muted-foreground uppercase tracking-widest">
                        Showing {tickets.length} of {stats.total} ticket{stats.total !== 1 ? 's' : ''}
                    </p>

                    {/* Ticket list */}
                    {isLoading ? (
                        <div className="py-24 flex items-center justify-center">
                            <Loader2 size={32} className="animate-spin text-primary" />
                        </div>
                    ) : tickets.length === 0 ? (
                        <div className="py-32 text-center flex flex-col items-center gap-4 opacity-30 border-2 border-dashed border-border/50 rounded-[3rem]">
                            <Ticket size={48} />
                            <p className="font-black uppercase tracking-tighter">No tickets found</p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {tickets.map(ticket => {
                                const isEditing = editId === ticket.id;
                                const bStatus = ticket.bookingStatus || ticket.status;
                                return (
                                    <div key={ticket.id} className={cn(
                                        "bg-card border rounded-[2rem] overflow-hidden transition-all",
                                        bStatus === 'confirmed' ? "border-emerald-500/20" :
                                        bStatus === 'cancelled' ? "border-red-500/20" : "border-border/50"
                                    )}>
                                        <div className="p-5">
                                            {/* Header */}
                                            <div className="flex items-start gap-4 mb-4">
                                                <div className={cn("size-12 rounded-2xl flex items-center justify-center shrink-0",
                                                    bStatus === 'confirmed' ? "bg-emerald-500/10" :
                                                    bStatus === 'cancelled' ? "bg-red-500/10" : "bg-blue-500/10"
                                                )}>
                                                    {bStatus === 'confirmed'
                                                        ? <CheckCircle2 size={20} className="text-emerald-500" />
                                                        : bStatus === 'cancelled'
                                                            ? <XCircle size={20} className="text-red-500" />
                                                            : <Ticket size={20} className="text-blue-500" />
                                                    }
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-black text-sm uppercase tracking-tighter">{ticket.customerName || 'Unknown'}</p>
                                                    <p className="text-[9px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5">
                                                        {ticket.from} → {ticket.to}
                                                    </p>
                                                    <div className="flex items-center gap-3 mt-1 flex-wrap">
                                                        <span className="text-[7px] font-bold text-muted-foreground/50 font-mono">{ticket.id.slice(0, 16)}…</span>
                                                        {ticket.stationId && (
                                                            <span className="text-[7px] font-bold text-muted-foreground flex items-center gap-0.5">
                                                                <MapPin size={8} /> {stationName(ticket.stationId)}
                                                            </span>
                                                        )}
                                                        {ticket.agentId && (
                                                            <span className="text-[7px] font-bold text-muted-foreground flex items-center gap-0.5">
                                                                <User size={8} /> {agentNames[ticket.agentId] || ticket.agentId.slice(0, 8)}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                                <div className="text-right shrink-0 space-y-1">
                                                    <p className="font-black text-sm text-primary">{Number(ticket.amount || ticket.price || 0).toLocaleString()} XAF</p>
                                                    <p className="text-[8px] font-bold text-muted-foreground uppercase">Seat {ticket.seatNumber}</p>
                                                </div>
                                            </div>

                                            {/* Status badges */}
                                            <div className="flex items-center gap-2 flex-wrap mb-4">
                                                <Badge className={cn("font-black text-[7px] uppercase h-5 border", paymentBadge(ticket.paymentStatus))}>
                                                    <DollarSign size={8} className="mr-1" />
                                                    {ticket.paymentStatus || 'pending'}
                                                </Badge>
                                                <Badge className={cn("font-black text-[7px] uppercase h-5 border", bookingBadge(bStatus))}>
                                                    {bStatus || 'reserved'}
                                                </Badge>
                                                <div className="flex items-center gap-1 ml-auto">
                                                    <Clock size={9} className="text-muted-foreground" />
                                                    <p className="text-[8px] font-bold text-muted-foreground">
                                                        {ticket.createdAt?.toDate ? format(ticket.createdAt.toDate(), 'MMM d, HH:mm') : '—'}
                                                    </p>
                                                </div>
                                            </div>

                                            {/* Inline edit form */}
                                            {isEditing && (
                                                <div className="space-y-3 p-4 bg-secondary/30 rounded-2xl border border-border/50 mb-4">
                                                    <p className="text-[9px] font-black text-primary uppercase tracking-widest">Manager Override — Edit Ticket</p>
                                                    <div className="grid grid-cols-2 gap-3">
                                                        <div>
                                                            <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 flex items-center gap-1">
                                                                <User size={8} /> Name
                                                            </label>
                                                            <Input className="h-9 rounded-xl bg-background border-border/50 text-sm font-bold"
                                                                value={editForm.customerName}
                                                                onChange={e => setEditForm(f => ({ ...f, customerName: e.target.value }))} />
                                                        </div>
                                                        <div>
                                                            <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 flex items-center gap-1">
                                                                <Phone size={8} /> Phone
                                                            </label>
                                                            <Input className="h-9 rounded-xl bg-background border-border/50 text-sm font-bold"
                                                                value={editForm.phone}
                                                                onChange={e => setEditForm(f => ({ ...f, phone: e.target.value }))} />
                                                        </div>
                                                        <div>
                                                            <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 flex items-center gap-1">
                                                                <Hash size={8} /> Seat
                                                            </label>
                                                            <Input className="h-9 rounded-xl bg-background border-border/50 text-sm font-bold"
                                                                value={editForm.seatNumber}
                                                                onChange={e => setEditForm(f => ({ ...f, seatNumber: e.target.value }))} />
                                                        </div>
                                                        <div>
                                                            <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 flex items-center gap-1">
                                                                <DollarSign size={8} /> Payment
                                                            </label>
                                                            <Select value={editForm.paymentStatus} onValueChange={v => setEditForm(f => ({ ...f, paymentStatus: v }))}>
                                                                <SelectTrigger className="h-9 rounded-xl bg-background border-border/50 text-sm font-bold">
                                                                    <SelectValue />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {PAYMENT_STATUSES.map(s => <SelectItem key={s} value={s}>{s}</SelectItem>)}
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                        <div className="col-span-2">
                                                            <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 flex items-center gap-1">
                                                                <CheckCircle2 size={8} /> Booking Status
                                                            </label>
                                                            <Select value={editForm.bookingStatus} onValueChange={v => setEditForm(f => ({ ...f, bookingStatus: v }))}>
                                                                <SelectTrigger className="h-9 rounded-xl bg-background border-border/50 text-sm font-bold">
                                                                    <SelectValue />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    {BOOKING_STATUSES.map(s => <SelectItem key={s} value={s}>{s}</SelectItem>)}
                                                                </SelectContent>
                                                            </Select>
                                                        </div>
                                                    </div>
                                                    <div className="flex gap-2 justify-end">
                                                        <Button variant="ghost" size="sm" className="h-8 rounded-xl font-black text-[9px] uppercase tracking-widest"
                                                            onClick={() => setEditId(null)} disabled={isSaving}>
                                                            <X size={12} className="mr-1" /> Cancel
                                                        </Button>
                                                        <Button size="sm" className="h-8 rounded-xl font-black text-[9px] uppercase tracking-widest bg-primary"
                                                            onClick={() => handleSave(ticket)} disabled={isSaving}>
                                                            {isSaving ? <Loader2 size={12} className="mr-1 animate-spin" /> : <Save size={12} className="mr-1" />}
                                                            Save
                                                        </Button>
                                                    </div>
                                                </div>
                                            )}

                                            {/* Actions */}
                                            <div className="flex items-center gap-2 pt-3 border-t border-border/30 flex-wrap">
                                                {!isEditing && (
                                                    <button onClick={() => openEdit(ticket)}
                                                        className="flex items-center gap-1.5 h-8 px-3 rounded-xl bg-primary/10 text-primary font-black text-[8px] uppercase tracking-widest hover:bg-primary/20 transition-all">
                                                        <Edit3 size={11} /> Edit
                                                    </button>
                                                )}
                                                <button
                                                    onClick={() => auditTicketId === ticket.id ? setAuditTicketId(null) : loadAuditLogs(ticket.id)}
                                                    className="flex items-center gap-1.5 h-8 px-3 rounded-xl bg-secondary/50 text-muted-foreground font-black text-[8px] uppercase tracking-widest hover:text-foreground transition-all">
                                                    <History size={11} />
                                                    {auditTicketId === ticket.id ? 'Hide' : 'History'}
                                                    {auditTicketId === ticket.id ? <ChevronUp size={10} /> : <ChevronDown size={10} />}
                                                </button>
                                                <button onClick={() => setDeleteId(ticket.id)}
                                                    className="ml-auto flex items-center gap-1.5 h-8 px-3 rounded-xl bg-red-500/10 text-red-500 font-black text-[8px] uppercase tracking-widest hover:bg-red-500/20 transition-all">
                                                    <Trash2 size={11} /> Delete
                                                </button>
                                            </div>
                                        </div>

                                        {/* Audit trail */}
                                        {auditTicketId === ticket.id && (
                                            <div className="border-t border-border/30 bg-secondary/20 p-5">
                                                <p className="text-[9px] font-black text-muted-foreground uppercase tracking-widest mb-4 flex items-center gap-2">
                                                    <History size={11} /> Audit Timeline
                                                </p>
                                                {isAuditLoading ? (
                                                    <div className="flex items-center gap-2 text-muted-foreground text-[9px] font-bold">
                                                        <Loader2 size={12} className="animate-spin" /> Loading…
                                                    </div>
                                                ) : auditLogs.length === 0 ? (
                                                    <p className="text-[9px] font-bold text-muted-foreground/50">No audit history found.</p>
                                                ) : (
                                                    <div className="space-y-3">
                                                        {auditLogs.map((log, i) => (
                                                            <div key={log.id} className="flex gap-3">
                                                                <div className="flex flex-col items-center">
                                                                    <div className={cn("size-6 rounded-full flex items-center justify-center text-[7px] font-black border", actionTypeBadge(log.actionType))}>
                                                                        {log.actionType[0]}
                                                                    </div>
                                                                    {i < auditLogs.length - 1 && <div className="w-px flex-1 bg-border/30 my-1" />}
                                                                </div>
                                                                <div className="flex-1 pb-3">
                                                                    <div className="flex items-center gap-2 mb-1 flex-wrap">
                                                                        <Badge className={cn("font-black text-[7px] uppercase h-4", actionTypeBadge(log.actionType))}>
                                                                            {log.actionType}
                                                                        </Badge>
                                                                        <span className="text-[7px] font-bold text-muted-foreground uppercase tracking-widest">
                                                                            by {log.performedByRole}
                                                                        </span>
                                                                        <span className="text-[7px] font-bold text-muted-foreground/50 ml-auto">
                                                                            {log.timestamp?.toDate ? format(log.timestamp.toDate(), 'MMM d, HH:mm') : '—'}
                                                                        </span>
                                                                    </div>
                                                                    {log.changes?.length > 0 && (
                                                                        <div className="space-y-1">
                                                                            {log.changes.map((c, j) => (
                                                                                <p key={j} className="text-[8px] font-bold text-muted-foreground">
                                                                                    <span className="text-foreground">{c.field}</span>: {String(c.from)} → {String(c.to)}
                                                                                </p>
                                                                            ))}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </div>
            </main>

            {/* Global Audit Log Dialog */}
            <Dialog open={globalAuditOpen} onOpenChange={setGlobalAuditOpen}>
                <DialogContent className="rounded-[2rem] border-border/50 max-w-2xl max-h-[85vh] flex flex-col">
                    <DialogHeader>
                        <DialogTitle className="font-black uppercase tracking-tighter flex items-center gap-2">
                            <History size={18} className="text-primary" /> Agency Audit Log
                        </DialogTitle>
                        <DialogDescription className="text-[9px] font-bold uppercase tracking-widest text-muted-foreground">
                            Tamper-proof history of all ticket operations
                        </DialogDescription>
                    </DialogHeader>

                    {/* Audit filters */}
                    <div className="grid grid-cols-2 gap-3 mt-2">
                        <div>
                            <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">Station</label>
                            <Select value={auditFilterStation} onValueChange={setAuditFilterStation}>
                                <SelectTrigger className="h-8 rounded-xl bg-secondary/30 border-border/50 text-[9px] font-bold">
                                    <SelectValue placeholder="All" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Stations</SelectItem>
                                    {stations.map(s => <SelectItem key={s.id} value={s.id}>{s.name}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">Agent</label>
                            <Select value={auditFilterAgent} onValueChange={setAuditFilterAgent}>
                                <SelectTrigger className="h-8 rounded-xl bg-secondary/30 border-border/50 text-[9px] font-bold">
                                    <SelectValue placeholder="All" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Agents</SelectItem>
                                    {uniqueAgents.map(id => <SelectItem key={id} value={id}>{agentNames[id] || id.slice(0, 8)}</SelectItem>)}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">From</label>
                            <Input type="date" className="h-8 rounded-xl bg-secondary/30 border-border/50 text-[9px] font-bold"
                                value={auditFilterFrom} onChange={e => setAuditFilterFrom(e.target.value)} />
                        </div>
                        <div>
                            <label className="text-[8px] font-black text-muted-foreground uppercase tracking-widest mb-1 block">To</label>
                            <Input type="date" className="h-8 rounded-xl bg-secondary/30 border-border/50 text-[9px] font-bold"
                                value={auditFilterTo} onChange={e => setAuditFilterTo(e.target.value)} />
                        </div>
                    </div>

                    <div className="overflow-y-auto flex-1 mt-4 space-y-3 pr-1">
                        {isGlobalAuditLoading ? (
                            <div className="flex items-center justify-center py-12">
                                <Loader2 size={24} className="animate-spin text-primary" />
                            </div>
                        ) : filteredGlobalAudit.length === 0 ? (
                            <p className="text-center text-[9px] font-bold text-muted-foreground py-12 uppercase tracking-widest">No audit logs found</p>
                        ) : (
                            filteredGlobalAudit.map((log, i) => (
                                <div key={log.id} className="flex gap-3">
                                    <div className="flex flex-col items-center">
                                        <div className={cn("size-7 rounded-full flex items-center justify-center text-[8px] font-black border flex-shrink-0", actionTypeBadge(log.actionType))}>
                                            {log.actionType[0]}
                                        </div>
                                        {i < filteredGlobalAudit.length - 1 && <div className="w-px flex-1 bg-border/30 my-1" />}
                                    </div>
                                    <div className="flex-1 pb-3 min-w-0">
                                        <div className="flex items-start gap-2 mb-1 flex-wrap">
                                            <Badge className={cn("font-black text-[7px] uppercase h-4 shrink-0", actionTypeBadge(log.actionType))}>
                                                {log.actionType}
                                            </Badge>
                                            <span className="text-[7px] font-bold text-muted-foreground uppercase tracking-widest">
                                                {log.performedByRole} · {log.stationId ? stationName(log.stationId) : ''}
                                            </span>
                                            <span className="text-[7px] font-bold text-muted-foreground/50 ml-auto">
                                                {log.timestamp?.toDate ? format(log.timestamp.toDate(), 'MMM d, HH:mm') : '—'}
                                            </span>
                                        </div>
                                        <p className="text-[9px] font-bold truncate">{log.customerName || 'Ticket'}</p>
                                        {log.changes?.length > 0 && (
                                            <div className="mt-1 space-y-0.5">
                                                {log.changes.map((c, j) => (
                                                    <p key={j} className="text-[7px] font-bold text-muted-foreground">
                                                        <span className="text-foreground">{c.field}</span>: {String(c.from)} → {String(c.to)}
                                                    </p>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </DialogContent>
            </Dialog>

            {/* Delete confirmation */}
            <Dialog open={!!deleteId} onOpenChange={open => !open && setDeleteId(null)}>
                <DialogContent className="rounded-[2rem] border-border/50 max-w-sm">
                    <DialogHeader>
                        <div className="flex items-center gap-3 mb-2">
                            <div className="size-12 rounded-2xl bg-red-500/10 flex items-center justify-center">
                                <AlertTriangle size={20} className="text-red-500" />
                            </div>
                            <div>
                                <DialogTitle className="font-black uppercase tracking-tighter">Delete Ticket?</DialogTitle>
                                <DialogDescription className="text-[9px] font-bold uppercase tracking-widest text-muted-foreground">
                                    Soft-delete — fully traceable in audit logs.
                                </DialogDescription>
                            </div>
                        </div>
                    </DialogHeader>
                    <div className="flex gap-2 justify-end">
                        <Button variant="ghost" className="rounded-xl font-black text-[9px] uppercase" onClick={() => setDeleteId(null)}>Cancel</Button>
                        <Button variant="destructive" className="rounded-xl font-black text-[9px] uppercase"
                            disabled={isDeleting} onClick={() => deleteId && handleDelete(deleteId)}>
                            {isDeleting ? <Loader2 size={12} className="mr-1 animate-spin" /> : <Trash2 size={12} className="mr-1" />}
                            Delete
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            
        </div>
    );
}
