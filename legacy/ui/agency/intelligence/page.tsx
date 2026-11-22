'use client';

/**
 * @fileOverview Agency Intelligence Hub.
 * Specialized terminal for transport agencies to upload legal and registration manifests.
 * Synchronized with the EataSync S3 media protocol and administrative audit grid.
 */

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useUser, useDoc, useFirestore, useMemoFirebase } from '@/firebase';
import { doc, updateDoc, serverTimestamp, arrayUnion, arrayRemove, getDoc } from 'firebase/firestore';
import LoadingSpinner from '@/components/loading-spinner';
import { Button } from '@/components/ui/button';
import { 
    ArrowLeft, 
    FileText, 
    UploadCloud, 
    ShieldCheck, 
    Trash2, 
    Info, 
    Activity,
    CheckCircle2,
    Loader2,
    Lock,
    Eye,
    ShieldAlert,
    Building2,
    Briefcase
} from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import { useS3Upload } from '@/hooks/use-s3-upload';
import { Label } from '@/components/ui/label';
import Image from 'next/image';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

type AgencyProfile = {
    id: string;
    name: string;
    documents?: string[];
    isVerified: boolean;
};

export default function AgencyIntelligencePage() {
    const router = useRouter();
    const { user, isUserLoading } = useUser();
    const firestore = useFirestore();
    const { toast } = useToast();
    const { uploadFile, isUploading } = useS3Upload();
    
    // Logic to resolve agency ID (handling sub-agents)
    const [resolvedAgencyId, setResolvedAgencyId] = useState<string | null>(null);

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

    const agencyRef = useMemoFirebase(() => {
        if (!resolvedAgencyId || !firestore) return null;
        return doc(firestore, 'agencies', resolvedAgencyId);
    }, [resolvedAgencyId, firestore]);

    const { data: agency, isLoading: isAgencyLoading } = useDoc<AgencyProfile>(agencyRef);

    const handleFileUpload = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file || !agencyRef) return;

        const result = await uploadFile(file, 'agencies');
        if (result) {
            await updateDoc(agencyRef, {
                documents: arrayUnion(result.publicUrl),
                updatedAt: serverTimestamp()
            });
            toast({ title: "Manifest Updated", description: "Legal document synchronized with the administration terminal." });
        }
    };

    const handleRemoveDoc = async (url: string) => {
        if (!agencyRef) return;
        try {
            await updateDoc(agencyRef, {
                documents: arrayRemove(url),
                updatedAt: serverTimestamp()
            });
            toast({ title: "Asset Purged", description: "Document removed from the cloud hub." });
        } catch (error) {
toast({ title: "Error", description: (error as Error).message, variant: "destructive" });
        }
    };

    if (isUserLoading || isAgencyLoading) return <LoadingSpinner />;
    if (!agency) return <div className="p-20 text-center uppercase font-black opacity-20">Agency Node Missing</div>;

    const documents = agency.documents || [];

    return (
        <div className="min-h-screen bg-background text-foreground font-display selection:bg-primary selection:text-white flex flex-col animate-in fade-in duration-1000">
            <header className="sticky top-0 z-50 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center justify-between shadow-sm">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50" onClick={() => router.back()}>
                        <ArrowLeft size={20} />
                    </Button>
                    <div>
                        <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Compliance Node</span>
                        <h1 className="text-xl font-black uppercase tracking-tighter leading-none">Company Intelligence</h1>
                    </div>
                </div>
                <Badge variant="outline" className="h-10 px-5 rounded-xl font-black text-[10px] uppercase tracking-[0.3em] bg-background border-primary/20 text-primary">
                    <Lock size={14} className="mr-2" /> AUDIT SECURE
                </Badge>
            </header>

            <main className="max-w-5xl mx-auto w-full p-6 md:p-10 lg:p-12 space-y-12 pb-40 flex-1">
                <section className="flex flex-col md:flex-row gap-10 items-center bg-[#1c2434] rounded-[3rem] p-10 text-white shadow-2xl relative overflow-hidden group">
                    <div className="absolute top-0 right-0 p-10 opacity-10 rotate-12 transition-transform [transition-duration:2000ms] group-hover:rotate-0">
                        <ShieldCheck size={320} />
                    </div>
                    <div className="relative z-10 flex-1 space-y-6">
                        <div className="size-14 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center border border-white/10 shadow-xl">
                            <Briefcase size={28} className="text-primary animate-pulse" />
                        </div>
                        <div className="space-y-3">
                            <h2 className="text-4xl md:text-5xl font-black uppercase tracking-tighter leading-none">Legal & <br/> Registration Manifest</h2>
                            <p className="text-sm font-medium text-white/60 max-w-md leading-relaxed uppercase tracking-widest opacity-80">
                                Upload your commercial registry, transport license, and tax identifiers. All data is end-to-end encrypted and shared only with the ecosystem administrators.
                            </p>
                        </div>
                    </div>
                </section>

                <section className="space-y-10">
                    <div className="flex justify-between items-end px-1">
                        <div className="space-y-1">
                            <h3 className="text-2xl font-black uppercase tracking-tighter">Document Vault</h3>
                            <p className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em] opacity-60">Verified Credentials Grid</p>
                        </div>
                        <Label className={cn(
                            "h-14 px-8 rounded-2xl bg-primary text-white font-black uppercase tracking-widest text-[10px] shadow-xl shadow-primary/20 transition-all flex items-center justify-center gap-3 cursor-pointer group active:scale-95",
                            isUploading && "opacity-60 cursor-not-allowed"
                        )}>
                            <input type="file" className="sr-only" onChange={handleFileUpload} accept="image/*,application/pdf" disabled={isUploading} />
                            {isUploading ? <Loader2 className="animate-spin size-4" /> : <UploadCloud size={40} className="size-4 group-hover:scale-110 transition-transform" />}
                            <span>{isUploading ? 'SYNCHRONIZING...' : 'UPLOAD PROOF'}</span>
                        </Label>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                        {documents.map((url, index) => (
                            <div key={index} className="group relative aspect-[4/3] rounded-[2.5rem] bg-card border border-border/50 overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-700">
                                <Image 
                                    src={url} 
                                    alt="Credential Proof" 
                                    fill 
                                    className="object-cover opacity-80 group-hover:opacity-100 transition-all duration-1000 group-hover:scale-110"
                                    unoptimized={url.startsWith('blob:')}
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                                
                                <div className="absolute bottom-6 left-6 right-6 flex items-center justify-between translate-y-4 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-700">
                                    <Badge className="bg-background/90 backdrop-blur-md text-foreground border-none font-black text-[8px] uppercase px-3 h-7 shadow-xl flex items-center gap-1.5">
                                        <ShieldCheck size={12} className="text-primary" /> Node Verified
                                    </Badge>
                                    <button 
                                        onClick={() => handleRemoveDoc(url)}
                                        className="size-10 rounded-xl bg-destructive text-white flex items-center justify-center shadow-xl active:scale-90 transition-transform hover:bg-red-600"
                                    >
                                        <Trash2 size={18} />
                                    </button>
                                </div>
                            </div>
                        ))}

                        {documents.length === 0 && !isUploading && (
                            <div className="col-span-full py-32 text-center flex flex-col items-center gap-6 opacity-30 grayscale border-2 border-dashed border-border/50 rounded-[3.5rem] bg-secondary/10 group hover:opacity-50 transition-all duration-700">
                                <div className="relative">
                                    <FileText size={64} className="text-muted-foreground relative z-10" />
                                    <div className="absolute inset-0 bg-primary/10 rounded-full blur-3xl group-hover:bg-primary/20 transition-all"></div>
                                </div>
                                <div className="space-y-2">
                                    <h3 className="text-2xl font-black uppercase tracking-tighter text-foreground">Registry Empty</h3>
                                    <p className="text-xs font-bold text-muted-foreground uppercase tracking-widest max-w-xs mx-auto leading-relaxed px-8">
                                        No legal documents found in your organizational node. Upload proof of registration to activate your verification status.
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>
                </section>

                <div className="p-10 rounded-[3.5rem] bg-secondary/30 border border-border/50 text-center space-y-6 shadow-inner">
                    <div className="size-16 rounded-3xl bg-background mx-auto flex items-center justify-center border border-border shadow-sm text-primary">
                        <ShieldCheck size={32} />
                    </div>
                    <div className="space-y-2 px-10">
                        <h4 className="text-lg font-black uppercase tracking-tighter leading-none">Security Protocol Active</h4>
                        <p className="text-[10px] text-muted-foreground font-medium leading-relaxed uppercase tracking-widest opacity-60">
                            The EataLyft national grid requires verified documentation for all carrier partners. Your data is stored in encrypted nodes and processed only for regulatory compliance.
                        </p>
                    </div>
                </div>
            </main>
            
            <nav className="fixed bottom-6 left-6 right-6 z-50 bg-background/80 backdrop-blur-2xl border border-border/50 rounded-full px-6 py-3 flex items-center justify-center gap-6 shadow-2xl max-w-sm mx-auto">
                <ShieldCheck size={16} className="text-primary" />
                <span className="text-[9px] font-black uppercase tracking-[0.4em] text-muted-foreground">Node Sync: AM-04-CM</span>
            </nav>
        </div>
    );
}
