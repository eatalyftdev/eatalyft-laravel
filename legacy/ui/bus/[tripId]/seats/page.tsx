'use client';

import React, { useState, useEffect, useCallback, useMemo } from 'react';
import { useParams, useRouter } from 'next/navigation';
import { useFirestore } from '@/firebase';
import { subscribeTripSeats } from '@/lib/services/busService';
import { useBusStore } from '@/store/busStore';
import { BusTrip, BusSeat, BusStation } from '@/lib/types/bus';
import { formatXAF, formatTripDate, formatTime, cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Bus, ChevronLeft, ArrowRight, AlertCircle, RefreshCw,
  MapPin, Calendar, Clock, Users, CheckCircle2,
} from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import EnhancedSeatMap from '@/components/bus/EnhancedSeatMap';
import TerminalSelection from '@/components/bus/TerminalSelection';

const MAX_SEATS = 5;

export default function SeatSelectionPage() {
  const params = useParams();
  const router = useRouter();
  const db = useFirestore();
  const { toast } = useToast();
  const store = useBusStore();
  const tripId = params.tripId as string;

  const [trip, setTrip] = useState<BusTrip | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [selectedSeats, setSelectedSeats] = useState<string[]>([]);
  const [currentDeck, setCurrentDeck] = useState<'lower' | 'upper'>('lower');
  const [showTerminalSelection, setShowTerminalSelection] = useState(false);
  const [selectedBoarding, setSelectedBoarding] = useState<string | null>(null);
  const [selectedDropping, setSelectedDropping] = useState<string | null>(null);

  const toggleSeat = useCallback(
    (seatNumber: string) => {
      setSelectedSeats((prev) => {
        if (prev.includes(seatNumber)) {
          return prev.filter((s) => s !== seatNumber);
        }
        if (prev.length >= MAX_SEATS) {
          toast({ title: 'Max ' + MAX_SEATS + ' seats per booking', variant: 'destructive' });
          return prev;
        }
        return [...prev, seatNumber];
      });
    },
    [toast]
  );

  useEffect(() => {
    if (!db || !tripId) return;
    setLoading(true);
    setError(null);

    const unsubscribe = subscribeTripSeats(db, tripId, (updatedTrip) => {
      setTrip(updatedTrip);
      setLoading(false);

      const nowUnavailable = updatedTrip.seats
        .filter((s) => s.status !== 'available')
        .map((s) => s.seatNumber);

      setSelectedSeats((prev) => {
        const stillValid = prev.filter((s) => !nowUnavailable.includes(s));
        if (stillValid.length < prev.length) {
          toast({
            title: 'Seat update',
            description: 'Some of your selected seats were just booked. Please reselect.',
            variant: 'destructive',
          });
        }
        return stillValid;
      });
    });

    return () => unsubscribe();
  }, [db, tripId, toast]);

  const handleProceed = () => {
    if (!trip || selectedSeats.length === 0) return;
    
    if (trip.stations && trip.stations.length > 0) {
      setShowTerminalSelection(true);
      return;
    }
    
    proceedToCheckout();
  };

  const proceedToCheckout = () => {
    if (!trip || selectedSeats.length === 0) return;
    store.setSelectedTrip(trip);
    const params = new URLSearchParams({
      type: 'Bus',
      fare: String(trip.baseFare),
      seats: String(selectedSeats.length),
      tripId,
      seatNumbers: selectedSeats.join(','),
      ...(selectedBoarding && { boardingStation: selectedBoarding }),
      ...(selectedDropping && { droppingStation: selectedDropping }),
    });
    router.push('/checkout?' + params.toString());
  };

  const handleBoardingChange = (stationId: string) => {
    setSelectedBoarding(stationId);
  };

  const handleDroppingChange = (stationId: string) => {
    setSelectedDropping(stationId);
  };

  const handleTerminalProceed = () => {
    setShowTerminalSelection(false);
    proceedToCheckout();
  };

  const handleTerminalBack = () => {
    setShowTerminalSelection(false);
    setSelectedBoarding(null);
    setSelectedDropping(null);
  };

  const totalFare = (trip?.baseFare ?? 0) * selectedSeats.length;

  return (
    React.createElement('div', { className: 'min-h-screen bg-background pb-36' },
      React.createElement('div', { className: 'sticky top-0 z-20 bg-background/80 backdrop-blur-md border-b border-border' },
        React.createElement('div', { className: 'max-w-2xl mx-auto px-4 py-3 flex items-center gap-3' },
          React.createElement('button', { onClick: () => router.back(), className: 'p-2 rounded-xl hover:bg-muted transition-colors' },
            React.createElement(ChevronLeft, { className: 'size-5' })
          ),
          React.createElement('div', { className: 'flex items-center gap-2' },
            React.createElement(Bus, { className: 'size-5 text-primary' }),
            React.createElement('h1', { className: 'font-bold text-lg' }, 'Select Seats')
          )
        )
      ),
      React.createElement('div', { className: 'max-w-2xl mx-auto px-4 py-4 space-y-4' },
        trip && React.createElement('div', { className: 'rounded-2xl border border-border bg-card p-4 shadow-sm' },
          React.createElement('div', { className: 'flex items-center gap-2 mb-3' },
            React.createElement('div', { className: 'size-8 rounded-lg bg-primary/10 flex items-center justify-center' },
              React.createElement(Bus, { className: 'size-4 text-primary' })
            ),
            React.createElement('div', null,
              React.createElement('p', { className: 'font-semibold text-sm' }, trip.busCompany ?? ''),
              React.createElement('p', { className: 'text-xs text-muted-foreground' }, trip.busNumber ?? '' + ' \u00B7 ' + (trip.busType ?? ''))
            )
          ),
          React.createElement('div', { className: 'flex items-center gap-3 mb-2' },
            React.createElement('div', { className: 'text-center' },
              React.createElement('p', { className: 'font-bold' }, trip.from?.substring(0, 3).toUpperCase()),
              React.createElement('p', { className: 'text-xs text-muted-foreground' }, formatTime(trip.departureTime ?? ''))
            ),
            React.createElement('div', { className: 'flex-1 flex items-center' },
              React.createElement('div', { className: 'h-px flex-1 border-t border-dashed border-border' }),
              React.createElement(ArrowRight, { className: 'size-3 text-muted-foreground mx-1' }),
              React.createElement('div', { className: 'h-px flex-1 border-t border-dashed border-border' })
            ),
            React.createElement('div', { className: 'text-center' },
              React.createElement('p', { className: 'font-bold' }, trip.to?.substring(0, 3).toUpperCase()),
              React.createElement('p', { className: 'text-xs text-muted-foreground' }, formatTime(trip.arrivalTime ?? ''))
            )
          ),
          React.createElement('div', { className: 'flex items-center gap-3 text-xs text-muted-foreground' },
            React.createElement('span', { className: 'flex items-center gap-1' },
              React.createElement(Calendar, { className: 'size-3' }),
              formatTripDate(trip.departureDate ?? '')
            ),
            React.createElement('span', { className: 'flex items-center gap-1' },
              React.createElement(Users, { className: 'size-3' }),
              (trip.availableSeats ?? 0) + ' seats left'
            )
          )
        ),
        loading && React.createElement('div', { className: 'space-y-4 animate-pulse' },
          React.createElement('div', { className: 'h-5 bg-muted rounded w-1/3 mx-auto' }),
          [1, 2, 3, 4].map((r) => React.createElement('div', { key: r, className: 'grid grid-cols-5 gap-2' },
            [1, 2, 3, 4, 5].map((c) => React.createElement('div', { key: c, className: 'aspect-square bg-muted rounded-lg' }))
          ))
        ),
        error && !loading && React.createElement('div', { className: 'flex flex-col items-center gap-3 py-12 text-center' },
          React.createElement(AlertCircle, { className: 'size-10 text-destructive' }),
          React.createElement('p', { className: 'text-sm text-muted-foreground' }, error),
          React.createElement(Button, { variant: 'outline', size: 'sm', onClick: () => router.refresh() },
            React.createElement(RefreshCw, { className: 'size-4 mr-2' }),
            'Try Again'
          )
        ),
        !loading && trip && !showTerminalSelection && React.createElement(React.Fragment, null,
          React.createElement(EnhancedSeatMap, {
            busClass: trip.busType,
            seats: trip.seats,
            selectedSeats: selectedSeats,
            pricePerSeat: trip.baseFare,
            onSeatClick: toggleSeat,
            onDeckChange: setCurrentDeck,
            currentDeck: currentDeck,
            isAdmin: false,
            showLegend: true,
            maxSelection: MAX_SEATS,
            showPrice: true,
          }),
          React.createElement(Button, {
            className: 'w-full rounded-xl',
            disabled: selectedSeats.length === 0,
            onClick: handleProceed,
          }, selectedSeats.length === 0 ? 'Select at least 1 seat' : 'Proceed to ' + (trip.stations?.length ? 'Terminal Selection' : 'Payment') + ' \u00B7 ' + formatXAF(totalFare),
            selectedSeats.length > 0 && React.createElement(ArrowRight, { className: 'size-4 ml-2' })
          ),
          React.createElement('p', { className: 'text-center text-xs text-muted-foreground' },
            'Max ' + MAX_SEATS + ' seats per booking \u00B7 Live seat availability'
          )
        ),
        showTerminalSelection && trip && trip.stations && trip.stations.length > 0 && React.createElement(React.Fragment, null,
          React.createElement('div', { className: 'sticky top-0 z-20 bg-background/80 backdrop-blur-md border-b border-border' },
            React.createElement('div', { className: 'max-w-2xl mx-auto px-4 py-3 flex items-center gap-3' },
              React.createElement('button', { onClick: () => setShowTerminalSelection(false), className: 'p-2 rounded-xl hover:bg-muted transition-colors' },
                React.createElement(ChevronLeft, { className: 'size-5' })
              ),
              React.createElement('div', { className: 'flex items-center gap-2' },
                React.createElement(Bus, { className: 'size-5 text-primary' }),
                React.createElement('h1', { className: 'font-bold text-lg' }, 'Select Terminals')
              )
            )
          ),
          React.createElement('div', { className: 'max-w-2xl mx-auto px-4 py-4 space-y-4 pb-24' },
            React.createElement(TerminalSelection, {
              stations: trip.stations,
              selectedBoarding: selectedBoarding,
              selectedDropping: selectedDropping,
              onBoardingChange: handleBoardingChange,
              onDroppingChange: handleDroppingChange,
              departureTime: trip.departureTime,
              arrivalTime: trip.arrivalTime,
              onNext: handleTerminalProceed,
              disabled: false,
            })
          )
        ),
        error && !loading && React.createElement('div', { className: 'flex flex-col items-center gap-3 py-12 text-center' },
          React.createElement(AlertCircle, { className: 'size-10 text-destructive' }),
          React.createElement('p', { className: 'text-sm text-muted-foreground' }, error),
          React.createElement(Button, { variant: 'outline', size: 'sm', onClick: () => router.refresh() },
            React.createElement(RefreshCw, { className: 'size-4 mr-2' }),
            'Try Again'
          )
        ),
      React.createElement('div', { className: 'fixed bottom-0 left-0 right-0 z-30 bg-background/95 backdrop-blur-md border-t border-border' },
        React.createElement('div', { className: 'max-w-2xl mx-auto px-4 py-3 space-y-2' },
          selectedSeats.length > 0 && React.createElement('div', { className: 'flex items-center justify-between text-sm' },
            React.createElement('div', { className: 'flex items-center gap-1 flex-wrap' },
              React.createElement('span', { className: 'text-muted-foreground' }, 'Selected:'),
              selectedSeats.map((s) => React.createElement(Badge, { key: s, variant: 'secondary', className: 'text-xs' }, s))
            ),
            React.createElement('div', { className: 'text-right' },
              React.createElement('p', { className: 'text-xs text-muted-foreground' }, selectedSeats.length + ' \u00D7 ' + formatXAF(trip?.baseFare ?? 0)),
              React.createElement('p', { className: 'font-bold text-primary' }, formatXAF(totalFare))
            )
          ),
          React.createElement(Button, {
            className: 'w-full rounded-xl',
            disabled: selectedSeats.length === 0,
            onClick: handleProceed,
          }, selectedSeats.length === 0 ? 'Select at least 1 seat' : 'Proceed to ' + (trip?.stations?.length ? 'Terminal Selection' : 'Payment') + ' \u00B7 ' + formatXAF(totalFare),
            selectedSeats.length > 0 && React.createElement(ArrowRight, { className: 'size-4 ml-2' })
          ),
          React.createElement('p', { className: 'text-center text-xs text-muted-foreground' },
            'Max ' + MAX_SEATS + ' seats per booking \u00B7 Live seat availability'
          )
        )
),
      )
    )
);
}