function ChangedRow({ field, oldValue, newValue, changed }) {
  return (
    <tr className={changed ? "bg-amber-50" : ""}>
      <td className="px-3 py-1.5 text-xs font-medium text-slate-500">{field}</td>
      <td className="px-3 py-1.5 text-xs text-slate-400 line-through decoration-slate-300">{changed ? String(oldValue ?? "—") : ""}</td>
      <td className="px-3 py-1.5 text-xs font-medium text-slate-800">{String(newValue ?? "—")}</td>
    </tr>
  );
}

function PayloadDetail({ action, payload }) {
  if (action === "UPDATE" && payload?.old && payload?.new) {
    const fields = Object.keys(payload.new);

    return (
      <table className="w-full border-collapse">
        <thead>
          <tr className="text-left text-xs uppercase text-slate-400">
            <th className="px-3 py-1.5">Kolom</th>
            <th className="px-3 py-1.5">Sebelum</th>
            <th className="px-3 py-1.5">Sesudah</th>
          </tr>
        </thead>
        <tbody>
          {fields.map((field) => {
            const changed = String(payload.old[field]) !== String(payload.new[field]);
            return <ChangedRow key={field} field={field} oldValue={payload.old[field]} newValue={payload.new[field]} changed={changed} />;
          })}
        </tbody>
      </table>
    );
  }

  return <pre className="overflow-x-auto rounded-md bg-slate-50 p-3 text-xs text-slate-700">{JSON.stringify(payload, null, 2)}</pre>;
}

export default PayloadDetail;
