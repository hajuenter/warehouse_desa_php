import { useCallback, useEffect, useRef, useState } from "react";

import Navbar from "../components/Navbar";
import TabMenu from "../components/TabMenu";
import Footer from "../components/Footer";
import LoginHistoryTable from "../components/LoginHistoryTable";
import Pagination from "../components/Pagination";

import { fetchLogs } from "../services/logService";
import { POLL_INTERVAL } from "../utils/config";

const LIMIT = 10;
const TABLE_NAME = "user";

function LoginHistoryPage() {
  const [offset, setOffset] = useState(0);
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [autoRefresh, setAutoRefresh] = useState(true);
  const [lastUpdated, setLastUpdated] = useState(null);

  const offsetRef = useRef(offset);
  offsetRef.current = offset;

  const loadLogs = useCallback(async ({ silent = false } = {}) => {
    if (!silent) {
      setLoading(true);
    }

    try {
      const data = await fetchLogs({
        table_name: TABLE_NAME,
        limit: LIMIT,
        offset: offsetRef.current,
      });

      setLogs(data);
      setLastUpdated(new Date());
      setError(null);
    } catch {
      setError("Gagal mengambil data riwayat login. Pastikan Receiver berjalan.");
    } finally {
      if (!silent) {
        setLoading(false);
      }
    }
  }, []);

  useEffect(() => {
    loadLogs();
  }, [offset, loadLogs]);

  useEffect(() => {
    if (!autoRefresh || offset !== 0) return;

    const interval = setInterval(() => {
      loadLogs({ silent: true });
    }, POLL_INTERVAL);

    return () => clearInterval(interval);
  }, [autoRefresh, offset, loadLogs]);

  return (
    <div className="flex min-h-screen flex-col bg-slate-100">
      <Navbar autoRefresh={autoRefresh} lastUpdated={lastUpdated} isOnline={!error} />

      <TabMenu />

      <main className="mx-auto w-full max-w-7xl flex-1 space-y-6 p-4 sm:p-6">
        <section>
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-md font-bold text-slate-900 sm:text-3xl lg:text-2xl">Riwayat Login</h2>

              <p className="mt-2 text-sm text-slate-500">Riwayat aktivitas login pengguna pada sistem.</p>
            </div>
          </div>
        </section>

        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 p-4">
            <div className="flex items-center gap-2">
              <span className="text-sm font-medium text-red-700">{error}</span>
            </div>
          </div>
        )}

        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          <div className="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
              <h3 className="font-semibold text-slate-800">Daftar Riwayat Login</h3>

              <p className="mt-1 text-sm text-slate-500">Menampilkan aktivitas login terbaru.</p>
            </div>

            {lastUpdated && (
              <div className="text-xs text-slate-500 sm:text-sm">
                Update terakhir : <span className="font-medium">{lastUpdated.toLocaleTimeString("id-ID")}</span>
              </div>
            )}
          </div>

          <LoginHistoryTable logs={logs} loading={loading} />

          <div className="border-t border-slate-200 bg-slate-50 px-4 py-4 sm:px-6">
            <Pagination
              offset={offset}
              limit={LIMIT}
              count={logs.length}
              onPrev={() => setOffset((prev) => Math.max(0, prev - LIMIT))}
              onNext={() => setOffset((prev) => prev + LIMIT)}
            />
          </div>
        </section>
      </main>
      <Footer />
    </div>
  );
}

export default LoginHistoryPage;
