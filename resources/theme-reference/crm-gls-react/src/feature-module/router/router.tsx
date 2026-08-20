
import {  Route, Routes } from "react-router";
import { authRoutes, publicRoutes } from "./router.link";
import Feature from "../feature";
import AuthFeature from "../authFeature";
import React, { Suspense } from "react";

// Lazy load Login component
const Login = React.lazy(() => import("../auth/login/login"));

// Loading fallback component
const LoadingFallback = () => (
  <div style={{ 
    display: 'flex', 
    justifyContent: 'center', 
    alignItems: 'center', 
    height: '100vh',
    fontSize: '18px'
  }}>
    Loading...
  </div>
);

const ALLRoutes: React.FC = () => {
  return (
    <Suspense fallback={<LoadingFallback />}>
      <Routes>
        <Route path="/" element={<Login />} />
        <Route element={<Feature />}>
          {publicRoutes.map((route, idx) => (
            <Route 
              path={route.path} 
              element={route.element}
              key={idx} 
            />
          ))}
        </Route>

        <Route element={<AuthFeature />}>
          {authRoutes.map((route, idx) => (
            <Route 
              path={route.path} 
              element={route.element} 
              key={idx} 
            />
          ))}
        </Route>
      </Routes>
    </Suspense>
  );
};

export default ALLRoutes;
