import { motion, AnimatePresence } from 'framer-motion';
import { useState, useRef, useEffect } from 'react';

interface Props {
    open: boolean;
    onClose: () => void;
    onVerify: (code: string) => Promise<boolean>;
    onResend: () => void;
}

export default function OtpVerificationModal({ open, onClose, onVerify, onResend }: Props) {
    const [digits, setDigits] = useState(['', '', '', '', '', '']);
    const [verified, setVerified] = useState(false);
    const [error, setError] = useState('');
    const inputs = useRef<(HTMLInputElement | null)[]>([]);

    useEffect(() => {
        if (open) {
            setDigits(['', '', '', '', '', '']);
            setVerified(false);
            setError('');
            setTimeout(() => inputs.current[0]?.focus(), 100);
        }
    }, [open]);

    const handleChange = (i: number, val: string) => {
        if (!/^\d*$/.test(val)) return;
        const next = [...digits];
        next[i] = val.slice(-1);
        setDigits(next);
        if (val && i < 5) inputs.current[i + 1]?.focus();
        if (next.every(d => d) ) {
            onVerify(next.join('')).then(ok => {
                if (ok) {
                    setVerified(true);
                    setTimeout(onClose, 1500);
                } else {
                    setError('Invalid code. Try again.');
                    setDigits(['', '', '', '', '', '']);
                    inputs.current[0]?.focus();
                }
            });
        }
    };

    const handleKeyDown = (i: number, e: React.KeyboardEvent) => {
        if (e.key === 'Backspace' && !digits[i] && i > 0) {
            inputs.current[i - 1]?.focus();
        }
    };

    return (
        <AnimatePresence>
            {open && (
                <motion.div
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    exit={{ opacity: 0 }}
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                >
                    <motion.div
                        initial={{ scale: 0.9, opacity: 0 }}
                        animate={{ scale: 1, opacity: 1 }}
                        exit={{ scale: 0.9, opacity: 0 }}
                        transition={{ type: 'spring', duration: 0.2 }}
                        className="bg-white rounded-2xl p-8 max-w-sm w-full mx-4 shadow-2xl"
                    >
                        {verified ? (
                            <div className="text-center">
                                <motion.div
                                    initial={{ scale: 0 }}
                                    animate={{ scale: 1 }}
                                    transition={{ type: 'spring', duration: 0.3 }}
                                    className="w-16 h-16 rounded-full bg-[#16A34A] flex items-center justify-center mx-auto mb-4"
                                >
                                    <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                </motion.div>
                                <p className="font-[Poppins] font-semibold text-lg text-[#0A0A0A]">OTP Verified</p>
                            </div>
                        ) : (
                            <>
                                <h3 className="font-[Poppins] font-bold text-xl text-center mb-2">Enter OTP</h3>
                                <p className="text-sm text-gray-500 text-center mb-6">We sent a 6-digit code to your contact.</p>
                                <div className="flex gap-2 justify-center mb-4">
                                    {digits.map((d, i) => (
                                        <input
                                            key={i}
                                            ref={(el) => { inputs.current[i] = el; }}
                                            type="text"
                                            inputMode="numeric"
                                            maxLength={1}
                                            value={d}
                                            onChange={(e) => handleChange(i, e.target.value)}
                                            onKeyDown={(e) => handleKeyDown(i, e)}
                                            className="w-10 h-12 border-2 border-gray-300 rounded-lg text-center text-lg font-bold focus:ring-2 focus:ring-[#DC2626] focus:border-[#DC2626]"
                                        />
                                    ))}
                                </div>
                                {error && <p className="text-red-500 text-xs text-center mb-4">{error}</p>}
                                <button onClick={onResend} className="w-full text-sm text-[#DC2626] font-medium hover:underline">
                                    Resend Code
                                </button>
                            </>
                        )}
                    </motion.div>
                </motion.div>
            )}
        </AnimatePresence>
    );
}