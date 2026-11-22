import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const VIEWPORTS = [
  { name: 'mobile', width: 390, height: 844 },
  { name: 'tablet', width: 820, height: 1180 },
  { name: 'desktop', width: 1440, height: 900 },
];

const THEMES = ['light', 'dark'];

const PAGES = [
  { name: 'home', url: '/' },
  { name: 'services', url: '/services' },
  { name: 'services-bus', url: '/services/bus_tickets' },
  { name: 'about', url: '/about' },
  { name: 'agencies', url: '/agencies' },
  { name: 'contact', url: '/contact' },
  { name: 'faq', url: '/faq' },
  { name: 'terms', url: '/legal/terms' },
  { name: 'privacy', url: '/legal/privacy' },
  { name: 'cookies', url: '/legal/cookies' },
];

const SHOTS_DIR = 'docs/shots';

function ensureDir(dir: string) {
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
  }
}

test.describe('Marketing site screenshots', () => {
  for (const viewport of VIEWPORTS) {
    for (const theme of THEMES) {
      for (const page of PAGES) {
        test(`${page.name} @ ${viewport.name} ${theme}`, async ({ page: pwPage }) => {
          await pwPage.setViewportSize({ width: viewport.width, height: viewport.height });
          
          // Set theme via localStorage before navigation
          await pwPage.addInitScript((theme) => {
            localStorage.setItem('theme', theme);
            document.documentElement.setAttribute('data-theme', theme);
          }, theme);
          
          await pwPage.goto(page.url, { waitUntil: 'networkidle' });
          
          // Wait for any animations to settle
          await pwPage.waitForTimeout(500);
          
          const filename = `${page.name}-${viewport.name}-${theme}.png`;
          const filepath = path.join(SHOTS_DIR, filename);
          
          ensureDir(SHOTS_DIR);
          
          await pwPage.screenshot({ 
            path: filepath, 
            fullPage: true,
            animations: 'disabled',
          });
          
          console.log(`Saved: ${filepath}`);
        });
      }
    }
  }
});