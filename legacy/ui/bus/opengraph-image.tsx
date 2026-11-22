import { buildOgImage, OG_DIMENSIONS } from '@/lib/og-image';

export const runtime = 'edge';
export const alt = 'EataLyft Bus – Book Bus Tickets Online in Cameroon';
export const size = OG_DIMENSIONS;
export const contentType = 'image/png';

export default function OgImage() {
  return buildOgImage({
    title: 'Book Bus Tickets',
    tagline: 'Bamenda · Douala · Yaoundé · All Cameroon',
    meta: 'Inter-city travel in comfort, booked in seconds from your phone.',
  });
}