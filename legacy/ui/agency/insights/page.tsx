'use client';

import React, { useMemo } from 'react';
import { useRouter, usePathname } from 'next/navigation';
import { useUser, useFirestore, useCollection, useMemoFirebase } from '@/firebase';
import { collection, query, where, orderBy } from 'firebase/firestore';
import LoadingSpinner from '@/components/loading-spinner';
import { 
    Area, 
    AreaChart, 
    CartesianGrid, 
    XAxis, 
    YAxis, 
    Tooltip, 
    ResponsiveContainer 
} from 'recharts';
import { 
    ArrowLeft, 
    TrendingUp, 
    Users, 
    Wallet, 
    Bus, 
    ChevronRight, 
    FileText, 
    Activity,
    Clock,
    Target,
    LayoutDashboard,
    Settings
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import { cn } from '@/lib/utils';
import Link from 'next/link';


type BusTrip = {
    id: string;
    origin: string;
    destination: string;
    price: number;
    occupancy: number;
    totalSeats: number;
    status: string;
    departureAt: any;
    busNumber?: string;
};

export default function AgencyInsightsPage() {
    const { user } = useUser();
    const firestore = useFirestore();
    const router = useRouter();
    const pathname = usePathname();

    const tripsQuery = useMemoFirebase(() => {
        if (!user || !firestore) return null;
        return query(
            collection(firestore, 'trips'),
            where('agencyId', '==', user.uid),
            orderBy('departureAt', 'desc')
        );
    }, [user, firestore]);

    const { data: allTrips, isLoading } = useCollection<BusTrip>(tripsQuery);

    const metrics = useMemo(() => {
        if (!allTrips) return null;
        const validTrips = allTrips.filter(t => t.id !== '_init');
        
        let totalRevenue = 0;
        let totalPassengers = 0;
        let totalCap = 0;
        let completedMissionsCount = 0;
        const chartData: any[] = [];

        validTrips.reverse().forEach(t => {
            const date = t.departureAt?.toDate ? format(t.departureAt.toDate(), 'MMM d') : 'Unknown';
            totalPassengers += (t.occupancy || 0);
            totalCap += (t.totalSeats || 1);
            
            if (t.status === 'departed' || t.status === 'completed') {
                totalRevenue += (t.occupancy || 0) * (t.price || 0);
            }

            if (t.status === 'completed') {
                completedMissionsCount++;
            }

            chartData.push({
                name: date,
                occupancy: Math.round(((t.occupancy || 0) / (t.totalSeats || 1)) * 100),
                passengers: t.occupancy || 0
            });
        });

        const loadFactor = totalCap > 0 ? (totalPassengers / totalCap) * 100 : 0;

        return {
            totalRevenue,
            totalPassengers,
            loadFactor: Math.round(loadFactor),
            tripsCount: completedMissionsCount,
            chartData: chartData.slice(-10), // Last 10 trips for the chart
            history: validTrips.reverse()
        };
    }, [allTrips]);

    if (isLoading) return <LoadingSpinner />;

    return (
        <div className="flex min-h-screen bg-background text-foreground font-display selection:bg-primary selection:text-white">
            <main className="flex-1 max-w-5xl mx-auto w-full p-6 md:p-10 lg:p-12 space-y-10 pb-40">
                
                <header className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" className="rounded-2xl size-11 bg-secondary/50" onClick={() => router.back()}>
                        <ArrowLeft size={20} />
                    </Button>
                    <div>
                        <span className="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Operational Ledger</span>
                        <h1 className="text-3xl font-black uppercase tracking-tighter leading-none">Mission Insights</h1>
                    </div>
                </header>

                <section className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <Card className="border-none shadow-sm bg-card rounded-[2rem] p-6 overflow-hidden relative group">
                        <div className="absolute top-0 left-0 w-1 h-full bg-primary opacity-40"></div>
                        <div className="flex flex-col gap-1">
                            <p className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Gross Settlement</p>
                            <p className="text-2xl font-black tracking-tighter">₣{metrics?.totalRevenue.toLocaleString()}</p>
                        </div>
                        <Wallet className="absolute right-6 bottom-6 size-8 text-primary opacity-10 group-hover:scale-110 transition-transform" />
                    </Card>
                    <Card className="border-none shadow-sm bg-card rounded-[2rem] p-6 overflow-hidden relative group">
                        <div className="absolute top-0 left-0 w-1 h-full bg-blue-500 opacity-40"></div>
                        <div className="flex flex-col gap-1">
                            <p className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Total Manifest</p>
                            <p className="text-2xl font-black tracking-tighter">{metrics?.totalPassengers} Pax</p>
                        </div>
                        <Users className="absolute right-6 bottom-6 size-8 text-blue-500 opacity-10 group-hover:scale-110 transition-transform" />
                    </Card>
                    <Card className="border-none shadow-sm bg-card rounded-[2rem] p-6 overflow-hidden relative group">
                        <div className="absolute top-0 left-0 w-1 h-full bg-emerald-500 opacity-40"></div>
                        <div className="flex flex-col gap-1">
                            <p className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Avg Load Factor</p>
                            <p className="text-2xl font-black tracking-tighter">{metrics?.loadFactor}%</p>
                        </div>
                        <Target className="absolute right-6 bottom-6 size-8 text-emerald-500 opacity-10 group-hover:scale-110 transition-transform" />
                    </Card>
                    <Card className="border-none shadow-sm bg-card rounded-[2rem] p-6 overflow-hidden relative group">
                        <div className="absolute top-0 left-0 w-1 h-full bg-orange-500 opacity-40"></div>
                        <div className="flex flex-col gap-1">
                            <p className="text-[9px] font-black text-muted-foreground uppercase tracking-[0.2em]">Total Missions</p>
                            <p className="text-2xl font-black tracking-tighter">{metrics?.tripsCount}</p>
                        </div>
                        <Activity className="absolute right-6 bottom-6 size-8 text-orange-500 opacity-10 group-hover:scale-110 transition-transform" />
                    </Card>
                </section>

                <Card className="border-none shadow-sm bg-card rounded-[2.5rem] overflow-hidden">
                    <CardHeader className="bg-secondary/30 border-b border-border/10 pb-8 p-8">
                        <div className="flex items-center gap-3">
                            <div className="size-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary">
                                <TrendingUp size={20} />
                            </div>
                            <div>
                                <CardTitle className="text-xl font-black uppercase tracking-tight">Occupancy Velocity</CardTitle>
                                <CardDescription className="text-[10px] font-bold uppercase tracking-widest mt-1">Fleet efficiency over the last 10 missions</CardDescription>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-8 h-80">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={metrics?.chartData}>
                                <defs>
                                    <linearGradient id="colorOcc" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="hsl(var(--primary))" stopOpacity={0.3}/>
                                        <stop offset="95%" stopColor="hsl(var(--primary))" stopOpacity={0}/>
                                    </linearGradient>
                                </defs>
                                <CartesianGrid vertical={false} strokeDasharray="3 3" className="stroke-muted/20" />
                                <XAxis 
                                    dataKey="name" 
                                    axisLine={false} 
                                    tickLine={false} 
                                    tick={{ fontSize: 10, fontWeight: 'bold' }} 
                                    className="uppercase font-black"
                                />
                                <YAxis hide domain={[0, 100]} />
                                <Tooltip 
                                    contentStyle={{ borderRadius: '1rem', border: 'none', boxShadow: '0 10px 15px -3px rgb(0 0 0 / 0.1)', fontWeight: 'bold' }}
                                />
                                <Area 
                                    type="monotone" 
                                    dataKey="occupancy" 
                                    stroke="hsl(var(--primary))" 
                                    fillOpacity={1} 
                                    fill="url(#colorOcc)" 
                                    strokeWidth={3}
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    </CardContent>
                </Card>

                <section className="space-y-6">
                    <div className="flex items-center justify-between px-1">
                        <h3 className="text-xl font-black uppercase tracking-tighter">Mission History</h3>
                        <Badge variant="secondary" className="font-black text-[9px] uppercase tracking-widest">Global Node Log</Badge>
                    </div>

                    <div className="grid gap-4">
                        {metrics?.history.map((trip) => (
                            <div key={trip.id} className="bg-card border border-border/50 rounded-[2rem] p-6 flex flex-col md:flex-row items-center justify-between gap-6 group hover:border-primary/30 transition-all duration-500">
                                <div className="flex items-center gap-4 flex-1">
                                    <div className="size-12 rounded-2xl bg-secondary flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-sm">
                                        <Bus size={24} />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="flex items-center gap-3">
                                            <span className="font-black text-lg uppercase tracking-tighter truncate">{trip.origin.split(' ')[0]} ➞ {trip.destination.split(' ')[0]}</span>
                                        </div>
                                        <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest mt-1 opacity-60">
                                            Bus #{trip.busNumber || 'TBD'} • {trip.departureAt?.toDate ? format(trip.departureAt.toDate(), 'PPP') : 'N/A'}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-center gap-10">
                                    <div className="text-center">
                                        <p className="text-sm font-black">{trip.occupancy}/{trip.totalSeats}</p>
                                        <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest">Occupancy</p>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-black text-primary">₣{(trip.occupancy * trip.price).toLocaleString()}</p>
                                        <p className="text-[8px] font-bold text-muted-foreground uppercase tracking-widest">Yield</p>
                                    </div>
                                    <Badge className={cn(
                                        "uppercase font-black text-[8px] tracking-widest px-3 py-1",
                                        trip.status === 'completed' || trip.status === 'departed' ? "bg-emerald-500/10 text-emerald-500 border-emerald-500/20" : "bg-primary/10 text-primary border-primary/20"
                                    )}>
                                        {trip.status}
                                    </Badge>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            </main>

            
        </div>
    );
}
