'use client';

import React, { useState, useEffect, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { doc, getDoc, updateDoc, serverTimestamp, writeBatch } from 'firebase/firestore';
import { useFirestore } from '@/firebase';
import { createUserWithEmailAndPassword, updateProfile } from 'firebase/auth';
import { firebaseAuth } from '@/lib/firebase/client';
import { completeSignup } from '@/lib/session-client';
import { useTranslation } from '@/lib/i18n';
import { useToast } from '@/hooks/use-toast';
import { RoleAuthShell, AuthInput, AuthButton } from '@/components/auth/RoleAuthShell';
import { PasswordRequirementsAlert } from '@/components/shadcn-space/alert/alert-01';
import { Building2, MapPin, Lock, Eye, EyeOff, User, CheckCircle2, AlertTriangle } from 'lucide-react';
import LoadingSpinner from '@/components/loading-spinner';

const formSchema = z.object({
  name: z.string().min(3, 'Full name is required'),
  password: z.string()
    .min(8, 'Password must be at least 8 characters.')
    .regex(/[a-z]/, 'Include a lowercase letter.')
    .regex(/[A-Z]/, 'Include an uppercase letter.')
    .regex(/[0-9]/, 'Include a number.'),
  confirmPassword: z.string(),
}).refine((data) => data.password === data.confirmPassword, {
  message: 'Passwords do not match',
  path: ['confirmPassword'],
});

type FormValues = z.infer<typeof formSchema>;

type InviteData = {
  id: string;
  agencyId: string;
  agencyName: string;
  managerName: string;
  email: string;
  phoneNumber?: string;
  stationName: string;
  status: 'pending' | 'accepted';
};

function JoinFlow() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const firestore = useFirestore();
  const { toast } = useToast();

  const inviteId = searchParams.get('invite');
  const [invite, setInvite] = useState<InviteData | null>(null);
  const [isLoadingInvite, setIsLoadingInvite] = useState(true);
  const [inviteError, setInviteError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const [isDone, setIsDone] = useState(false);
  const { language } = useTranslation();
  const fr = language === 'fr';
  const copy = fr
    ? {
        doneTitle: 'Tout est prêt', doneSubtitle: 'Votre compte de gare est actif.',
        activated: 'Compte activé', redirecting: 'Redirection vers votre tableau de bord…',
        errorTitle: 'Invitation indisponible', errorSubtitle: 'Nous n’avons pas pu ouvrir cette invitation de gare.',
        newInvite: 'Demandez un nouveau lien d’invitation à votre responsable d’agence.',
        title: 'Configurer votre compte de gare',
        subtitle: 'Vous êtes invité à gérer {station}.',
        aStation: 'une gare',
        assignment: 'Votre affectation', agency: 'Agence', station: 'Gare', loginEmail: 'E-mail de connexion',
        fullName: 'Votre nom complet', fullNamePlaceholder: 'Votre nom légal',
        password: 'Créer un mot de passe', passwordPlaceholder: 'Créez un mot de passe fort',
        confirm: 'Confirmer le mot de passe', confirmPlaceholder: 'Répétez votre mot de passe',
        show: 'Afficher le mot de passe', hide: 'Masquer le mot de passe',
        submit: 'Activer le compte de gare',
      }
    : {
        doneTitle: 'You are all set', doneSubtitle: 'Your station account is active.',
        activated: 'Account activated', redirecting: 'Taking you to your station dashboard…',
        errorTitle: 'Invite unavailable', errorSubtitle: 'We could not open this station invitation.',
        newInvite: 'Ask your agency manager for a new invite link.',
        title: 'Set up your station account',
        subtitle: 'You have been invited to manage {station}.',
        aStation: 'a station',
        assignment: 'Your assignment', agency: 'Agency', station: 'Station', loginEmail: 'Login email',
        fullName: 'Your full name', fullNamePlaceholder: 'Your legal name',
        password: 'Create a password', passwordPlaceholder: 'Create a strong password',
        confirm: 'Confirm password', confirmPlaceholder: 'Repeat your password',
        show: 'Show password', hide: 'Hide password',
        submit: 'Activate station account',
      };

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: { name: '', password: '', confirmPassword: '' },
  });
  const errors = form.formState.errors;
  const password = form.watch('password');
  const confirmPassword = form.watch('confirmPassword');

  useEffect(() => {
    if (!inviteId || !firestore) return;
    const fetchInvite = async () => {
      try {
        const snap = await getDoc(doc(firestore, 'agencyInvites', inviteId));
        if (!snap.exists()) {
          setInviteError('This invite link is invalid or has expired.');
        } else {
          const data = { ...(snap.data() as Omit<InviteData, 'id'>), id: snap.id };
          if (data.status === 'accepted') {
            setInviteError('This invite has already been used.');
          } else {
            setInvite(data);
          }
        }
      } catch {
        setInviteError('Unable to verify this invite. Please try again.');
      } finally {
        setIsLoadingInvite(false);
      }
    };
    fetchInvite();
  }, [inviteId, firestore]);

  const onSubmit = async (values: FormValues) => {
    if (!invite || !firestore) return;
    setIsLoading(true);
    try {
      const cred = await createUserWithEmailAndPassword(firebaseAuth, invite.email, values.password);
      await updateProfile(cred.user, { displayName: values.name });
      const userId = cred.user.uid;

      const batch = writeBatch(firestore);

      batch.set(doc(firestore, 'sub_agents', userId), {
        uid: userId,
        name: values.name,
        email: invite.email,
        phoneNumber: invite.phoneNumber || '',
        role: 'sub_agent',
        managedAgencyId: invite.agencyId,
        stationName: invite.stationName,
        createdAt: serverTimestamp(),
        isActive: true,
      });

      batch.set(doc(firestore, 'wallets', userId), {
        balance: 0,
        points: 0,
        currency: 'XAF',
        createdAt: serverTimestamp(),
      });

      batch.update(doc(firestore, 'agencyInvites', invite.id), {
        status: 'accepted',
        acceptedAt: serverTimestamp(),
        acceptedByUid: userId,
      });

      await batch.commit();

      const completion = await completeSignup(cred.user, 'sub_agent', 'active');
      if (!completion.ok) {
        throw new Error('Your account was created, but we could not start your session. Please sign in.');
      }

      setIsDone(true);
      setTimeout(() => router.replace(completion.destination), 2000);
    } catch (err: any) {
      const message =
        err.code === 'auth/email-already-in-use'
          ? 'This email is already registered. Try signing in.'
          : err.message;
      toast({ variant: 'destructive', title: 'Registration failed', description: message });
    } finally {
      setIsLoading(false);
    }
  };

  if (isLoadingInvite) return <LoadingSpinner />;

  if (isDone) {
    return (
      <RoleAuthShell role="station" type="signup" title={copy.doneTitle} subtitle={copy.doneSubtitle}>
        <div className="rounded-2xl border border-success/30 bg-success/10 p-6 text-center">
          <CheckCircle2 className="mx-auto size-10 text-success" aria-hidden="true" />
          <p className="mt-4 text-sm font-semibold text-foreground">{copy.activated}</p>
          <p className="mt-1 text-xs text-muted-foreground">{copy.redirecting}</p>
        </div>
      </RoleAuthShell>
    );
  }

  if (inviteError) {
    return (
      <RoleAuthShell role="station" type="signup" title={copy.errorTitle} subtitle={copy.errorSubtitle}>
        <div className="rounded-2xl border border-destructive/30 bg-destructive/10 p-6 text-center">
          <AlertTriangle className="mx-auto size-8 text-destructive" aria-hidden="true" />
          <p className="mt-3 text-sm font-medium text-foreground">{inviteError}</p>
          <p className="mt-1 text-xs text-muted-foreground">{copy.newInvite}</p>
        </div>
      </RoleAuthShell>
    );
  }

  return (
    <RoleAuthShell
      role="station"
      type="signup"
      title={copy.title}
      subtitle={copy.subtitle.replace('{station}', invite?.stationName ?? copy.aStation)}
    >
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5" noValidate>
        <div className="rounded-2xl border border-border bg-background/80 p-4">
          <p className="text-xs font-medium text-muted-foreground">{copy.assignment}</p>
          <div className="mt-3 space-y-3">
            <div className="flex items-center gap-3">
              <Building2 className="size-4 text-muted-foreground" aria-hidden="true" />
              <div className="min-w-0">
                <p className="text-[11px] text-muted-foreground">{copy.agency}</p>
                <p className="truncate text-sm font-medium text-foreground">{invite?.agencyName}</p>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <MapPin className="size-4 text-muted-foreground" aria-hidden="true" />
              <div className="min-w-0">
                <p className="text-[11px] text-muted-foreground">{copy.station}</p>
                <p className="truncate text-sm font-medium text-foreground">{invite?.stationName}</p>
              </div>
            </div>
            <div className="flex items-center gap-3">
              <User className="size-4 text-muted-foreground" aria-hidden="true" />
              <div className="min-w-0">
                <p className="text-[11px] text-muted-foreground">{copy.loginEmail}</p>
                <p className="truncate text-sm font-medium text-foreground">{invite?.email}</p>
              </div>
            </div>
          </div>
        </div>

        <div className="space-y-1.5">
          <label htmlFor="station-name" className="text-sm font-medium text-foreground">{copy.fullName}</label>
          <AuthInput id="station-name" icon={User} autoComplete="name" placeholder={copy.fullNamePlaceholder} error={errors.name?.message} {...form.register('name')} />
        </div>

        <div className="space-y-1.5">
          <label htmlFor="station-password" className="text-sm font-medium text-foreground">{copy.password}</label>
          <div className="relative">
            <AuthInput
              id="station-password"
              icon={Lock}
              type={showPassword ? 'text' : 'password'}
              autoComplete="new-password"
              placeholder={copy.passwordPlaceholder}
              error={errors.password?.message}
              className="pe-11"
              {...form.register('password')}
            />
            <button
              type="button"
              onClick={() => setShowPassword((value) => !value)}
              aria-label={showPassword ? copy.hide : copy.show}
              aria-pressed={showPassword}
              className="absolute end-3.5 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-foreground"
            >
              {showPassword ? <EyeOff className="size-4" aria-hidden="true" /> : <Eye className="size-4" aria-hidden="true" />}
            </button>
          </div>
          <PasswordRequirementsAlert password={password} confirmPassword={confirmPassword} showConfirmMatch />
        </div>

        <div className="space-y-1.5">
          <label htmlFor="station-confirm" className="text-sm font-medium text-foreground">{copy.confirm}</label>
          <AuthInput
            id="station-confirm"
            icon={Lock}
            type={showPassword ? 'text' : 'password'}
            autoComplete="new-password"
            placeholder={copy.confirmPlaceholder}
            error={errors.confirmPassword?.message}
            {...form.register('confirmPassword')}
          />
        </div>

        <AuthButton type="submit" loading={isLoading}>
          {copy.submit}
        </AuthButton>
      </form>
    </RoleAuthShell>
  );
}

export default function AgencyJoinPage() {
  return (
    <Suspense fallback={<LoadingSpinner />}>
      <JoinFlow />
    </Suspense>
  );
}
