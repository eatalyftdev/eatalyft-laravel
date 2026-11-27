import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import ProgressiveEmailPasswordFields from '../../Components/Auth/ProgressiveEmailPasswordFields';

const cities = ['Douala', 'Yaoundé', 'Bamenda', 'Bafoussam', 'Garoua', 'Maroua', 'Ngaoundéré', 'Bertoua', 'Ebolowa', 'Buea', 'Kribi', 'Limbe', 'Dschang', 'Foumban', 'Kousseri', 'Edéa', 'Kumbo', 'Nkongsamba', 'Tiko', 'Mbalmayo'];
const fleetTypes = ['bus75', 'bus65', 'bus50', 'bus30', 'bus18'];
const fleetLabels: Record<string, string> = { bus75: '75 seats', bus65: '65 seats', bus50: '50 seats', bus30: '30 seats', bus18: '18 seats' };

export default function AgencySignup() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [agencyName, setAgencyName] = useState('');
    const [managerName, setManagerName] = useState('');
    const [headOffice, setHeadOffice] = useState('');
    const [selectedCities, setSelectedCities] = useState<string[]>([]);
    const [fleet, setFleet] = useState<Record<string, number>>({ bus75: 0, bus65: 0, bus50: 0, bus30: 0, bus18: 0 });

    const toggleCity = (c: string) => setSelectedCities(prev => prev.includes(c) ? prev.filter(x => x !== c) : [...prev, c]);
    const updateFleet = (k: string, v: number) => setFleet(prev => ({ ...prev, [k]: v }));

    return (
        <>
            <Head title="Agency Signup - EataLyft" />
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center px-4 py-8">
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-2xl">
                    <h1 className="font-[Poppins] text-2xl font-bold text-[#0A0A0A] mb-6">Register Your Agency</h1>
                    <form className="space-y-4">
                        <div className="grid md:grid-cols-2 gap-4">
                            <div><label className="block text-sm font-medium mb-1">Agency Name</label><input value={agencyName} onChange={e => setAgencyName(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm" /></div>
                            <div><label className="block text-sm font-medium mb-1">Manager Name</label><input value={managerName} onChange={e => setManagerName(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm" /></div>
                        </div>
                        <ProgressiveEmailPasswordFields email={email} password={password} onEmailChange={setEmail} onPasswordChange={setPassword} />
                        <div><label className="block text-sm font-medium mb-1">Head Office</label><input value={headOffice} onChange={e => setHeadOffice(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm" /></div>
                        <div>
                            <label className="block text-sm font-medium mb-2">Operating Cities</label>
                            <div className="grid grid-cols-3 gap-2 max-h-40 overflow-y-auto">
                                {cities.map(c => (
                                    <label key={c} className="flex items-center gap-2 text-sm cursor-pointer">
                                        <input type="checkbox" checked={selectedCities.includes(c)} onChange={() => toggleCity(c)} className="rounded text-[#DC2626]" />
                                        {c}
                                    </label>
                                ))}
                            </div>
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-2">Initial Fleet</label>
                            <div className="grid grid-cols-5 gap-2">
                                {fleetTypes.map(t => (
                                    <div key={t} className="text-center">
                                        <label className="text-xs text-gray-500">{fleetLabels[t]}</label>
                                        <input type="number" min={0} value={fleet[t]} onChange={e => updateFleet(t, Number(e.target.value))} className="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm text-center" />
                                    </div>
                                ))}
                            </div>
                        </div>
                        <button type="submit" className="w-full bg-[#DC2626] text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">
                            Register Agency
                        </button>
                    </form>
                    <p className="text-center text-sm text-gray-500 mt-4">
                        Already registered? <Link href="/agency/login" className="text-[#DC2626] font-medium">Log in</Link>
                    </p>
                </motion.div>
            </div>
        </>
    );
}