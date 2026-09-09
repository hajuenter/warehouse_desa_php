import { X } from "lucide-react";

function ConfirmModal({
    open,
    title,
    message,
    confirmText = "Konfirmasi",
    cancelText = "Batal",
    confirmColor = "rose",
    onConfirm,
    onCancel,
    icon,
}) {
    if (!open) return null;

    const confirmClass =
        confirmColor === "rose"
            ? "bg-rose-600 hover:bg-rose-700"
            : "bg-blue-600 hover:bg-blue-700";

    return (
        <div
            className="fixed inset-0 z-100 flex items-start justify-center bg-black/40 px-4 pt-24"
            onClick={onCancel}
        >
            <div
                className="w-full max-w-sm rounded-xl bg-white shadow-2xl"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="relative p-6">
                    <button
                        onClick={onCancel}
                        className="absolute top-4 right-4 cursor-pointer rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                    >
                        <X size={18} />
                    </button>

                    {icon && (
                        <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            {icon}
                        </div>
                    )}

                    <h2 className="text-lg font-semibold text-slate-900">
                        {title}
                    </h2>

                    <p className="mt-2 text-sm text-slate-500">
                        {message}
                    </p>
                </div>

                <div className="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <button
                        onClick={onCancel}
                        className="cursor-pointer rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100"
                    >
                        {cancelText}
                    </button>

                    <button
                        onClick={onConfirm}
                        className={`cursor-pointer rounded-md px-4 py-2 text-sm font-medium text-white ${confirmClass}`}
                    >
                        {confirmText}
                    </button>
                </div>
            </div>
        </div>
    );
}

export default ConfirmModal;