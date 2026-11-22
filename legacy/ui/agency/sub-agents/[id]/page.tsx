'use client';

/**
 * Agency Portal → Sub-agent detail.
 *
 * Trips sold on, 30-day sales, activity logs, edit + suspend. Scoped
 * server-side: managers only ever see agents inside their own agency.
 */
import React, { useCallback, useEffect, useState } from 'react';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import { useAuth } from '@/firebase';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import LoadingSpinner from '@/components/loading-spinner';

import { ArrowLeft } from 'lucide-react';

type Detail = {
  agent: { uid: string; name: string; email: string; phoneNumber: string; stationName: string; active: boolean; agencyId: string };
  sales30d: { tickets: number; gross: number; boarded: number; wallet: number; cash: number };
  trips: Array<{ tripId: string; count: number; revenue: number; from: string; to: string }>;
  logs: Array<{ id: string; action: string | null; timestamp: unknown }>;
};

export default function SubAgentDetailPage() {
  const params = useParams<{ id: string }>();
  const id = params.id;
  const auth = useAuth();
  const { toast } = useToast();
  const [detail, setDetail] = useState<Detail | null>(null);
  const [loading, setLoading] = useState(true);
  const [name, setName] = useState('');
  const [phoneNumber, setPhoneNumber] = useState('');
  const [stationName, setStationName] = useState('');
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    if (!auth?.currentUser || !id) return;
    setLoading(true);
    try {
      const token = await auth.currentUser.getIdToken();
      const res = await fetch(`/api/agency/sub-agents/${encodeURIComponent(id)}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const j = await res.json();
      if (!res.ok || !j.success) throw new Error(j.error || 'Load failed');
      setDetail(j);
      setName(j.agent.name || '');
      setPhoneNumber(j.agent.phoneNumber || '');
      setStationName(j.agent.stationName || '');
    } catch (e) {
      toast({ variant: 'destructive', title: 'Load failed', description: e instanceof Error ? e.message : 'Unknown error' });
    } finally {
      setLoading(false);
    }
  }, [auth, id, toast]);

  useEffect(() => {
    load().catch(() => {});
  }, [load]);

  const save = async () => {
    if (!auth?.currentUser) return;
    setSaving(true);
    try {
      const token = await auth.currentUser.getIdToken();
      const res = await fetch(`/api/agency/sub-agents/${encodeURIComponent(id)}`, {
        method: 'PATCH',
        headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, phoneNumber, stationName }),
      });
      const j = await res.json();
      if (!res.ok || !j.success) throw new Error(j.error || 'Save failed');
      toast({ title: 'Sub-agent updated' });
      await load();
    } catch (e) {
      toast({ variant: 'destructive', title: 'Save failed', description: e instanceof Error ? e.message : 'Unknown error' });
    } finally {
      setSaving(false);
    }
  };

  const toggleActive = async () => {
    if (!auth?.currentUser || !detail) return;
    const action = detail.agent.active ? 'suspend' : 'reactivate';
    if (!window.confirm(`Confirm ${action} of ${detail.agent.name || id}?`)) return;
    setSaving(true);
    try {
      const token = await auth.currentUser.getIdToken();
      const url = `/api/agency/sub-agents/${encodeURIComponent(id)}`;
      const res = detail.agent.active
        ? await fetch(url, { method: 'DELETE', headers: { Authorization: `Bearer ${token}` } })
        : await fetch(url, {
            method: 'PATCH',
            headers: { Authorization: `Bearer ${token}`, 'Content-Type': 'application/json' },
            body: JSON.stringify({ active: true }),
          });
      const j = await res.json();
      if (!res.ok || !j.success) throw new Error(j.error || 'Action failed');
      toast({ title: detail.agent.active ? 'Sub-agent suspended' : 'Sub-agent reactivated' });
      await load();
    } catch (e) {
      toast({ variant: 'destructive', title: 'Action failed', description: e instanceof Error ? e.message : 'Unknown error' });
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="min-h-screen pb-28">
      
      <main className="mx-auto max-w-5xl px-4 pt-6">
        <Link href="/agency/sub-agents" className="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground">
          <ArrowLeft size={14} /> All sub-agents
        </Link>
        {loading ? <div className="mt-8"><LoadingSpinner /></div> : detail && (
          <>
            <div className="mt-2 flex flex-wrap items-center gap-2">
              <h1 className="text-2xl font-bold">{detail.agent.name || detail.agent.uid}</h1>
              {detail.agent.active ? <Badge>active</Badge> : <Badge variant="destructive">suspended</Badge>}
            </div>
            <p className="text-sm text-muted-foreground">{detail.agent.email} · {detail.agent.stationName}</p>

            <div className="mt-4 grid gap-4 md:grid-cols-2">
              <Card>
                <CardHeader><CardTitle>30-day sales</CardTitle></CardHeader>
                <CardContent className="text-sm">
                  <p><strong>{detail.sales30d.tickets}</strong> tickets · <strong>{detail.sales30d.gross.toLocaleString()}</strong> XAF</p>
                  <p className="text-muted-foreground">wallet {detail.sales30d.wallet.toLocaleString()} · cash {detail.sales30d.cash.toLocaleString()} · boarded {detail.sales30d.boarded}</p>
                </CardContent>
              </Card>
              <Card>
                <CardHeader><CardTitle>Edit</CardTitle></CardHeader>
                <CardContent className="space-y-2">
                  <div><Label htmlFor="sa-name">Name</Label><Input id="sa-name" value={name} onChange={(e) => setName(e.target.value)} /></div>
                  <div><Label htmlFor="sa-phone">Phone</Label><Input id="sa-phone" value={phoneNumber} onChange={(e) => setPhoneNumber(e.target.value)} /></div>
                  <div><Label htmlFor="sa-station">Station</Label><Input id="sa-station" value={stationName} onChange={(e) => setStationName(e.target.value)} /></div>
                  <div className="flex gap-2 pt-1">
                    <Button onClick={save} disabled={saving}>Save</Button>
                    <Button variant={detail.agent.active ? 'destructive' : 'default'} onClick={toggleActive} disabled={saving}>
                      {detail.agent.active ? 'Suspend' : 'Reactivate'}
                    </Button>
                  </div>
                </CardContent>
              </Card>
            </div>

            <Card className="mt-4">
              <CardHeader><CardTitle>Trips sold on</CardTitle></CardHeader>
              <CardContent>
                {detail.trips.length === 0 ? <p className="text-sm text-muted-foreground">No sales in the last 30 days.</p> : (
                  <ul className="space-y-1 text-sm">
                    {detail.trips.map((t) => (
                      <li key={t.tripId}>{t.from} → {t.to} · {t.count} tickets · {t.revenue.toLocaleString()} XAF</li>
                    ))}
                  </ul>
                )}
              </CardContent>
            </Card>

            <Card className="mt-4">
              <CardHeader><CardTitle>Recent activity</CardTitle></CardHeader>
              <CardContent>
                {detail.logs.length === 0 ? <p className="text-sm text-muted-foreground">No logged activity.</p> : (
                  <ul className="space-y-1 text-sm">
                    {detail.logs.map((l) => (<li key={l.id}>{l.action || 'event'}</li>))}
                  </ul>
                )}
              </CardContent>
            </Card>
          </>
        )}
      </main>
    </div>
  );
}
