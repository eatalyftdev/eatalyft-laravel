import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { motion } from 'framer-motion';
import ProgressiveEmailPasswordFields from '../../Components/Auth/ProgressiveEmailPasswordFields';

export default function AdminVaultLogin() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    return (
        <>
            <Head title="Admin Vault - EataLyft" />
            <div className="min-h-screen bg-[#0A0A0A] flex items-center justify-center px-4">
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="bg-[#171717] rounded-2xl shadow-2xl p-8 w-full max-w-md border border-gray-800">
                    <div className="text-center mb-6">
                        <div className="w-12 h-12 rounded-full bg-[#DC2626] flex items-center justify-center mx-auto mb-3">
                            <span className="text-white font-bold text-lg">🔒</span>
                        </div>
                        <h1 className="font-[Poppins] text-2xl font-bold text-white">Admin Vault</h1>
                        <p className="text-gray-400 text-sm mt-1">Command Center Access</p>
                    </div>
                    <form className="space-y-4">
                        <ProgressiveEmailPasswordFields email={email} password={password} onEmailChange={setEmail} onPasswordChange={setPassword} />
                        <button type="submit" className="w-full bg-[#DC2626] text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">Access Vault</button>
                    </form>
                </motion.div>
            </div>
        </>
    );
}