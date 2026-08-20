import { useSelector } from "react-redux";
import { Outlet, useLocation } from "react-router";
import Header from "../core/common/header";
import Sidebar from "../core/common/sidebar";
import ThemeSettings from "../core/common/theme-settings";
import { useEffect, useState } from "react";
import { all_routes } from "./router/all_routes";

const Feature = () => {
  const routes = all_routes;
  const [showLoader, setShowLoader] = useState(true);
  const mobileSidebar = useSelector(
    (state: any) => state.sidebarSlice.mobileSidebar
  );

  const expandMenu = useSelector((state: any) => state.sidebarSlice.expandMenu);

  const dataLayout = useSelector((state: any) => state.themeSetting.dataLayout);
  const dataTopBar = useSelector((state: any) => state.themeSetting.dataTopBar);
  const dataTheme = useSelector((state: any) => state.themeSetting.dataTheme);
  const dataSidebar = useSelector(
    (state: any) => state.themeSetting.dataSidebar
  );
  const dataSidebarBg = useSelector(
    (state: any) => state.themeSetting.dataSidebarBg
  );
  const dataColor = useSelector((state: any) => state.themeSetting.dataColor);
  const location = useLocation();
  useEffect(() => {
    // Mapping functions
    const layoutMap: Record<string, string> = {
      default_layout: "default",
      mini_layout: "mini",
      boxed_layout: "boxed",
      rtl: "rtl",
    };
    const sidebarMap: Record<string, string> = {
      default_data_sidebar: "light",
      dark_data_sidebar: "dark",
      primary_data_sidebar: "primary",
      darkblack_data_sidebar: "darkblack",
      darkblue_data_sidebar: "darkblue",
    };
    const sidebarBgMap: Record<string, string> = {
      default_data_sidebar_bg: "default",
      data_sidebar_1: "sidebarbg1",
      data_sidebar_2: "sidebarbg2",
      data_sidebar_3: "sidebarbg3",
      data_sidebar_4: "sidebarbg4",
      data_sidebar_5: "sidebarbg5",
      data_sidebar_6: "sidebarbg6",
    };
    const colorMap: Record<string, string> = {
      default_data_color: "primary",
      violet_data_color: "violet",
      pink_data_color: "pink",
      orange_data_color: "orange",
      green_data_color: "green",
      red_data_color: "red",
    };
    const topBarMap: Record<string, string> = {
      default_topbar_color: "white",
      dark_topbar_color: "dark",
      primary_topbar_color: "primary",
      grey_topbar_color: "grey",
    };
    const themeMap: Record<string, string> = {
      default_data_theme: "light",
      dark_data_theme: "dark",
    };

    // Set data-theme
    document.documentElement.setAttribute(
      "data-theme",
      themeMap[dataTheme] || ""
    );
    // Set data-layout
    document.documentElement.setAttribute(
      "data-layout",
      layoutMap[dataLayout] || ""
    );
    // Set data-sidebar
    document.documentElement.setAttribute(
      "data-sidebar",
      sidebarMap[dataSidebar] || ""
    );
    // Set data-sidebarbg (was data-sidebar-bg)
    document.documentElement.setAttribute(
      "data-sidebarbg",
      sidebarBgMap[dataSidebarBg] || ""
    );
    // Set data-color
    document.documentElement.setAttribute(
      "data-color",
      colorMap[dataColor] || ""
    );
    // Set data-topbar
    document.documentElement.setAttribute(
      "data-topbar",
      topBarMap[dataTopBar] || ""
    );
  }, [dataTheme, dataLayout, dataSidebar, dataSidebarBg, dataColor, dataTopBar]);
  useEffect(() => {
    if (
      location.pathname === routes.adminDashboard ||
      location.pathname === routes.teacherDashboard ||
      location.pathname === routes.studentDashboard ||
      location.pathname === routes.parentDashboard
    ) {
      // Show the loader when navigating to a new route
      setShowLoader(true);

      // Hide the loader after 2 seconds
      const timeoutId = setTimeout(() => {
        setShowLoader(false);
      }, 2000);

      return () => {
        clearTimeout(timeoutId); // Clear the timeout when component unmounts
      };
    } else {
      setShowLoader(false);
    }
    window.scrollTo(0, 0);
  }, [location.pathname]);
  const Preloader = () => {
    return (
      <div id="global-loader">
        <div className="page-loader"></div>
      </div>
    );
  };
  // Compute theme/layout classes
  let layoutClass = "";
  if (dataLayout === "mini_layout") layoutClass = "mini-sidebar";
  else if (dataLayout === "boxed_layout") layoutClass = "layout-box-mode";
  else if (dataLayout === "rtl") layoutClass = "layout-mode-rtl";
  // // All other theme classes
  // const themeBodyClass = `
  //   ${layoutClass}
  //   ${dataTheme === "dark_data_theme" ? "dark-data-theme" : ""}
  //   ${dataTopBar === "default_topbar_color" ? "default-topbar" : ""}
  //   ${dataTopBar === "dark_topbar_color" ? "dark-topbar" : ""}
  //   ${dataTopBar === "primary_topbar_color" ? "primary-topbar" : ""}
  //   ${dataTopBar === "grey_topbar_color" ? "grey-topbar" : ""}
  //   ${dataTheme === "default_data_theme" ? "default-data-theme" : ""}
  //   ${dataSidebar === "default_data_sidebar" ? "default-data-sidebar" : ""}
  //   ${dataSidebar === "dark_data_sidebar" ? "dark-data-sidebar" : ""}
  //   ${dataSidebar === "primary_data_sidebar" ? "primary-data-sidebar" : ""}
  //   ${dataSidebar === "darkblack_data_sidebar" ? "darkblack-data-sidebar" : ""}
  //   ${dataSidebar === "darkblue_data_sidebar" ? "darkblue-data-sidebar" : ""}
  //   ${dataSidebarBg === "default_data_sidebar_bg" ? "default-data-sidebar-bg" : ""}
  //   ${dataSidebarBg === "data_sidebar_1" ? "data-sidebar-1" : ""}
  //   ${dataSidebarBg === "data_sidebar_2" ? "data-sidebar-2" : ""}
  //   ${dataSidebarBg === "data_sidebar_3" ? "data-sidebar-3" : ""}
  //   ${dataSidebarBg === "data_sidebar_4" ? "data-sidebar-4" : ""}
  //   ${dataSidebarBg === "data_sidebar_5" ? "data-sidebar-5" : ""}
  //   ${dataSidebarBg === "data_sidebar_6" ? "data-sidebar-6" : ""}
  //   ${dataColor === "default_data_color" ? "default-data-color" : ""}
  //   ${dataColor === "violet_data_color" ? "violet-data-color" : ""}
  //   ${dataColor === "pink_data_color" ? "pink-data-color" : ""}
  //   ${dataColor === "orange_data_color" ? "orange-data-color" : ""}
  //   ${dataColor === "green_data_color" ? "green-data-color" : ""}
  //   ${dataColor === "red_data_color" ? "red-data-color" : ""}
  // `.replace(/\s+/g, ' ').trim();

  useEffect(() => {
    // Remove any lingering unwanted classes
    const unwanted = ['default-layout', 'expand-menu'];
    unwanted.forEach(cls => document.body.classList.remove(cls));
    let bodyClass = layoutClass;
    if (dataTheme === "dark_data_theme") {
      bodyClass += " dark-data-theme";
    }
    if (dataLayout === "mini_layout" && expandMenu) {
      bodyClass += " expand-menu";
    }
    document.body.className = bodyClass.trim();
    // Set data-sidebarbg on body as attribute (not as class)
    const sidebarBgMap: { [key: string]: string } = {
      "default_data_sidebar_bg": "default-data-sidebar-bg",
      "data_sidebar_1": "sidebarbg1",
      "data_sidebar_2": "sidebarbg2",
      "data_sidebar_3": "sidebarbg3",
      "data_sidebar_4": "sidebarbg4",
      "data_sidebar_5": "sidebarbg5",
      "data_sidebar_6": "sidebarbg6",
    };
    document.body.setAttribute(
      "data-sidebarbg",
      sidebarBgMap[dataSidebarBg] || ""
    );
  }, [layoutClass, dataSidebarBg, dataLayout, expandMenu, dataTheme]);
  return (
    <div>
      {showLoader ? (
        <>
          <Preloader />
          <div
            className={`main-wrapper 
        ${mobileSidebar ? "slide-nav" : ""}`}
          >
            <Header />
            <Sidebar />
            <Outlet />
            {!location.pathname.includes("layout") && <ThemeSettings />}
          </div>
        </>
      ) : (
        <>
          <div
            className={`main-wrapper 
        ${mobileSidebar ? "slide-nav" : ""}`}
          >
            <Header />
            <Sidebar />
            <Outlet />
            {!location.pathname.includes("layout") && <ThemeSettings />}
          </div>
        </>
      )}
      {/* <Loader/> */}

      <div className="sidebar-overlay"></div>
    </div>
  );
};

export default Feature;
