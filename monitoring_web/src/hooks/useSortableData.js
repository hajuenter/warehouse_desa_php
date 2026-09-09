import { useMemo, useState } from "react";

export default function useSortableData(items, defaultKey = null, defaultDir = "asc") {
  const [sortConfig, setSortConfig] = useState(defaultKey ? { key: defaultKey, direction: defaultDir } : null);

  function requestSort(key) {
    setSortConfig((prev) => {
      if (!prev || prev.key !== key) return { key, direction: "asc" };
      if (prev.direction === "asc") return { key, direction: "desc" };
      return null;
    });
  }

  const sortedItems = useMemo(() => {
    if (!sortConfig) return items;

    const sorted = [...items].sort((a, b) => {
      let aVal = a[sortConfig.key];
      let bVal = b[sortConfig.key];

      if (aVal == null && bVal == null) return 0;
      if (aVal == null) return 1;
      if (bVal == null) return -1;

      let cmp;

      if (typeof aVal === "number" && typeof bVal === "number") {
        cmp = aVal - bVal;
      } else if (typeof aVal === "string" && typeof bVal === "string") {
        const aDate = Date.parse(aVal);
        const bDate = Date.parse(bVal);
        if (!isNaN(aDate) && !isNaN(bDate)) {
          cmp = aDate - bDate;
        } else {
          cmp = aVal.localeCompare(bVal, "id-ID");
        }
      } else {
        cmp = String(aVal).localeCompare(String(bVal), "id-ID");
      }

      return sortConfig.direction === "asc" ? cmp : -cmp;
    });

    return sorted;
  }, [items, sortConfig]);

  return { sortedItems, sortConfig, requestSort };
}
