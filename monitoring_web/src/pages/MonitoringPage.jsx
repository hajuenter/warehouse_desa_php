import { useCallback, useEffect, useRef, useState } from "react";

import Navbar from "../components/Navbar";
import TabMenu from "../components/TabMenu";
import DashboardStats from "../components/DashboardStats";
import Footer from "../components/Footer";
import FilterBar from "../components/FilterBar";
import LogTable from "../components/LogTable";
import Pagination from "../components/Pagination";
import { useLocation, useNavigate } from "react-router-dom";
import toast from "react-hot-toast";
import { fetchLogs } from "../services/logService";
import { fetchDesaList } from "../services/metaService";
import { POLL_INTERVAL } from "../utils/config";

const LIMIT = 10;
const STATS_LIMIT = 1000;

function MonitoringPage() {
  const location = useLocation();
  const navigate = useNavigate();
  const [filters, setFilters] = useState({});
  const [offset, setOffset] = useState(0);
  const [logs, setLogs] = useState([]);
  const [statsLogs, setStatsLogs] = useState([]);
  const [totalDesa, setTotalDesa] = useState(0);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [autoRefresh, setAutoRefresh] = useState(true);
  const [lastUpdated, setLastUpdated] = useState(null);

  const filtersRef = useRef(filters);
  const offsetRef = useRef(offset);

  filtersRef.current = filters;
  offsetRef.current = offset;

  const loadLogs = useCallback(async ({ silent = false } = {}) => {
    if (!silent) {
      setLoading(true);
    }

    try {
      const data = await fetchLogs({
        ...filtersRef.current,
        limit: LIMIT,
        offset: offsetRef.current,
      });

      setLogs(data);
      setLastUpdated(new Date());
      setError(null);
    } catch {
      setError("Gagal mengambil data monitoring. Pastikan Receiver berjalan.");
    } finally {
      if (!silent) {
        setLoading(false);
      }
    }
  }, []);

  const loadStats = useCallback(async () => {
    try {
      const data = await fetchLogs({
        ...filtersRef.current,
        limit: STATS_LIMIT,
        offset: 0,
      });

      setStatsLogs(data);
    } catch { }
  }, []);

  useEffect(() => {
    loadLogs();
  }, [filters, offset, loadLogs]);

  useEffect(() => {
    loadStats();
  }, [filters, loadStats]);

  useEffect(() => {
    fetchDesaList()
      .then((data) => setTotalDesa(data.length))
      .catch(() => { });
  }, []);

  useEffect(() => {
    if (!autoRefresh || offset !== 0) return;

    const interval = setInterval(() => {
      loadLogs({ silent: true });
    }, POLL_INTERVAL);

    return () => clearInterval(interval);
  }, [autoRefresh, offset, loadLogs]);

  useEffect(() => {
    if (!autoRefresh) return;

    const interval = setInterval(() => {
      loadStats();
    }, POLL_INTERVAL);

    return () => clearInterval(interval);
  }, [autoRefresh, loadStats]);

  useEffect(() => {
    if (location.state?.loginSuccess) {
      toast.success("Berhasil masuk ke dashboard.");

      navigate(location.pathname, {
        replace: true,
        state: {},
      });
    }
  }, [location, navigate]);

  function handleApplyFilters(newFilters) {
    setFilters(newFilters);
    setOffset(0);
  }

  return (
    <div className="flex min-h-screen flex-col bg-slate-100">
      <Navbar autoRefresh={autoRefresh} lastUpdated={lastUpdated} isOnline={!error} />

      <TabMenu />

      <main className="mx-auto w-full max-w-7xl flex-1 space-y-6 p-4 sm:p-6">
        <section>
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-md lg:text-2xl font-bold text-slate-900 sm:text-3xl">Monitoring Sinkronisasi Data Desa</h2>

              <p className="mt-2 text-sm text-slate-500">Riwayat aktivitas sinkronisasi webhook dari seluruh desa menuju database induk.</p>
            </div>
          </div>
        </section>

        <DashboardStats logs={statsLogs} totalDesa={totalDesa} />

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
              <h3 className="font-semibold text-slate-800">Aktivitas Sinkronisasi</h3>

              <p className="mt-1 text-sm text-slate-500">Menampilkan data monitoring terbaru.</p>
            </div>

            {lastUpdated && (
              <div className="text-xs text-slate-500 sm:text-sm">
                Update terakhir : <span className="font-medium">{lastUpdated.toLocaleTimeString("id-ID")}</span>
              </div>
            )}
          </div>

          <FilterBar onApply={handleApplyFilters} />

          <LogTable logs={logs} loading={loading} />

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

export default MonitoringPage;
