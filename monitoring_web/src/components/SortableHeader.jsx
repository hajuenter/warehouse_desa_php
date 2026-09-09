import { ArrowUpDown, ArrowUp, ArrowDown } from "lucide-react";

export default function SortableHeader({ children, sortKey, sortConfig, onSort, className = "" }) {
  const active = sortConfig && sortConfig.key === sortKey;

  return (
    <th className={`cursor-pointer select-none whitespace-nowrap hover:bg-slate-100 ${className}`} onClick={() => onSort(sortKey)}>
      <span className="inline-flex items-center gap-1">
        {children}
        <span className="ml-0.5 text-slate-400">
          {!active && <ArrowUpDown size={13} strokeWidth={2} />}
          {active && sortConfig.direction === "asc" && <ArrowUp size={13} strokeWidth={2.5} />}
          {active && sortConfig.direction === "desc" && <ArrowDown size={13} strokeWidth={2.5} />}
        </span>
      </span>
    </th>
  );
}
