

import { Outlet } from "react-router";

const AuthFeature = () => {
  return (
    <div className="account-page">
      <div className="main-wrapper">
        <Outlet />
      </div>
    </div>
  );
};

export default AuthFeature;
