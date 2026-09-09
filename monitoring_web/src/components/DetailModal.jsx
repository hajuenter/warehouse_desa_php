import { useEffect } from "react";
import StatusBadge from "./StatusBadge";
import PayloadDetail from "./PayloadDetail";
import { formatTableName } from "../utils/formatText";

function formatDate(value) {
  return new Date(value).toLocaleString("id-ID", {
    dateStyle: "full",
    timeStyle: "medium",
  });
}

function DetailModal({ log, onClose }) {
  useEffect(() => {
    if (!log) return;

    function handleKeyDown(e) {
      if (e.key === "Escape") onClose();
    }

    document.addEventListener("keydown", handleKeyDown);
    document.body.style.overflow = "hidden";

    return () => {
      document.removeEventListener("keydown", handleKeyDown);
      document.body.style.overflow = "";
    };
  }, [log, onClose]);

  if (!log) return null;

  return (
    <div className="fixed inset-0 z-100 flex items-center justify-center bg-slate-900/50 p-4" onClick={onClose}>
      <div className="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-xl" onClick={(e) => e.stopPropagation()}>
        {/* Header */}
        <div className="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
          <div className="min-w-0">
            <h3 className="text-base font-semibold text-slate-900">Detail Event Sinkronisasi</h3>
            <p className="mt-1 truncate text-xs text-slate-500">{formatDate(log.received_at)}</p>
          </div>

          <button onClick={onClose} className="shrink-0 rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
              <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* Body - scrollable */}
        <div className="min-h-0 flex-1 overflow-y-auto px-5 py-4">
          <div className="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div>
              <p className="text-xs text-slate-400">Desa</p>
              <p className="text-sm font-medium text-slate-800">{log.kode_desa}</p>
            </div>
            <div>
              <p className="text-xs text-slate-400">Tabel</p>
              <p className="text-sm font-medium text-slate-800">{formatTableName(log.table_name)}</p>
            </div>
            <div>
              <p className="text-xs text-slate-400">ID Record</p>
              <p className="text-sm font-medium text-slate-800">#{log.record_id ?? "-"}</p>
            </div>
            <div>
              <p className="mb-1 text-xs text-slate-400">Aksi / Status</p>
              <div className="flex gap-1.5">
                <StatusBadge value={log.action} variant="action" />
                <StatusBadge value={log.status} />
              </div>
            </div>
          </div>

          {log.event_id && <p className="mb-3 font-mono text-xs text-slate-400">Event ID : {log.event_id}</p>}

          {log.status === "FAILED" && log.error_message && (
            <div className="mb-3 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">{log.error_message}</div>
          )}

          <PayloadDetail action={log.action} payload={log.payload} />
        </div>
      </div>
    </div>
  );
}

export default DetailModal;
