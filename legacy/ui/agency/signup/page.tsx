'use client';

import { useEffect, useMemo, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { signInWithEmailAndPassword } from 'firebase/auth';

import { authApiError } from '@/lib/auth/auth-errors';
import { agencySignupSchema, type AgencySignupValues } from '@/lib/auth/signup-schemas';
import { firebaseAuth } from '@/lib/firebase/client';
import { completeSignup } from '@/lib/session-client';
import { useTranslation } from '@/lib/i18n';
import { useToast } from '@/hooks/use-toast';
import { RoleAuthShell, AuthInput, AuthButton } from '@/components/auth/RoleAuthShell';
import { Checkbox } from '@/components/ui/checkbox';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Building, Phone, Mail, Lock, Users, MapPin, Eye, EyeOff, ShieldCheck } from 'lucide-react';

const CITIES = [
  'Douala', 'Yaoundé', 'Bamenda', 'Bafoussam', 'Garoua', 'Maroua',
  'Ngaoundéré', 'Bertoua', 'Ebolowa', 'Buea', 'Kribi', 'Limbe',
  'Dschang', 'Foumban', 'Kousseri', 'Edéa', 'Kumbo', 'Nkongsamba', 'Tiko', 'Mbalmayo',
];

type AgencyFormValue = AgencySignupValues;

const FLEET_TYPES = ['bus75', 'bus65', 'bus50', 'bus30', 'bus18'] as const;
const FLEET_LABELS: Record<(typeof FLEET_TYPES)[number], string> = {
  bus75: '75 seats',
  bus65: '65 seats',
  bus50: '50 seats',
  bus30: '30 seats',
  bus18: '18 seats',
};

export default function AgencySignupPage() {
  const router = useRouter();
  const { toast } = useToast();
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);
  const { language } = useTranslation();
  const fr = language === 'fr';
  const copy = fr
    ? {
        agencyName: 'Nom de l’agence', agencyPlaceholder: 'Inter-City Express',
        managerName: 'Nom du responsable', managerPlaceholder: 'Nom légal complet',
        email: 'E-mail professionnel', phone: 'Numéro de téléphone',
        office: 'Siège social', officePlaceholder: 'Rue, quartier, ville',
        cities: 'Villes desservies', selected: 'sélectionnée(s)', fleet: 'Taille de la flotte',
        optional: '(facultatif)', password: 'Mot de passe', passwordPlaceholder: 'Créez un mot de passe fort',
        show: 'Afficher le mot de passe', hide: 'Masquer le mot de passe',
        review: 'Les comptes agence sont vérifiés avant activation. Nous pouvons vous contacter pour confirmer votre flotte et vos villes desservies.',
        submit: 'Créer mon compte agence', seats: 'places',
      }
    : {
        agencyName: 'Agency name', agencyPlaceholder: 'Inter-City Express',
        managerName: 'Manager name', managerPlaceholder: 'Legal full name',
        email: 'Work email', phone: 'Phone number',
        office: 'Head office', officePlaceholder: 'Street, neighbourhood, city',
        cities: 'Operating cities', selected: 'selected', fleet: 'Fleet size',
        optional: '(optional)', password: 'Password', passwordPlaceholder: 'Create a strong password',
        show: 'Show password', hide: 'Hide password',
        review: 'Agency accounts are reviewed before activation. We may contact you to verify your fleet and operating cities.',
        submit: 'Create agency account', seats: 'seats',
      };

  const formSchema = useMemo(() => agencySignupSchema(fr), [fr]);
  const form = useForm<AgencyFormValue>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      agencyName: '',
      managerName: '',
      email: '',
      phoneNumber: '',
      headOffice: '',
      country: 'Cameroon',
      cities: [],
      fleet: { bus75: 0, bus65: 0, bus50: 0, bus30: 0, bus18: 0 },
      password: '',
    },
  });

  useEffect(() => { form.clearErrors(); }, [fr, form]);

  const selectedCities = form.watch('cities');
  const errors = form.formState.errors;

  const onSubmit = async (data: AgencyFormValue) => {
    setLoading(true);

    try {
      const res = await fetch('/api/auth/signup', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          role: 'agency',
          full_name: data.managerName,
          email: data.email,
          phone: data.phoneNumber,
          password: data.password,
          agency_name: data.agencyName,
          head_office: data.headOffice,
          country: data.country,
          operating_cities: data.cities,
          fleet: data.fleet,
        }),
      });
      const result = await res.json();
      if (!res.ok) throw new Error(result.error || 'Signup failed');

      const cred = await signInWithEmailAndPassword(firebaseAuth, data.email, data.password);
      const completion = await completeSignup(cred.user, result.role ?? 'agency', result.status);
      if (!completion.ok) {
        toast({
          variant: 'destructive',
          title: fr ? 'Compte créé' : 'Account created',
          description: fr
            ? 'Votre agence existe, mais la session n’a pas pu démarrer. Réessayez dans un instant.'
            : 'Your agency was created, but we could not start your session. Try again in a moment.',
        });
        return;
      }

      toast({
        title: fr ? 'Demande d’agence reçue' : 'Agency application received',
        description: fr ? 'Votre compte transport est en cours de vérification.' : 'Your transport account is now under review.',
      });
      router.replace(completion.destination);
    } catch (error) {
      toast({ variant: 'destructive', title: fr ? 'Inscription échouée' : 'Signup failed', description: authApiError((error as Error).message, fr) });
    } finally {
      setLoading(false);
    }
  };

  return (
    <RoleAuthShell role="agency" type="signup">
      <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-5" noValidate>
        <div className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-1.5">
            <label htmlFor="agency-name" className="text-sm font-medium text-foreground">{copy.agencyName}</label>
            <AuthInput id="agency-name" icon={Building} placeholder={copy.agencyPlaceholder} error={errors.agencyName?.message} {...form.register('agencyName')} />
          </div>
          <div className="space-y-1.5">
            <label htmlFor="manager-name" className="text-sm font-medium text-foreground">{copy.managerName}</label>
            <AuthInput id="manager-name" icon={Users} placeholder={copy.managerPlaceholder} error={errors.managerName?.message} {...form.register('managerName')} />
          </div>
          <div className="space-y-1.5">
            <label htmlFor="agency-email" className="text-sm font-medium text-foreground">{copy.email}</label>
            <AuthInput id="agency-email" icon={Mail} type="email" inputMode="email" autoComplete="email" placeholder="fleet@agency.cm" error={errors.email?.message} {...form.register('email')} />
          </div>
          <div className="space-y-1.5">
            <label htmlFor="agency-phone" className="text-sm font-medium text-foreground">{copy.phone}</label>
            <AuthInput id="agency-phone" icon={Phone} type="tel" inputMode="tel" autoComplete="tel" placeholder="+237 6XX XXX XXX" error={errors.phoneNumber?.message} {...form.register('phoneNumber')} />
          </div>
        </div>

        <div className="space-y-1.5">
          <label htmlFor="agency-office" className="text-sm font-medium text-foreground">{copy.office}</label>
          <AuthInput id="agency-office" icon={MapPin} placeholder={copy.officePlaceholder} error={errors.headOffice?.message} {...form.register('headOffice')} />
        </div>

        <fieldset className="rounded-2xl border border-border bg-background/80 p-4">
          <div className="flex items-center justify-between gap-3">
            <legend className="text-sm font-medium text-foreground">{copy.cities}</legend>
            <span className="text-xs text-muted-foreground">{selectedCities.length} {copy.selected}</span>
          </div>
          <ScrollArea className="mt-3 h-40">
            <div className="grid grid-cols-2 gap-x-4 gap-y-1 pe-3">
              {CITIES.map((city) => {
                const checked = selectedCities.includes(city);
                return (
                  <label key={city} className="flex cursor-pointer items-center gap-2.5 rounded-lg px-2 py-2 text-sm text-foreground hover:bg-muted/60">
                    <Checkbox
                      className="min-h-0"
                      checked={checked}
                      onCheckedChange={(next) => {
                        const current = form.getValues('cities');
                        form.setValue(
                          'cities',
                          next ? [...current, city] : current.filter((value) => value !== city),
                          { shouldValidate: true },
                        );
                      }}
                    />
                    {city}
                  </label>
                );
              })}
            </div>
          </ScrollArea>
          {errors.cities?.message && <p className="mt-2 text-xs text-destructive">{errors.cities.message}</p>}
        </fieldset>

        <fieldset className="rounded-2xl border border-border bg-background/80 p-4">
          <legend className="text-sm font-medium text-foreground">{copy.fleet} <span className="font-normal text-muted-foreground">{copy.optional}</span></legend>
          <div className="mt-3 grid grid-cols-5 gap-2">
            {FLEET_TYPES.map((type) => (
              <div key={type} className="space-y-1.5">
                <label htmlFor={`fleet-${type}`} className="block text-center text-[11px] text-muted-foreground">
                  {FLEET_LABELS[type].replace('seats', copy.seats)}
                </label>
                <input
                  id={`fleet-${type}`}
                  type="number"
                  min={0}
                  inputMode="numeric"
                  className="h-11 w-full rounded-lg border border-border bg-background text-center text-sm text-foreground outline-none focus:border-ring focus:ring-2 focus:ring-ring/25"
                  {...form.register(`fleet.${type}` as const)}
                />
              </div>
            ))}
          </div>
        </fieldset>

        <div className="space-y-1.5">
          <label htmlFor="agency-password" className="text-sm font-medium text-foreground">{copy.password}</label>
          <div className="relative">
            <AuthInput
              id="agency-password"
              icon={Lock}
              showPasswordRequirements
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
              className="absolute end-3.5 top-3 text-muted-foreground transition-colors hover:text-foreground"
            >
              {showPassword ? <EyeOff className="size-4" aria-hidden="true" /> : <Eye className="size-4" aria-hidden="true" />}
            </button>
          </div>
        </div>

        <div className="flex items-start gap-3 rounded-2xl border border-border bg-background/80 p-4">
          <ShieldCheck className="mt-0.5 size-4 shrink-0 text-success" aria-hidden="true" />
          <p className="text-xs leading-5 text-muted-foreground">
            {copy.review}
          </p>
        </div>

        <AuthButton type="submit" loading={loading}>
          {copy.submit}
        </AuthButton>
      </form>
    </RoleAuthShell>
  );
}
