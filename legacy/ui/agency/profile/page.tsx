'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useUser, useDoc, useMemoFirebase, useFirestore, useAuth } from '@/firebase';
import { doc, serverTimestamp, collection, query, where, updateDoc, getDoc, setDoc, getDocs, orderBy, limit } from 'firebase/firestore';

import { performLogout } from '@/lib/logout';
import { format as dateFnsFormat } from 'date-fns';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import {
    ArrowLeft,
    ChevronRight,
    Bus,
    MapPin,
    BarChart3,
    Edit,
    LogOut,
    Wallet,
    FileText,
    Camera,
    Loader2,
    Globe,
    Mail,
    Phone,
    Truck,
    Ticket,
    Building2,
    ChevronDown,
    ChevronUp
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import Link from 'next/link';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Checkbox } from '@/components/ui/checkbox';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useS3Upload } from '@/hooks/use-s3-upload';
import { VerifiedBadge } from '@/components/ui/verified-badge';
import { usePWAInstall } from '@/hooks/use-pwa-install';
import PWAInstallCard from '@/components/PWAInstallCard';

const CAMEROON_CITIES = [
    'Douala', 'Yaoundé', 'Bamenda', 'Bafoussam', 'Garoua', 'Maroua', 
    'Ngaoundéré', 'Bertoua', 'Ebolowa', 'Buea', 'Kribi', 'Limbe', 
    'Dschang', 'Foumban', 'Kousseri', 'Edéa', 'Kumbo', 'Nkongsamba', 'Tiko', 'Mbalmayo'
];

type AgencyProfile = {
    id: string;
    name: string;
    operatingCities: string[];
    isVerified: boolean;
    headOffice: string;
    country: string;
    email: string;
    phoneNumber: string;
    logoUrl?: string;
    officeHubs?: any[];
};

type FleetHubSummary = {
    bus75: number;
    bus65: number;
    bus50: number;
    bus30: number;
    bus18: number;
};

const editProfileSchema = z.object({
    name: z.string().min(2, 'Agency name is required'),
    phoneNumber: z.string().min(9, 'Valid phone number is required'),
    headOffice: z.string().min(5, 'Head office location is required'),
    operatingCities: z.array(z.string()).min(1, 'At least one city required'),
    bus75: z.coerce.number().min(0),
    bus65: z.coerce.number().min(0),
    bus50: z.coerce.number().min(0),
    bus30: z.coerce.number().min(0),
    bus18: z.coerce.number().min(0),
});

type EditProfileValues = z.infer<typeof editProfileSchema>;

const MenuNode = ({ icon: Icon, label, sub, href = "#" }: { icon: any, label: string, sub?: string, href?: string }) => (
    <Link href={href} className="group relative flex items-center gap-4 overflow-hidden rounded-3xl border border-border/50 bg-card p-4 sm:p-5 transition-all duration-300 hover:border-primary/30 hover:shadow-md active:scale-[0.99]">
        <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-transform duration-300 group-hover:scale-105">
            <Icon size={22} aria-hidden="true" />
        </span>
        <span className="min-w-0 flex-1">
            <span className="block truncate text-sm font-black uppercase tracking-tighter text-foreground">{label}</span>
            {sub && <span className="mt-0.5 block truncate text-[10px] font-bold uppercase tracking-widest text-muted-foreground opacity-70">{sub}</span>}
        </span>
        <ChevronRight className="size-5 shrink-0 text-muted-foreground transition-all group-hover:translate-x-0.5 group-hover:text-primary" aria-hidden="true" />
    </Link>
);

export default function AgencyProfilePage() {
    const { user, isUserLoading } = useUser();
    const firestore = useFirestore();
    const auth = useAuth();
    const router = useRouter();
    const { toast } = useToast();
    const { uploadFile, isUploading } = useS3Upload();
    const { isInstallable, isStandalone, promptInstall } = usePWAInstall();

    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [resolvedAgencyId, setResolvedAgencyId] = useState<string | null>(null);

    // Station office hubs state
    const [stations, setStations] = useState<{ id: string; name: string; location?: string; phoneNumber?: string; managerName?: string }[]>([]);
    const [stationTicketCounts, setStationTicketCounts] = useState<Record<string, number>>({});
    const [stationRevenue, setStationRevenue] = useState<Record<string, number>>({});

    // Audit log state
    const [recentAuditLogs, setRecentAuditLogs] = useState<any[]>([]);
    const [isAuditExpanded, setIsAuditExpanded] = useState(false);

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

    // Load stations + ticket volumes + recent audit logs when agencyId is resolved
    useEffect(() => {
        if (!resolvedAgencyId || !firestore) return;
        (async () => {
            try {
                // Load stations
                const stSnap = await getDocs(query(collection(firestore, 'agencies', resolvedAgencyId, 'stations')));
                const stationList = stSnap.docs.map(d => ({ id: d.id, ...(d.data() as any) }));
                setStations(stationList);

                // Load tickets and count by station
                const tSnap = await getDocs(query(collection(firestore, 'bus'), where('agencyId', '==', resolvedAgencyId)));
                const counts: Record<string, number> = {};
                const revenues: Record<string, number> = {};
                tSnap.docs.forEach(d => {
                    const data = d.data();
                    if (data.deleted) return;
                    const sid = data.stationId || 'unknown';
                    counts[sid] = (counts[sid] || 0) + 1;
                    if (data.paymentStatus === 'paid') {
                        revenues[sid] = (revenues[sid] || 0) + Number(data.amount || data.price || 0);
                    }
                });
                setStationTicketCounts(counts);
                setStationRevenue(revenues);

                // Load recent audit logs
                const aSnap = await getDocs(query(
                    collection(firestore, 'ticketAuditLogs'),
                    where('agencyId', '==', resolvedAgencyId),
                    orderBy('timestamp', 'desc'),
                    limit(20)
                ));
                setRecentAuditLogs(aSnap.docs.map(d => ({ id: d.id, ...d.data() })));
            } catch {
                // silent
            }
        })();
    }, [resolvedAgencyId, firestore]);

    const agencyRef = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return doc(firestore, 'agencies', resolvedAgencyId);
    }, [resolvedAgencyId, firestore]);

    const fleetRef = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return doc(firestore, 'fleet', resolvedAgencyId);
    }, [resolvedAgencyId, firestore]);

    const { data: agency, isLoading: isAgencyLoading } = useDoc<AgencyProfile>(agencyRef);
    const { data: fleetHub, isLoading: isFleetLoading } = useDoc<FleetHubSummary>(fleetRef);

    const form = useForm<EditProfileValues>({
        resolver: zodResolver(editProfileSchema),
        values: {
            name: agency?.name || '',
            phoneNumber: agency?.phoneNumber || '',
            headOffice: agency?.headOffice || '',
            operatingCities: agency?.operatingCities || [],
            bus75: fleetHub?.bus75 || 0,
            bus65: fleetHub?.bus65 || 0,
            bus50: fleetHub?.bus50 || 0,
            bus30: fleetHub?.bus30 || 0,
            bus18: fleetHub?.bus18 || 0,
        }
    });

    const handleLogoUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file || !agencyRef) return;
        // Preferred path: validated + re-encoded server route (PNG/JPEG, <=1 MB).
        try {
            const token = auth?.currentUser ? await auth.currentUser.getIdToken() : '';
            if (token && resolvedAgencyId) {
                const fd = new FormData();
                fd.append('agencyId', resolvedAgencyId);
                fd.append('file', file);
                const res = await fetch('/api/agency/logo', {
                    method: 'POST',
                    headers: { Authorization: `Bearer ${token}` },
                    body: fd,
                });
                if (res.ok) {
                    const data = await res.json();
                    await updateDoc(agencyRef, { logoUrl: data.url, updatedAt: serverTimestamp() });
                    toast({ title: "Identity Synchronized", description: "Agency brand logo has been updated." });
                    return;
                }
            }
        } catch { /* fall through to legacy uploader */ }
        const result = await uploadFile(file, 'agencies', { docType: 'logo', role: 'agency' });
        if (result) {
            await updateDoc(agencyRef, { logoUrl: result.publicUrl, updatedAt: serverTimestamp() });
            toast({ title: "Identity Synchronized", description: "Agency brand logo has been updated." });
        }
    };

    const handleSaveProfile = async (data: EditProfileValues) => {
        if (!agencyRef || !fleetRef) return;
        setIsSaving(true);
        try {
            await updateDoc(agencyRef, {
                name: data.name,
                phoneNumber: data.phoneNumber,
                headOffice: data.headOffice,
                operatingCities: data.operatingCities,
                updatedAt: serverTimestamp(),
            });

            await setDoc(fleetRef, {
                bus75: data.bus75,
                bus65: data.bus65,
                bus50: data.bus50,
                bus30: data.bus30,
                bus18: data.bus18,
                updatedAt: serverTimestamp()
            }, { merge: true });

            toast({ title: "Intelligence Synchronized", description: "Agency and Fleet nodes have been updated." });
            setIsEditDialogOpen(false);
        } catch (error) {
toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        } finally {
            setIsSaving(false);
        }
    };

    const handleLogout = () => { if (auth) performLogout(auth, '/agency/login'); };


    if (isUserLoading || isAgencyLoading || isFleetLoading) return <LoadingSpinner />;
    if (!agency) return null;

    const totalFleet = (Number(fleetHub?.bus75) || 0) + 
                     (Number(fleetHub?.bus65) || 0) + 
                     (Number(fleetHub?.bus50) || 0) + 
                     (Number(fleetHub?.bus30) || 0) + 
                     (Number(fleetHub?.bus18) || 0);

    return (
        <div className="min-h-screen bg-background text-foreground selection:bg-primary selection:text-white font-display">
            <header className="sticky top-0 z-50 bg-background/90 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center justify-between shadow-sm">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50" onClick={() => router.back()}>
                        <ArrowLeft size={20} />
                    </Button>
                    <div className="flex flex-col">
                        <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Partner Node</span>
                        <h1 className="text-lg font-black uppercase tracking-tighter leading-none">Administrative Profile</h1>
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-primary text-white shadow-xl shadow-primary/30 active:scale-90 transition-all" onClick={() => setIsEditDialogOpen(true)}>
                        <Edit size={20} />
                    </Button>
                </div>
            </header>

            <main className="max-w-7xl mx-auto p-6 md:p-10 lg:p-12 pb-40">
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                    
                    <div className="lg:col-span-4 space-y-6 animate-in fade-in slide-in-from-left-4 duration-1000">
                        <section className="overflow-hidden rounded-[2rem] border border-border/50 bg-card shadow-xl" aria-label="Agency identity">
                            <div className="relative bg-gradient-to-br from-[#1a2333] via-[#241114] to-black px-6 pb-16 pt-8 text-center">
                                <div className="absolute -right-12 -top-12 size-44 rounded-full bg-primary/20 blur-[60px]" aria-hidden="true" />
                                <div className="relative">
                                    <input type="file" className="sr-only" id="logo-upload" onChange={handleLogoUpload} accept="image/*" disabled={isUploading} />
                                    <Label htmlFor="logo-upload" className="inline-block cursor-pointer">
                                        <span className="relative block">
                                            <Avatar className="size-28 rounded-[2rem] border-4 border-white/15 shadow-2xl">
                                                <AvatarImage src={agency.logoUrl} className="object-cover" />
                                                <AvatarFallback className="bg-white/10 text-4xl font-black uppercase text-white">{agency.name[0]}</AvatarFallback>
                                            </Avatar>
                                            {agency.isVerified && <VerifiedBadge className="absolute -bottom-1 -right-1 z-20 size-8 border-4 border-[#1a2333]" />}
                                            <span className="absolute bottom-1 right-1 z-30 flex size-9 items-center justify-center rounded-xl border-4 border-[#1a2333] bg-primary text-white shadow-xl transition-transform hover:scale-105">
                                                {isUploading ? <Loader2 className="size-4 animate-spin" aria-hidden="true" /> : <Camera className="size-4" aria-hidden="true" />}
                                            </span>
                                        </span>
                                    </Label>
                                </div>
                                <h3 className="mt-5 truncate text-2xl font-black uppercase tracking-tighter text-white leading-tight">{agency.name}</h3>
                                <div className="mt-3 flex justify-center">
                                    <Badge className="border border-white/15 bg-white/10 px-3 py-1 text-[9px] font-black uppercase tracking-widest text-white">
                                        {agency.isVerified ? 'Verified Fleet Partner' : 'Unverified Partner'}
                                    </Badge>
                                </div>
                            </div>
                            <div className="space-y-3 p-5 sm:p-6">
                                <div className="flex items-center gap-3 rounded-2xl border border-border/50 bg-secondary/30 px-4 py-3">
                                    <Mail className="size-4 shrink-0 text-primary" aria-hidden="true" />
                                    <div className="min-w-0 text-left">
                                        <p className="text-[8px] font-black uppercase tracking-widest text-muted-foreground">Digital Terminal</p>
                                        <p className="truncate text-xs font-bold">{agency.email}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3 rounded-2xl border border-border/50 bg-secondary/30 px-4 py-3">
                                    <Phone className="size-4 shrink-0 text-primary" aria-hidden="true" />
                                    <div className="min-w-0 text-left">
                                        <p className="text-[8px] font-black uppercase tracking-widest text-muted-foreground">Direct Proxy</p>
                                        <p className="truncate text-xs font-bold">{agency.phoneNumber}</p>
                                    </div>
                                </div>
                                <div className="flex flex-col gap-3 pt-1 sm:flex-row">
                                    <Button variant="outline" className="h-13 flex-1 rounded-2xl border-primary/25 bg-primary/5 py-3.5 text-[10px] font-black uppercase tracking-widest text-primary transition-all hover:bg-primary hover:text-white active:scale-95" onClick={() => setIsEditDialogOpen(true)}>
                                        <Edit size={16} className="mr-2" aria-hidden="true" /> Edit Profile
                                    </Button>
                                    <Button variant="outline" className="h-13 flex-1 rounded-2xl border-destructive/25 bg-destructive/5 py-3.5 text-[10px] font-black uppercase tracking-widest text-destructive transition-all hover:bg-destructive hover:text-white active:scale-95" onClick={handleLogout}>
                                        <LogOut size={16} className="mr-2" aria-hidden="true" /> Sign Out
                                    </Button>
                                </div>
                                {!isStandalone && isInstallable && (
                                    <Button
                                        variant="ghost"
                                        onClick={promptInstall}
                                        className="h-12 w-full rounded-2xl text-[10px] font-black uppercase tracking-widest text-primary hover:bg-primary/10"
                                    >
                                        Install EataLyft
                                    </Button>
                                )}
                            </div>
                        </section>

                        {/* Fleet snapshot */}
                        <section className="overflow-hidden rounded-[2rem] border border-border/50 bg-card shadow-sm" aria-label="Fleet summary">
                            <div className="flex items-center justify-between border-b border-border/50 bg-secondary/30 px-5 py-4">
                                <h3 className="text-[10px] font-black uppercase tracking-[0.25em]">Fleet Hub Summary</h3>
                                <Truck size={16} className="text-primary" aria-hidden="true" />
                            </div>
                            <dl className="divide-y divide-border/30">
                                {[
                                    { label: '75-Seater', count: fleetHub?.bus75 || 0 },
                                    { label: '65-Seater', count: fleetHub?.bus65 || 0 },
                                    { label: '50-Seater', count: fleetHub?.bus50 || 0 },
                                    { label: '30-Seater', count: fleetHub?.bus30 || 0 },
                                    { label: '18-Seater', count: fleetHub?.bus18 || 0 },
                                ].map(item => (
                                    <div key={item.label} className="flex items-center justify-between px-5 py-3">
                                        <dt className="text-[10px] font-bold uppercase tracking-widest text-muted-foreground">{item.label}</dt>
                                        <dd className="text-sm font-black tabular-nums">{item.count} <span className="text-[9px] font-bold uppercase text-muted-foreground">nodes</span></dd>
                                    </div>
                                ))}
                            </dl>
                        </section>
                    </div>

                    <div className="lg:col-span-8 space-y-12 animate-in fade-in slide-in-from-right-4 duration-1000">
                        <section className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <MenuNode icon={FileText} label="Company Intelligence" sub="Audit legal and registration data" href="/agency/intelligence" />
                            <MenuNode icon={Bus} label="Fleet Inventory" sub={`${totalFleet} Total verified assets`} href="/agency/fleet" />
                            <MenuNode icon={MapPin} label="Office Hubs" sub={`${agency.operatingCities?.length || stations.length || 0} Operational cities`} href="/agency/hubs" />
                            <MenuNode icon={BarChart3} label="Audit Analytics" sub="Review growth and revenue streams" href="/agency/insights" />
                            <MenuNode icon={Wallet} label="Earnings Hub" sub="Manage wallet and request payouts" href="/agency/wallet" />
                            <MenuNode icon={Ticket} label="Ticket Registry" sub="View and manage all bookings" href="/agency/tickets" />
                        </section>

                        {/* Station Office Hubs Section */}
                        {stations.length > 0 && (
                            <section className="bg-card border border-border/50 rounded-[2.5rem] overflow-hidden shadow-sm">
                                <div className="p-6 bg-secondary/30 border-b border-border/10 flex items-center justify-between">
                                    <div>
                                        <h3 className="text-[10px] font-black uppercase tracking-[0.3em]">Station Office Hubs</h3>
                                        <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5">{stations.length} operational location{stations.length !== 1 ? 's' : ''}</p>
                                    </div>
                                    <Building2 size={16} className="text-primary" />
                                </div>
                                <div className="divide-y divide-border/30">
                                    {stations.map(station => {
                                        const ticketCount = stationTicketCounts[station.id] || 0;
                                        const revenue = stationRevenue[station.id] || 0;
                                        return (
                                            <div key={station.id} className="p-5 flex items-center gap-4 hover:bg-secondary/20 transition-colors">
                                                <div className="size-10 rounded-2xl bg-primary/10 flex items-center justify-center shrink-0">
                                                    <Building2 size={16} className="text-primary" />
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-black text-sm uppercase tracking-tighter truncate">{station.name}</p>
                                                    {station.location && (
                                                        <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5 flex items-center gap-1">
                                                            <MapPin size={8} /> {station.location}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-3 shrink-0">
                                                    <div className="text-right">
                                                        <p className="font-black text-sm text-foreground">{ticketCount}</p>
                                                        <p className="text-[7px] font-bold text-muted-foreground uppercase tracking-widest">Tickets</p>
                                                    </div>
                                                    <div className="w-px h-8 bg-border/30" />
                                                    <div className="text-right">
                                                        <p className="font-black text-sm text-primary">{revenue.toLocaleString()}</p>
                                                        <p className="text-[7px] font-bold text-muted-foreground uppercase tracking-widest">XAF Rev.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </section>
                        )}

                        {/* Audit Log Section */}
                        {recentAuditLogs.length > 0 && (
                            <section className="bg-card border border-border/50 rounded-[2.5rem] overflow-hidden shadow-sm">
                                <div className="p-6 bg-secondary/30 border-b border-border/10 flex items-center justify-between">
                                    <div>
                                        <h3 className="text-[10px] font-black uppercase tracking-[0.3em]">Recent Audit Activity</h3>
                                        <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest mt-0.5">Tamper-proof ticket operation log</p>
                                    </div>
                                    <button onClick={() => setIsAuditExpanded(!isAuditExpanded)}
                                        className="flex items-center gap-1.5 text-[8px] font-black text-primary uppercase tracking-widest">
                                        {isAuditExpanded ? <ChevronUp size={14} /> : <ChevronDown size={14} />}
                                        {isAuditExpanded ? 'Show Less' : 'Show All'}
                                    </button>
                                </div>
                                <div className="p-6 space-y-4">
                                    {(isAuditExpanded ? recentAuditLogs : recentAuditLogs.slice(0, 5)).map((log, i) => {
                                        const actionColor =
                                            log.actionType === 'CREATE' ? 'bg-emerald-500/10 text-emerald-600' :
                                            log.actionType === 'DELETE' ? 'bg-red-500/10 text-red-600' :
                                            log.actionType === 'STATUS_CHANGE' ? 'bg-blue-500/10 text-blue-600' :
                                            'bg-amber-500/10 text-amber-600';
                                        return (
                                            <div key={log.id} className="flex gap-3">
                                                <div className="flex flex-col items-center">
                                                    <div className={cn("size-7 rounded-full flex items-center justify-center text-[8px] font-black border flex-shrink-0", actionColor)}>
                                                        {log.actionType?.[0]}
                                                    </div>
                                                    {i < (isAuditExpanded ? recentAuditLogs.length : Math.min(5, recentAuditLogs.length)) - 1 && (
                                                        <div className="w-px flex-1 bg-border/30 my-1 min-h-[8px]" />
                                                    )}
                                                </div>
                                                <div className="flex-1 pb-1 min-w-0">
                                                    <div className="flex items-center gap-2 mb-0.5 flex-wrap">
                                                        <span className={cn("text-[7px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded-md", actionColor)}>
                                                            {log.actionType}
                                                        </span>
                                                        <span className="text-[7px] font-bold text-muted-foreground uppercase tracking-widest">
                                                            by {log.performedByRole}
                                                        </span>
                                                        <span className="text-[7px] font-bold text-muted-foreground/50 ml-auto">
                                                            {log.timestamp?.toDate ? (() => { try { return dateFnsFormat(log.timestamp.toDate(), 'MMM d, HH:mm'); } catch { return '—'; } })() : '—'}
                                                        </span>
                                                    </div>
                                                    <p className="text-[9px] font-bold truncate">{log.customerName || 'Ticket Operation'}</p>
                                                    {log.changes?.length > 0 && (
                                                        <p className="text-[7px] font-bold text-muted-foreground mt-0.5">
                                                            {log.changes.map((c: any) => `${c.field}: ${c.from} → ${c.to}`).join(' · ')}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                    <Link href="/agency/tickets" className="flex items-center justify-center gap-2 h-10 rounded-2xl bg-primary/5 border border-primary/20 text-primary text-[9px] font-black uppercase tracking-widest hover:bg-primary/10 transition-all">
                                        <Ticket size={12} /> View Full Registry
                                    </Link>
                                </div>
                            </section>
                        )}
                    </div>
                </div>
            </main>

            <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
                <DialogContent className="max-w-2xl rounded-[2.5rem] p-0 overflow-hidden border-none shadow-2xl">
                    <DialogHeader className="p-8 bg-secondary/30 border-b border-border/10">
                        <DialogTitle className="text-2xl font-black uppercase tracking-tighter flex items-center gap-3">
                            <Edit className="text-primary" /> Intelligence Update
                        </DialogTitle>
                        <DialogDescription className="text-xs font-bold text-muted-foreground uppercase tracking-widest mt-1">Refine your organizational node parameters</DialogDescription>
                    </DialogHeader>
                    <Form {...form}>
                        <form onSubmit={form.handleSubmit(handleSaveProfile)} className="p-8 space-y-8 max-h-[70vh] overflow-y-auto no-scrollbar">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <FormField control={form.control} name="name" render={({ field }) => (
                                    <FormItem><FormLabel className="text-[10px] font-black uppercase">Agency Brand Name</FormLabel><FormControl><Input className="h-14 rounded-2xl bg-secondary/50 border-none font-bold" {...field} /></FormControl></FormItem>
                                )}/>
                                <FormField control={form.control} name="phoneNumber" render={({ field }) => (
                                    <FormItem><FormLabel className="text-[10px] font-black uppercase">Operational Proxy</FormLabel><FormControl><Input className="h-14 rounded-2xl bg-secondary/50 border-none font-bold" {...field} /></FormControl></FormItem>
                                )}/>
                            </div>
                            <FormField control={form.control} name="headOffice" render={({ field }) => (
                                <FormItem><FormLabel className="text-[10px] font-black uppercase">Headquarters Node</FormLabel><FormControl><Input className="h-14 rounded-2xl bg-secondary/50 border-none font-bold" {...field} /></FormControl></FormItem>
                            )}/>
                            <div className="space-y-4">
                                <h4 className="text-[10px] font-black uppercase tracking-[0.3em] text-primary flex items-center gap-2"><Globe size={14} /> Operational Jurisdictions</h4>
                                <ScrollArea className="h-48 rounded-2xl border border-border/50 bg-secondary/30 p-4">
                                    <div className="grid grid-cols-2 gap-3">
                                        {CAMEROON_CITIES.map(city => (
                                            <label key={city} className="flex items-center gap-3 p-3 rounded-xl bg-card border border-border/50 cursor-pointer hover:border-primary/30 transition-all">
                                                <Checkbox checked={form.watch('operatingCities').includes(city)} onCheckedChange={(checked) => {
                                                    const current = form.getValues('operatingCities') || [];
                                                    if (checked) form.setValue('operatingCities', [...current, city]);
                                                    else form.setValue('operatingCities', current.filter(c => c !== city));
                                                }} />
                                                <span className="text-[10px] font-black uppercase tracking-widest truncate">{city}</span>
                                            </label>
                                        ))}
                                    </div>
                                </ScrollArea>
                            </div>
                            <div className="space-y-4">
                                <h4 className="text-[10px] font-black uppercase tracking-[0.3em] text-primary flex items-center gap-2"><Bus size={14} /> Fleet Asset Composition</h4>
                                <div className="grid grid-cols-5 gap-4">
                                    {['bus75', 'bus65', 'bus50', 'bus30', 'bus18'].map((type) => (
                                        <div key={type} className="space-y-2">
                                            <Label className="text-[8px] font-black uppercase text-muted-foreground block text-center truncate">{type.replace('bus', '')} Seat</Label>
                                            <Input type="number" {...form.register(type as any)} className="h-12 rounded-xl bg-secondary/50 border-none font-black text-center" />
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <Button type="submit" disabled={isSaving} className="w-full h-16 rounded-2xl bg-primary text-white font-black uppercase tracking-widest text-xs shadow-xl shadow-primary/20 transition-all active:scale-95">
                                {isSaving ? 'Synchronizing...' : 'Save Strategy'}
                            </Button>
                        </form>
                    </Form>
                </DialogContent>
            </Dialog>

            <div className="px-4 max-w-4xl mx-auto pb-10">
              <PWAInstallCard />
            </div>
        </div>
    );
}
