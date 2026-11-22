'use client';

/**
 * Agency Portal → Sales reports with export.
 *
 * Date-range per-day per-seller totals with wallet/cash split. Downloads a
 * CSV for the accountant. All reads go through the scoped server endpoint.
 */
import React, { useCallback, useEffect, useState } from 'react';
import { doc, getDoc } from 'firebase/firestore';
import { useAuth, useFirestore, useUser } from '@/firebase';
import { useToast } from '@/hooks/use-toast';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import LoadingSpinner from '@/components/loading-spinner';

import { Download } from 'lucide-react';

type ReportRow = {
  day: string;
  seller_id: string;
  seller_name: string;
  tickets: number;
  gross_xaf: number;
  wallet_xaf: number;
  cash_xaf: number;
  boarded: number;
};

export default function AgencyReportsPage() {
  const firestore = useFirestore();
  const { user } = useUser();
  const auth = useAuth();
  const { toast } = useToast();
  const [agencyId, setAgencyId] = useState<string | null>(null);
  const [from, setFrom] = useState(() => {
    const d = new Date();
    d.setDate(d.getDate() - 30);
    return d.toISOString().slice(0, 10);
  });
  const [to, setTo] = useState(() => new Date().toISOString().slice(0, 10));
  const [rows, setRows] = useState<ReportRow[]>([]);
  const [totals, setTotals] = useState({ tickets: 0, gross: 0, wallet: 0, cash: 0, boarded: 0 });
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
      const qs = new URLSearchParams({ agencyId, from, to, format: 'json' });
      const res = await fetch(`/api/agency/sales-report?${qs}`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      const j = await res.json();
      if (!res.ok || !j.success) throw new Error(j.error || 'Load failed');
      setRows(j.rows);
      setTotals(j.totals);
    } catch (e) {
      toast({ variant: 'destructive', title: 'Report failed', description: e instanceof Error ? e.message : 'Unknown error' });
    } finally {
      setLoading(false);
    }
  }, [agencyId, from, to, auth, toast]);

  useEffect(() => {
    load().catch(() => {});
  }, [load]);

  const downloadCsv = useCallback(async () => {
    if (!agencyId || !auth?.currentUser) return;
    const token = await auth.currentUser.getIdToken();
    const qs = new URLSearchParams({ agencyId, from, to, format: 'csv' });
    const res = await fetch(`/api/agency/sales-report?${qs}`, {
      headers: { Authorization: `Bearer ${token}` },
    });
    if (!res.ok) {
      toast({ variant: 'destructive', title: 'Export failed' });
      return;
    }
    const blob = await res.blob();
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `sales-${agencyId}-${from}-${to}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }, [agencyId, from, to, auth, toast]);

  return (
    <div className="min-h-screen pb-28">
      
      <main className="mx-auto max-w-5xl px-4 pt-6">
        <h1 className="text-2xl font-bold">Sales reports</h1>
        <p className="text-sm text-muted-foreground">Per-day, per-seller revenue. Export CSV for the accountant.</p>
        <div className="mt-4 flex flex-wrap items-end gap-2">
          <div>
            <label htmlFor="rep-from" className="text-xs font-medium">From</label>
            <Input id="rep-from" type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
          </div>
          <div>
            <label htmlFor="rep-to" className="text-xs font-medium">To</label>
            <Input id="rep-to" type="date" value={to} onChange={(e) => setTo(e.target.value)} />
          </div>
          <Button onClick={load} disabled={loading}>Run</Button>
          <Button variant="outline" onClick={downloadCsv} disabled={loading}>
            <Download size={14} className="mr-1" /> CSV
          </Button>
        </div>

        {loading ? <div className="mt-8"><LoadingSpinner /></div> : (
          <Card className="mt-4">
            <CardHeader>
              <CardTitle>
                {totals.tickets} tickets · {totals.gross.toLocaleString()} XAF
                <span className="ml-2 text-sm font-normal text-muted-foreground">
                  wallet {totals.wallet.toLocaleString()} · cash {totals.cash.toLocaleString()} · boarded {totals.boarded}
                </span>
              </CardTitle>
            </CardHeader>
            <CardContent>
              {rows.length === 0 ? <p className="text-sm text-muted-foreground">No sales in range.</p> : (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead>
                      <tr className="border-b text-left text-muted-foreground">
                        <th className="py-2 pr-3">Day</th>
                        <th className="py-2 pr-3">Seller</th>
                        <th className="py-2 pr-3 text-right">Tickets</th>
                        <th className="py-2 pr-3 text-right">Gross</th>
                        <th className="py-2 pr-3 text-right">Wallet</th>
                        <th className="py-2 pr-3 text-right">Cash</th>
                        <th className="py-2 text-right">Boarded</th>
                      </tr>
                    </thead>
                    <tbody>
                      {rows.map((r) => (
                        <tr key={`${r.day}-${r.seller_id}`} className="border-b last:border-0">
                          <td className="py-2 pr-3 font-mono text-xs">{r.day}</td>
                          <td className="py-2 pr-3">{r.seller_name || r.seller_id}</td>
                          <td className="py-2 pr-3 text-right">{r.tickets}</td>
                          <td className="py-2 pr-3 text-right">{r.gross_xaf.toLocaleString()}</td>
                          <td className="py-2 pr-3 text-right">{r.wallet_xaf.toLocaleString()}</td>
                          <td className="py-2 pr-3 text-right">{r.cash_xaf.toLocaleString()}</td>
                          <td className="py-2 text-right">{r.boarded}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </CardContent>
          </Card>
        )}
      </main>
    </div>
  );
}
