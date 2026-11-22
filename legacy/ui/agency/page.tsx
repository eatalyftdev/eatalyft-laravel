'use client';

import Link from 'next/link';
import { Bus, ShieldCheck, Zap, ArrowRight, MapPin, Globe, BarChart3, Users } from 'lucide-react';
import { motion } from 'framer-motion';

const FEATURES = [
    { icon: Bus,         title: 'Fleet Management',  desc: 'Register your inter-city transport agency and manage your entire bus fleet through a single unified dashboard.' },
    { icon: BarChart3,   title: 'Ticket Ledger',     desc: 'Automated ticketing and seat management. Monitor revenue yields and occupancy with precision analytics.' },
    { icon: Zap,         title: 'Instant Booking',   desc: 'E-Travey users can book seats in real-time. Yields are settled instantly via the EataPay financial grid.' },
    { icon: Globe,       title: 'National Grid',     desc: 'Your agency is integrated into the national transport network, reaching travelers across all major hubs.' },
    { icon: Users,       title: 'Customer Hub',      desc: 'Build customer loyalty with integrated profiles, digital tickets, and real-time status notifications.' },
    { icon: ShieldCheck, title: 'Safe Travel Node',  desc: 'Every trip is logged and verified. Integrated manifests ensure safety and regulatory compliance.' },
];

export default function AgencyLandingPage() {
    return (
        <div className="min-h-screen bg-[#050505] text-white font-display flex flex-col overflow-x-hidden">

            {/* ── Background ── */}
            <div className="fixed inset-0 pointer-events-none overflow-hidden">
                <div className="absolute top-0 right-0 w-[800px] h-[800px] rounded-full bg-emerald-600/5 blur-[120px]" />
                <div className="absolute bottom-0 left-0 w-[600px] h-[600px] rounded-full bg-teal-600/5 blur-[120px]" />
            </div>

            {/* ── Header ── */}
            <header className="relative z-10 px-8 py-6 flex items-center justify-between border-b border-white/[0.05] backdrop-blur-xl bg-black/40">
                <div className="flex items-center gap-3">
                    <div className="size-10 bg-gradient-to-br from-emerald-600 to-teal-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                        <Bus size={20} className="text-white" />
                    </div>
                    <div className="flex flex-col">
                        <span className="text-sm font-black tracking-widest text-white uppercase italic leading-none">EataLyft</span>
                        <span className="text-[10px] font-black tracking-[0.3em] text-emerald-400 uppercase italic"> E-Travey Hub</span>
                    </div>
                </div>
                <div className="flex items-center gap-3">
                    <Link href="/agency/login" className="px-6 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white text-xs font-black uppercase tracking-widest hover:bg-white/10 transition-all">
                        Sign In
                    </Link>
                    <Link href="/agency/signup" className="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-black uppercase tracking-widest hover:bg-emerald-500 transition-all shadow-xl shadow-emerald-500/20">
                        Register Agency
                    </Link>
                </div>
            </header>

            {/* ── Hero ── */}
            <main className="relative z-10 flex-1 flex flex-col items-center justify-center px-6 py-24 text-center">
                <motion.div initial={{ opacity: 0, scale: 0.95 }} animate={{ opacity: 1, scale: 1 }} transition={{ duration: 0.8 }} className="space-y-10 max-w-5xl">
                    
                    <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-black tracking-[0.5em] uppercase">
                        <span className="size-1.5 rounded-full bg-emerald-400 animate-pulse" />
                        Transport Infrastructure Node
                    </div>

                    <h1 className="text-6xl md:text-9xl font-black tracking-tighter leading-[0.8] text-white uppercase italic">
                        Fleet
                        <span className="block text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">
                            Intelligence
                        </span>
                    </h1>

                    <p className="text-lg md:text-xl text-white/40 max-w-2xl mx-auto leading-relaxed font-medium tracking-wide">
                        The ultimate inter-city transport management platform for Cameroon. Digitalize your agency, automate ticketing, and scale your fleet with E-Travey.
                    </p>

                    <div className="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link href="/agency/signup" className="flex items-center justify-center gap-3 px-12 py-6 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xl uppercase tracking-widest transition-all hover:shadow-2xl hover:shadow-emerald-500/40 active:scale-[0.98]">
                            Join Network <ArrowRight size={20} />
                        </Link>
                        <Link href="/agency/login" className="flex items-center justify-center gap-3 px-12 py-6 rounded-2xl bg-white/[0.03] border border-white/10 hover:bg-white/5 text-white font-black text-xl uppercase tracking-widest transition-all active:scale-[0.98]">
                            Agency Login
                        </Link>
                    </div>
                </motion.div>
            </main>

            {/* ── Features ── */}
            <section className="relative z-10 px-8 pb-32">
                <div className="max-w-7xl mx-auto">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-8">
                        {FEATURES.map(({ icon: Icon, title, desc }, i) => (
                            <motion.div key={title} initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} transition={{ delay: i * 0.1 }}
                                className="relative group bg-[#0C0C0C] border border-white/[0.05] rounded-[2.5rem] p-10 hover:border-emerald-500/40 hover:bg-emerald-500/[0.02] transition-all duration-700"
                            >
                                <div className="size-16 rounded-2xl bg-gradient-to-br from-emerald-600/20 to-teal-500/10 flex items-center justify-center mb-8 border border-emerald-500/20 group-hover:bg-emerald-600 transition-colors duration-500">
                                    <Icon size={28} className="text-emerald-400 group-hover:text-white transition-colors" />
                                </div>
                                <h3 className="text-2xl font-black text-white mb-4 uppercase tracking-tighter italic">{title}</h3>
                                <p className="text-base text-white/30 leading-relaxed font-medium">{desc}</p>
                            </motion.div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ── Footer ── */}
            <footer className="relative z-10 px-10 py-10 border-t border-white/[0.05] flex flex-col md:flex-row items-center justify-between gap-6">
                <div className="flex flex-col gap-1">
                    <span className="text-[10px] font-black text-white/20 uppercase tracking-[0.4em]">© 2026 EataLyft Technologies Ltd</span>
                    <span className="text-[9px] font-black text-emerald-500/40 uppercase tracking-[0.6em]">E-Travey Protocol v2.5.1</span>
                </div>
                <div className="flex gap-8">
      <Link href="/terms" className="text-[10px] font-black text-white/10 hover:text-emerald-400 transition-colors uppercase tracking-[0.2em]">Agency Agreement</Link>
      <Link href="/contact" className="text-[10px] font-black text-white/10 hover:text-emerald-400 transition-colors uppercase tracking-[0.2em]">Support Terminal</Link>
                </div>
            </footer>
        </div>
    );
}
