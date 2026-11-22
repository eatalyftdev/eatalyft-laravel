'use client';

import React, { useState, useMemo } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import { useUser, useDoc, useFirestore, useMemoFirebase } from '@/firebase';
import { doc, serverTimestamp, arrayUnion, arrayRemove } from 'firebase/firestore';
import { updateDocumentNonBlocking } from '@/firebase/non-blocking-updates';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import { 
    ArrowLeft, 
    Plus, 
    MapPin, 
    Phone, 
    User, 
    Trash2, 
    Building2, 
    ChevronRight, 
    Sparkles,
    ShieldCheck,
    Save,
    X,
    Activity,
    LayoutDashboard,
    Bus,
    Settings
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Card, CardContent } from '@/components/ui/card';
import { useToast } from '@/hooks/use-toast';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import Link from 'next/link';


type OfficeHub = {
    city: string;
    address: string;
    phoneNumber: string;
    managerName: string;
};

type AgencyProfile = {
    id: string;
    name: string;
    officeHubs?: OfficeHub[];
    operatingCities: string[];
};

export default function AgencyHubsPage() {
    const { user, isUserLoading } = useUser();
    const firestore = useFirestore();
    const router = useRouter();
    const pathname = usePathname();
    const { toast } = useToast();
    const [isAddOpen, setIsAddOpen] = useState(false);
    const [isSaving, setIsSaving] = useState(false);

    const agencyRef = useMemoFirebase(() => {
        if (!user || !firestore) return null;
        return doc(firestore, 'agencies', user.uid);
    }, [user, firestore]);

    const { data: agency, isLoading: isAgencyLoading } = useDoc<AgencyProfile>(agencyRef);

    const handleAddHub = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!agencyRef) return;
        
        setIsSaving(true);
        const formData = new FormData(e.currentTarget);
        const newHub: OfficeHub = {
            city: formData.get('city') as string,
            address: formData.get('address') as string,
            phoneNumber: formData.get('phoneNumber') as string,
            managerName: formData.get('managerName') as string,
        };

        try {
            updateDocumentNonBlocking(agencyRef, {
                officeHubs: arrayUnion(newHub),
                // Sync operating cities list as well
                operatingCities: arrayUnion(newHub.city),
                updatedAt: serverTimestamp(),
            });
            toast({ title: "Node Synchronized", description: `${newHub.city} hub is now live in the network.` });
            setIsAddOpen(false);
        } catch (error) {
toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        } finally {
            setIsSaving(false);
        }
    };

    const handleDeleteHub = async (hub: OfficeHub) => {
        if (!agencyRef) return;
        try {
            updateDocumentNonBlocking(agencyRef, {
                officeHubs: arrayRemove(hub),
                updatedAt: serverTimestamp(),
            });
            toast({ title: "Node Terminated", description: `${hub.city} hub has been removed from the registry.` });
        } catch (error) {
toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        }
    };

    if (isUserLoading || isAgencyLoading) return <LoadingSpinner />;

    const hubs = agency?.officeHubs || [];

    return (
        <div className="min-h-screen bg-background text-foreground font-display selection:bg-primary selection:text-white">
            <header className="sticky top-0 z-50 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center justify-between shadow-sm">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50" onClick={() => router.back()}>
                        <ArrowLeft size={20} />
                    </Button>
                    <div>
                        <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Operational Nodes</span>
                        <h1 className="text-xl font-black uppercase tracking-tighter leading-none">Office Hubs</h1>
                    </div>
                </div>
                <Button onClick={() => setIsAddOpen(true)} className="rounded-2xl h-11 px-6 font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20 gap-2 group">
                    <Plus className="size-4 group-hover:rotate-90 transition-transform duration-500" />
                    Register New Hub
                </Button>
            </header>

            <main className="max-w-5xl mx-auto p-6 md:p-10 lg:p-12 space-y-10 pb-32">
                <section className="flex flex-col gap-2">
                    <h2 className="text-3xl font-black uppercase tracking-tight leading-none">Network Geometry</h2>
                    <p className="text-sm font-medium text-muted-foreground uppercase tracking-widest opacity-60">
                        Manage city terminals and localized support nodes.
                    </p>
                </section>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    {hubs.length > 0 ? hubs.map((hub, index) => (
                        <Card key={`${hub.city}-${index}`} className="group relative bg-card border border-border/50 hover:border-primary/30 transition-all duration-700 rounded-[2.5rem] shadow-sm hover:shadow-2xl overflow-hidden active:scale-[0.99]">
                            <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-primary to-transparent opacity-20"></div>
                            <CardContent className="p-8 space-y-8">
                                <div className="flex justify-between items-start">
                                    <div className="size-14 rounded-2xl bg-secondary flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all duration-500 shadow-sm">
                                        <Building2 size={28} />
                                    </div>
                                    <Button variant="ghost" size="icon" className="rounded-full text-muted-foreground hover:text-destructive hover:bg-destructive/5" onClick={() => handleDeleteHub(hub)}>
                                        <Trash2 size={18} />
                                    </Button>
                                </div>

                                <div className="space-y-1">
                                    <h3 className="text-2xl font-black uppercase tracking-tighter text-foreground">{hub.city} Hub</h3>
                                    <div className="flex items-center gap-2">
                                        <Badge variant="outline" className="text-[8px] font-black uppercase tracking-widest border-primary/20 text-primary h-5">Verified Node</Badge>
                                        <span className="size-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                    </div>
                                </div>

                                <div className="space-y-5 pt-2">
                                    <div className="flex items-start gap-4">
                                        <MapPin className="size-4 text-primary shrink-0 mt-0.5" />
                                        <div className="flex flex-col min-w-0">
                                            <span className="text-[8px] font-black text-muted-foreground uppercase tracking-widest">Station Protocol</span>
                                            <p className="text-xs font-bold truncate group-hover:text-foreground transition-colors">{hub.address}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-4">
                                        <Phone className="size-4 text-primary shrink-0 mt-0.5" />
                                        <div className="flex flex-col min-w-0">
                                            <span className="text-[8px] font-black text-muted-foreground uppercase tracking-widest">Support Proxy</span>
                                            <p className="text-xs font-black tracking-widest">{hub.phoneNumber}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-start gap-4">
                                        <User className="size-4 text-primary shrink-0 mt-0.5" />
                                        <div className="flex flex-col min-w-0">
                                            <span className="text-[8px] font-black text-muted-foreground uppercase tracking-widest">Node Manager</span>
                                            <p className="text-xs font-bold truncate uppercase tracking-tighter">{hub.managerName}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="pt-4 border-t border-border/10 flex items-center justify-between">
                                    <span className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.3em]">Operational Node AM-04</span>
                                    <ChevronRight className="size-4 text-muted-foreground opacity-30 group-hover:opacity-100 group-hover:translate-x-1 transition-all" />
                                </div>
                            </CardContent>
                            <div className="absolute -right-4 -bottom-4 size-24 bg-primary/5 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        </Card>
                    )) : (
                        <div className="col-span-full py-32 text-center flex flex-col items-center gap-6 opacity-30 grayscale border-2 border-dashed border-border/50 rounded-[3rem]">
                            <MapPin size={64} className="text-muted-foreground" />
                            <div className="space-y-2">
                                <h3 className="text-xl font-black uppercase tracking-tighter text-foreground">Registry Empty</h3>
                                <p className="text-xs font-bold text-muted-foreground uppercase tracking-widest max-w-xs mx-auto leading-relaxed">
                                    Register your operational hubs to provide localized support intelligence to passengers.
                                </p>
                            </div>
                            <Button variant="outline" className="rounded-xl px-8 h-12 font-black uppercase tracking-widest text-[10px]" onClick={() => setIsAddOpen(true)}>
                                Initialize Registry
                            </Button>
                        </div>
                    )}
                </div>
            </main>

            <Dialog open={isAddOpen} onOpenChange={setIsAddOpen}>
                <DialogContent className="max-w-md rounded-[2.5rem] p-0 overflow-hidden border-none shadow-2xl">
                    <DialogHeader className="p-8 bg-secondary/30 border-b border-border/10">
                        <DialogTitle className="text-2xl font-black uppercase tracking-tighter flex items-center gap-3">
                            <Plus className="text-primary size-6" /> Node Initialization
                        </DialogTitle>
                        <DialogDescription className="text-xs font-bold text-muted-foreground uppercase tracking-widest mt-1">
                            Synchronize a new city hub with the network
                        </DialogDescription>
                    </DialogHeader>
                    
                    <form onSubmit={handleAddHub} className="p-8 space-y-6">
                        <div className="space-y-2">
                            <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">City Jurisdiction</Label>
                            <Input name="city" required placeholder="e.g. Douala" className="h-14 rounded-2xl bg-secondary/50 border-none font-bold text-base shadow-inner focus-visible:ring-1 focus-visible:ring-primary/20" />
                        </div>
                        <div className="space-y-2">
                            <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Physical Station Address</Label>
                            <Input name="address" required placeholder="Street / Terminal Location" className="h-14 rounded-2xl bg-secondary/50 border-none font-bold text-base shadow-inner focus-visible:ring-1 focus-visible:ring-primary/20" />
                        </div>
                        <div className="space-y-2">
                            <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Support Proxy (Phone)</Label>
                            <Input name="phoneNumber" required placeholder="+237..." className="h-14 rounded-2xl bg-secondary/50 border-none font-black text-base tracking-widest shadow-inner focus-visible:ring-1 focus-visible:ring-primary/20" />
                        </div>
                        <div className="space-y-2">
                            <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Node Administrator</Label>
                            <Input name="managerName" required placeholder="Manager Name" className="h-14 rounded-2xl bg-secondary/50 border-none font-bold text-base shadow-inner focus-visible:ring-1 focus-visible:ring-primary/20" />
                        </div>

                        <div className="pt-4 flex gap-4">
                            <Button type="button" variant="ghost" className="flex-1 h-14 rounded-2xl font-black uppercase tracking-widest text-[10px]" onClick={() => setIsAddOpen(false)}>Abort</Button>
                            <Button type="submit" disabled={isSaving} className="flex-[2] h-14 rounded-2xl bg-primary hover:bg-red-600 text-white font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20 gap-3 group active:scale-[0.98] transition-all">
                                <Save className="size-4 group-hover:scale-110 transition-transform" />
                                {isSaving ? 'Syncing...' : 'Commit Node'}
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            
        </div>
    );
}
