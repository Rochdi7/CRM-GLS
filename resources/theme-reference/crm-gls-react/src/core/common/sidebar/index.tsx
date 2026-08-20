import { useEffect, useState } from "react";
import { Link, useLocation } from "react-router-dom";

import { SidebarData } from "../../data/json/sidebarData";
import ImageWithBasePath from "../imageWithBasePath";
import "../../../style/icon/tabler-icons/webfont/tabler-icons.css";
import { setExpandMenu } from "../../data/redux/sidebarSlice";
import { useDispatch } from "react-redux";
import {
  resetAllMode,
  setDataLayout,
} from "../../data/redux/themeSettingSlice";
import usePreviousRoute from "./usePreviousRoute";

import "../../../../node_modules/react-perfect-scrollbar/dist/css/styles.css";
import PerfectScrollbar from "react-perfect-scrollbar";
import "../../../../node_modules/react-perfect-scrollbar/dist/css/styles.css";

const Sidebar = () => {
  const Location = useLocation();

  const [subOpen, setSubopen] = useState<any>("");
  const [subsidebar, setSubsidebar] = useState("");

  const toggleSidebar = (title: any) => {
    localStorage.setItem("menuOpened", title);
    if (title === subOpen) {
      setSubopen("");
    } else {
      setSubopen(title);
    }
  };

  const toggleSubsidebar = (subitem: any) => {
    if (subitem === subsidebar) {
      setSubsidebar("");
    } else {
      setSubsidebar(subitem);
    }
  };

  const handleLayoutChange = (layout: string) => {
    dispatch(setDataLayout(layout));
  };

  const handleClick = (label: any, themeSetting: any, layout: any) => {
    toggleSidebar(label);
    if (themeSetting) {
      handleLayoutChange(layout);
    }
  };

  const getLayoutClass = (label: any) => {
    switch (label) {
      case "Default":
        return "default_layout";
      case "Mini":
        return "mini_layout";
      case "Box":
        return "boxed_layout";
      case "Dark":
        return "dark_data_theme";
      case "RTL":
        return "rtl";
      default:
        return "";
    }
  };
  const location = useLocation();
  const dispatch = useDispatch();
  const previousLocation = usePreviousRoute();

  useEffect(() => {
    const layoutPages = [
      "/layout-dark",
      "/layout-rtl",
      "/layout-mini",
      "/layout-box",
      "/layout-default",
    ];

    const isCurrentLayoutPage = layoutPages.some((path) =>
      location.pathname.includes(path)
    );
    const isPreviousLayoutPage =
      previousLocation &&
      layoutPages.some((path) => previousLocation.pathname.includes(path));

    if (isPreviousLayoutPage && !isCurrentLayoutPage) {
      dispatch(resetAllMode());
    }
  }, [location, previousLocation, dispatch]);

  useEffect(() => {
    setSubopen(localStorage.getItem("menuOpened"));
    // Select all 'submenu' elements
    const submenus = document.querySelectorAll(".submenu");

     const mainWrapper = document.querySelector('.main-wrapper');
    if (mainWrapper) {
      mainWrapper.classList.remove('slide-nav');
    }
    // Loop through each 'submenu'
    submenus.forEach((submenu) => {
      // Find all 'li' elements within the 'submenu'
      const listItems = submenu.querySelectorAll("li");
      submenu.classList.remove("active");
      // Check if any 'li' has the 'active' class
      listItems.forEach((item) => {
        if (item.classList.contains("active")) {
          // Add 'active' class to the 'submenu'
          submenu.classList.add("active");
          return;
        }
      });
    });
  }, [Location.pathname]);

  const onMouseEnter = () => {
    dispatch(setExpandMenu(true));
  };
  const onMouseLeave = () => {
    dispatch(setExpandMenu(false));
  };
  return (
    <>
      <div
        className="sidebar"
        id="sidebar"
        onMouseEnter={onMouseEnter}
        onMouseLeave={onMouseLeave}
      >
        <PerfectScrollbar>
          <div className="sidebar-inner slimscroll">
            <div id="sidebar-menu" className="sidebar-menu">
              <ul>
                <li>
                  <Link
                    to="#"
                    className="d-flex align-items-center border bg-white rounded p-2 mb-4"
                  >
                    <ImageWithBasePath
                      src="assets/img/icons/global-img.svg"
                      className="avatar avatar-md img-fluid rounded"
                      alt="Profile"
                    />
                    <span className="text-dark ms-2 fw-normal">
                      Global International
                    </span>
                  </Link>
                </li>
              </ul>

              <ul>
                {SidebarData?.map((mainLabel, index) => (
                  <li key={index}>
                    <h6 className="submenu-hdr">
                      <span>{mainLabel?.label}</span>
                    </h6>
                    <ul>
                      {mainLabel?.submenuItems?.map((title: any) => {
                        // Flatten all nested links for active state matching
                        const flatLinks: string[] = [
                          title?.link,
                          title?.subLink1,
                          title?.subLink2,
                          title?.subLink3,
                          title?.subLink4,
                          title?.subLink5,
                          title?.subLink6,
                          title?.subLink7,
                          ...(title?.submenuItems?.flatMap((link: any) => {
                            return [
                              link?.link,
                              ...(link?.submenuItems?.map(
                                (item: any) => item?.link
                              ) || []),
                            ];
                          }) || []),
                        ].filter(Boolean); // remove undefined/null

                        const isActive = flatLinks.includes(Location.pathname);

                        return (
                          <li className="submenu" key={title.label}>
                            <Link
                              to={title?.submenu ? "#" : title?.link}
                              onClick={() =>
                                handleClick(
                                  title?.label,
                                  title?.themeSetting,
                                  getLayoutClass(title?.label)
                                )
                              }
                              className={`${
                                subOpen === title?.label ? "subdrop" : ""
                              } ${isActive ? "active" : ""}`}
                            >
                              <i className={title.icon}></i>
                              <span>{title?.label}</span>
                              {title?.version && (
                                <span className="badge badge-primary badge-xs text-white fs-10 ms-auto">
                                  {title?.version}
                                </span>
                              )}
                              <span
                                className={title?.submenu ? "menu-arrow" : ""}
                              />
                            </Link>

                            {/* Submenu Level 1 */}
                            {title?.submenu !== false &&
                              subOpen === title?.label && (
                                <ul style={{ display: "block" }}>
                                  {title?.submenuItems?.map((item: any) => {
                                    const subLinks: string[] = [
                                      item?.link,
                                      item?.subLink1,
                                      item?.subLink2,
                                      item?.subLink3,
                                      item?.subLink4,
                                      item?.subLink5,
                                      item?.subLink6,
                                      ...(item?.submenuItems?.map(
                                        (sub: any) => sub?.link
                                      ) || []),
                                    ].filter(Boolean);

                                    const isSubActive = subLinks.includes(
                                      Location.pathname
                                    );

                                    return (
                                      <li
                                        key={item.label}
                                        className={
                                          item?.submenuItems
                                            ? "submenu submenu-two"
                                            : ""
                                        }
                                      >
                                        <Link
                                          to={item?.link}
                                          className={`${
                                            isSubActive ? "active" : ""
                                          } ${
                                            subsidebar === item?.label
                                              ? "subdrop"
                                              : ""
                                          }`}
                                          onClick={() =>
                                            toggleSubsidebar(item?.label)
                                          }
                                        >
                                          {item?.label}
                                          <span
                                            className={
                                              item?.submenu ? "menu-arrow" : ""
                                            }
                                          />
                                        </Link>

                                        {/* Submenu Level 2 */}
                                        {item?.submenuItems &&
                                          subsidebar === item?.label && (
                                            <ul style={{ display: "block" }}>
                                              {item?.submenuItems?.map(
                                                (subItem: any) => {
                                                  const isDeepActive =
                                                    subItem?.link ===
                                                    Location.pathname;

                                                  return (
                                                    <li key={subItem.label}>
                                                      <Link
                                                        to={subItem?.link}
                                                        className={`submenu-two ${
                                                          isDeepActive
                                                            ? "active"
                                                            : ""
                                                        }`}
                                                      >
                                                        {subItem?.label}
                                                      </Link>
                                                    </li>
                                                  );
                                                }
                                              )}
                                            </ul>
                                          )}
                                      </li>
                                    );
                                  })}
                                </ul>
                              )}
                          </li>
                        );
                      })}
                    </ul>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </PerfectScrollbar>
      </div>
    </>
  );
};

export default Sidebar;
