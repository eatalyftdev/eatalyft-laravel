import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { motion } from 'framer-motion';
import ProgressiveEmailPasswordFields from '../../Components/Auth/ProgressiveEmailPasswordFields';

const cities = ['Douala', 'Yaoundé', 'Bamenda', 'Bafoussam', 'Garoua', 'Maroua', 'Ngaoundéré', 'Bertoua', 'Ebolowa', 'Buea', 'Kribi', 'Limbe', 'Dschang', 'Foumban', 'Kousseri', 'Edéa', 'Kumbo', 'Nkongsamba', 'Tiko', 'Mbalmayo'];
const specialties = ['Plumbing', 'Electrical Work', 'Painting', 'Carpentry', 'Tiling', 'AC Repair', 'General Repairs', 'Welding', 'Masonry', 'Cleaning'];

export default function HandymanSignup() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [firstName, setFirstName] = useState('');
    const [lastName, setLastName] = useState('');
    const [city, setCity] = useState('Douala');
    const [specialty, setSpecialty] = useState('');
    const [ratePerHour, setRatePerHour] = useState(2000);
    return (
        <>
            <Head title="Handyman Signup - EataLyft" />
            <div className="min-h-screen bg-[#F8FAFC] flex items-center justify-center px-4 py-8">
                <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md">
                    <h1 className="font-[Poppins] text-2xl font-bold text-[#0A0A0A] mb-6">Join as Handyman</h1>
                    <form className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div><label className="block text-sm font-medium mb-1">First Name</label><input value={firstName} onChange={e => setFirstName(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm" /></div>
                            <div><label className="block text-sm font-medium mb-1">Last Name</label><input value={lastName} onChange={e => setLastName(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm" /></div>
                        </div>
                        <ProgressiveEmailPasswordFields email={email} password={password} onEmailChange={setEmail} onPasswordChange={setPassword} />
                        <div><label className="block text-sm font-medium mb-1">City</label><select value={city} onChange={e => setCity(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm">{cities.map(c => <option key={c} value={c}>{c}</option>)}</select></div>
                        <div><label className="block text-sm font-medium mb-1">Specialty</label><select value={specialty} onChange={e => setSpecialty(e.target.value)} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm"><option value="">Select specialty</option>{specialties.map(s => <option key={s} value={s}>{s}</option>)}</select></div>
                        <div><label className="block text-sm font-medium mb-1">Rate per Hour (XAF)</label><input type="number" value={ratePerHour} onChange={e => setRatePerHour(Number(e.target.value))} className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm" /></div>
                        <button type="submit" className="w-full bg-[#DC2626] text-white py-3 rounded-lg font-semibold hover:bg-red-700 transition-colors">Register</button>
                    </form>
                    <p className="text-center text-sm text-gray-500 mt-4">Already registered? <Link href="/handyman/login" className="text-[#DC2626] font-medium">Log in</Link></p>
                </motion.div>
            </div>
        </>
    );
}