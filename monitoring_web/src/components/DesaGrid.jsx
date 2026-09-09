import DesaCard from "./DesaCard";

function DesaGrid({ desaList, lastUpdated, onSelectDesa }) {
  return (
    <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div className="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
          <h3 className="font-semibold text-slate-800">Daftar Desa</h3>
          <p className="mt-1 text-sm text-slate-500">Pilih desa untuk melihat detail antrian sinkronisasi.</p>
        </div>

        {lastUpdated && (
          <div className="text-xs text-slate-500 sm:text-sm">
            Update terakhir : <span className="font-medium">{lastUpdated.toLocaleTimeString("id-ID")}</span>
          </div>
        )}
      </div>

      <div className="p-4 sm:p-6">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {desaList.map((desa) => (
            <DesaCard key={desa.kode_desa} desa={desa} onClick={onSelectDesa} />
          ))}
        </div>
      </div>
    </section>
  );
}

export default DesaGrid;
