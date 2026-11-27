import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import ProgressiveEmailPasswordFields from '../../Components/Auth/ProgressiveEmailPasswordFields';

const cities = ['Douala', 'Yaoundé', 'Bamenda', 'Bafoussam', 'Garoua', 'Maroua', 'Ngaoundéré', 'Bertoua', 'Ebolowa', 'Buea', 'Kribi', 'Limbe', 'Dschang', 'Foumban', 'Kousseri', 'Edéa', 'Kumbo', 'Nkongsamba', 'Tiko', 'Mbalmayo'];

export default function CustomerLogin() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [city, setCity] = useState('Douala');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/login/otp/request', { contact: email });
    };

    return (
        <>
            <Head title="Customer Login - EataLyft" />
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center px-4">
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md">
                    <div className="text-center mb-6">
                        <h1 className="font-[Poppins] text-2xl font-bold text-[#0A0A0A]">Welcome Back</h1>
                        <p className="text-gray-500 text-sm mt-1">Sign in to your EataLyft account</p>
                    </div>
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <ProgressiveEmailPasswordFields email={email} password={password} onEmailChange={setEmail} onPasswordChange={setPassword} />
                        <div>
                            <label className="block text-sm font-medium text-[#0A0A0A] mb-1">Active City</label>
                            <select value={city} onChange={(e) => setCity(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-[#DC2626]">
                                {cities.map(c => <option key={c} value={c}>{c}</option>)}
                            </select>
                        </div>
                        <button type="submit" className="w-full bg-[#DC2626] text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">
                            Continue
                        </button>
                    </form>
                    <p className="text-center text-sm text-gray-500 mt-4">
                        Don't have an account? <Link href="/user/signup" className="text-[#DC2626] font-medium">Sign up</Link>
                    </p>
                </motion.div>
            </div>
        </>
    );
}