export const API_URL =
  window.__APP_CONFIG__?.VITE_API_URL ||
  import.meta.env.VITE_API_URL ||
  "http://localhost:3000/api";

export const POLL_INTERVAL =
  Number(window.__APP_CONFIG__?.VITE_POLL_INTERVAL) ||
  Number(import.meta.env.VITE_POLL_INTERVAL) ||
  5000;
