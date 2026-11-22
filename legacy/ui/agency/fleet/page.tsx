'use client';

/**
 * @fileOverview Redefined Agency Fleet Registry.
 * High-fidelity terminal for individual asset management and visual manifest synchronization.
 * Synchronized with the EataSync S3 media protocol and the new /fleet root path.
 */

import React, { useMemo, useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useUser, useDoc, useFirestore, useMemoFirebase, useCollection, useFirebase } from '@/firebase';
import { doc, collection, query, where, updateDoc, serverTimestamp, deleteDoc } from 'firebase/firestore';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Bus,
    Search,
    Bell,
    SlidersHorizontal,
    ArrowLeft,
    Plus,
    ShieldCheck,
    Edit,
    Trash2,
    Camera,
    Loader2,
    Users,
    Sparkles,
    Calendar,
    AlertCircle
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import Link from 'next/link';
import Image from 'next/image';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { useToast } from '@/hooks/use-toast';
import { useS3Upload } from '@/hooks/use-s3-upload';
import { Label } from '@/components/ui/label';


type FleetAsset = {
    id: string;
    firstName: string; // Model name
    photoUrl?: string;
    vehicleDetails: {
        model: string;
        licensePlate: string;
        type: string;
        seater: number;
        year?: string;
    };
    status: 'available' | 'maintenance' | 'occupied' | 'pending_verification';
    isOnline: boolean;
};

const EditAssetForm = ({ asset, onComplete, agencyId }: { asset: FleetAsset, onComplete: () => void, agencyId: string }) => {
    const { firestore } = useFirebase();
    const { toast } = useToast();
    const { uploadFile, isUploading } = useS3Upload();
    const [isSaving, setIsSaving] = useState(false);
    const [syncedUrl, setSyncedUrl] = useState(asset.photoUrl);

    const handlePhotoSync = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const result = await uploadFile(file, 'agencies');
        if (result) {
            setSyncedUrl(result.publicUrl);
            toast({ title: "Visual Node Synced" });
        }
    };

    const handleSave = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if (!firestore) return;
        setIsSaving(true);

        const formData = new FormData(e.currentTarget);
        const updates = {
            firstName: formData.get('model') as string,
            photoUrl: syncedUrl || asset.photoUrl || '',
            agencyId,
            vehicleDetails: {
                ...asset.vehicleDetails,
                model: formData.get('model') as string,
                licensePlate: formData.get('plate') as string,
                seater: Number(formData.get('seater')),
                year: formData.get('year') as string,
            },
            updatedAt: serverTimestamp()
        };

        try {
            await updateDoc(doc(firestore, 'fleet', agencyId, 'assets', asset.id), updates);
            toast({ title: "Asset Synchronized", description: "Vehicle manifest has been updated in the cloud registry." });
            onComplete();
        } catch (error) {
toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        } finally {
            setIsSaving(false);
        }
    };

    return (
        <form onSubmit={handleSave} className="space-y-8 pt-4">
            <div className="flex flex-col items-center py-6 bg-secondary/30 rounded-[2.5rem] border border-border/50 shadow-inner relative group">
                <div className="size-32 rounded-2xl overflow-hidden relative border-2 border-primary/20 shadow-xl bg-background flex items-center justify-center">
                    {syncedUrl ? (
                        <Image src={syncedUrl} alt="Bus" fill className="object-cover" unoptimized={syncedUrl.startsWith('blob:')} />
                    ) : (
                        <Bus size={32} className="text-muted-foreground opacity-20" />
                    )}
                    {isUploading && <div className="absolute inset-0 bg-black/40 flex items-center justify-center z-20"><Loader2 className="animate-spin text-white" /></div>}
                </div>
                <input type="file" id="edit-bus-photo" className="sr-only" onChange={handlePhotoSync} accept="image/*" disabled={isUploading} />
                <Label htmlFor="edit-bus-photo" className="mt-4 px-5 h-10 rounded-xl bg-primary text-white font-black uppercase text-[8px] tracking-[0.2em] cursor-pointer hover:bg-red-600 transition-all shadow-lg active:scale-95 flex items-center gap-2">
                    <Camera size={14} /> {isUploading ? 'SYNCING...' : 'SYNC PHOTO'}
                </Label>
            </div>

            <div className="space-y-4">
                <div className="space-y-2">
                    <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Asset Model</Label>
                    <Input name="model" defaultValue={asset.vehicleDetails.model} required className="h-14 rounded-2xl bg-secondary/50 border-none font-bold shadow-inner" />
                </div>
                <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                        <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Plate ID</Label>
                        <Input name="plate" defaultValue={asset.vehicleDetails.licensePlate} required className="h-14 rounded-2xl bg-secondary/50 border-none font-black uppercase tracking-widest shadow-inner" />
                    </div>
                    <div className="space-y-2">
                        <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Year</Label>
                        <Input name="year" type="number" defaultValue={asset.vehicleDetails.year} required className="h-14 rounded-2xl bg-secondary/50 border-none font-black shadow-inner" />
                    </div>
                </div>
                <div className="space-y-2">
                    <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground ml-1">Capacity</Label>
                    <select 
                        name="seater" 
                        defaultValue={asset.vehicleDetails.seater}
                        className="w-full h-14 rounded-2xl bg-secondary/50 border-none font-black text-sm uppercase tracking-widest focus:ring-1 focus:ring-primary shadow-inner px-5 outline-none appearance-none"
                    >
                        <option value="75">75 Seats</option>
                        <option value="65">65 Seats</option>
                        <option value="50">50 Seats</option>
                        <option value="30">30 Seats</option>
                        <option value="18">18 Seats</option>
                    </select>
                </div>
            </div>

            <Button type="submit" disabled={isSaving || isUploading} className="w-full h-16 rounded-2xl bg-primary hover:bg-red-600 text-white font-black uppercase tracking-widest text-[11px] shadow-xl transition-all">
                {isSaving ? 'Synchronizing Node...' : 'Commit Manifest Updates'}
            </Button>
        </form>
    );
};

export default function AgencyFleetPage() {
    const { user, isUserLoading } = useUser();
    const firestore = useFirestore();
    const router = useRouter();
    const { toast } = useToast();
    const [searchQuery, setSearchQuery] = useState('');
    const [editingAsset, setEditingAsset] = useState<FleetAsset | null>(null);

    const fleetQuery = useMemoFirebase(() => {
        if (!user || !firestore) return null;
        return query(collection(firestore, 'fleet', user.uid, 'assets'));
    }, [user, firestore]);

    const { data: assets, isLoading: areAssetsLoading } = useCollection<FleetAsset>(fleetQuery);

    const filteredAssets = useMemo(() => {
        if (!assets) return [];
        const valid = assets.filter(a => a.id !== '_init');
        if (!searchQuery.trim()) return valid;
        const q = searchQuery.toLowerCase();
        return valid.filter(a => 
            a.vehicleDetails.model.toLowerCase().includes(q) || 
            a.vehicleDetails.licensePlate.toLowerCase().includes(q)
        );
    }, [assets, searchQuery]);

    const handleDelete = async (assetId: string) => {
        if (!user || !firestore || !confirm('Execute nuclear wipe for this asset? This action is terminal.')) return;
        try {
            await deleteDoc(doc(firestore, 'fleet', user.uid, 'assets', assetId));
            toast({ title: "Node Erased", description: "Vehicle has been purged from the fleet registry." });
        } catch (error) {
toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        }
    };

    if (isUserLoading || areAssetsLoading) return <LoadingSpinner />;
    if (!user) return null;

    return (
        <div className="min-h-screen bg-background font-display text-foreground selection:bg-primary selection:text-white">
            <main className="flex flex-col relative overflow-x-hidden pb-32">
                <header className="sticky top-0 z-40 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50 lg:hidden" onClick={() => router.back()}>
                            <ArrowLeft size={20} />
                        </Button>
                        <div>
                            <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Fleet Registry</span>
                            <h1 className="text-xl font-black uppercase tracking-tighter leading-none">Vehicle Management</h1>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50 hover:bg-secondary">
                            <SlidersHorizontal size={20} />
                        </Button>
                        <Button variant="ghost" size="icon" className="relative size-11 rounded-2xl bg-secondary/50 hover:bg-secondary">
                            <Bell size={20} />
                            <span className="absolute top-2.5 right-2.5 size-2 bg-primary rounded-full animate-pulse border-2 border-background"></span>
                        </Button>
                    </div>
                </header>

                <div className="p-6 space-y-8 max-w-6xl mx-auto w-full">
                    <section className="space-y-6">
                        <div className="relative group">
                            <Search className="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground group-focus-within:text-primary transition-colors" size={20} />
                            <Input 
                                className="h-14 pl-12 pr-6 rounded-2xl bg-secondary/50 border-none shadow-inner font-black uppercase tracking-[0.3em] text-[10px] focus-visible:ring-primary/20" 
                                placeholder="Search asset model or plate number..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                    </section>

                    <section className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 animate-in fade-in slide-in-from-bottom-4 duration-1000">
                        {filteredAssets.length > 0 ? (
                            filteredAssets.map(asset => {
                                const isPending = asset.status === 'pending_verification';
                                const statusMeta =
                                    asset.status === 'maintenance' ? { label: 'In Shop', dot: 'bg-amber-500', text: 'text-amber-600 dark:text-amber-400' } :
                                    asset.status === 'occupied' ? { label: 'On Route', dot: 'bg-blue-500', text: 'text-blue-600 dark:text-blue-400' } :
                                    isPending ? { label: 'Audit', dot: 'bg-orange-500', text: 'text-orange-600 dark:text-orange-400' } :
                                    { label: 'Ready', dot: 'bg-green-500', text: 'text-green-600 dark:text-green-400' };
                                return (
                                    <article key={asset.id} className={cn(
                                        "group relative flex flex-col overflow-hidden rounded-[2rem] bg-card border border-border/50 shadow-sm transition-all duration-300 hover:shadow-xl hover:border-primary/30 hover:-translate-y-0.5 active:scale-[0.99]",
                                        isPending && "border-orange-500/40"
                                    )}>
                                        {/* Photo tile */}
                                        <div className="relative h-44 shrink-0 overflow-hidden bg-secondary/40">
                                            {asset.photoUrl ? (
                                                <img src={asset.photoUrl} alt={`${asset.vehicleDetails.model} — ${asset.vehicleDetails.licensePlate}`} className="size-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy" />
                                            ) : (
                                                <div className="flex size-full items-center justify-center">
                                                    <Bus className="size-16 text-muted-foreground/30" aria-hidden="true" />
                                                </div>
                                            )}
                                            <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-black/5 to-transparent" aria-hidden="true" />
                                            <span className={cn(
                                                "absolute left-4 top-4 flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-widest backdrop-blur-md",
                                                asset.isOnline ? "bg-green-500/90 text-white" : "bg-black/50 text-white/80"
                                            )}>
                                                <span className={cn("size-1.5 rounded-full", asset.isOnline ? "bg-white animate-pulse" : "bg-white/50")} />
                                                {asset.isOnline ? 'Grid Active' : 'Offline'}
                                            </span>
                                            {isPending ? (
                                                <Badge className="absolute right-4 top-4 h-6 px-3 rounded-full border-none bg-orange-500 text-white font-black uppercase text-[8px] tracking-widest">Awaiting audit</Badge>
                                            ) : (
                                                <Badge className="absolute right-4 top-4 h-6 px-3 rounded-full border-none bg-white/90 text-foreground font-black uppercase text-[9px] tracking-widest">{asset.vehicleDetails.seater} seats</Badge>
                                            )}
                                            <div className="absolute inset-x-4 bottom-3">
                                                <h3 className="text-white text-xl font-black uppercase tracking-tighter leading-tight truncate drop-shadow-md">{asset.vehicleDetails.model}</h3>
                                                <p className="text-white/75 font-mono text-[11px] font-bold uppercase tracking-[0.25em]">{asset.vehicleDetails.licensePlate}</p>
                                            </div>
                                        </div>

                                        {/* Spec strip */}
                                        <dl className="grid grid-cols-3 divide-x divide-border/50 border-b border-border/50">
                                            <div className="flex flex-col items-center gap-1 px-2 py-3">
                                                <dt className="flex items-center gap-1 text-[8px] font-black uppercase tracking-[0.2em] text-muted-foreground"><Calendar className="size-3 text-primary/60" aria-hidden="true" /> Year</dt>
                                                <dd className="text-sm font-black tabular-nums">{asset.vehicleDetails.year || 'N/A'}</dd>
                                            </div>
                                            <div className="flex flex-col items-center gap-1 px-2 py-3">
                                                <dt className="flex items-center gap-1 text-[8px] font-black uppercase tracking-[0.2em] text-muted-foreground"><Users className="size-3 text-primary/60" aria-hidden="true" /> Class</dt>
                                                <dd className="text-sm font-black tabular-nums">{asset.vehicleDetails.seater} pax</dd>
                                            </div>
                                            <div className="flex flex-col items-center gap-1 px-2 py-3">
                                                <dt className="text-[8px] font-black uppercase tracking-[0.2em] text-muted-foreground">Status</dt>
                                                <dd className={cn("flex items-center gap-1.5 text-sm font-black uppercase tracking-wide", statusMeta.text)}>
                                                    <span className={cn("size-1.5 rounded-full", statusMeta.dot)} />
                                                    {statusMeta.label}
                                                </dd>
                                            </div>
                                        </dl>

                                        {/* Footer */}
                                        <div className="flex items-center justify-between gap-2 px-4 py-3">
                                            <span className="flex min-w-0 items-center gap-1.5 text-[9px] font-black text-muted-foreground uppercase tracking-widest">
                                                {isPending ? <AlertCircle size={13} className="shrink-0 text-orange-500" aria-hidden="true" /> : <ShieldCheck size={13} className="shrink-0 text-primary" aria-hidden="true" />}
                                                <span className="truncate">{isPending ? 'Registry sync pending' : 'Verified node'}</span>
                                            </span>
                                            <div className="flex shrink-0 gap-2">
                                                <Button variant="ghost" size="icon" aria-label={`Edit ${asset.vehicleDetails.model}`} className="rounded-xl size-11 bg-secondary/50 hover:bg-primary hover:text-white transition-all" onClick={() => setEditingAsset(asset)}>
                                                    <Edit size={18} aria-hidden="true" />
                                                </Button>
                                                <Button variant="ghost" size="icon" aria-label={`Delete ${asset.vehicleDetails.model}`} className="rounded-xl size-11 bg-secondary/50 text-destructive hover:bg-destructive hover:text-white transition-all" onClick={() => handleDelete(asset.id)}>
                                                    <Trash2 size={18} aria-hidden="true" />
                                                </Button>
                                            </div>
                                        </div>
                                    </article>
                                );
                            })
                        ) : (
                            <div className="col-span-full py-32 text-center flex flex-col items-center gap-6 opacity-30 grayscale border-2 border-dashed border-border/50 rounded-[3rem]">
                                <Bus size={64} className="text-muted-foreground" />
                                <div className="space-y-2">
                                    <h3 className="text-xl font-black uppercase tracking-tighter">Inventory Empty</h3>
                                    <p className="text-[10px] font-bold uppercase tracking-widest">Awaiting organizational node configuration</p>
                                </div>
                                <Button variant="outline" className="rounded-xl px-8 h-12 font-black uppercase tracking-widest text-[10px]" asChild>
                                    <Link href="/agency/fleet/new">Deploy First Asset</Link>
                                </Button>
                            </div>
                        )}
                    </section>
                </div>
            </main>

            <Dialog open={!!editingAsset} onOpenChange={(open) => !open && setEditingAsset(null)}>
                <DialogContent className="max-w-md rounded-[2.5rem] p-0 border-none shadow-2xl bg-background max-h-[90dvh] overflow-y-auto overscroll-contain">
                    <DialogHeader className="sticky top-0 z-10 p-8 bg-secondary/30 border-b">
                        <DialogTitle className="text-2xl font-black uppercase tracking-tighter flex items-center gap-3 text-foreground">
                            <Sparkles className="text-primary size-6 animate-pulse" /> Asset Intelligence
                        </DialogTitle>
                        <DialogDescription className="text-xs font-bold uppercase tracking-widest mt-1">Refine vehicle manifest nodes</DialogDescription>
                    </DialogHeader>
                    <div className="p-8">
                        {editingAsset && (
                            <EditAssetForm asset={editingAsset} agencyId={user.uid} onComplete={() => setEditingAsset(null)} />
                        )}
                    </div>
                </DialogContent>
            </Dialog>

            <Link href="/agency/fleet/new" aria-label="Deploy new asset" className="fixed z-50 right-4 bottom-32 md:right-10 md:bottom-10 group flex h-14 max-w-[calc(100vw-2rem)] items-center gap-3 rounded-full bg-primary pl-2.5 pr-6 text-white shadow-2xl shadow-primary/40 transition-all duration-300 hover:bg-red-600 hover:shadow-primary/60 hover:-translate-y-0.5 active:scale-95">
                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/20 border border-white/20">
                    <Plus className="size-5 transition-transform duration-300 group-hover:rotate-90" aria-hidden="true" />
                </span>
                <span className="truncate font-black text-[11px] uppercase tracking-[0.25em]">Deploy New Asset</span>
            </Link>

            
        </div>
    );
}
