'use client';

/**
 * @fileOverview Agency Financial Ledger Terminal.
 * High-fidelity financial node synchronized with EataPay Core and Mission Manifests.
 * 
 * - Real-time Wallet & Points Sync.
 * - Mission Analytics visualization.
 * - Payout Protocol Integration.
 */

import React, { useMemo, useState, useEffect } from 'react';
import { useUser, useDoc, useCollection, useFirestore, useMemoFirebase } from '@/firebase';
import LoadingSpinner from '@/components/loading-spinner';
import { useRouter } from 'next/navigation';
import { doc, collection, query, orderBy, limit, where, getDoc } from 'firebase/firestore';
import { Button } from '@/components/ui/button';
import {
    ArrowLeft,
    TrendingUp,
    Banknote,
    Bolt,
    ShieldCheck,
    Receipt,
    Star,
    Sun,
    Moon,
    Filter,
    Search
} from 'lucide-react';
import { format, startOfDay } from 'date-fns';
import TransactionLedger from '@/components/shared/transaction-ledger';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';

type WalletData = {
    balance: number;
    points: number;
};

type Transaction = {
    id: string;
    amount: number;
    type: 'credit' | 'debit' | 'bonus' | 'fee' | 'payout' | 'payment';
    details: string;
    transactionDate: any;
    serviceType?: string;
    status: string;
};

const TransactionRow = ({ tx }: { tx: Transaction }) => {
    const isCredit = tx.type === 'credit' || tx.type === 'bonus' || tx.type === 'payment';
    const dateVal = tx.transactionDate?.toDate ? tx.transactionDate.toDate() : (tx.transactionDate?.seconds ? new Date(tx.transactionDate.seconds * 1000) : new Date());

    return (
        <div className="flex items-center gap-4 rounded-3xl bg-card border border-border/50 p-4 transition-all duration-300 hover:border-primary/30 hover:shadow-md active:scale-[0.99]">
            <div className={cn(
                "flex size-12 shrink-0 items-center justify-center rounded-2xl border",
                isCredit ? "bg-green-500/10 text-green-600 dark:text-green-400 border-green-500/20" : "bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20"
            )}>
                {tx.type === 'payout' ? <Banknote size={22} aria-hidden="true" /> : <Receipt size={22} aria-hidden="true" />}
            </div>
            <div className="flex-1 min-w-0">
                <p className="truncate text-sm font-black uppercase tracking-tight text-foreground">{tx.details || tx.serviceType || 'Mission Settlement'}</p>
                <p className="mt-0.5 text-[9px] font-bold uppercase tracking-widest text-muted-foreground opacity-70">
                    {format(dateVal, 'MMM d, p')}
                </p>
            </div>
            <div className="shrink-0 text-right">
                <p className={cn("text-base font-black tracking-tighter tabular-nums", isCredit ? "text-green-600 dark:text-green-400" : "text-foreground")}>
                    {isCredit ? '+' : '-'}₣{Math.floor(tx.amount).toLocaleString()}
                </p>
                <Badge variant="outline" className={cn(
                    "mt-1 border-none px-1.5 h-4 text-[8px] font-black uppercase tracking-widest",
                    tx.status === 'completed' ? "text-green-600 dark:text-green-400" : "text-orange-600 dark:text-orange-400"
                )}>{tx.status || 'VERIFIED'}</Badge>
            </div>
        </div>
    );
};

export default function AgencyWalletPage() {
  const { user, isUserLoading } = useUser();
  const router = useRouter();
  const firestore = useFirestore();
  const [isDark, setIsDark] = useState(false);
  const [resolvedAgencyId, setResolvedAgencyId] = useState<string | null>(null);

  useEffect(() => {
    const theme = localStorage.getItem('theme') || 'light';
    setIsDark(theme === 'dark');
  }, []);

  const toggleTheme = () => {
    const newTheme = !isDark;
    setIsDark(newTheme);
    localStorage.setItem('theme', newTheme ? 'dark' : 'light');
    if (newTheme) document.documentElement.classList.add('dark');
    else document.documentElement.classList.remove('dark');
  };

  // Resolve the Agency ID (Owner UID) even if the user is a sub-agent
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

  const walletRef = useMemoFirebase(() => (firestore && resolvedAgencyId) ? doc(firestore, 'wallets', resolvedAgencyId) : null, [firestore, resolvedAgencyId]);
  const transactionsQuery = useMemoFirebase(() => (firestore && resolvedAgencyId) ? query(collection(firestore, 'agencies', resolvedAgencyId, 'transactions'), orderBy('transactionDate', 'desc'), limit(20)) : null, [firestore, resolvedAgencyId]);
  
  const { data: wallet, isLoading: isWalletLoading } = useDoc<WalletData>(walletRef);
  const { data: transactions, isLoading: areTransactionsLoading } = useCollection<Transaction>(transactionsQuery);

  const earningsToday = useMemo(() => {
      if (!transactions) return 0;
      const today = startOfDay(new Date());
      return transactions
        .filter(tx => {
            const date = tx.transactionDate?.toDate ? tx.transactionDate.toDate() : (tx.transactionDate?.seconds ? new Date(tx.transactionDate.seconds * 1000) : new Date(tx.transactionDate));
            return date >= today && (tx.type === 'credit' || tx.type === 'payment');
        })
        .reduce((sum, tx) => sum + tx.amount, 0);
  }, [transactions]);

  if (isUserLoading || isWalletLoading || areTransactionsLoading) return <LoadingSpinner />;
  if (!user) return null;

  return (
    <div className="min-h-screen w-full bg-background text-foreground font-display selection:bg-primary selection:text-white">
        <main className="flex flex-col relative overflow-x-hidden pb-32">
            <header className="sticky top-0 z-50 bg-background/80 backdrop-blur-xl border-b border-border/50 px-6 py-4 flex items-center justify-between shadow-sm">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50 lg:hidden" onClick={() => router.back()}>
                        <ArrowLeft size={20} />
                    </Button>
                    <div className="flex flex-col">
                        <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">EataPay Agency</span>
                        <h1 className="text-xl font-black uppercase tracking-tighter leading-none text-foreground">Earnings Ledger</h1>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50 hover:bg-secondary transition-all" onClick={toggleTheme}>
                        {isDark ? <Sun size={20} /> : <Moon size={20} />}
                    </Button>
                    <Badge variant="outline" className="hidden sm:flex h-10 px-4 rounded-xl border-primary/20 text-primary font-black uppercase text-[10px] bg-primary/5">
                        <ShieldCheck size={14} className="mr-2" /> Secure Hub Node
                    </Badge>
                </div>
            </header>

            <div className="max-w-5xl mx-auto w-full p-4 sm:p-6 md:p-10 space-y-8 md:space-y-10">

                {/* Liquidity hero */}
                <section className="overflow-hidden rounded-[2rem] bg-[#151d2b] text-white shadow-2xl relative">
                    <div className="absolute -right-16 -top-16 size-64 rounded-full bg-primary/20 blur-[80px]" aria-hidden="true" />
                    <div className="absolute -left-10 -bottom-20 size-52 rounded-full bg-primary/10 blur-[70px]" aria-hidden="true" />
                    <div className="relative p-6 sm:p-8 md:p-10">
                        <div className="flex items-start justify-between gap-4">
                            <div className="min-w-0">
                                <p className="text-[10px] font-black uppercase tracking-[0.3em] text-white/50">Available Hub Liquidity</p>
                                <p className="mt-2 flex items-baseline gap-2">
                                    <span className="text-2xl font-black italic text-primary">₣</span>
                                    <span className="truncate text-5xl sm:text-6xl font-black tracking-tighter tabular-nums">
                                        {Math.floor(wallet?.balance ?? 0).toLocaleString()}
                                    </span>
                                </p>
                                <p className="mt-2 text-[9px] font-bold uppercase tracking-[0.25em] text-white/40">Whole XAF • EataPay Agency</p>
                            </div>
                            <div className="flex shrink-0 items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-3 py-2 backdrop-blur-md">
                                <Star size={15} className="fill-current text-amber-400" aria-hidden="true" />
                                <span className="text-sm font-black tabular-nums">{(wallet?.points ?? 0).toLocaleString()}</span>
                                <span className="hidden text-[8px] font-black uppercase tracking-widest text-white/40 sm:inline">pts</span>
                            </div>
                        </div>

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Button
                                onClick={() => router.push('/payout/request')}
                                disabled={!wallet?.balance || wallet.balance < 500}
                                className="h-14 flex-1 rounded-2xl bg-primary px-8 text-[11px] font-black uppercase tracking-[0.2em] text-white shadow-xl shadow-primary/30 transition-all hover:bg-red-600 active:scale-[0.98] disabled:opacity-40 sm:flex-none sm:px-10"
                            >
                                <Bolt size={17} className="mr-2" aria-hidden="true" />
                                Process Hub Payout
                            </Button>
                            <p className="text-center text-[9px] font-bold uppercase tracking-widest text-white/35 sm:text-left">
                                Minimum payout ₣500
                            </p>
                        </div>
                    </div>
                </section>

                {/* Live stats */}
                <section className="grid grid-cols-2 gap-4 md:gap-6" aria-label="Ledger statistics">
                    <div className="rounded-[1.75rem] border border-border/50 bg-card p-5 sm:p-6 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <TrendingUp size={20} aria-hidden="true" />
                            </span>
                            <Badge variant="outline" className="border-none bg-green-500/10 px-2 py-0.5 text-[8px] font-black uppercase text-green-600 dark:text-green-400">Live</Badge>
                        </div>
                        <p className="mt-4 text-2xl sm:text-3xl font-black tracking-tighter tabular-nums">₣{Math.floor(earningsToday).toLocaleString()}</p>
                        <p className="mt-1 text-[9px] font-bold uppercase tracking-widest text-muted-foreground">Ticket yield • today</p>
                    </div>
                    <div className="rounded-[1.75rem] border border-border/50 bg-card p-5 sm:p-6 shadow-sm">
                        <div className="flex items-center justify-between">
                            <span className="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <Receipt size={20} aria-hidden="true" />
                            </span>
                            <span className="size-2 rounded-full bg-green-500 animate-pulse" aria-hidden="true" />
                        </div>
                        <p className="mt-4 text-2xl sm:text-3xl font-black tracking-tighter tabular-nums">{transactions?.length ?? 0}</p>
                        <p className="mt-1 text-[9px] font-bold uppercase tracking-widest text-muted-foreground">Settlements • latest 20</p>
                    </div>
                </section>

                {/* Settlement ledger */}
                <section className="space-y-5" aria-label="Settlement ledger">
                    <div className="flex items-center justify-between px-1">
                        <div>
                            <h2 className="text-2xl sm:text-3xl font-black uppercase tracking-tighter">Settlement Ledger</h2>
                            <p className="mt-1 text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground opacity-70">Verified manifest history</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button variant="ghost" size="icon" aria-label="Filter ledger" className="rounded-xl size-11 bg-secondary/50"><Filter size={18} aria-hidden="true" /></Button>
                            <Button variant="ghost" size="icon" aria-label="Search ledger" className="rounded-xl size-11 bg-secondary/50"><Search size={18} aria-hidden="true" /></Button>
                        </div>
                    </div>

                    <TransactionLedger uid={user!.uid} />
                </section>

                <div className="flex items-center gap-4 rounded-[2rem] border border-border/50 bg-secondary/30 p-5 sm:p-6">
                    <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-background border border-border text-primary shadow-sm">
                        <ShieldCheck size={22} aria-hidden="true" />
                    </span>
                    <div className="min-w-0">
                        <h3 className="text-sm font-black uppercase tracking-tighter">EataPay Secure Sync</h3>
                        <p className="mt-1 text-[9px] font-medium uppercase tracking-widest text-muted-foreground opacity-70 leading-relaxed">
                            Settlements sync with the platform ledger. Payouts are protected by multi-factor audit nodes.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
  );
}
