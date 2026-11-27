import { Head } from '@inertiajs/react';
import { motion } from 'framer-motion';

export default function StationDashboard() {
    return (
        <>
            <Head title="Station Dashboard - EataLyft" />
            <div className="min-h-screen bg-[#F8FAFC] p-8">
                <motion.h1 initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="font-[Poppins] text-3xl font-bold text-[#0A0A0A] mb-6">Station Teller Dashboard</motion.h1>
                <div className="grid md:grid-cols-3 gap-4">
                    <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100"><p className="text-gray-500 text-sm">Today's Sales</p><p className="font-[Poppins] text-2xl font-bold text-[#DC2626] tabular-nums">0 XAF</p></div>
                    <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100"><p className="text-gray-500 text-sm">Tickets Issued</p><p className="font-[Poppins] text-2xl font-bold tabular-nums">0</p></div>
                    <div className="bg-white rounded-xl p-6 shadow-sm border border-gray-100"><p className="text-gray-500 text-sm">Departures Today</p><p className="font-[Poppins] text-2xl font-bold tabular-nums">0</p></div>
                </div>
            </div>
        </>
    );
}