import { motion } from 'framer-motion';
import { useState, useEffect } from 'react';

interface Props {
    email: string;
    password: string;
    onEmailChange: (v: string) => void;
    onPasswordChange: (v: string) => void;
    emailError?: string;
    passwordError?: string;
}

function isValidEmail(email: string): boolean {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

export default function ProgressiveEmailPasswordFields({ email, password, onEmailChange, onPasswordChange, emailError, passwordError }: Props) {
    const [emailValid, setEmailValid] = useState(false);

    useEffect(() => {
        setEmailValid(isValidEmail(email));
    }, [email]);

    return (
        <div className="space-y-4">
            <div>
                <label className="block text-sm font-medium text-[#0A0A0A] mb-1">Email</label>
                <input
                    type="email"
                    value={email}
                    onChange={(e) => onEmailChange(e.target.value)}
                    className="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-[#DC2626] focus:border-transparent"
                    placeholder="you@example.com"
                />
                {emailError && <p className="text-red-500 text-xs mt-1">{emailError}</p>}
            </div>
            <motion.div
                initial={false}
                animate={{ opacity: emailValid ? 1 : 0.5 }}
                transition={{ duration: 0.18 }}
            >
                <label className="block text-sm font-medium text-[#0A0A0A] mb-1">
                    Password
                    {!emailValid && <span className="ml-2 text-xs text-gray-400">🔒 locked</span>}
                </label>
                <input
                    type="password"
                    value={password}
                    onChange={(e) => onPasswordChange(e.target.value)}
                    disabled={!emailValid}
                    className={`w-full border rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-[#DC2626] focus:border-transparent ${!emailValid ? 'bg-gray-100 cursor-not-allowed border-gray-200' : 'border-gray-300'}`}
                    placeholder="••••••••"
                />
                {passwordError && <p className="text-red-500 text-xs mt-1">{passwordError}</p>}
            </motion.div>
        </div>
    );
}