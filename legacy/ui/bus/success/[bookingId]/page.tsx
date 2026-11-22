'use client';

import React, { useState, useEffect, useRef } from 'react';
import { useParams, useSearchParams, useRouter } from 'next/navigation';
import { useFirestore } from '@/firebase';
import { doc, onSnapshot } from 'firebase/firestore';
import { formatXAF, cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  CheckCircle2, Bus, ArrowRight, ChevronLeft, Download,
  Share2, History, Calendar, User, Phone, AlertCircle, Clock,
} from 'lucide-react';
import Link from 'next/link';
import dynamic from 'next/dynamic';
import { Suspense } from 'react';
import { format } from 'date-fns';

const QRCode = dynamic(() => import('qrcode.react').then((m) => ({ default: m.QRCodeSVG })), {
  ssr: false,
  loading: () => <div className="size-24 bg-muted rounded animate-pulse" />,
});

type BusTicket = {
  id: string;
  tripId?: string;
  agencyId?: string;
  agencyName?: string;
  customerName?: string;
  customerPhone?: string;
  from?: string;
  to?: string;
  seatNumber?: string;
  price?: number;
  basePrice?: number;
  bookingFee?: number;
  totalCharge?: number;
  busNumber?: string;
  busClass?: string;
  stationId?: string;
  stationName?: string;
  stationLocation?: string;
  status?: string;
  paymentStatus?: string;
  paymentMethod?: string;
  checkedIn?: boolean;
  ticketCode?: string;
  departureAt?: { toDate?: () => Date; seconds?: number };
  departureDate?: { toDate?: () => Date; seconds?: number };
};

const PAYMENT_STATUS_STYLES: Record<string, string> = {
  paid: 'bg-green-500/10 text-green-600',
  pending: 'bg-amber-500/10 text-amber-600',
  failed: 'bg-red-500/10 text-red-600',
  refunded: 'bg-purple-500/10 text-purple-600',
};

function toDate(val: BusTicket['departureAt']): Date | null {
  if (!val) return null;
  if (typeof val.toDate === 'function') return val.toDate();
  if (typeof val.seconds === 'number') return new Date(val.seconds * 1000);
  return null;
}

function TicketContent({ ticket }: { ticket: BusTicket }) {
  const ticketRef = useRef<HTMLDivElement>(null);

  const dep = toDate(ticket.departureAt) ?? toDate(ticket.departureDate);

  // v2 structured payload — understood by /api/agency/validate-ticket
  const qrPayload = JSON.stringify({
    v: 2,
    type: 'eatalyft-bus-ticket',
    bookingId: ticket.id,
    ticketCode: ticket.ticketCode || '',
    agencyId: ticket.agencyId || '',
    stationId: ticket.stationId || '',
    tripId: ticket.tripId || '',
    seat: ticket.seatNumber || '',
    pax: ticket.customerName || '',
    from: ticket.from || '',
    to: ticket.to || '',
    dep: dep ? dep.toISOString() : '',
  });

  const handleDownload = async () => {
    try {
      const { default: html2canvas } = await import('html2canvas');
      if (!ticketRef.current) return;
      const canvas = await html2canvas(ticketRef.current, { useCORS: true, scale: 2 });
      const link = document.createElement('a');
      link.download = `eatalyft-ticket-${ticket.ticketCode ?? ticket.id}.png`;
      link.href = canvas.toDataURL();
      link.click();
    } catch {
      alert('Download failed. Please take a screenshot instead.');
    }
  };

  const handleShare = async () => {
    const text = `EataLyft Bus Ticket\n${ticket.from} → ${ticket.to}\n${dep ? format(dep, 'EEE, MMM d yyyy HH:mm') : ''}\nTicket: ${ticket.ticketCode}`;
    if (navigator.share) {
      await navigator.share({ title: 'EataLyft Ticket', text }).catch(() => null);
    } else {
      await navigator.clipboard.writeText(text).catch(() => null);
      alert('Ticket details copied to clipboard!');
    }
  };

  const isPaid = (ticket.paymentStatus ?? '').toLowerCase() === 'paid';
  const checkedIn = ticket.checkedIn === true;

  return (
    <div className="space-y-4">
      {/* Confirmed banner */}
      <div className="flex flex-col items-center gap-2 py-4">
        <div className={cn(
          'size-14 rounded-full flex items-center justify-center',
          checkedIn ? 'bg-muted' : isPaid ? 'bg-green-500/10' : 'bg-amber-500/10'
        )}>
          {checkedIn ? <Clock className="size-8 text-muted-foreground" /> :
            isPaid ? <CheckCircle2 className="size-8 text-green-500" /> :
            <Clock className="size-8 text-amber-500" />}
        </div>
        <h1 className="text-xl font-bold">
          {checkedIn ? 'Already Boarded' : isPaid ? 'Booking Confirmed!' : 'Pending Payment'}
        </h1>
        <p className="text-sm text-muted-foreground text-center">
          {checkedIn ? 'This ticket has already been used.' :
           isPaid ? 'Your ticket has been issued. Show the QR code at boarding.' :
           'Complete payment to confirm your seat.'}
        </p>
      </div>

      {/* Ticket card */}
      <div ref={ticketRef} className="rounded-2xl overflow-hidden border border-border bg-card shadow-md">
        {/* Header */}
        <div className="bg-primary p-4 text-white">
          <div className="flex items-center justify-between mb-2">
            <div className="flex items-center gap-2">
              <Bus className="size-5" />
              <span className="font-bold">{ticket.agencyName || 'EataLyft Bus'}</span>
            </div>
            <Badge className="bg-white/20 text-white border-white/30 text-xs">
              {(ticket.status ?? '').toUpperCase()}
            </Badge>
          </div>
          <p className="font-mono text-xl font-bold tracking-widest">{ticket.ticketCode ?? ''}</p>
          <p className="text-white/80 text-xs mt-0.5">{ticket.busNumber ?? ''}</p>
        </div>

        {/* Perforation divider */}
        <div className="flex items-center">
          <div className="size-4 -ml-2 rounded-full bg-background border border-border" />
          <div className="flex-1 border-t-2 border-dashed border-border mx-1" />
          <div className="size-4 -mr-2 rounded-full bg-background border border-border" />
        </div>

        {/* Body */}
        <div className="p-4 grid grid-cols-5 gap-4">
          {/* Left: info */}
          <div className="col-span-3 space-y-3">
            {/* Route */}
            <div className="flex items-center gap-2">
              <div className="text-center">
                <p className="font-bold text-base">{(ticket.from ?? '').substring(0, 3).toUpperCase()}</p>
              </div>
              <div className="flex-1 flex items-center">
                <div className="h-px flex-1 border-t border-dashed border-border" />
                <ArrowRight className="size-3 text-muted-foreground mx-1" />
                <div className="h-px flex-1 border-t border-dashed border-border" />
              </div>
              <div className="text-center">
                <p className="font-bold text-base">{(ticket.to ?? '').substring(0, 3).toUpperCase()}</p>
              </div>
            </div>

            <div className="space-y-1.5 text-xs">
              {dep && (
                <div className="flex items-center gap-1.5 text-muted-foreground">
                  <Calendar className="size-3 flex-shrink-0" />
                  <span>{format(dep, 'EEE, MMM d yyyy · HH:mm')}</span>
                </div>
              )}
              <div className="flex items-center gap-1.5 text-muted-foreground">
                <User className="size-3 flex-shrink-0" />
                <span className="truncate">{ticket.customerName ?? ''}</span>
              </div>
              {ticket.customerPhone && (
                <div className="flex items-center gap-1.5 text-muted-foreground">
                  <Phone className="size-3 flex-shrink-0" />
                  <span>{ticket.customerPhone}</span>
                </div>
              )}
              <div className="flex items-center gap-1.5 text-muted-foreground">
                <Bus className="size-3 flex-shrink-0" />
                <span>{ticket.busNumber || '—'}</span>
              </div>
            </div>

            {/* Seat */}
            <div>
              <p className="text-xs text-muted-foreground mb-1">Seat</p>
              <Badge variant="secondary" className="text-sm font-mono">{ticket.seatNumber ?? '—'}</Badge>
            </div>

            {/* Fare snapshot */}
            <div className="space-y-1 pt-2 border-t border-border text-xs">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Fare</span>
                <span className="font-semibold">{formatXAF(ticket.basePrice ?? ticket.price ?? 0)}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Platform fee</span>
                <span className="font-semibold">{formatXAF(ticket.bookingFee ?? 0)}</span>
              </div>
              <div className="flex justify-between pt-1 border-t border-border/40">
                <div className="text-muted-foreground">
                  <p className="capitalize">{(ticket.paymentMethod ?? '').replace('momopay', 'MoMo Pay')}</p>
                  <span className={cn('px-1.5 py-0.5 rounded text-[10px] font-semibold', PAYMENT_STATUS_STYLES[ticket.paymentStatus ?? 'pending'] ?? '')}>
                    {(ticket.paymentStatus ?? '').toUpperCase()}
                  </span>
                </div>
                <p className="font-bold text-sm text-primary">{formatXAF(ticket.totalCharge ?? ticket.price ?? 0)}</p>
              </div>
            </div>
          </div>

          {/* Right: QR */}
          <div className="col-span-2 flex flex-col items-center justify-center gap-2">
            <Suspense fallback={<div className="size-24 bg-muted rounded animate-pulse" />}>
              <div className={cn('p-2 bg-white rounded-xl border border-border', checkedIn && 'opacity-40')}>
                <QRCode value={qrPayload} size={104} level="M" />
              </div>
            </Suspense>
            <p className="text-[10px] text-muted-foreground text-center">
              {checkedIn ? 'Already used' : 'Scan at boarding'}
            </p>
          </div>
        </div>

        {/* Bottom strip */}
        <div className="bg-muted/50 px-4 py-2">
          <p className="font-mono text-xs text-center text-muted-foreground tracking-wider">
            {ticket.ticketCode ?? ''}
          </p>
        </div>
      </div>

      {/* Actions */}
      <div className="grid grid-cols-2 gap-3">
        <Button variant="outline" onClick={handleDownload} className="rounded-xl">
          <Download className="size-4 mr-2" />
          Download
        </Button>
        <Button variant="outline" onClick={handleShare} className="rounded-xl">
          <Share2 className="size-4 mr-2" />
          Share
        </Button>
      </div>
      <Link href="/history/bus">
        <Button variant="ghost" className="w-full rounded-xl">
          <History className="size-4 mr-2" />
          View All Bookings
        </Button>
      </Link>
      <Link href="/bus">
        <Button className="w-full rounded-xl">
          <Bus className="size-4 mr-2" />
          Book Another Trip
        </Button>
      </Link>
    </div>
  );
}

function BusSuccessInner() {
  const params = useParams();
  const searchParams = useSearchParams();
  const router = useRouter();
  const db = useFirestore();

  const bookingId = (params.bookingId as string) ?? searchParams?.get('bookingId') ?? '';
  const [ticket, setTicket] = useState<BusTicket | null>(null);
  const [loading, setLoading] = useState(true);
  const [notFound, setNotFound] = useState(false);

  useEffect(() => {
    if (!db || !bookingId) { setLoading(false); setNotFound(true); return; }
    const unsub = onSnapshot(
      doc(db, 'bus', bookingId),
      (snap) => {
        if (!snap.exists()) { setNotFound(true); setTicket(null); }
        else { setTicket({ id: snap.id, ...(snap.data() as Omit<BusTicket, 'id'>) }); setNotFound(false); }
        setLoading(false);
      },
      () => { setNotFound(true); setLoading(false); }
    );
    return () => unsub();
  }, [db, bookingId]);

  return (
    <div className="min-h-screen bg-background pb-24">
      <div className="sticky top-0 z-20 bg-background/80 backdrop-blur-md border-b border-border">
        <div className="max-w-2xl mx-auto px-4 py-3 flex items-center gap-3">
          <button onClick={() => router.push('/bus')} className="p-2 rounded-xl hover:bg-muted transition-colors">
            <ChevronLeft className="size-5" />
          </button>
          <h1 className="font-bold text-lg">Your Ticket</h1>
        </div>
      </div>

      <div className="max-w-2xl mx-auto px-4 py-4">
        {loading && (
          <div className="animate-pulse space-y-4">
            <div className="h-32 bg-muted rounded-2xl" />
            <div className="h-48 bg-muted rounded-2xl" />
            <div className="h-10 bg-muted rounded-xl" />
          </div>
        )}

        {notFound && !loading && (
          <div className="flex flex-col items-center gap-3 py-16 text-center">
            <AlertCircle className="size-10 text-destructive" />
            <p className="font-semibold">Ticket not found</p>
            <p className="text-sm text-muted-foreground">
              We couldn&apos;t find this ticket. It may have been removed or the link is invalid.
            </p>
            <Link href="/history/bus">
              <Button variant="outline">View My Bookings</Button>
            </Link>
          </div>
        )}

        {ticket && !loading && <TicketContent ticket={ticket} />}
      </div>
    </div>
  );
}

export default function BusSuccessPage() {
  return (
    <Suspense fallback={<div className="flex min-h-screen items-center justify-center"><div className="animate-pulse space-y-4 w-full max-w-2xl mx-auto px-4"><div className="h-32 bg-muted rounded-2xl" /><div className="h-48 bg-muted rounded-2xl" /></div></div>}>
      <BusSuccessInner />
    </Suspense>
  );
}
