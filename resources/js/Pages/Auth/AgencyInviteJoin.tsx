import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { motion } from 'framer-motion';

export default function AgencyInviteJoin() {
    const [name, setName] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    return (
        <>
            <Head title="Join Agency - EataLyft" />
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center px-4 py-8">
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md">
                    <div className="bg-[#DC2626]/10 border border-[#DC2626]/20 rounded-lg p-4 mb-6">
                        <p className="text-sm font-medium text-[#DC2626]">Agency Invite</p>
                        <p className="text-sm text-gray-600 mt-1">You've been invited to join an agency as a teller.</p>
                    </div>
                    <h1 className="font-[Poppins] text-2xl font-bold text-[#0A0A0A] mb-6">Activate Account</h1>
                    <form className="space-y-4">
                        <div><label className="block text-sm font-medium mb-1">Full Name</label><input value={name} onChange={e => setName(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm" placeholder="Your full name" /></div>
                        <div><label className="block text-sm font-medium mb-1">Password</label><input type="password" value={password} onChange={e => setPassword(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm" /></div>
                        <div><label className="block text-sm font-medium mb-1">Confirm Password</label><input type="password" value={confirmPassword} onChange={e => setConfirmPassword(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm" /></div>
                        <button type="submit" className="w-full bg-[#DC2626] text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">Activate Account</button>
                    </form>
                </motion.div>
            </div>
        </>
    );
}