import { Fragment, useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { CircleUser, KeyRound, LogOut, ChevronDown, TriangleAlert } from "lucide-react";
import { Dialog, Transition } from "@headlessui/react";

function Navbar({ autoRefresh, isOnline }) {
  const [now, setNow] = useState(new Date());
  const [menuOpen, setMenuOpen] = useState(false);
  const [showLogoutModal, setShowLogoutModal] = useState(false);

  const menuRef = useRef(null);
  const navigate = useNavigate();

  useEffect(() => {
    const interval = setInterval(() => {
      setNow(new Date());
    }, 1000);

    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    function handleClickOutside(e) {
      if (menuRef.current && !menuRef.current.contains(e.target)) {
        setMenuOpen(false);
      }
    }

    document.addEventListener("mousedown", handleClickOutside);

    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, []);

  function handleLogout() {
    setShowLogoutModal(false);
    localStorage.removeItem("auth_token");
    navigate("/login");
  }

  function openLogoutModal() {
    setMenuOpen(false);
    setShowLogoutModal(true);
  }

  function handleChangePassword() {
    setMenuOpen(false);
    navigate("/ganti-password");
  }

  return (
    <>
      <nav className="sticky top-0 z-50 border-b border-slate-200 bg-white shadow-sm">
        <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-2 px-3 sm:px-6">
          <div className="flex min-w-0 items-center gap-2 sm:gap-3">
            <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-blue-900 text-sm font-bold text-white sm:h-10 sm:w-10 sm:text-lg">
              M
            </div>

            <div className="min-w-0">
              <h1 className="truncate text-sm font-semibold text-blue-900 sm:text-lg">Monitoring Sinkronisasi Desa</h1>

              <p className="hidden text-xs text-slate-500 sm:block">Sistem Monitoring Webhook OpenSID</p>
            </div>
          </div>

          <div className="flex shrink-0 items-center gap-2 text-xs sm:gap-6 sm:text-sm">
            <div className="hidden items-center gap-2 sm:flex" style={{ fontSize: "14px", fontFamily: "'Inter', sans-serif" }}>
              <span className={`h-2.5 w-2.5 rounded-full ${isOnline ? "animate-pulse bg-green-500" : "bg-red-500"}`} />

              <span className={`font-medium ${isOnline ? "text-green-600" : "text-red-600"}`}>{isOnline ? "System Online" : "System Offline"}</span>
            </div>

            <span className={`h-2 w-2 rounded-full sm:hidden ${isOnline ? "animate-pulse bg-green-500" : "bg-red-500"}`} />

            <span className="hidden text-slate-300 sm:block">|</span>

            <div className="hidden text-slate-500 md:block" style={{ fontSize: "14px", fontFamily: "'Inter', sans-serif" }}>{now.toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" })}</div>

            <span className="hidden text-slate-300 sm:block">|</span>

            <div className="relative" ref={menuRef}>
              <button
                onClick={() => setMenuOpen((prev) => !prev)}
                className="flex cursor-pointer items-center gap-1.5 rounded-md px-1.5 py-1 hover:bg-slate-100"
              >
                <CircleUser className="h-7 w-7 text-slate-400 sm:h-8 sm:w-8" />
                <span className="hidden font-medium text-slate-700 lg:block">Administrator</span>
                <ChevronDown className="hidden h-4 w-4 text-slate-400 lg:block" />
              </button>

              {menuOpen && (
                <div className="absolute right-0 mt-2 w-48 overflow-hidden rounded-md border border-slate-200 bg-white shadow-lg">
                  <button
                    onClick={handleChangePassword}
                    className="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50"
                  >
                    <KeyRound className="h-4 w-4" />
                    Ganti Password
                  </button>

                  <button
                    onClick={openLogoutModal}
                    className="flex w-full cursor-pointer items-center gap-2 px-4 py-2.5 text-left text-sm text-rose-600 hover:bg-rose-50"
                  >
                    <LogOut className="h-4 w-4" />
                    Keluar
                  </button>
                </div>
              )}
            </div>
          </div>
        </div>
      </nav>

      <Transition appear show={showLogoutModal} as={Fragment}>
        <Dialog as="div" className="relative z-100" onClose={setShowLogoutModal}>
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-200"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-150"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 bg-black/40" />
          </Transition.Child>

          <div className="fixed inset-0 overflow-y-auto">
            <div className="flex min-h-full items-start justify-center p-4 pt-6">
              <Transition.Child
                as={Fragment}
                enter="ease-out duration-200"
                enterFrom="opacity-0 scale-95 -translate-y-2"
                enterTo="opacity-100 scale-100 translate-y-0"
                leave="ease-in duration-150"
                leaveFrom="opacity-100 scale-100"
                leaveTo="opacity-0 scale-95"
              >
                <Dialog.Panel className="w-full max-w-sm overflow-hidden rounded-xl bg-white shadow-2xl">
                  <div className="p-6">
                    <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100">
                      <TriangleAlert className="h-6 w-6 text-rose-600" />
                    </div>

                    <Dialog.Title className="mt-4 text-center text-lg font-semibold text-slate-900">Keluar</Dialog.Title>

                    <Dialog.Description className="mt-2 text-center text-sm text-slate-500">
                      Apakah Anda yakin ingin keluar dari dashboard?
                    </Dialog.Description>
                  </div>

                  <div className="flex justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button
                      onClick={() => setShowLogoutModal(false)}
                      className="cursor-pointer rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-100"
                    >
                      Batal
                    </button>

                    <button
                      onClick={handleLogout}
                      className="cursor-pointer rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
                    >
                      Keluar
                    </button>
                  </div>
                </Dialog.Panel>
              </Transition.Child>
            </div>
          </div>
        </Dialog>
      </Transition>
    </>
  );
}

export default Navbar;
