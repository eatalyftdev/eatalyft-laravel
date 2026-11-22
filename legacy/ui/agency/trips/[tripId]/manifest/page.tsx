'use client';

/**
 * Agency Manager — Trip Passenger Manifest
 *
 * Full passenger table for a single trip with:
 *   • Agency name, Sub-agent name, trip details in header
 *   • Sortable table: #, Passenger, Phone, Seat, Ticket Price, Destination, Payment, Agent
 *   • Download CSV button (client-side)
 *   • Email manifest button → POST /api/bus/manifest (sends to manager email from bustrips@eatalyft.cm)
 */

import React, { useState, useMemo, useCallback } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useUser, useFirestore, useDoc, useMemoFirebase, useCollection } from '@/firebase';
import { doc, collection, query, where } from 'firebase/firestore';
import LoadingSpinner from '@/components/loading-spinner';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';
import { format } from 'date-fns';
import Link from 'next/link';
import {
    ArrowLeft, Bus, MapPin, CalendarClock, Users, Ticket, Wallet,
    Download, Mail, Hash, Phone, User as UserIcon,
    TrendingUp, Loader2, CheckCircle2, AlertCircle, Clock, ChevronUp, ChevronDown,
} from 'lucide-react';

type Trip = {
    id: string;
    origin?: string;
    destination?: string;
    departureAt?: any;
    price?: number;
    totalSeats?: number;
    occupancy?: number;
    busNumber?: string;
    busClass?: string;
    agencyId?: string;
    agencyName?: string;
    status?: string;
    bookedSeats?: string[];
};

type BusTicket = {
    id: string;
    customerName?: string;
    customerPhone?: string;
    phone?: string;
    seatNumber?: string;
    amount?: number;
    price?: number;
    totalCharge?: number;
    paymentStatus?: string;
    status?: string;
    from?: string;
    to?: string;
    subAgentId?: string;
    subAgentName?: string;
    agentName?: string;
    bookedByRole?: string;
    createdAt?: any;
};

const ticketAmt = (t: BusTicket) => Number(t.totalCharge ?? t.amount ?? t.price ?? 0);

type SortKey = 'seatNumber' | 'customerName' | 'price' | 'paymentStatus';

export default function TripManifestPage() {
    const { tripId } = useParams<{ tripId: string }>();
    const { user }   = useUser();
    const firestore  = useFirestore();
    const router     = useRouter();
    const { toast }  = useToast();

    const [emailSending, setEmailSending] = useState(false);
    const [sortKey,   setSortKey]   = useState<SortKey>('seatNumber');
    const [sortAsc,   setSortAsc]   = useState(true);

    const tripRef = useMemoFirebase(
        () => (firestore && tripId) ? doc(firestore, 'trips', tripId) : null,
        [firestore, tripId],
    );
    const { data: trip, isLoading: tripLoading } = useDoc<Trip>(tripRef);

    const ticketsQ = useMemoFirebase(
        () => (firestore && tripId)
            ? query(collection(firestore, 'bus'), where('tripId', '==', tripId))
            : null,
        [firestore, tripId],
    );
    const { data: rawTickets, isLoading: ticketsLoading } = useCollection<BusTicket>(ticketsQ);

    const tickets = useMemo(
        () => (rawTickets ?? []).filter(t => t.id !== '_init' && t.status !== 'cancelled'),
        [rawTickets],
    );

    const sorted = useMemo(() => {
        return [...tickets].sort((a, b) => {
            let av: any, bv: any;
            if (sortKey === 'seatNumber') { av = parseInt(a.seatNumber || '999', 10); bv = parseInt(b.seatNumber || '999', 10); }
            else if (sortKey === 'customerName') { av = a.customerName || ''; bv = b.customerName || ''; }
            else if (sortKey === 'price')        { av = ticketAmt(a); bv = ticketAmt(b); }
            else if (sortKey === 'paymentStatus'){ av = a.paymentStatus || ''; bv = b.paymentStatus || ''; }
            else { av = 0; bv = 0; }
            if (av < bv) return sortAsc ? -1 : 1;
            if (av > bv) return sortAsc ?  1 : -1;
            return 0;
        });
    }, [tickets, sortKey, sortAsc]);

    const stats = useMemo(() => {
        const total    = tickets.length;
        const paid     = tickets.filter(t => t.paymentStatus === 'paid').length;
        const pending  = tickets.filter(t => t.paymentStatus === 'pending').length;
        const unpaid   = total - paid - pending;
        const revenue  = tickets.reduce((s, t) => s + ticketAmt(t), 0);
        const settled  = tickets.filter(t => t.paymentStatus === 'paid').reduce((s, t) => s + ticketAmt(t), 0);
        const agents   = [...new Set(tickets.map(t => t.subAgentName || t.agentName).filter(Boolean))];
        return { total, paid, pending, unpaid, revenue, settled, agents };
    }, [tickets]);

    const toggleSort = (key: SortKey) => {
        if (sortKey === key) setSortAsc(a => !a);
        else { setSortKey(key); setSortAsc(true); }
    };

    const SortIcon = ({ k }: { k: SortKey }) =>
        sortKey === k
            ? sortAsc ? <ChevronUp size={12} className="text-primary" /> : <ChevronDown size={12} className="text-primary" />
            : <ChevronDown size={12} className="text-muted-foreground/40" />;

    /* ── CSV Download ── */
    const downloadCSV = useCallback(() => {
        if (!trip) return;
        const depLabel = trip.departureAt?.toDate
            ? format(trip.departureAt.toDate(), 'MMM d yyyy HH:mm')
            : '—';
        const agencyLabel = trip.agencyName || user?.displayName || 'Agency';
        const agentNames  = stats.agents.join(' / ') || 'System';
        const header = [
            `Agency: ${agencyLabel}`,
            `Sub-Agent(s): ${agentNames}`,
            `Trip: ${trip.origin} → ${trip.destination}  |  ${depLabel}  |  Bus: ${trip.busNumber || '—'} ${trip.busClass || ''}`,
            `Trip Reference: ${tripId}`,
            '',
            'No,Passenger Name,Phone,Seat,Ticket Price (XAF),Destination,Payment Status,Agent',
        ];
        const rows = sorted.map((t, i) => [
            String(i + 1),
            `"${(t.customerName || 'Anonymous').replace(/"/g, '""')}"`,
            t.customerPhone || t.phone || '',
            t.seatNumber || '',
            ticketAmt(t).toString(),
            `"${(trip.destination || '').replace(/"/g, '""')}"`,
            t.paymentStatus || 'pending',
            `"${(t.subAgentName || t.agentName || '').replace(/"/g, '""')}"`,
        ].join(','));
        const csv  = [...header, ...rows].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `manifest_${(trip.origin || '').replace(/\s+/g, '_')}_${(trip.destination || '').replace(/\s+/g, '_')}_${tripId.slice(0, 8)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }, [trip, sorted, stats.agents, tripId, user]);

    /* ── Email manifest ── */
    const sendEmail = async () => {
        if (emailSending) return;
        setEmailSending(true);
        try {
            const res  = await fetch('/api/bus/manifest', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tripId }),
            });
            const data = await res.json();
            if (res.ok) {
                toast({
                    title: 'Manifest sent',
                    description: `Emailed to ${data.managerEmail} · ${data.passengerCount} passengers`,
                });
            } else {
                toast({ variant: 'destructive', title: 'Email failed', description: data.message || data.error });
            }
        } catch {
            toast({ variant: 'destructive', title: 'Network error' });
        } finally {
            setEmailSending(false);
        }
    };


    const payBadge = (s?: string) => {
        if (s === 'paid')    return 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30';
        if (s === 'unpaid')  return 'bg-red-500/10 text-red-600 border-red-500/30';
        return 'bg-amber-500/10 text-amber-600 border-amber-500/30';
    };

    if (tripLoading || ticketsLoading) return <LoadingSpinner />;

    const depLabel = trip?.departureAt?.toDate
        ? format(trip.departureAt.toDate(), 'EEE d MMM yyyy · HH:mm')
        : '—';

    return (
        <>
            {/* Print-only: hide everything except the table */}
            <style>{`@media print {
                .no-print { display: none !important; }
                body { background: white; }
                .print-container { padding: 0; }
            }`}</style>

            <div className="min-h-screen bg-background text-foreground print-container">

                {/* ── Header ── */}
                <header className="no-print sticky top-0 z-40 bg-background/80 backdrop-blur-xl border-b border-border/50 px-5 py-4 flex items-center gap-3">
                    <button
                        type="button"
                        onClick={() => router.back()}
                        className="size-10 rounded-xl bg-secondary flex items-center justify-center shrink-0"
                    >
                        <ArrowLeft size={18} />
                    </button>
                    <div className="flex-1 min-w-0">
                        <p className="text-[9px] font-black text-primary uppercase tracking-[0.2em]">Passenger Manifest</p>
                        <h1 className="text-lg font-black tracking-tight truncate leading-tight">
                            {trip?.origin || '…'} → {trip?.destination || '…'}
                        </h1>
                    </div>
                    {/* Action buttons */}
                    <div className="flex items-center gap-2 shrink-0">
                        <button
                            type="button"
                            onClick={downloadCSV}
                            className="h-9 px-3 rounded-xl bg-secondary hover:bg-secondary/80 text-foreground text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-1.5"
                        >
                            <Download size={13} /> CSV
                        </button>
                        <button
                            type="button"
                            onClick={sendEmail}
                            disabled={emailSending}
                            className="h-9 px-3 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary text-[10px] font-black uppercase tracking-widest transition-colors flex items-center gap-1.5 disabled:opacity-50"
                        >
                            {emailSending ? <Loader2 size={12} className="animate-spin" /> : <Mail size={13} />}
                            Email
                        </button>
                    </div>
                </header>

                <main className="max-w-5xl mx-auto px-5 py-6 space-y-5">

                    {/* ── Trip metadata card ── */}
                    <div className="rounded-[2rem] bg-card border border-border/50 p-5 shadow-sm">
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Route</p>
                                <p className="text-base font-black tracking-tight mt-1">{trip?.origin} → {trip?.destination}</p>
                            </div>
                            <div>
                                <p className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Departure</p>
                                <p className="text-sm font-bold mt-1">{depLabel}</p>
                            </div>
                            <div>
                                <p className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Bus</p>
                                <div className="flex items-center gap-2 mt-1">
                                    <p className="text-sm font-bold">{trip?.busNumber || '—'}</p>
                                    {trip?.busClass && (
                                        <Badge variant="outline" className="text-[8px] font-black uppercase tracking-widest h-4 px-1.5 border-border/50">
                                            {trip.busClass}
                                        </Badge>
                                    )}
                                </div>
                            </div>
                            <div>
                                <p className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Agent(s)</p>
                                <p className="text-sm font-bold mt-1 truncate">{stats.agents.join(', ') || 'System / Multiple'}</p>
                            </div>
                        </div>
                    </div>

                    {/* ── Stats ── */}
                    <div className="grid grid-cols-3 md:grid-cols-6 gap-3">
                        {[
                            { label: 'Passengers', value: stats.total,                   color: 'text-blue-500',    bg: 'bg-blue-500/10',    icon: Users },
                            { label: 'Paid',        value: stats.paid,                    color: 'text-emerald-500', bg: 'bg-emerald-500/10', icon: CheckCircle2 },
                            { label: 'Pending',     value: stats.pending,                 color: 'text-amber-500',   bg: 'bg-amber-500/10',   icon: Clock },
                            { label: 'Unpaid',      value: stats.unpaid,                  color: 'text-red-500',     bg: 'bg-red-500/10',     icon: AlertCircle },
                            { label: 'Revenue',     value: `XAF ${stats.revenue.toLocaleString()}`,   color: 'text-primary',     bg: 'bg-primary/10',     icon: TrendingUp },
                            { label: 'Settled',     value: `XAF ${stats.settled.toLocaleString()}`,   color: 'text-emerald-600', bg: 'bg-emerald-500/10', icon: Wallet },
                        ].map(s => (
                            <div key={s.label} className="rounded-2xl bg-card border border-border/50 p-3 shadow-sm text-center">
                                <div className={cn('size-8 rounded-xl mx-auto flex items-center justify-center mb-2', s.bg)}>
                                    <s.icon size={15} className={s.color} />
                                </div>
                                <p className={cn('text-base font-black', s.color)}>{s.value}</p>
                                <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5">{s.label}</p>
                            </div>
                        ))}
                    </div>

                    {/* ── Passenger Table ── */}
                    <div className="rounded-[1.5rem] bg-card border border-border/50 shadow-sm overflow-hidden">
                        {/* Table header */}
                        <div className="px-5 py-4 border-b border-border/50 flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Users size={16} className="text-primary" />
                                <h2 className="text-[10px] font-black uppercase tracking-[0.25em] text-muted-foreground">
                                    Passenger List ({sorted.length})
                                </h2>
                            </div>
                            <p className="text-[9px] text-muted-foreground font-bold uppercase tracking-widest">
                                Trip ref: {tripId.slice(0, 10).toUpperCase()}
                            </p>
                        </div>

                        {/* Desktop table */}
                        <div className="hidden md:block overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-secondary/40 border-b border-border/40">
                                    <tr>
                                        {[
                                            { key: null,            label: '#',            w: 'w-10' },
                                            { key: 'customerName',  label: 'Passenger',    w: '' },
                                            { key: null,            label: 'Phone',        w: '' },
                                            { key: 'seatNumber',    label: 'Seat',         w: 'w-16' },
                                            { key: 'price',         label: 'Ticket Price', w: '' },
                                            { key: null,            label: 'Destination',  w: '' },
                                            { key: 'paymentStatus', label: 'Payment',      w: '' },
                                            { key: null,            label: 'Agent',        w: '' },
                                        ].map((col, i) => (
                                            <th
                                                key={i}
                                                className={cn(
                                                    'px-4 py-3 text-left text-[9px] font-black uppercase tracking-[0.15em] text-muted-foreground',
                                                    col.w,
                                                    col.key && 'cursor-pointer hover:text-foreground transition-colors select-none',
                                                )}
                                                onClick={() => col.key && toggleSort(col.key as SortKey)}
                                            >
                                                <span className="flex items-center gap-1">
                                                    {col.label}
                                                    {col.key && <SortIcon k={col.key as SortKey} />}
                                                </span>
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border/30">
                                    {sorted.length === 0 ? (
                                        <tr>
                                            <td colSpan={8} className="px-4 py-12 text-center text-muted-foreground">
                                                <Ticket size={28} className="mx-auto mb-3 text-muted-foreground/40" />
                                                <p className="text-sm font-black">No passengers yet</p>
                                            </td>
                                        </tr>
                                    ) : sorted.map((t, i) => (
                                        <tr key={t.id} className="hover:bg-secondary/20 transition-colors">
                                            <td className="px-4 py-3 text-[11px] font-black text-muted-foreground">{i + 1}</td>
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2.5">
                                                    <div className="size-8 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center shrink-0">
                                                        <UserIcon size={13} className="text-primary" />
                                                    </div>
                                                    <span className="font-bold text-[13px] text-foreground">{t.customerName || 'Anonymous'}</span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-[12px] text-muted-foreground font-medium">
                                                {t.customerPhone || t.phone || '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="inline-flex items-center gap-1 text-[12px] font-black text-foreground">
                                                    <Hash size={10} className="text-muted-foreground" />
                                                    {t.seatNumber || '—'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 font-black text-[13px]">
                                                XAF {ticketAmt(t).toLocaleString()}
                                            </td>
                                            <td className="px-4 py-3 text-[12px] text-muted-foreground font-medium">
                                                {trip?.destination || t.to || '—'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    variant="outline"
                                                    className={cn('text-[8px] font-black uppercase tracking-widest h-5 px-2', payBadge(t.paymentStatus))}
                                                >
                                                    {t.paymentStatus || 'pending'}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-[11px] text-muted-foreground font-medium">
                                                {t.subAgentName || t.agentName || (t.bookedByRole === 'agency_manager' ? 'Manager' : 'System')}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                {sorted.length > 0 && (
                                    <tfoot className="border-t-2 border-border/50 bg-secondary/20">
                                        <tr>
                                            <td colSpan={4} className="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-muted-foreground">
                                                Totals
                                            </td>
                                            <td className="px-4 py-3 font-black text-sm text-primary">
                                                XAF {stats.revenue.toLocaleString()}
                                            </td>
                                            <td colSpan={3} className="px-4 py-3 text-[11px] text-muted-foreground font-medium">
                                                {stats.paid} paid · {stats.pending} pending · {stats.unpaid} unpaid
                                            </td>
                                        </tr>
                                    </tfoot>
                                )}
                            </table>
                        </div>

                        {/* Mobile card list */}
                        <div className="md:hidden divide-y divide-border/30">
                            {sorted.length === 0 ? (
                                <div className="p-12 text-center text-muted-foreground">
                                    <Ticket size={28} className="mx-auto mb-3 text-muted-foreground/40" />
                                    <p className="text-sm font-black">No passengers yet</p>
                                </div>
                            ) : sorted.map((t, i) => (
                                <div key={t.id} className="p-4 flex items-start gap-3 hover:bg-secondary/20 transition-colors">
                                    <div className="size-9 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center shrink-0">
                                        <UserIcon size={14} className="text-primary" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between gap-2">
                                            <p className="font-black text-[13px] truncate">{t.customerName || 'Anonymous'}</p>
                                            <p className="font-black text-sm text-foreground shrink-0">XAF {ticketAmt(t).toLocaleString()}</p>
                                        </div>
                                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1">
                                            {t.seatNumber && (
                                                <span className="text-[10px] font-bold text-muted-foreground flex items-center gap-1">
                                                    <Hash size={9} /> Seat {t.seatNumber}
                                                </span>
                                            )}
                                            {(t.customerPhone || t.phone) && (
                                                <span className="text-[10px] font-bold text-muted-foreground flex items-center gap-1">
                                                    <Phone size={9} /> {t.customerPhone || t.phone}
                                                </span>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2 mt-2">
                                            <Badge variant="outline" className={cn('text-[8px] font-black uppercase tracking-widest h-4 px-1.5', payBadge(t.paymentStatus))}>
                                                {t.paymentStatus || 'pending'}
                                            </Badge>
                                            <span className="text-[9px] text-muted-foreground font-medium">
                                                {t.subAgentName || t.agentName || 'System'}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Footer note */}
                    <p className="text-center text-[10px] text-muted-foreground font-medium py-2">
                        Generated by EataLyft BusTrips · {new Date().toLocaleDateString('en-GB')}
                    </p>
                </main>
            </div>
        </>
    );
}
