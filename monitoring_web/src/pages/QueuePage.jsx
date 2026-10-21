import { useCallback, useEffect, useRef, useState } from "react";

import Navbar from "../components/Navbar";
import TabMenu from "../components/TabMenu";
import Footer from "../components/Footer";
import DesaGrid from "../components/DesaGrid";
import QueueDetailView from "../components/QueueDetailView";

import { fetchQueueSummary, fetchQueue } from "../services/queueService";
import { POLL_INTERVAL } from "../utils/config";

const LIMIT = 10;

function QueuePage() {
  const [desaList, setDesaList] = useState([]);
  const [selectedDesaCode, setSelectedDesaCode] = useState(null);

  const [offset, setOffset] = useState(0);
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [autoRefresh, setAutoRefresh] = useState(true);
  const [lastUpdated, setLastUpdated] = useState(null);

  const offsetRef = useRef(offset);
  const selectedDesaCodeRef = useRef(selectedDesaCode);

  offsetRef.current = offset;
  selectedDesaCodeRef.current = selectedDesaCode;

  useEffect(() => {
    fetchQueueSummary()
      .then((data) => {
        setDesaList(data);
        setLastUpdated(new Date());
      })
      .catch(() => setError("Gagal mengambil daftar desa. Pastikan Receiver berjalan."));
  }, []);

  const loadLogs = useCallback(async ({ silent = false } = {}) => {
    const kodeDesa = selectedDesaCodeRef.current;
    if (!kodeDesa) return;

    if (!silent) setLoading(true);

    try {
      const data = await fetchQueue(kodeDesa, {
        limit: LIMIT,
        offset: offsetRef.current,
      });

      setLogs(data);
      setLastUpdated(new Date());
      setError(null);
    } catch {
      setError("Gagal mengambil data antrian. Pastikan Receiver berjalan.");
    } finally {
      if (!silent) setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (!selectedDesaCode) return;
    setOffset(0);
  }, [selectedDesaCode]);

  useEffect(() => {
    if (!selectedDesaCode) return;
    loadLogs();
  }, [selectedDesaCode, offset, loadLogs]);

  useEffect(() => {
    if (!autoRefresh || !selectedDesaCode || offset !== 0) return;

    const interval = setInterval(() => {
      loadLogs({ silent: true });
    }, POLL_INTERVAL);

    return () => clearInterval(interval);
  }, [autoRefresh, offset, selectedDesaCode, loadLogs]);

  useEffect(() => {
    if (!autoRefresh || selectedDesaCode) return;

    const interval = setInterval(() => {
      fetchQueueSummary()
        .then((data) => {
          setDesaList(data);
          setLastUpdated(new Date());
        })
        .catch(() => { });
    }, POLL_INTERVAL);

    return () => clearInterval(interval);
  }, [autoRefresh, selectedDesaCode]);

  function handleBack() {
    setSelectedDesaCode(null);
    setLogs([]);
    setOffset(0);
    setError(null);
  }

  const selectedDesa = desaList.find((d) => d.kode_desa === selectedDesaCode);

  return (
    <div className="flex min-h-screen flex-col bg-slate-100">
      <Navbar autoRefresh={autoRefresh} lastUpdated={lastUpdated} isOnline={!error} />

      <TabMenu />

      <main className="mx-auto w-full max-w-7xl flex-1 space-y-6 p-4 sm:p-6">
        {/* <section>
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h2 className="text-md font-bold text-slate-900 sm:text-3xl lg:text-2xl">Antrian</h2>
              <p className="mt-2 text-sm text-slate-500">Kondisi tabel antrian sinkronisasi langsung dari tiap desa.</p>
            </div>
          </div>
        </section> */}

        {error && (
          <div className="rounded-lg border border-red-200 bg-red-50 p-4">
            <div className="flex items-center gap-2">
              <span className="text-sm font-medium text-red-700">{error}</span>
            </div>
          </div>
        )}

        {!selectedDesaCode ? (
          <DesaGrid desaList={desaList} lastUpdated={lastUpdated} onSelectDesa={setSelectedDesaCode} />
        ) : (
          <QueueDetailView
            desaName={selectedDesa?.nama_desa}
            kodeDesa={selectedDesaCode}
            logs={logs}
            loading={loading}
            lastUpdated={lastUpdated}
            offset={offset}
            onBack={handleBack}
            onPrev={() => setOffset((prev) => Math.max(0, prev - LIMIT))}
            onNext={() => setOffset((prev) => prev + LIMIT)}
          />
        )}
      </main>

      <Footer />
    </div>
  );
}

export default QueuePage;
