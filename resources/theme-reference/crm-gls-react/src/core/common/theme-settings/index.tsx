
import { useDispatch, useSelector } from "react-redux";
import {
  resetAllMode,
  setDataColor,
  setDataLayout,
  setDataSidebar,

  setDataTheme,
  setTopBarColor,
} from "../../data/redux/themeSettingSlice";
import { Link } from "react-router-dom";
import ImageWithBasePath from "../imageWithBasePath";

const ThemeSettings = () => {
  const buyNow = () => {
    window.open('https://1.envato.market/52Ndo','_blank')
  }
  const dispatch = useDispatch();
  const dataLayout = useSelector((state: any) => state.themeSetting.dataLayout);
  const dataTopBar = useSelector((state: any) => state.themeSetting.dataTopBar);
  const dataTheme = useSelector((state: any) => state.themeSetting.dataTheme);
  const dataSidebar = useSelector(
    (state: any) => state.themeSetting.dataSidebar
  );
 
  const dataColor = useSelector((state: any) => state.themeSetting.dataColor);

  const handleLayoutChange = (layout: string) => {
    dispatch(setDataLayout(layout));
  };
  const handleTopBarColorChange = (color: string) => {
    dispatch(setTopBarColor(color));
  };
  const handleDataThemeChange = (theme: string) => {
    dispatch(setDataTheme(theme));
  };
  const handleDataSidebarChange = (theme: string) => {
    dispatch(setDataSidebar(theme));
  };
  
  const handleDataColorChange = (bg: string) => {
    dispatch(setDataColor(bg));
  };

  const handleReset = () => {
    dispatch(resetAllMode());
  };

  return (
    <>
      <div className="sidebar-contact ">
        <div
          className="toggle-theme"
          data-bs-toggle="offcanvas"
          data-bs-target="#theme-setting"
        >
          <i className="fa fa-cog fa-w-16 fa-spin" />
        </div>
      </div>
      <div
        className="sidebar-themesettings offcanvas offcanvas-end"
        id="theme-setting"
      >
        <div className="offcanvas-header d-flex align-items-center justify-content-between bg-light-500">
          <div>
            <h4 className="mb-1">Theme Customizer</h4>
            <p>Choose your themes &amp; layouts etc.</p>
          </div>
          <Link
            to="#"
            className="custom-btn-close d-flex align-items-center justify-content-center text-white"
            data-bs-dismiss="offcanvas"
          >
            <i className="ti ti-x" />
          </Link>
        </div>
        <div className="themesettings-inner offcanvas-body">
          <div
            className="accordion accordion-customicon1 accordions-items-seperate"
            id="settingtheme"
          >
            <div className="accordion-item">
              <h2 className="accordion-header">
                <button
                  className="accordion-button text-dark fs-16"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#layoutsetting"
                  aria-expanded="true"
                  aria-controls="collapsecustomicon1One"
                >
                  Select Layouts
                </button>
              </h2>
              <div
                id="layoutsetting"
                className="accordion-collapse collapse show"
              >
                <div className="accordion-body">
                  <div className="row gx-3">
                    <div className="col-4">
                      <div
                        className={`theme-layout mb-3 ${
                          dataLayout === "default_layout" ? "active" : ""
                        }`}
                        onClick={() => handleLayoutChange("default_layout")}
                      >
                        <input
                          type="radio"
                          name="LayoutTheme"
                          id="defaultLayout"
                          defaultValue="default"
                          defaultChecked
                        />
                        <label htmlFor="defaultLayout">
                          <span className="d-block mb-2 layout-img">
                            <ImageWithBasePath
                              src="assets/img/theme/default.svg"
                              alt="img"
                            />
                          </span>
                          <span className="layout-type">Default</span>
                        </label>
                      </div>
                    </div>
                    <div className="col-4">
                      <div
                        className={`theme-layout mb-3 ${
                          dataLayout === "mini_layout" ? "active" : ""
                        }`}
                        onClick={() => handleLayoutChange("mini_layout")}
                      >
                        <input
                          type="radio"
                          name="LayoutTheme"
                          id="miniLayout"
                          defaultValue="mini"
                        />
                        <label htmlFor="miniLayout">
                          <span className="d-block mb-2 layout-img">
                            <ImageWithBasePath
                              src="assets/img/theme/mini.svg"
                              alt="img"
                            />
                          </span>
                          <span className="layout-type">Mini</span>
                        </label>
                      </div>
                    </div>
                    <div className="col-4">
                      <div
                        className={`theme-layout mb-3 ${
                          dataLayout === "boxed_layout" ? "active" : ""
                        }`}
                        onClick={() => handleLayoutChange("boxed_layout")}
                      >
                        <input
                          type="radio"
                          name="LayoutTheme"
                          id="boxLayout"
                          defaultValue="box"
                        />
                        <label htmlFor="boxLayout">
                          <span className="d-block mb-2 layout-img">
                            <ImageWithBasePath
                              src="assets/img/theme/box.svg"
                              alt="img"
                            />
                          </span>
                          <span className="layout-type">Boxed</span>
                        </label>
                      </div>
                    </div>
                    <div className="col-4">
                      <Link
                        to="#"
                        className={`theme-layout mb-3 ${
                          dataLayout === "rtl" ? "active" : ""
                        }`}
                        onClick={() => handleLayoutChange("rtl")}
                      >
                        <span className="d-block mb-2 layout-img">
                          <ImageWithBasePath
                            src="assets/img/theme/rtl.svg"
                            alt="img"
                          />
                        </span>
                        <span className="layout-type">RTL</span>
                      </Link>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="accordion-item">
              <h2 className="accordion-header">
                <button
                  className="accordion-button text-dark fs-16"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#colorsetting"
                  aria-expanded="true"
                >
                  Top Bar Color
                </button>
              </h2>
              <div
                id="colorsetting"
                className="accordion-collapse collapse show"
              >
                <div className="accordion-body">
                  <div className="d-flex align-items-center">
                    <div
                      className={`custom-themecolor custom-themecolor-rounded m-1 me-3 ${
                        dataTopBar === "default_topbar_color" ? "active" : ""
                      }`}
                      onClick={() =>
                        handleTopBarColorChange("default_topbar_color")
                      }
                    >
                      <input
                        type="radio"
                        name="topbar"
                        id="whiteTopbar"
                        defaultValue="white"
                        defaultChecked
                      />
                      <label htmlFor="whiteTopbar" className="white-topbar" />
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded m-1 me-3 ${
                        dataTopBar === "dark_topbar_color" ? "active" : ""
                      }`}
                      onClick={() =>
                        handleTopBarColorChange("dark_topbar_color")
                      }
                    >
                      <input
                        type="radio"
                        name="topbar"
                        id="darkTopbar"
                        defaultValue="dark"
                      />
                      <label htmlFor="darkTopbar" className="dark-topbar bg-dark" />
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded m-1 me-3 ${
                        dataTopBar === "primary_topbar_color" ? "active" : ""
                      }`}
                      onClick={() =>
                        handleTopBarColorChange("primary_topbar_color")
                      }
                    >
                      <input
                        type="radio"
                        name="topbar"
                        id="primaryTopbar"
                        defaultValue="primary"
                      />
                      <label
                        htmlFor="primaryTopbar"
                        className="primary-topbar bg-primary"
                      />
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded m-1 ${
                        dataTopBar === "grey_topbar_color" ? "active" : ""
                      }`}
                      onClick={() =>
                        handleTopBarColorChange("grey_topbar_color")
                      }
                    >
                      <input
                        type="radio"
                        name="topbar"
                        id="greyTopbar"
                        defaultValue="grey"
                      />
                      <label htmlFor="greyTopbar" className="grey-topbar bg-light" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="accordion-item">
              <h2 className="accordion-header">
                <button
                  className="accordion-button text-dark fs-16"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#modesetting"
                  aria-expanded="true"
                >
                  Color Mode
                </button>
              </h2>
              <div
                id="modesetting"
                className="accordion-collapse collapse show"
              >
                <div className="accordion-body">
                  <div className="row gx-3">
                    <div className="col-6">
                      <div
                        className={`theme-mode ${
                          dataTheme === "default_data_theme" ? "active" : ""
                        }`}
                        onClick={() =>
                          handleDataThemeChange("default_data_theme")
                        }
                      >
                        <input
                          type="radio"
                          name="theme"
                          id="lightTheme"
                          defaultValue="light"
                          defaultChecked
                        />
                        <label
                          htmlFor="lightTheme"
                          className="p-2 rounded fw-medium w-100"
                        >
                          <span className="avatar avatar-md d-inline-flex rounded me-2">
                            <i className="ti ti-sun-filled" />
                          </span>
                          Light Mode
                        </label>
                      </div>
                    </div>
                    <div className="col-6">
                      <div
                        className={`theme-mode ${
                          dataTheme === "dark_data_theme" ? "active" : ""
                        }`}
                        onClick={() => handleDataThemeChange("dark_data_theme")}
                      >
                        <input
                          type="radio"
                          name="theme"
                          id="darkTheme"
                          defaultValue="dark"
                        />
                        <label
                          htmlFor="darkTheme"
                          className="p-2 rounded fw-medium w-100"
                        >
                          <span className="avatar avatar-md d-inline-flex rounded me-2">
                            <i className="ti ti-moon-filled" />
                          </span>
                          Dark Mode
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div className="accordion-item">
              <h2 className="accordion-header">
                <button
                  className="accordion-button text-dark fs-16"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#sidebarsetting"
                  aria-expanded="true"
                >
                  Sidebar Color
                </button>
              </h2>
              <div
                id="sidebarsetting"
                className="accordion-collapse collapse show"
              >
                <div className="accordion-body">
                  <div className="d-flex align-items-center">
                    <div
                      className={`custom-themecolor custom-themecolor-rounded m-1 me-3 ${
                        dataSidebar === "default_data_sidebar" ? "active" : ""
                      }`}
                      onClick={() =>
                        handleDataSidebarChange("default_data_sidebar")
                      }
                    >
                      <input
                        type="radio"
                        name="sidebar"
                        id="lightSidebar"
                        defaultValue="light"
                        defaultChecked
                      />
                      <label
                        htmlFor="lightSidebar"
                        className="d-block rounded mb-2"
                      ></label>
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded m-1 me-3  ${
                        dataSidebar === "dark_data_sidebar" ? "active" : ""
                      }`}
                      onClick={() =>
                        handleDataSidebarChange("dark_data_sidebar")
                      }
                    >
                      <input
                        type="radio"
                        name="sidebar"
                        id="darkSidebar"
                        defaultValue="dark"
                      />
                      <label
                        htmlFor="darkSidebar"
                        className="d-block rounded bg-dark mb-2"
                      ></label>
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded m-1 me-3 ${
                        dataSidebar === "primary_data_sidebar" ? "active" : ""
                      }`}
                      onClick={() =>
                        handleDataSidebarChange("primary_data_sidebar")
                      }
                    >
                      <input
                        type="radio"
                        name="sidebar"
                        id="primarySidebar"
                        defaultValue="primary"
                      />
                      <label
                        htmlFor="primarySidebar"
                        className="d-block rounded bg-primary mb-2"
                      ></label>
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded m-1 me-3 ${
                        dataSidebar === "darkblack_data_sidebar" ? "active" : ""
                      }`}
                      onClick={() =>
                        handleDataSidebarChange("darkblack_data_sidebar")
                      }
                    >
                      <input
                        type="radio"
                        name="sidebar"
                        id="darkblackSidebar"
                        defaultValue="darkblack"
                      />
                      <label
                        htmlFor="darkblackSidebar"
                        className="d-block rounded bg-darkblack mb-2"
                      ></label>
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded m-1 ${
                        dataSidebar === "darkblue_data_sidebar" ? "active" : ""
                      }`}
                      onClick={() =>
                        handleDataSidebarChange("darkblue_data_sidebar")
                      }
                    >
                      <input
                        type="radio"
                        name="sidebar"
                        id="darkblueSidebar"
                        defaultValue="darkblue"
                      />
                      <label
                        htmlFor="darkblueSidebar"
                        className="d-block rounded bg-darkblue mb-2"
                      ></label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
      
            <div className="accordion-item">
              <h2 className="accordion-header">
                <button
                  className="accordion-button text-dark fs-16"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#sidebarcolor"
                  aria-expanded="true"
                >
                  Theme Colors
                </button>
              </h2>
              <div
                id="sidebarcolor"
                className="accordion-collapse collapse show"
              >
                <div className="accordion-body pb-2">
                  <div className="d-flex align-items-center">
                    <div
                      className={`custom-themecolor custom-themecolor-rounded me-3 mb-2 ${
                        dataColor === "default_data_color" ? "active" : ""
                      }`}
                      onClick={() =>
                        handleDataColorChange("default_data_color")
                      }
                    >
                      <input
                        type="radio"
                        name="color"
                        id="primaryColor"
                        defaultValue="primary"
                        defaultChecked
                      />
                      <label htmlFor="primaryColor" className="primary-clr" />
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded me-3 mb-2 ${
                        dataColor === "violet_data_color" ? "active" : ""
                      }`}
                      onClick={() => handleDataColorChange("violet_data_color")}
                    >
                      <input
                        type="radio"
                        name="color"
                        id="violetColor"
                        defaultValue="violet"
                      />
                      <label htmlFor="violetColor" className="violet-clr" />
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded me-3 mb-2 ${
                        dataColor === "pink_data_color" ? "active" : ""
                      }`}
                      onClick={() => handleDataColorChange("pink_data_color")}
                    >
                      <input
                        type="radio"
                        name="color"
                        id="pinkColor"
                        defaultValue="pink"
                      />
                      <label htmlFor="pinkColor" className="pink-clr" />
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded me-3 mb-2 ${
                        dataColor === "orange_data_color" ? "active" : ""
                      }`}
                      onClick={() => handleDataColorChange("orange_data_color")}
                    >
                      <input
                        type="radio"
                        name="color"
                        id="orangeColor"
                        defaultValue="orange"
                      />
                      <label htmlFor="orangeColor" className="orange-clr" />
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded me-3 mb-2 ${
                        dataColor === "green_data_color" ? "active" : ""
                      }`}
                      onClick={() => handleDataColorChange("green_data_color")}
                    >
                      <input
                        type="radio"
                        name="color"
                        id="greenColor"
                        defaultValue="green"
                      />
                      <label htmlFor="greenColor" className="green-clr" />
                    </div>
                    <div
                      className={`custom-themecolor custom-themecolor-rounded me-3 mb-2 ${
                        dataColor === "red_data_color" ? "active" : ""
                      }`}
                      onClick={() => handleDataColorChange("red_data_color")}
                    >
                      <input
                        type="radio"
                        name="color"
                        id="redColor"
                        defaultValue="red"
                      />
                      <label htmlFor="redColor" className="red-clr" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div className="bg-light-500 p-3">
          <div className="row gx-3">
            <div className="col-6">
              <Link
                onClick={handleReset}
                to="#"
                id="resetbutton"
                className="btn btn-light close-theme w-100"
              >
                Reset
              </Link>
            </div>
            <div className="col-6">
              <Link onClick={buyNow}
                to="#"
                target="_blank"
                className="btn btn-primary w-100"
                data-bs-dismiss="offcanvas"
              >
                Buy Now
              </Link>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default ThemeSettings;
