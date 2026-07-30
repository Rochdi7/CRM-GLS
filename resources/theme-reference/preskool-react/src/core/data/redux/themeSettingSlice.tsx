import { createSlice } from "@reduxjs/toolkit";

const initialState = {
  dataLayout: localStorage.getItem("dataLayout") || "default_layout",
  dataTopBar: localStorage.getItem("dataTopBar") || "default_topbar_color",
  dataTheme: localStorage.getItem("dataTheme") || "default_data_theme",
  dataSidebar: localStorage.getItem("dataSidebar") || "default_data_sidebar",
  dataSidebarBg:
    localStorage.getItem("dataSidebarBg") || "default_data_sidebar_bg",
  dataColor: localStorage.getItem("dataColor") || "default_data_color",
  isRtl: localStorage.getItem("rtl") || false,
};

const themeSettingSlice = createSlice({
  name: "themeSetting",
  initialState,
  reducers: {
    setDataLayout: (state, action) => {
      state.dataLayout = action.payload;
      localStorage.setItem("dataLayout", action.payload);
    },
    setTopBarColor: (state, action) => {
      state.dataTopBar = action.payload;
      localStorage.setItem("dataTopBar", action.payload);
    },
    setDataTheme: (state, action) => {
      state.dataTheme = action.payload;
      localStorage.setItem("dataTheme", action.payload);
    },
    setDataSidebar: (state, action) => {
      state.dataSidebar = action.payload;
      localStorage.setItem("dataSidebar", action.payload);
    },
    setDataSidebarBg: (state, action) => {
      state.dataSidebarBg = action.payload;
      localStorage.setItem("dataSidebarBg", action.payload);
    },
    setDataColor: (state, action) => {
      state.dataColor = action.payload;
      localStorage.setItem("dataColor", action.payload);
    },
    setRtl: (state, action) => {
      state.isRtl = action.payload;
      localStorage.setItem("rtl", action.payload);
    },
    resetAllMode: (state: any) => {
      state.dataLayout = "default_layout";
      state.dataTopBar = "default_topbar_color";
      state.dataTheme = "default_data_theme";
      state.dataSidebar = "default_data_sidebar";
      state.dataSidebarBg = "data_sidebar_default";
      state.dataColor = "default_data_color";

      localStorage.setItem("dataLayout", "default_layout");
      localStorage.setItem("dataTopBar", "default_topbar_color");
      localStorage.setItem("dataTheme", "default_data_theme");
      localStorage.setItem("dataSidebar", "default_data_sidebar");
      localStorage.setItem("dataSidebarBg", "default_data_sidebar_bg");
      localStorage.setItem("dataColor", "default_data_color");
    },
  },
});

export const {
  setDataLayout,
  resetAllMode,
  setTopBarColor,
  setDataTheme,
  setDataSidebar,
  setDataSidebarBg,
  setDataColor,
  setRtl,
} = themeSettingSlice.actions;

export default themeSettingSlice.reducer;
