function DesaCard({ desa, onClick }) {
  return (
    <button
      onClick={() => onClick(desa.kode_desa)}
      className="relative flex cursor-pointer flex-col items-start rounded-xl border border-slate-200 bg-white p-5 text-left shadow-sm transition hover:border-slate-400 hover:shadow-md"
    >
      <span
        className={`absolute right-4 top-4 h-2.5 w-2.5 rounded-full ${
          desa.online === false ? "bg-slate-300" : "bg-emerald-500"
        }`}
      />

      <span className="text-sm font-semibold text-slate-800">{desa.nama_desa}</span>

      <span className="mt-1 text-xs text-slate-400">{desa.kode_desa}</span>

      <div className="mt-4 flex gap-3 text-xs">
        <span className="text-yellow-600">Pending: {desa.pending}</span>
        <span className="text-emerald-600">Success: {desa.success}</span>
        <span className="text-rose-600">Failed: {desa.failed}</span>
      </div>

      <span className="mt-4 text-xs font-medium text-slate-500">Lihat Log Detail</span>
    </button>
  );
}

export default DesaCard;
