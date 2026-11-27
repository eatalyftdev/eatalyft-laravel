import { motion } from 'framer-motion';

export type SeatState = 'available' | 'selected' | 'booked' | 'female_quota' | 'sleeper' | 'admin_lock';

interface Seat {
    number: string;
    state: SeatState;
    type?: 'standard' | 'sleeper' | 'female_quota' | 'admin_lock' | 'driver';
    deck?: 'lower' | 'upper';
    price?: number;
}

interface SeatMapProps {
    seats: Seat[];
    selectedSeats: string[];
    onSelect: (seatNumber: string) => void;
    layout: '75' | '65' | '50' | '30' | '18';
}

const stateColors: Record<SeatState, string> = {
    available: 'bg-white border-gray-300 hover:border-[#DC2626] hover:bg-red-50 cursor-pointer',
    selected: 'bg-[#DC2626] border-[#DC2626] text-white cursor-pointer',
    booked: 'bg-gray-300 border-gray-300 text-gray-500 cursor-not-allowed',
    female_quota: 'bg-pink-100 border-pink-300 text-pink-600 cursor-pointer',
    sleeper: 'bg-blue-100 border-blue-300 text-blue-600 cursor-pointer',
    admin_lock: 'bg-gray-800 border-gray-800 text-white cursor-not-allowed',
};

export default function SeatMap({ seats, selectedSeats, onSelect, layout }: SeatMapProps) {
    return (
        <div className="bg-white rounded-2xl p-4 shadow-sm border border-gray-100">
            <div className="flex items-center justify-between mb-4">
                <h3 className="font-[Poppins] font-semibold text-lg">Select Your Seat</h3>
                <div className="flex gap-3 text-xs">
                    <span className="flex items-center gap-1"><span className="w-3 h-3 rounded bg-white border border-gray-300" /> Available</span>
                    <span className="flex items-center gap-1"><span className="w-3 h-3 rounded bg-[#DC2626]" /> Selected</span>
                    <span className="flex items-center gap-1"><span className="w-3 h-3 rounded bg-gray-300" /> Booked</span>
                </div>
            </div>
            <div className="grid grid-cols-5 gap-2 max-w-sm mx-auto">
                {seats.map((seat) => (
                    <motion.button
                        key={seat.number}
                        whileTap={{ scale: 0.9 }}
                        onClick={() => seat.state === 'available' && onSelect(seat.number)}
                        className={`w-10 h-10 rounded-lg border-2 text-xs font-bold flex items-center justify-center transition-colors ${stateColors[seat.state]} ${selectedSeats.includes(seat.number) ? 'bg-[#DC2626] border-[#DC2626] text-white' : ''}`}
                        disabled={seat.state === 'booked' || seat.state === 'admin_lock'}
                    >
                        {seat.number}
                    </motion.button>
                ))}
            </div>
        </div>
    );
}