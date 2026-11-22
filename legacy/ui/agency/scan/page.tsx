'use client';

import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { useUser, useFirestore, useAuth } from '@/firebase';
import { doc, getDoc } from 'firebase/firestore';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    ArrowLeft, QrCode, ShieldCheck, User, MapPin, Bus, AlertTriangle,
    CheckCircle2, X, Ticket, LayoutGrid, RefreshCw, Clock, Zap,
    UserCheck, XCircle, Building2, Phone, CreditCard, Hash, Activity
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';
import { format } from 'date-fns';


type ScannedTicket = {
    id: string;
    customerName?: string;
    customerPhone?: string;
    from: string;
    to: string;
    seatNumber: string;
    busNumber?: string;
    busClass?: string;
    status: string;
    checkedIn?: boolean;
    checkedInAt?: any;
    departureAt?: any;
    departureDate?: any;
    tripId?: string;
    agencyId?: string;
    paymentStatus?: string;
    ticketCode?: string;
    price?: number;
    bookingFee?: number;
    totalCharge?: number;
    stationName?: string;
    stationLocation?: string;
    createdAt?: any;
};

export default function TicketScannerPage() {
    const router = useRouter();
    const firestore = useFirestore();
    const { user } = useUser();
    const auth = useAuth();
    const { toast } = useToast();

    const [scannedTicket, setScannedTicket] = useState<ScannedTicket | null>(null);
    const [scanState, setScanState] = useState<'idle' | 'scanning' | 'found' | 'error' | 'duplicate' | 'invalid'>('idle');
    const [isLoading, setIsLoading] = useState(false);
    const [errorMsg, setErrorMsg] = useState<string | null>(null);
    const [manualId, setManualId] = useState('');
    const [isCheckingIn, setIsCheckingIn] = useState(false);
    const [agentStation, setAgentStation] = useState<string | null>(null);
    const [agentRole, setAgentRole] = useState<string | null>(null);
    const [scanCount, setScanCount] = useState(0);

    const videoRef = useRef<HTMLVideoElement>(null);
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const rafRef = useRef<number | null>(null);
    const [hasCameraPermission, setHasCameraPermission] = useState<boolean | null>(null);

    // Load agent profile (station name, role)
    useEffect(() => {
        if (!user || !firestore) return;
        // Sub-agents have stationName; agency managers may also have it
        getDoc(doc(firestore, 'sub_agents', user.uid)).then(async snap => {
            if (snap.exists()) {
                const d = snap.data();
                setAgentStation(d.stationName || null);
                setAgentRole(d.role || 'sub_agent');
            } else {
                const mgrSnap = await getDoc(doc(firestore, 'agency_managers', user.uid));
                if (mgrSnap.exists()) {
                    const d = mgrSnap.data();
                    setAgentStation(d.stationName || null);
                    setAgentRole(d.role || 'agency_manager');
                }
            }
        }).catch(() => {});
    }, [user, firestore]);

    const stopCamera = useCallback(() => {
        if (rafRef.current) cancelAnimationFrame(rafRef.current);
        if (streamRef.current) streamRef.current.getTracks().forEach(t => t.stop());
        streamRef.current = null;
    }, []);

    const verifyTicket = useCallback(async (ticketId: string) => {
        if (!firestore || isLoading) return;
        stopCamera();
        setIsLoading(true);
        setErrorMsg(null);
        try {
            // Support ticket codes (8-char alphanumeric) by querying the `bus` collection
            let resolvedId = ticketId.trim();

            const ticketRef = doc(firestore, 'bus', resolvedId);
            const snap = await getDoc(ticketRef);

            if (!snap.exists()) {
                setScanState('invalid');
                setErrorMsg('Ticket not found in the national registry. This may be counterfeit or invalid.');
                return;
            }

            const data = { ...(snap.data() as Omit<ScannedTicket, 'id'>), id: snap.id };

            if (data.status === 'cancelled' || data.status === 'refunded') {
                setScannedTicket(data);
                setScanState('error');
                setErrorMsg(`Ticket has been ${data.status}. Entry denied.`);
                return;
            }

            if (data.paymentStatus === 'pending') {
                setScannedTicket(data);
                setScanState('error');
                setErrorMsg('Payment not confirmed for this ticket. Entry denied.');
                return;
            }

            if (data.checkedIn) {
                setScannedTicket(data);
                setScanState('duplicate');
                return;
            }

            setScannedTicket(data);
            setScanState('found');
        } catch (err: any) {
            setScanState('error');
            setErrorMsg(err.message);
        } finally {
            setIsLoading(false);
        }
    }, [firestore, isLoading, stopCamera]);

    const scanFrame = useCallback(async () => {
        if (!videoRef.current || !canvasRef.current) return;
        const video = videoRef.current;
        const canvas = canvasRef.current;
        if (video.readyState !== video.HAVE_ENOUGH_DATA) {
            rafRef.current = requestAnimationFrame(scanFrame);
            return;
        }
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

        try {
            const jsQR = (await import('jsqr')).default;
            const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
            if (code?.data) {
                await verifyTicket(code.data.trim());
                return;
            }
        } catch {}

        rafRef.current = requestAnimationFrame(scanFrame);
    }, [verifyTicket]);

    const startCamera = useCallback(async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            streamRef.current = stream;
            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
            }
            setHasCameraPermission(true);
            rafRef.current = requestAnimationFrame(scanFrame);
        } catch {
            setHasCameraPermission(false);
        }
    }, [scanFrame]);

    useEffect(() => {
        setScanState('scanning');
        startCamera();
        return () => stopCamera();
    }, []);

    const handleCheckIn = async () => {
        if (!scannedTicket || !user || !auth?.currentUser) return;
        setIsCheckingIn(true);
        try {
            const idToken = await auth.currentUser.getIdToken();
            const res = await fetch('/api/agency/validate-ticket', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${idToken}` },
                body: JSON.stringify({ ticketId: scannedTicket.id }),
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                if (res.status === 400 && data.error === 'Already used') {
                    setScanState('duplicate');
                    toast({ variant: 'destructive', title: 'Already Used', description: 'This ticket has already been scanned.' });
                } else {
                    toast({ variant: 'destructive', title: 'Check-in Denied', description: data.error || 'Validation failed.' });
                }
                return;
            }
            setScanCount(n => n + 1);
            setScannedTicket(prev => prev ? { ...prev, checkedIn: true } : null);
            toast({ title: 'Passenger Boarded', description: `${scannedTicket.customerName} — Seat ${scannedTicket.seatNumber}` });
        } catch (err: any) {
            toast({ variant: 'destructive', title: 'Check-in Failed', description: err.message });
        } finally {
            setIsCheckingIn(false);
        }
    };

    const handleReset = () => {
        setScannedTicket(null);
        setScanState('scanning');
        setErrorMsg(null);
        setManualId('');
        startCamera();
    };

    return (
        <div className="min-h-screen bg-background text-foreground font-display flex flex-col">
            <style>{`@keyframes scanLine { 0%,100%{top:10%} 50%{top:85%} }`}</style>

            <header className="sticky top-0 z-50 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4">
                <div className="flex items-center justify-between max-w-lg mx-auto w-full">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50" onClick={() => router.back()}>
                            <ArrowLeft size={20} />
                        </Button>
                        <div>
                            <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Validation Terminal</span>
                            <h1 className="text-xl font-black uppercase tracking-tighter leading-none">Ticket Scanner</h1>
                        </div>
                    </div>
                    <div className="flex flex-col items-end gap-1">
                        <div className="flex items-center gap-2">
                            <div className={cn("size-2.5 rounded-full", scanState === 'scanning' ? "bg-emerald-500 animate-pulse" : "bg-amber-500")} />
                            <span className="text-[9px] font-black uppercase tracking-widest text-muted-foreground">
                                {scanState === 'scanning' ? 'Live' : 'Paused'}
                            </span>
                        </div>
                        {scanCount > 0 && (
                            <span className="text-[8px] font-black text-emerald-500 uppercase tracking-widest">{scanCount} scanned today</span>
                        )}
                    </div>
                </div>
            </header>

            {/* Sub-agent station badge */}
            {agentStation && (
                <div className="bg-primary/5 border-b border-primary/10 px-6 py-3">
                    <div className="max-w-lg mx-auto flex items-center gap-3">
                        <Building2 size={14} className="text-primary shrink-0" />
                        <div className="flex items-center gap-2 min-w-0">
                            <span className="text-[9px] font-black uppercase tracking-widest text-muted-foreground">Your Station:</span>
                            <span className="text-[11px] font-black text-primary uppercase tracking-tighter truncate">{agentStation}</span>
                        </div>
                        <Badge className="bg-emerald-500/15 text-emerald-600 border-none text-[8px] font-black uppercase shrink-0">Active</Badge>
                    </div>
                </div>
            )}

            <main className="flex-1 p-6 space-y-6 max-w-lg mx-auto w-full pb-32">
                {(scanState === 'scanning' || scanState === 'idle') && (
                    <div className="space-y-4">
                        <div className="relative aspect-square w-full max-w-sm mx-auto rounded-[2.5rem] overflow-hidden bg-black border border-border/50 shadow-2xl">
                            <video ref={videoRef} className="w-full h-full object-cover" muted playsInline />
                            <canvas ref={canvasRef} className="hidden" />
                            <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                <div className="relative size-52">
                                    <div className="absolute inset-0 border border-white/10 rounded-2xl" />
                                    <div className="absolute top-0 left-0 w-10 h-10 border-t-[3px] border-l-[3px] border-primary rounded-tl-2xl" />
                                    <div className="absolute top-0 right-0 w-10 h-10 border-t-[3px] border-r-[3px] border-primary rounded-tr-2xl" />
                                    <div className="absolute bottom-0 left-0 w-10 h-10 border-b-[3px] border-l-[3px] border-primary rounded-bl-2xl" />
                                    <div className="absolute bottom-0 right-0 w-10 h-10 border-b-[3px] border-r-[3px] border-primary rounded-br-2xl" />
                                    <div className="absolute left-2 right-2 h-0.5 bg-gradient-to-r from-transparent via-primary to-transparent" style={{ animation: 'scanLine 2s ease-in-out infinite', top: '10%' }} />
                                </div>
                            </div>
                            {hasCameraPermission === false && (
                                <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/90 p-6 text-center">
                                    <AlertTriangle size={32} className="text-amber-500" />
                                    <p className="text-white font-black text-sm uppercase tracking-tighter">Camera Access Denied</p>
                                    <p className="text-white/60 text-[10px] font-bold uppercase tracking-widest">Enable camera in browser settings</p>
                                </div>
                            )}
                        </div>

                        <p className="text-center text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">
                            Point camera at passenger QR code
                        </p>

                        <div className="relative flex items-center gap-3">
                            <div className="flex-1 h-px bg-border/40" />
                            <span className="text-[9px] font-black uppercase tracking-widest text-muted-foreground">or enter ticket ID</span>
                            <div className="flex-1 h-px bg-border/40" />
                        </div>

                        <div className="flex gap-3">
                            <Input
                                className="flex-1 h-14 rounded-2xl bg-secondary/30 border-border/50 font-bold text-sm"
                                placeholder="Paste Ticket ID or code…"
                                value={manualId}
                                onChange={e => setManualId(e.target.value)}
                                onKeyDown={e => { if (e.key === 'Enter' && manualId.trim()) verifyTicket(manualId.trim()); }}
                            />
                            <Button
                                className="h-14 px-6 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20"
                                onClick={() => { if (manualId.trim()) verifyTicket(manualId.trim()); }}
                                disabled={!manualId.trim() || isLoading}
                            >
                                {isLoading ? <RefreshCw size={16} className="animate-spin" /> : 'Verify'}
                            </Button>
                        </div>
                    </div>
                )}

                {isLoading && (
                    <div className="py-20 flex flex-col items-center gap-4">
                        <div className="size-16 rounded-full bg-primary/10 flex items-center justify-center animate-pulse">
                            <Zap size={28} className="text-primary" />
                        </div>
                        <p className="text-[10px] font-black uppercase tracking-[0.3em] text-muted-foreground">Verifying with national registry…</p>
                    </div>
                )}

                {/* ─── VALID TICKET ─── */}
                {(scanState === 'found' || scanState === 'duplicate') && scannedTicket && !isLoading && (
                    <div className={cn(
                        "rounded-[2.5rem] border p-6 space-y-5 animate-in fade-in slide-in-from-bottom-4 duration-500",
                        scanState === 'duplicate' ? "border-amber-500/30 bg-amber-500/5" :
                        scannedTicket.checkedIn ? "border-emerald-500/30 bg-emerald-500/5" :
                        "border-blue-500/30 bg-blue-500/5"
                    )}>
                        {/* Passenger header */}
                        <div className="flex items-start gap-4">
                            <div className={cn(
                                "size-16 rounded-[1.5rem] flex items-center justify-center shrink-0 font-black text-2xl",
                                scanState === 'duplicate' ? "bg-amber-500/20 text-amber-500" :
                                scannedTicket.checkedIn ? "bg-emerald-500/20 text-emerald-500" : "bg-blue-500/20 text-blue-500"
                            )}>
                                {(scannedTicket.customerName || 'P').charAt(0).toUpperCase()}
                            </div>
                            <div className="flex-1 min-w-0">
                                <p className="font-black text-xl uppercase tracking-tighter truncate">{scannedTicket.customerName || 'Unknown Passenger'}</p>
                                {scannedTicket.customerPhone && (
                                    <p className="flex items-center gap-1 text-[11px] text-muted-foreground font-bold mt-0.5"><Phone size={10} />{scannedTicket.customerPhone}</p>
                                )}
                                <div className="flex items-center gap-2 mt-2 flex-wrap">
                                    {scanState === 'duplicate' ? (
                                        <Badge className="bg-amber-500/20 text-amber-600 border-amber-500/30 font-black text-[8px] uppercase">Already Scanned</Badge>
                                    ) : scannedTicket.checkedIn ? (
                                        <Badge className="bg-emerald-500/20 text-emerald-600 border-emerald-500/30 font-black text-[8px] uppercase">Boarded</Badge>
                                    ) : (
                                        <Badge className="bg-blue-500/20 text-blue-600 border-blue-500/30 font-black text-[8px] uppercase">Valid · Ready to Board</Badge>
                                    )}
                                    {scannedTicket.paymentStatus === 'paid' && (
                                        <Badge className="bg-green-500/15 text-green-600 border-none font-black text-[8px] uppercase"><CreditCard size={8} className="mr-1" />Paid</Badge>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Route & seat */}
                        <div className="grid grid-cols-2 gap-3">
                            {[
                                { label: 'From', value: scannedTicket.from, icon: MapPin },
                                { label: 'To', value: scannedTicket.to, icon: MapPin },
                                { label: 'Seat', value: scannedTicket.seatNumber, icon: LayoutGrid },
                                { label: 'Class', value: scannedTicket.busClass || 'Standard', icon: Bus },
                            ].map(({ label, value, icon: Icon }) => (
                                <div key={label} className="p-4 rounded-2xl bg-background/60 border border-border/30">
                                    <p className="text-[8px] font-black uppercase tracking-widest text-muted-foreground mb-1 flex items-center gap-1"><Icon size={8} />{label}</p>
                                    <p className="font-black text-sm uppercase tracking-tight">{value}</p>
                                </div>
                            ))}
                        </div>

                        {/* Ticket code */}
                        {scannedTicket.ticketCode && (
                            <div className="flex items-center gap-3 p-4 rounded-2xl bg-background/60 border border-border/30">
                                <Hash size={14} className="text-muted-foreground shrink-0" />
                                <div>
                                    <p className="text-[8px] font-black uppercase tracking-widest text-muted-foreground">Ticket Code</p>
                                    <p className="font-black text-base tracking-[0.3em]">{scannedTicket.ticketCode}</p>
                                </div>
                            </div>
                        )}

                        {/* Departure */}
                        {(scannedTicket.departureAt || scannedTicket.departureDate) && (
                            <div className="flex items-center gap-3 p-4 rounded-2xl bg-background/60 border border-border/30">
                                <Clock size={14} className="text-muted-foreground shrink-0" />
                                <div>
                                    <p className="text-[8px] font-black uppercase tracking-widest text-muted-foreground">Departure</p>
                                    <p className="font-black text-sm">
                                        {(() => {
                                            const dt = scannedTicket.departureAt || scannedTicket.departureDate;
                                            if (dt?.toDate) return format(dt.toDate(), 'PPP · p');
                                            if (dt?.seconds) return format(new Date(dt.seconds * 1000), 'PPP · p');
                                            return String(dt);
                                        })()}
                                    </p>
                                </div>
                            </div>
                        )}

                        {/* Station */}
                        {scannedTicket.stationName && (
                            <div className="flex items-center gap-3 p-4 rounded-2xl bg-primary/5 border border-primary/20">
                                <Building2 size={14} className="text-primary shrink-0" />
                                <div>
                                    <p className="text-[8px] font-black uppercase tracking-widest text-muted-foreground">Boarding Station</p>
                                    <p className="font-black text-sm text-primary uppercase tracking-tighter">{scannedTicket.stationName}</p>
                                    {scannedTicket.stationLocation && <p className="text-[10px] text-muted-foreground">{scannedTicket.stationLocation}</p>}
                                </div>
                            </div>
                        )}

                        {/* Booking reference */}
                        <div className="flex items-center gap-3 p-3 rounded-xl bg-background/40 border border-border/20">
                            <Activity size={12} className="text-muted-foreground shrink-0" />
                            <p className="text-[9px] font-black uppercase tracking-widest text-muted-foreground opacity-60 truncate">Ticket ID: {scannedTicket.id}</p>
                        </div>

                        {/* Check-in button */}
                        {scanState === 'found' && !scannedTicket.checkedIn && (
                            <Button
                                onClick={handleCheckIn}
                                disabled={isCheckingIn}
                                className="w-full h-16 rounded-[1.5rem] bg-emerald-600 hover:bg-emerald-700 text-white font-black uppercase tracking-widest text-sm shadow-2xl shadow-emerald-600/30 transition-all active:scale-[0.98] gap-3"
                            >
                                {isCheckingIn ? <RefreshCw size={20} className="animate-spin" /> : <><UserCheck size={20} /> Confirm Boarding &amp; Check-In</>}
                            </Button>
                        )}

                        {(scanState === 'duplicate' || scannedTicket.checkedIn) && (
                            <div className="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center gap-3">
                                <AlertTriangle size={18} className="text-amber-500 shrink-0" />
                                <div>
                                    <p className="font-black text-sm text-amber-600 uppercase tracking-tighter">Already Boarded</p>
                                    {scannedTicket.checkedInAt?.toDate && (
                                        <p className="text-[10px] text-muted-foreground">Checked in at {format(scannedTicket.checkedInAt.toDate(), 'PPp')}</p>
                                    )}
                                </div>
                            </div>
                        )}

                        <Button onClick={handleReset} variant="outline" className="w-full h-12 rounded-2xl font-black uppercase tracking-widest text-[10px] border-border/50">
                            <RefreshCw size={14} className="mr-2" /> Scan Next Passenger
                        </Button>
                    </div>
                )}

                {/* ─── ERROR / INVALID ─── */}
                {(scanState === 'error' || scanState === 'invalid') && !isLoading && (
                    <div className="rounded-[2.5rem] border border-red-500/30 bg-red-500/5 p-6 space-y-5 animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <div className="flex items-center gap-4">
                            <div className="size-14 rounded-2xl bg-red-500/20 flex items-center justify-center shrink-0">
                                <XCircle size={28} className="text-red-500" />
                            </div>
                            <div>
                                <p className="font-black text-lg uppercase tracking-tighter text-red-600">{scanState === 'invalid' ? 'Invalid Ticket' : 'Entry Denied'}</p>
                                <p className="text-[11px] text-muted-foreground mt-1">{errorMsg}</p>
                            </div>
                        </div>
                        {scannedTicket && (
                            <div className="grid grid-cols-2 gap-3">
                                {[
                                    { label: 'Status', value: scannedTicket.status },
                                    { label: 'Payment', value: scannedTicket.paymentStatus || '—' },
                                ].map(({ label, value }) => (
                                    <div key={label} className="p-4 rounded-2xl bg-background/60 border border-border/30">
                                        <p className="text-[8px] font-black uppercase tracking-widest text-muted-foreground mb-1">{label}</p>
                                        <p className="font-black text-sm uppercase">{value}</p>
                                    </div>
                                ))}
                            </div>
                        )}
                        <Button onClick={handleReset} className="w-full h-14 rounded-2xl bg-primary hover:bg-red-600 text-white font-black uppercase tracking-widest text-sm shadow-xl shadow-primary/20 gap-3">
                            <RefreshCw size={16} /> Try Again
                        </Button>
                    </div>
                )}
            </main>
        </div>
    );
}
