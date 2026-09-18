/*
 * GLS CRM — Frontoffice entry (Vite module).
 *
 * The Frontoffice deliberately stays light: Bootstrap 5 (static) + this file.
 * No Alpine, no jQuery plugins — NEVER import or start Alpine here.
 */

import { initMotion } from './reveal';

// Module scripts run after the document is parsed, so the DOM is ready here.
initMotion();

// Frontoffice behaviour goes here as the public area grows.
