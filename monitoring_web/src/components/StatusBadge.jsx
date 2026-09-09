const BADGE_STYLES = {
  INSERT: "bg-emerald-100 text-emerald-700",
  UPDATE: "bg-amber-100 text-amber-700",
  DELETE: "bg-rose-100 text-rose-700",
};

const STATUS_STYLES = {
  RECEIVED: "text-blue-600",
  PROCESSING: "text-yellow-600",
  SUCCESS: "text-emerald-600",
  FAILED: "text-rose-600",
};

function StatusBadge({ value, variant = "status" }) {
  if (variant === "action") {
    const style = BADGE_STYLES[value] || "bg-slate-100 text-slate-700";
    return <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${style}`}>{value}</span>;
  }

  const style = STATUS_STYLES[value] || "text-slate-600";
  const label = value.charAt(0) + value.slice(1).toLowerCase();
  return (
    <span className={`inline-flex items-center gap-1.5 font-medium ${style}`} style={{ fontSize: "14px", fontFamily: "'Inter', sans-serif" }}>
      <span className="leading-none">&#9679;</span>
      {label}
    </span>
  );
}

export default StatusBadge;
