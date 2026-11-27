import { motion } from 'framer-motion';


interface BoardingPassProps {
    agencyName: string;
    agencyLogo?: string;
    tripClass: string;
    status: string;
    originCode: string;
    destinationCode: string;
    date: string;
    departureTime: string;
    busPlate: string;
    seat: string;
    passengerName: string;
    passengerPhone: string;
    bookingCode: string;
    qrPayload: string;
}

export default function BoardingPassTicket(props: BoardingPassProps) {
    return (
        <div className="max-w-md mx-auto">
            <motion.div
                initial={{ opacity: 0, y: 20 }}
                animate={{ opacity: 1, y: 0 }}
                className="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-200"
            >
                {/* Header */}
                <div className="bg-[#DC2626] text-white p-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        {props.agencyLogo && <img src={props.agencyLogo} alt={props.agencyName} className="w-8 h-8 rounded-full bg-white" />}
                        <span className="font-[Poppins] font-bold">{props.agencyName}</span>
                    </div>
                    <div className="flex gap-2">
                        <span className="bg-white/20 px-3 py-1 rounded-full text-xs font-semibold">{props.tripClass}</span>
                        <span className="bg-white/20 px-3 py-1 rounded-full text-xs font-semibold">{props.status}</span>
                    </div>
                </div>

                {/* Route */}
                <div className="p-6 text-center">
                    <div className="flex items-center justify-center gap-4 mb-4">
                        <span className="font-[Poppins] text-4xl font-bold">{props.originCode}</span>
                        <span className="text-2xl text-[#DC2626]">➔</span>
                        <span className="font-[Poppins] text-4xl font-bold">{props.destinationCode}</span>
                    </div>
                    <div className="grid grid-cols-4 gap-2 text-sm">
                        <div><p className="text-gray-500 text-xs">DATE</p><p className="font-semibold">{props.date}</p></div>
                        <div><p className="text-gray-500 text-xs">DEPARTURE</p><p className="font-semibold">{props.departureTime}</p></div>
                        <div><p className="text-gray-500 text-xs">BUS PLATE</p><p className="font-semibold">{props.busPlate}</p></div>
                        <div><p className="text-gray-500 text-xs">SEAT</p><p className="font-bold text-[#DC2626]">{props.seat}</p></div>
                    </div>
                </div>

                {/* Perforated tear line */}
                <div className="relative flex items-center">
                    <div className="absolute left-0 w-4 h-4 rounded-full bg-[#F8FAFC] -translate-x-1/2" />
                    <div className="flex-1 border-t-2 border-dashed border-gray-300 mx-4" />
                    <div className="absolute right-0 w-4 h-4 rounded-full bg-[#F8FAFC] translate-x-1/2" />
                </div>

                {/* Stub */}
                <div className="p-6 space-y-3">
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Passenger</span>
                        <span className="font-semibold">{props.passengerName}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Phone</span>
                        <span className="font-semibold">{props.passengerPhone}</span>
                    </div>
                    <div className="flex justify-between text-sm">
                        <span className="text-gray-500">Booking Code</span>
                        <span className="font-mono font-bold text-[#DC2626]">{props.bookingCode}</span>
                    </div>
                    <div className="flex justify-center mt-4">
                        <div className="bg-gray-100 p-4 rounded-xl">
                            ? QR
                        </div>
                    </div>
                    <div className="text-center text-xs text-gray-400 font-mono">{props.bookingCode}</div>
                </div>

                {/* Actions */}
                <div className="p-4 border-t border-gray-100 flex gap-3">
                    <button className="flex-1 flex items-center justify-center gap-2 bg-[#DC2626] text-white py-2.5 rounded-xl font-semibold text-sm hover:bg-red-700 transition-colors">
                        ? Download PDF
                    </button>
                    <button className="flex-1 flex items-center justify-center gap-2 border border-gray-200 py-2.5 rounded-xl font-semibold text-sm hover:bg-gray-50 transition-colors">
                        ?? Print
                    </button>
                </div>
            </motion.div>
        </div>
    );
}
