import { useState } from "react";
import QueueStatusBadge from "./QueueStatusBadge";
import DetailModal from "./DetailModal";
import SortableHeader from "./SortableHeader";
import useSortableData from "../hooks/useSortableData";
import { formatTableName } from "../utils/formatText";

const BODY_HEIGHT = 224;
const TABLE_MAX_HEIGHT = 268;

function formatDate(value) {
  if (!value) return "-";
  return new Date(value).toLocaleString("id-ID", { dateStyle: "medium", timeStyle: "short" });
}

function QueueDetailTable({ rows, loading }) {
  const [selectedLog, setSelectedLog] = useState(null);
  const { sortedItems, sortConfig, requestSort } = useSortableData(rows, "received_at", "desc");

  function handleOpenDetail(row) {
    setSelectedLog(row);
  }

  return (
    <div className="overflow-auto" style={{ maxHeight: loading || rows.length === 0 ? undefined : TABLE_MAX_HEIGHT }}>
      {loading && rows.length === 0 && (
        <div style={{ height: BODY_HEIGHT }} className="flex flex-col items-center justify-center gap-3">
          <svg className="h-8 w-8 animate-spin text-slate-300" fill="none" viewBox="0 0 24 24">
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          <p className="text-sm text-slate-400">Memuat data...</p>
        </div>
      )}

      {!loading && rows.length === 0 && (
        <div style={{ height: BODY_HEIGHT }} className="flex flex-col items-center justify-center gap-2">
          <svg className="h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"
            />
          </svg>
          <p className="text-sm font-medium text-slate-500">Tidak ada data antrian untuk filter ini.</p>
        </div>
      )}

      {(!loading || rows.length > 0) && rows.length > 0 && (
        <table className="w-full min-w-140 text-left text-sm">
          <thead className="sticky top-0 z-10 bg-slate-50 shadow-sm">
            <tr className="border-b border-slate-200">
              <SortableHeader sortKey="received_at" sortConfig={sortConfig} onSort={requestSort} className="px-4 py-2.5">
                Diterima
              </SortableHeader>
              <SortableHeader sortKey="table_name" sortConfig={sortConfig} onSort={requestSort} className="px-4 py-2.5">
                Tabel
              </SortableHeader>
              <SortableHeader sortKey="record_id" sortConfig={sortConfig} onSort={requestSort} className="px-4 py-2.5">
                ID
              </SortableHeader>
              <SortableHeader sortKey="action" sortConfig={sortConfig} onSort={requestSort} className="px-4 py-2.5">
                Event
              </SortableHeader>
              <SortableHeader sortKey="status" sortConfig={sortConfig} onSort={requestSort} className="px-4 py-2.5">
                Status
              </SortableHeader>
              <th className="px-4 py-2.5"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {sortedItems.map((row) => (
              <tr key={row.id} className="hover:bg-slate-50">
                <td className="whitespace-nowrap px-4 py-2.5 text-slate-500">{formatDate(row.received_at)}</td>
                <td className="px-4 py-2.5 font-medium text-slate-700">{formatTableName(row.table_name)}</td>
                <td className="px-4 py-2.5 text-slate-500">#{row.record_id ?? "-"}</td>
                <td className="px-4 py-2.5">
                  <QueueStatusBadge value={row.action} variant="action" />
                </td>
                <td className="px-4 py-2.5">
                  <QueueStatusBadge value={row.status} />
                </td>
                <td className="px-4 py-2.5 text-right">
                  <button onClick={() => handleOpenDetail(row)} className="text-xs font-medium text-slate-500 hover:text-slate-900">
                    Detail
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}

      <DetailModal log={selectedLog} onClose={() => setSelectedLog(null)} />
    </div>
  );
}

export default QueueDetailTable;
