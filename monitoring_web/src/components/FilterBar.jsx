import { useEffect, useState } from "react";
import { fetchDesaList, fetchTableList } from "../services/metaService";
import { formatTableName } from "../utils/formatText";

function FilterBar({ onApply }) {
  const [desaList, setDesaList] = useState([]);
  const [tableList, setTableList] = useState([]);

  const [kodeDesa, setKodeDesa] = useState("");
  const [tableName, setTableName] = useState("");
  const [action, setAction] = useState("");

  useEffect(() => {
    fetchDesaList()
      .then(setDesaList)
      .catch(() => setDesaList([]));
    fetchTableList()
      .then(setTableList)
      .catch(() => setTableList([]));
  }, []);

  function handleSubmit(e) {
    e.preventDefault();
    onApply({
      kode_desa: kodeDesa || undefined,
      table_name: tableName || undefined,
      action: action || undefined,
    });
  }

  function handleReset() {
    setKodeDesa("");
    setTableName("");
    setAction("");
    onApply({});
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="flex flex-col gap-3 border-b border-slate-200 bg-white px-4 py-4 sm:flex-row sm:flex-wrap sm:items-end sm:px-6"
    >
      <div className="flex flex-col gap-1">
        <label className="text-xs font-medium text-slate-500" htmlFor="kode_desa">
          Desa
        </label>
        <select
          id="kode_desa"
          value={kodeDesa}
          onChange={(e) => setKodeDesa(e.target.value)}
          className="w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500 sm:w-44"
        >
          <option value="">Semua desa</option>
          {desaList.map((d) => (
            <option key={d.kode_desa} value={d.kode_desa}>
              {d.nama_desa}
            </option>
          ))}
        </select>
      </div>

      <div className="flex flex-col gap-1">
        <label className="text-xs font-medium text-slate-500" htmlFor="table_name">
          Tabel
        </label>
        <select
          id="table_name"
          value={tableName}
          onChange={(e) => setTableName(e.target.value)}
          className="w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500 sm:w-40"
        >
          <option value="">Semua tabel</option>
          {tableList.map((t) => (
            <option key={t} value={t}>
              {formatTableName(t)}
            </option>
          ))}
        </select>
      </div>

      <div className="flex flex-col gap-1">
        <label className="text-xs font-medium text-slate-500" htmlFor="action">
          Aksi
        </label>
        <select
          id="action"
          value={action}
          onChange={(e) => setAction(e.target.value)}
          className="w-full rounded-md border border-slate-300 px-3 py-1.5 text-sm focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500 sm:w-36"
        >
          <option value="">Semua</option>
          <option value="INSERT">INSERT</option>
          <option value="UPDATE">UPDATE</option>
          <option value="DELETE">DELETE</option>
        </select>
      </div>

      <div className="flex gap-2">
        <button
          type="submit"
          className="flex-1 cursor-pointer rounded-md bg-slate-900 px-4 py-1.5 text-sm font-medium text-white hover:bg-slate-700 sm:flex-none"
        >
          Terapkan
        </button>
        <button
          type="button"
          onClick={handleReset}
          className="flex-1 rounded-md border cursor-pointer border-slate-300 px-4 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 sm:flex-none"
        >
          Reset
        </button>
      </div>
    </form>
  );
}

export default FilterBar;
