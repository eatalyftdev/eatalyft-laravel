'use client';

/**
 * Agency Portal → Sub-Agent Management
 *
 * Lets an agency manager (role: agency_manager) view, create, suspend, and
 * remove the sub-agents that are tied to *their* agency only. Mirrors the
 * admin-side page at /admin/management/sub-agents but is scoped to the
 * current manager's agency.
 *
 * Auth model:
 *   - Manager UID is the agency ID (legacy pattern), OR
 *   - Manager has `managedAgencyId` on their user doc.
 *
 * The same /api/agency/create-sub-manager endpoint is reused — it already
 * supports agency_manager callers.
 */

import React, { useEffect, useMemo, useState } from 'react';
import Link from 'next/link';
import { collection, doc, query, where, updateDoc, deleteDoc, serverTimestamp, getDoc } from 'firebase/firestore';
import { useFirestore, useUser, useAuth, useCollection, useMemoFirebase } from '@/firebase';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription,
} from '@/components/ui/dialog';
import LoadingSpinner from '@/components/loading-spinner';

import { format } from 'date-fns';
import { cn } from '@/lib/utils';
import {
  ArrowLeft, Plus, Users, Building2, Phone, Mail, Search, Trash2,
  ShieldCheck, Lock, Unlock, QrCode, Zap, MapPin, UserCheck,
} from 'lucide-react';

type SubAgent = {
  id: string;
  name?: string;
  displayName?: string;
  email?: string;
  phoneNumber?: string;
  stationName?: string;
  managedAgencyId?: string;
  role: string;
  status?: 'active' | 'suspended' | 'inactive';
  createdAt?: any;
};

type CreateForm = {
  name: string;
  email: string;
  password: string;
  phoneNumber: string;
  stationName: string;
};
const blankCreate: CreateForm = { name: '', email: '', password: '', phoneNumber: '', stationName: '' };

export default function AgencySubAgentsPage() {
  const firestore = useFirestore();
  const { user } = useUser();
  const auth = useAuth();
  const { toast } = useToast();

  // Resolve the agency ID for the current manager (uid OR managedAgencyId).
  const [agencyId, setAgencyId] = useState<string | null>(null);
  const [agencyResolving, setAgencyResolving] = useState(true);

  useEffect(() => {
    if (!user || !firestore) return;
    let alive = true;
    (async () => {
      setAgencyResolving(true);
      try {
        // Agency managers use their own uid; sub_agents use their managedAgencyId
        const subSnap = await getDoc(doc(firestore, 'sub_agents', user.uid));
        const resolved = subSnap.exists()
          ? (subSnap.data()?.managedAgencyId || user.uid)
          : user.uid;
        if (alive) setAgencyId(resolved as string);
      } finally {
        if (alive) setAgencyResolving(false);
      }
    })();
    return () => { alive = false; };
  }, [user, firestore]);

  // Query sub-agents tied to this agency
  const subAgentsRef = useMemoFirebase(() => {
    if (!firestore || !agencyId) return null;
    return query(
      collection(firestore, 'sub_agents'),
      where('managedAgencyId', '==', agencyId)
    );
  }, [firestore, agencyId]);
  const { data: rawAgents, isLoading } = useCollection<SubAgent>(subAgentsRef);
  const subAgents = useMemo(() => (rawAgents || []).filter(a => a.id !== '_init'), [rawAgents]);

  // Filters
  const [search, setSearch] = useState('');
  const filtered = useMemo(() => subAgents.filter(a => {
    if (!search) return true;
    const s = search.toLowerCase();
    return (
      (a.name || a.displayName || '').toLowerCase().includes(s) ||
      (a.email || '').toLowerCase().includes(s) ||
      (a.stationName || '').toLowerCase().includes(s)
    );
  }), [subAgents, search]);

  const stats = useMemo(() => {
    const total = subAgents.length;
    const active = subAgents.filter(a => a.status !== 'suspended').length;
    const suspended = total - active;
    const stations = new Set(subAgents.map(a => a.stationName).filter(Boolean)).size;
    return { total, active, suspended, stations };
  }, [subAgents]);

  // Per-sub-agent ticket revenue (tickets stamped with subAgentId)
  const ticketsRef = useMemoFirebase(() => {
    if (!firestore || !agencyId) return null;
    return query(collection(firestore, 'bus'), where('agencyId', '==', agencyId));
  }, [firestore, agencyId]);
  const { data: tickets } = useCollection<any>(ticketsRef);

  const perAgentTotals = useMemo(() => {
    const map: Record<string, { tickets: number; earnings: number }> = {};
    (tickets || []).forEach((t: any) => {
      if (!t?.subAgentId) return;
      if (!map[t.subAgentId]) map[t.subAgentId] = { tickets: 0, earnings: 0 };
      map[t.subAgentId].tickets += 1;
      map[t.subAgentId].earnings += Number(t.subAgentEarning || 0);
    });
    return map;
  }, [tickets]);

  // Create dialog state
  const [isCreateOpen, setIsCreateOpen] = useState(false);
  const [createForm, setCreateForm] = useState<CreateForm>(blankCreate);
  const [isCreating, setIsCreating] = useState(false);
  const [actionId, setActionId] = useState<string | null>(null);

  const onChange = (k: keyof CreateForm, v: string) => setCreateForm(p => ({ ...p, [k]: v }));

  const handleCreate = async () => {
    if (!auth?.currentUser || !agencyId) return;
    if (!createForm.name || !createForm.email || !createForm.password || !createForm.stationName) {
      toast({ variant: 'destructive', title: 'Missing fields', description: 'Name, email, password, and station are required.' });
      return;
    }
    setIsCreating(true);
    try {
      const idToken = await auth.currentUser.getIdToken();
      const res = await fetch('/api/agency/create-sub-manager', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${idToken}` },
        body: JSON.stringify({ ...createForm, agencyId }),
      });
      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.error || 'Create failed');
      toast({ title: 'Sub-Agent Created', description: `${createForm.name} can now log in and sell tickets.` });
      setCreateForm(blankCreate);
      setIsCreateOpen(false);
    } catch (e: any) {
      toast({ variant: 'destructive', title: 'Creation Failed', description: e.message });
    } finally { setIsCreating(false); }
  };

  const handleToggle = async (a: SubAgent, suspend: boolean) => {
    if (!firestore) return;
    setActionId(a.id);
    try {
      await updateDoc(doc(firestore, 'sub_agents', a.id), {
        status: suspend ? 'suspended' : 'active',
        updatedAt: serverTimestamp(),
      });
      toast({ title: suspend ? 'Suspended' : 'Reactivated', description: `${a.name || a.email} ${suspend ? 'can no longer sell or scan' : 'is active again'}.` });
    } catch (e: any) {
      toast({ variant: 'destructive', title: 'Error', description: e.message });
    } finally { setActionId(null); }
  };

  const handleDelete = async (a: SubAgent) => {
    if (!firestore) return;
    if (!confirm(`Permanently remove ${a.name || a.email}?`)) return;
    setActionId(a.id);
    try {
      await deleteDoc(doc(firestore, 'sub_agents', a.id));
      toast({ title: 'Removed', description: `${a.name || a.email} has been removed.` });
    } catch (e: any) {
      toast({ variant: 'destructive', title: 'Error', description: e.message });
    } finally { setActionId(null); }
  };

  if (agencyResolving) return <div className="p-20 flex justify-center"><LoadingSpinner /></div>;

  return (
    <div className="min-h-dvh pb-32">
      <div className="container max-w-6xl mx-auto p-6 lg:p-10 space-y-8 animate-in fade-in duration-700">
        {/* Header */}
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div className="flex items-center gap-4">
            <Link href="/agency/dashboard">
              <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50">
                <ArrowLeft size={18} />
              </Button>
            </Link>
            <div className="size-14 rounded-[1.5rem] bg-primary/10 flex items-center justify-center text-primary shadow-xl shadow-primary/5 border border-primary/10">
              <Users size={28} />
            </div>
            <div>
              <h2 className="text-3xl font-black uppercase tracking-tighter">My Sub-Agents</h2>
              <p className="text-xs font-bold uppercase tracking-widest mt-1 opacity-60">Station managers selling tickets under your agency</p>
            </div>
          </div>
          <Button onClick={() => setIsCreateOpen(true)} className="h-12 px-8 rounded-2xl bg-primary hover:bg-red-600 text-white font-black uppercase tracking-widest text-sm shadow-xl shadow-primary/20 gap-3 transition-all active:scale-[0.98]">
            <Plus size={18} /> Add Sub-Agent
          </Button>
        </div>

        {/* KPIs */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {[
            { label: 'Sub-Agents', value: stats.total, color: 'text-foreground', bg: 'bg-secondary/50' },
            { label: 'Active', value: stats.active, color: 'text-emerald-600', bg: 'bg-emerald-500/10' },
            { label: 'Suspended', value: stats.suspended, color: 'text-red-600', bg: 'bg-red-500/10' },
            { label: 'Stations Covered', value: stats.stations, color: 'text-primary', bg: 'bg-primary/10' },
          ].map(s => (
            <div key={s.label} className={cn('rounded-2xl p-5 border border-border/50', s.bg)}>
              <p className={cn('text-3xl font-black tracking-tighter leading-none', s.color)}>{s.value}</p>
              <p className="text-[9px] font-black uppercase tracking-widest text-muted-foreground mt-2">{s.label}</p>
            </div>
          ))}
        </div>

        {/* Search */}
        <div className="relative">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
          <Input
            placeholder="Search by name, email, or station..."
            value={search} onChange={e => setSearch(e.target.value)}
            className="pl-9 h-11 rounded-xl bg-card border-border/50 text-sm font-medium"
          />
        </div>

        {/* Table */}
        <Card className="border-none shadow-sm bg-card overflow-hidden rounded-[2.5rem]">
          <CardHeader className="bg-secondary/30 border-b border-border/10 pb-6 px-8">
            <CardTitle className="text-xl font-black uppercase tracking-tight flex items-center gap-3">
              <UserCheck size={20} className="text-primary" /> Roster ({filtered.length})
            </CardTitle>
          </CardHeader>
          <CardContent className="p-0">
            {isLoading ? (
              <div className="p-20 flex justify-center"><LoadingSpinner /></div>
            ) : filtered.length === 0 ? (
              <div className="py-24 flex flex-col items-center gap-4 opacity-30 px-6">
                <Users size={48} />
                <p className="text-xs font-black uppercase tracking-widest">No sub-agents yet</p>
                <p className="text-[10px] text-muted-foreground text-center max-w-xs">Tap "Add Sub-Agent" to onboard a station manager — they'll log in via the agency portal and start selling tickets.</p>
              </div>
            ) : (
              <div className="divide-y divide-border/30">
                {filtered.map(a => {
                  const name = a.name || a.displayName || 'Unknown';
                  const isSuspended = a.status === 'suspended';
                  const totals = perAgentTotals[a.id] || { tickets: 0, earnings: 0 };
                  const createdAt = a.createdAt?.toDate ? a.createdAt.toDate() : null;
                  return (
                    <div key={a.id} className="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-5 hover:bg-secondary/20 transition-all">
                      <div className={cn(
                        'size-12 rounded-2xl flex items-center justify-center shrink-0 font-black text-base border',
                        isSuspended
                          ? 'bg-red-500/10 text-red-500 border-red-500/20'
                          : 'bg-gradient-to-br from-primary/20 to-primary/10 text-primary border-primary/20'
                      )}>{name.charAt(0).toUpperCase()}</div>

                      <div className="flex-1 min-w-0">
                        <div className="flex flex-wrap items-center gap-2 mb-1">
                          <span className="font-black text-sm">{name}</span>
                          <Badge className={cn('text-[8px] font-black uppercase h-5 px-2 border-none', isSuspended ? 'bg-red-500/15 text-red-600' : 'bg-emerald-500/15 text-emerald-600')}>
                            {isSuspended ? <Lock size={8} className="mr-1" /> : <QrCode size={8} className="mr-1" />}
                            {isSuspended ? 'Suspended' : 'Active'}
                          </Badge>
                        </div>
                        <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-[10px] text-muted-foreground font-bold">
                          {a.email && <span className="flex items-center gap-1"><Mail size={9} />{a.email}</span>}
                          {a.phoneNumber && <span className="flex items-center gap-1"><Phone size={9} />{a.phoneNumber}</span>}
                          {a.stationName && <span className="flex items-center gap-1 text-primary font-black"><MapPin size={9} />{a.stationName}</span>}
                          {createdAt && <span className="opacity-50">Joined {format(createdAt, 'MMM d, yyyy')}</span>}
                        </div>
                      </div>

                      <div className="flex items-center gap-4 shrink-0">
                        <div className="text-right">
                          <p className="text-base font-black tabular-nums">{totals.tickets}</p>
                          <p className="text-[8px] font-black uppercase tracking-widest text-muted-foreground">Tickets</p>
                        </div>
                        <div className="text-right">
                          <p className="text-base font-black tabular-nums text-emerald-600">{totals.earnings.toLocaleString()}</p>
                          <p className="text-[8px] font-black uppercase tracking-widest text-muted-foreground">XAF Earned</p>
                        </div>
                        <div className="flex items-center gap-2">
                          <Button
                            size="sm" variant="outline" disabled={actionId === a.id}
                            onClick={() => handleToggle(a, !isSuspended)}
                            className={cn('h-8 px-3 rounded-xl text-[9px] font-black uppercase', isSuspended ? 'text-emerald-600 border-emerald-500/30 hover:bg-emerald-500/10' : 'text-amber-600 border-amber-500/30 hover:bg-amber-500/10')}
                          >
                            {isSuspended ? <><Unlock size={10} className="mr-1" />Activate</> : <><Lock size={10} className="mr-1" />Suspend</>}
                          </Button>
                          <Button
                            size="sm" variant="ghost" disabled={actionId === a.id}
                            onClick={() => handleDelete(a)}
                            className="h-8 w-8 p-0 rounded-xl text-red-500 hover:bg-red-500/10"
                          >
                            <Trash2 size={14} />
                          </Button>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Hint */}
        <div className="flex items-start gap-4 p-5 rounded-2xl bg-primary/5 border border-primary/20">
          <div className="size-9 rounded-xl bg-primary/15 flex items-center justify-center text-primary shrink-0"><ShieldCheck size={18} /></div>
          <div>
            <p className="text-xs font-black uppercase tracking-widest text-foreground">Sub-agents earn commission per ticket</p>
            <p className="text-[10px] text-muted-foreground mt-1 font-bold leading-relaxed">
              Their per-ticket commission is set centrally by the platform's pricing engine and is paid out of your agency's share automatically when they book a ticket. Suspending an account immediately disables ticket sales and QR scanning.
            </p>
          </div>
        </div>
      </div>

      {/* Create dialog */}
      <Dialog open={isCreateOpen} onOpenChange={setIsCreateOpen}>
        <DialogContent className="max-w-lg rounded-[2.5rem] p-0 overflow-hidden border-none shadow-2xl">
          <DialogHeader className="p-8 bg-secondary/30 border-b border-border/10">
            <DialogTitle className="text-2xl font-black uppercase tracking-tighter flex items-center gap-3">
              <Plus className="text-primary size-6" /> Add Sub-Agent
            </DialogTitle>
            <DialogDescription className="text-xs font-bold text-muted-foreground uppercase tracking-widest mt-1">
              Create a new station manager. They'll log in at the agency portal.
            </DialogDescription>
          </DialogHeader>
          <div className="p-8 space-y-5 max-h-[60vh] overflow-y-auto no-scrollbar">
            <div className="space-y-2">
              <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Full Name *</Label>
              <Input value={createForm.name} onChange={e => onChange('name', e.target.value)} placeholder="Sub-agent full name" className="h-12 bg-secondary/50 border-none rounded-xl font-bold" />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Email *</Label>
                <Input type="email" value={createForm.email} onChange={e => onChange('email', e.target.value)} placeholder="agent@email.com" className="h-12 bg-secondary/50 border-none rounded-xl font-bold" />
              </div>
              <div className="space-y-2">
                <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Password *</Label>
                <Input type="password" value={createForm.password} onChange={e => onChange('password', e.target.value)} placeholder="Min. 6 characters" className="h-12 bg-secondary/50 border-none rounded-xl font-bold" />
              </div>
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Station Name *</Label>
                <Input value={createForm.stationName} onChange={e => onChange('stationName', e.target.value)} placeholder="e.g. Douala Nord" className="h-12 bg-secondary/50 border-none rounded-xl font-bold" />
              </div>
              <div className="space-y-2">
                <Label className="text-[10px] font-black uppercase tracking-widest text-muted-foreground">Phone</Label>
                <Input value={createForm.phoneNumber} onChange={e => onChange('phoneNumber', e.target.value)} placeholder="+237 6XX XXX XXX" className="h-12 bg-secondary/50 border-none rounded-xl font-bold" />
              </div>
            </div>
            <div className="flex items-start gap-3 p-4 rounded-2xl bg-primary/5 border border-primary/20">
              <Building2 size={16} className="text-primary shrink-0 mt-0.5" />
              <p className="text-[10px] font-bold text-muted-foreground leading-relaxed">
                The new sub-agent will be linked to your agency. They can sell tickets and scan boarding passes from the agency portal.
              </p>
            </div>
            <Button onClick={handleCreate} disabled={isCreating} className="w-full h-14 rounded-2xl bg-primary hover:bg-red-600 text-white font-black uppercase tracking-widest text-sm shadow-xl shadow-primary/20 transition-all active:scale-[0.98]">
              {isCreating ? <><Zap size={16} className="animate-pulse mr-2" /> Creating…</> : 'Create Sub-Agent'}
            </Button>
          </div>
        </DialogContent>
      </Dialog>

      
    </div>
  );
}
