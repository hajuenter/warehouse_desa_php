import QueueDetailTable from "./QueueDetailTable";
import Pagination from "./Pagination";

const LIMIT = 10;

function QueueDetailView({ desaName, kodeDesa, logs, loading, lastUpdated, offset, onBack, onPrev, onNext }) {
  return (
    <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div className="flex items-center gap-3">
          <button
            onClick={onBack}
            className="cursor-pointer rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-50"
          >
            &larr; Kembali
          </button>

          <div>
            <h3 className="font-semibold text-slate-800">{desaName || kodeDesa}</h3>
            <p className="mt-0.5 text-xs text-slate-500">{kodeDesa}</p>
          </div>
        </div>

        {lastUpdated && (
          <div className="text-xs text-slate-500 sm:text-sm">
            Update terakhir : <span className="font-medium">{lastUpdated.toLocaleTimeString("id-ID")}</span>
          </div>
        )}
      </div>

      <QueueDetailTable rows={logs} loading={loading} />

      <div className="border-t border-slate-200 bg-slate-50 px-4 py-4 sm:px-6">
        <Pagination
          offset={offset}
          limit={LIMIT}
          count={logs.length}
          onPrev={onPrev}
          onNext={onNext}
        />
      </div>
    </section>
  );
}

export default QueueDetailView;
