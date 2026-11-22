'use client';

import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import { 
    ArrowLeft, 
    ArrowRight, 
    Bus, 
    ShieldCheck, 
    Calendar, 
    Hash, 
    Info,
    UploadCloud,
    Image as ImageIcon,
    Loader2,
    CheckCircle2,
    Clock
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';
import { useS3Upload } from '@/hooks/use-s3-upload';
import { useUser, useFirestore } from '@/firebase';
import { doc, collection, serverTimestamp, increment, writeBatch } from 'firebase/firestore';
import { Badge } from '@/components/ui/badge';

const CAPACITY_OPTIONS = [
    { value: '75', seats: '75', label: 'S-Class Coach' },
    { value: '65', seats: '65', label: 'VIP Luxury' },
    { value: '50', seats: '50', label: 'Business Liner' },
    { value: '30', seats: '30', label: 'Urban Shuttle' },
    { value: '18', seats: '18', label: 'Executive Van' },
];

export default function DeployAssetPage() {
    const router = useRouter();
    const { user } = useUser();
    const firestore = useFirestore();
    const { toast } = useToast();
    const { uploadFile, isUploading } = useS3Upload();
    
    const [isSaving, setIsSaving] = useState(false);
    const [assetImageUrl, setAssetImageUrl] = useState<string | null>(null);

    const handleAssetMediaSync = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const result = await uploadFile(file, 'agencies'); 
        if (result) {
            setAssetImageUrl(result.publicUrl);
            toast({ title: "Asset Node Synchronized", description: "Vehicle media successfully deployed to the hub." });
        }
    };

    const handleSave = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!user || !firestore) return;
        if (!assetImageUrl) {
            toast({ variant: 'destructive', title: 'Media Node Required', description: 'Sync a vehicle photo before deployment.' });
            return;
        }

        setIsSaving(true);
        const formData = new FormData(e.currentTarget);
        const capacityType = formData.get('type') as string;
        const model = formData.get('model') as string;
        const plate = formData.get('plate') as string;
        const year = formData.get('year') as string;
        
        try {
            const batch = writeBatch(firestore);
            
            // 1. Individual Asset Node - Auto-approved and immediately available
            const fleetCol = collection(firestore, 'fleet', user.uid, 'assets');
            const assetData = {
                firstName: model,
                photoUrl: assetImageUrl,
                vehicleDetails: {
                    model: model,
                    licensePlate: plate,
                    type: capacityType,
                    seater: Number(capacityType),
                    year: year,
                },
                status: 'available',
                isOnline: false,
                agencyId: user.uid,
                createdAt: serverTimestamp(),
                updatedAt: serverTimestamp()
            };

            const assetRef = doc(fleetCol);
            batch.set(assetRef, { id: assetRef.id, ...assetData });

            // 2. Atomic Sync: Set/Merge Count in Fleet Hub Node
            // Using batch.set with merge: true ensures the document is created if it doesn't exist
            const fleetHubRef = doc(firestore, 'fleet', user.uid);
            const busKey = `bus${capacityType}`;
            batch.set(fleetHubRef, {
                [busKey]: increment(1),
                updatedAt: serverTimestamp()
            }, { merge: true });

            await batch.commit();

            toast({ title: "Asset Node Deployed", description: "Vehicle registered and immediately available in your fleet." });
            router.push('/agency/fleet');
        } catch (error) {
            console.error("Fleet sync failure:", error);
            toast({ variant: 'destructive', title: 'Sync Failure', description: (error as Error).message });
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <div className="min-h-screen bg-background text-foreground font-display selection:bg-primary selection:text-white flex flex-col animate-in fade-in duration-700">
            <header className="sticky top-0 z-50 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center justify-between shadow-sm">
                <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50 hover:bg-secondary transition-all" onClick={() => router.back()}>
                    <ArrowLeft size={20} />
                </Button>
                <div className="flex flex-col items-center">
                    <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Fleet Registry</span>
                    <h1 className="text-lg font-black uppercase tracking-tighter leading-none">Initialize New Asset</h1>
                </div>
                <div className="size-11"></div>
            </header>

            <main className="flex-1 max-w-2xl mx-auto w-full px-6 py-10 space-y-12">
                <section className="space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="size-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary border border-primary/20">
                            <Bus size={20} />
                        </div>
                        <h2 className="text-3xl font-black uppercase tracking-tight leading-none">New Asset</h2>
                    </div>
                    <p className="text-xs font-bold text-muted-foreground uppercase tracking-widest leading-relaxed opacity-60">
                        Provide the parameters to register this vehicle. The asset will be immediately available in your fleet registry and visible to administration.
                    </p>
                </section>

                <form onSubmit={handleSave} id="fleet-deployment-form" className="space-y-10">
                    <div className="space-y-4">
                        <h3 className="text-[10px] font-black uppercase tracking-[0.3em] text-primary ml-1 flex items-center gap-2">
                            <ImageIcon size={14} /> Asset Visual Node
                        </h3>
                        <div className="relative overflow-hidden rounded-[2rem] border-2 border-dashed border-border/50 bg-secondary/20 transition-colors focus-within:border-primary/50 hover:border-primary/30">
                            <input type="file" className="sr-only" id="asset-media" onChange={handleAssetMediaSync} accept="image/*" disabled={isUploading} />
                            {assetImageUrl ? (
                                <div className="relative">
                                    <img src={assetImageUrl} alt="Asset preview" className="h-56 w-full object-cover sm:h-64" />
                                    <div className="absolute inset-0 bg-gradient-to-t from-black/65 via-black/10 to-transparent" aria-hidden="true" />
                                    <div className="absolute inset-x-4 bottom-4 flex flex-wrap items-center justify-between gap-3">
                                        <span className="flex items-center gap-1.5 rounded-full bg-green-500/90 px-3 py-1.5 text-[9px] font-black uppercase tracking-widest text-white backdrop-blur-md">
                                            <CheckCircle2 size={13} strokeWidth={3} aria-hidden="true" /> Node Synchronized
                                        </span>
                                        <span className="flex gap-2">
                                            <Label htmlFor="asset-media" className="flex h-11 cursor-pointer items-center rounded-xl bg-white/90 px-4 text-[10px] font-black uppercase tracking-widest text-foreground shadow-lg transition-transform active:scale-95">
                                                Change
                                            </Label>
                                            <button type="button" onClick={() => setAssetImageUrl(null)} className="flex h-11 items-center rounded-xl bg-black/50 px-4 text-[10px] font-black uppercase tracking-widest text-white backdrop-blur-md transition-colors hover:bg-destructive">
                                                Remove
                                            </button>
                                        </span>
                                    </div>
                                    {isUploading && (
                                        <div className="absolute inset-0 z-10 flex items-center justify-center bg-black/40">
                                            <Loader2 className="size-10 animate-spin text-white" aria-hidden="true" />
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <Label htmlFor="asset-media" className="group flex cursor-pointer flex-col items-center px-6 py-12 text-center sm:py-16">
                                    <span className="mb-6 flex size-20 items-center justify-center rounded-[2rem] bg-primary/10 text-primary shadow-sm transition-all duration-300 group-hover:scale-105">
                                        {isUploading ? <Loader2 className="size-10 animate-spin" aria-hidden="true" /> : <UploadCloud size={40} strokeWidth={1.5} aria-hidden="true" />}
                                    </span>
                                    <span className="font-black text-sm uppercase tracking-widest text-foreground">
                                        {isUploading ? 'Syncing media…' : 'Sync Asset Media'}
                                    </span>
                                    <span className="mt-3 text-[10px] font-bold uppercase tracking-tighter text-muted-foreground opacity-60">
                                        Max 5MB • 16:9 Landscape Recommended
                                    </span>
                                </Label>
                            )}
                        </div>
                    </div>

                    <div className="space-y-3">
                        <span id="capacity-label" className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Asset Classification</span>
                        <div role="radiogroup" aria-labelledby="capacity-label" className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            {CAPACITY_OPTIONS.map((opt, i) => (
                                <label key={opt.value} className="relative flex cursor-pointer flex-col items-center gap-1 rounded-2xl border-2 border-border/50 bg-secondary/40 px-3 py-4 text-center transition-all hover:border-primary/40 has-checked:border-primary has-checked:bg-primary/5 has-checked:shadow-lg active:scale-[0.98]">
                                    <input type="radio" name="type" value={opt.value} defaultChecked={i === 0} required className="sr-only" />
                                    <Bus className="size-6 text-primary" aria-hidden="true" />
                                    <span className="text-lg font-black tabular-nums leading-none">{opt.seats}<span className="ml-1 text-[9px] font-bold uppercase tracking-widest text-muted-foreground">seats</span></span>
                                    <span className="text-[9px] font-bold uppercase tracking-widest text-muted-foreground">{opt.label}</span>
                                </label>
                            ))}
                        </div>
                    </div>

                    <div className="space-y-3">
                        <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Asset Model</Label>
                        <div className="relative group">
                            <Bus className="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-muted-foreground group-focus-within:text-primary transition-colors" />
                            <Input name="model" required autoComplete="off" className="h-14 pl-12 rounded-2xl bg-secondary/50 border-none font-bold text-base shadow-inner focus-visible:ring-1 focus-visible:ring-primary/20" placeholder="e.g. Marcopolo Paradiso" />
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div className="space-y-3">
                            <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Digital Plate ID</Label>
                            <div className="relative group">
                                <Hash className="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                <Input name="plate" required className="h-14 pl-12 rounded-2xl bg-secondary/50 border-none font-black text-base uppercase tracking-[0.2em] shadow-inner focus-visible:ring-1 focus-visible:ring-primary/20" placeholder="LT-000-XX" />
                            </div>
                        </div>

                        <div className="space-y-3">
                            <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Manufacturing Vector</Label>
                            <div className="relative group">
                                <Calendar className="absolute left-4 top-1/2 -translate-y-1/2 size-5 text-muted-foreground group-focus-within:text-primary transition-colors" />
                                <Input name="year" required type="number" min="2000" max={new Date().getFullYear() + 1} className="h-14 pl-12 rounded-2xl bg-secondary/50 border-none font-black text-base shadow-inner focus-visible:ring-1 focus-visible:ring-primary/20" placeholder="2024" />
                            </div>
                        </div>
                    </div>

                    <div className="p-6 rounded-[2rem] bg-primary/5 border border-primary/10 flex items-start gap-4">
                        <Info className="size-5 text-primary shrink-0 mt-0.5" />
                        <div className="space-y-1">
                            <p className="text-[10px] font-black uppercase tracking-widest text-foreground">Instant Activation</p>
                            <p className="text-[9px] font-medium text-muted-foreground leading-relaxed uppercase tracking-widest opacity-60">
                                Assets are instantly available in your registry and visible to administration. Admin can edit, update status, or remove any asset at any time.
                            </p>
                        </div>
                    </div>

                    <div className="h-24" />
                </form>
            </main>

            <footer className="fixed bottom-0 left-0 right-0 z-50 border-t border-border/50 bg-background/90 px-6 pt-5 shadow-2xl backdrop-blur-xl pb-[max(1.25rem,env(safe-area-inset-bottom))]">
                <div className="max-w-2xl mx-auto w-full">
                    <Button 
                        form="fleet-deployment-form"
                        type="submit"
                        disabled={isSaving || isUploading}
                        className="w-full h-16 bg-primary hover:bg-red-600 text-white font-black rounded-2xl shadow-2xl shadow-primary/30 flex items-center justify-between px-8 transition-all active:scale-[0.98] group relative overflow-hidden"
                    >
                        <div className="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                        <div className="flex flex-col items-start leading-none relative z-10">
                            <span className="text-[9px] font-black text-white/60 uppercase tracking-widest mb-1">{isSaving ? 'Processing...' : 'Instant Activation'}</span>
                            <span className="text-sm font-black uppercase tracking-[0.2em]">{isSaving ? 'Synchronizing Node...' : 'Commit Asset Deployment'}</span>
                        </div>
                        <div className="p-2 rounded-xl bg-white/20 group-hover:translate-x-1 transition-transform border border-white/10 relative z-10">
                            <ArrowRight size={20} />
                        </div>
                    </Button>
                </div>
            </footer>
        </div>
    );
}
