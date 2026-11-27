import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import ProgressiveEmailPasswordFields from '../../Components/Auth/ProgressiveEmailPasswordFields';

export default function StationAgentLogin() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    return (
        <>
            <Head title="Station Agent Login - EataLyft" />
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center px-4">
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md">
                    <h1 className="font-[Poppins] text-2xl font-bold text-[#0A0A0A] mb-2">Station Teller Login</h1>
                    <p className="text-gray-500 text-sm mb-6">Sub-Branch & Counter Portal</p>
                    <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-6 text-sm text-amber-800">
                        Self-signup is disabled. Request an invite link from your Main Agency.
                    </div>
                    <form className="space-y-4">
                        <ProgressiveEmailPasswordFields email={email} password={password} onEmailChange={setEmail} onPasswordChange={setPassword} />
                        <button type="submit" className="w-full bg-[#DC2626] text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">Sign In</button>
                    </form>
                </motion.div>
            </div>
        </>
    );
}