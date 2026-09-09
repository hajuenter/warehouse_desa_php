import { Link, useLocation } from "react-router-dom";

const TABS = [
  { label: "Inbound Queue", path: "/" },
  { label: "Log Login", path: "/log-login" },
  { label: "Outbound Queue", path: "/outbound-queue" },
];

function TabMenu() {
  const location = useLocation();

  return (
    <div className="sticky top-16 z-40 border-b border-slate-200 bg-white shadow-sm">
      <div className="mx-auto flex max-w-7xl gap-4 overflow-x-auto px-3 sm:gap-8 sm:px-6">
        {TABS.map((tab) => {
          const isActive = location.pathname === tab.path;

          return (
            <Link
              key={tab.path}
              to={tab.path}
              className={`cursor-pointer whitespace-nowrap border-b-2 px-1 py-3 text-xs font-semibold transition sm:px-1 sm:py-4 sm:text-sm ${isActive ? "border-blue-900 text-blue-900" : "border-transparent text-slate-500 hover:text-slate-700"
                }`}
            >
              {tab.label}
            </Link>
          );
        })}
      </div>
    </div>
  );
}

export default TabMenu;
