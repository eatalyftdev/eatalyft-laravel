'use client';

/**
 * Agency Portal → Daily Reconciliation.
 *
 * Sub-agent sales vs station verification for one day: tickets sold, gross,
 * wallet/cash split, boarded count, mismatch flags. Alerts surface first so
 * the manager can chase unboarded tickets and cash gaps the same day.
 */
import React, { useCallback, useEffect, useState } from 'react';
import { doc, getDoc } from 'firebase/firestore';
import { useAuth, useFirestore, useUser } from '@/firebase';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import LoadingSpinner from '@/components/loading-spinner';

import { AlertTriangle, CheckCircle2 } from 'lucide-react';

type SellerRow = {
  sellerId: string;
  sellerName: string | null;
  tickets: number;
  gross: number;
  wallet: number;
  cash: number;
  boarded: number;
  flag: 'ok' | 'unboarded' | 'cash_mismatch';
};

export default function AgencyReconciliationPage() {
  const firestore = useFirestore();
  const { user } = useUser();
  const auth = useAuth();
  const { toast } = useToast();
  const [agencyId, setAgencyId] = useState<string | null>(null);
  const [day, setDay] = useState(() => new Date().toISOString().slice(0, 10));
  const [sellers, setSellers] = useState<SellerRow[]>([]);
  const [alerts, setAlerts] = useState<SellerRow[]>([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!user || !firestore) return;
    getDoc(doc(firestore, 'sub_agents', user.uid)).then((s) => {
      setAgencyId(s.exists() ? ((s.data()?.managedAgencyId as string) || user.uid) : user.uid);
    });
  }, [user, firestore]);

  const load = useCallback(async () => {
    if (!agencyId || !auth?.currentUser) return;
    setLoading(true);
    try {
      const token = await auth.currentUser.getIdToken();
      const res = await fetch(
        `/api/agency/reconciliation?agencyId=${encodeURIComponent(agencyId)}&date=${encodeURIComponent(day)}`,
        { headers: { Authorization: `Bearer ${token}` } }
      );
      const j = await res.json();
      if (!res.ok || !j.success) throw new Error(j.error || 'Load failed');
      setSellers(j.sellers);
      setAlerts(j.alerts);
    } catch (e) {
      toast({ variant: 'destructive', title: 'Reconciliation failed', description: e instanceof Error ? e.message : 'Unknown error' });
    } finally {
      setLoading(false);
    }
  }, [agencyId, day, auth, toast]);

  useEffect(() => {
    load().catch(() => {});
  }, [load]);

  return (
    <div className="min-h-screen pb-28">
      
      <main className="mx-auto max-w-5xl px-4 pt-6">
        <h1 className="text-2xl font-bold">Daily reconciliation</h1>
        <p className="text-sm text-muted-foreground">Sales vs station boarding per sub-agent. Unboarded tickets need a follow-up before cash closes.</p>
        <div className="mt-4 flex items-end gap-2">
          <div>
            <label htmlFor="recon-day" className="text-xs font-medium">Day</label>
            <Input id="recon-day" type="date" value={day} onChange={(e) => setDay(e.target.value)} />
          </div>
          <Button onClick={load} disabled={loading}>Refresh</Button>
        </div>

        {loading ? <div className="mt-8"><LoadingSpinner /></div> : (
          <>
            {alerts.length > 0 && (
              <Card className="mt-4 border-destructive/50">
                <CardHeader><CardTitle className="flex items-center gap-2 text-destructive"><AlertTriangle size={18} /> {alerts.length} mismatch{alerts.length > 1 ? 'es' : ''} need attention</CardTitle></CardHeader>
                <CardContent>
                  <ul className="space-y-1 text-sm">
                    {alerts.map((a) => (
                      <li key={a.sellerId}>
                        <strong>{a.sellerName || a.sellerId}</strong> — {a.tickets - a.boarded} of {a.tickets} tickets unboarded · {a.gross.toLocaleString()} XAF
                      </li>
                    ))}
                  </ul>
                </CardContent>
              </Card>
            )}

            <Card className="mt-4">
              <CardHeader><CardTitle>All sellers — {day}</CardTitle></CardHeader>
              <CardContent>
                {sellers.length === 0 ? <p className="text-sm text-muted-foreground">No paid sales this day.</p> : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                      <thead>
                        <tr className="border-b text-left text-muted-foreground">
                          <th className="py-2 pr-3">Seller</th>
                          <th className="py-2 pr-3 text-right">Sold</th>
                          <th className="py-2 pr-3 text-right">Gross</th>
                          <th className="py-2 pr-3 text-right">Wallet</th>
                          <th className="py-2 pr-3 text-right">Cash</th>
                          <th className="py-2 pr-3 text-right">Boarded</th>
                          <th className="py-2">Flag</th>
                        </tr>
                      </thead>
                      <tbody>
                        {sellers.map((s) => (
                          <tr key={s.sellerId} className="border-b last:border-0">
                            <td className="py-2 pr-3 font-medium">{s.sellerName || s.sellerId}</td>
                            <td className="py-2 pr-3 text-right">{s.tickets}</td>
                            <td className="py-2 pr-3 text-right">{s.gross.toLocaleString()}</td>
                            <td className="py-2 pr-3 text-right">{s.wallet.toLocaleString()}</td>
                            <td className="py-2 pr-3 text-right">{s.cash.toLocaleString()}</td>
                            <td className="py-2 pr-3 text-right">{s.boarded}</td>
                            <td className="py-2">
                              {s.flag === 'ok'
                                ? <Badge className="gap-1"><CheckCircle2 size={12} /> ok</Badge>
                                : <Badge variant="destructive">{s.flag}</Badge>}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </CardContent>
            </Card>
          </>
        )}
      </main>
    </div>
  );
}
