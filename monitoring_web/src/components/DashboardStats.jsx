function Card({ title, value, subtitle, color = "slate" }) {
  const colors = {
    slate: "text-slate-900",
    green: "text-green-600",
    red: "text-red-600",
    amber: "text-amber-600",
    rose: "text-rose-600",
  };

  return (
    <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
      <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{title}</p>

      <h2 className={`text-2xl font-bold sm:text-3xl ${colors[color]}`}>{value}</h2>

      <p className="mt-2 text-xs text-slate-500">{subtitle}</p>
    </div>
  );
}

function DashboardStats({ logs, totalDesa }) {
  const total = logs.length;

  const insert = logs.filter((x) => x.action === "INSERT").length;

  const update = logs.filter((x) => x.action === "UPDATE").length;

  const failed = logs.filter((x) => x.status === "FAILED").length;

  const deleteCount = logs.filter((x) => x.action === "DELETE").length;

  return (
    <div className="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-6">
      <Card title="Total Desa" value={totalDesa} subtitle="Jumlah desa aktif" color="slate" />

      <Card title="Total Event" value={total} subtitle="Total keseluruhan" />

      <Card title="INSERT" value={insert} subtitle="Event Insert" color="green" />

      <Card title="UPDATE" value={update} subtitle="Event Update" color="amber" />

      <Card title="DELETE" value={deleteCount} subtitle="Event Delete" color="rose" />

      <Card title="FAILED" value={failed} subtitle="Perlu perhatian" color="red" />
    </div>
  );
}

export default DashboardStats;
