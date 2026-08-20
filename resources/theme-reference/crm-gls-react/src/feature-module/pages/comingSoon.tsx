import  { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import ImageWithBasePath from "../../core/common/imageWithBasePath";

const ComingSoon = () => {
  const [seconds, setSeconds] = useState(60);

  useEffect(() => {
    const intervalId = setInterval(() => {
      if (seconds > 0) {
        setSeconds((prevSeconds) => prevSeconds - 1);
      }
    }, 1000);
    return () => clearInterval(intervalId);
  }, [seconds]);

  const formatTime = (time: number) => {
    // Add leading zero for single-digit numbers
    return time < 10 ? `0${time}` : time;
  };
  return (
    <div className="main-wrapper">
      <div className="text-center mt-4">
        <ImageWithBasePath src="assets/img/logo.svg" alt="img" className="img-fluid" />
      </div>
      <div className="comming-soon-pg w-100">
        <div>
          <div className="error-img m-auto">
            <ImageWithBasePath
              src="assets/img/authentication/coming-soon.svg"
              className="img-fluid"
              alt="Img"
            />
          </div>
          <ul className="d-inline-flex align-items-center justify-content-center mb-3">
            <li className="me-sm-3 me-2">
              <div className="d-flex align-items-center justify-content-center flex-column border rounded bg-white px-sm-4 py-sm-3 p-2">
                <h3 className="days fs-sm-30 fs-30">12</h3>
                <p className="fs-16">Day</p>
              </div>
            </li>
            <li className="text-gray fw-medium me-sm-3 me-2">:</li>
            <li className="me-sm-3 me-2">
              <div className="d-flex align-items-center justify-content-center flex-column border rounded bg-white px-sm-4 py-sm-3 p-2">
                <h3 className="hours fs-sm-30 fs-30">22</h3>
                <p className="fs-16">Hrs</p>
              </div>
            </li>
            <li className="text-gray fw-medium me-sm-3 me-2">:</li>
            <li className="me-sm-3 me-2">
              <div className="d-flex align-items-center justify-content-center flex-column border rounded bg-white px-sm-4 py-sm-3 p-2">
                <h3 className="minutes fs-sm-30 fs-30">23</h3>
                <p className="fs-16">Min</p>
              </div>
            </li>
            <li className="text-gray fw-medium me-sm-3 me-2">:</li>
            <li>
              <div className="d-flex align-items-center justify-content-center flex-column border rounded bg-white px-sm-4 py-sm-3 p-2">
                <h3 className="seconds fs-sm-30 fs-30">{formatTime(seconds)}</h3>
                <p className="fs-16">Sec</p>
              </div>
            </li>
          </ul>
          <div className="subscribe-form">
            <div className="mb-3 position-relative">
              <label className="d-flex form-label">
                Subscribe to get notified!
              </label>
              <div className="bg-white border border-2 p-1 d-flex align-items-center rounded ps-0">
                <input
                  type="email"
                  className="form-control border-0"
                  placeholder="Enter Your Email"
                />
                <Link to="#" className="btn btn-primary">
                  Subscribe
                </Link>
              </div>
            </div>
          </div>
          <div className="d-flex flex-wrap justify-content-center align-items-center">
            <Link to="#" className="btn btn-dark btn-icon btn-sm me-2">
              <i className="ti ti-brand-facebook fs-16" />
            </Link>
            <Link to="#" className="btn btn-dark btn-icon btn-sm me-2">
              <i className="ti ti-brand-instagram fs-16" />
            </Link>
            <Link to="#" className="btn btn-dark btn-icon btn-sm me-2">
              <i className="ti ti-brand-twitter fs-16" />
            </Link>
            <Link to="#" className="btn btn-dark btn-icon btn-sm me-2">
              <i className="ti ti-brand-pinterest fs-16" />
            </Link>
            <Link to="#" className="btn btn-dark btn-icon btn-sm">
              <i className="ti ti-brand-linkedin fs-16" />
            </Link>
          </div>
        </div>
      </div>
      <div className="text-center mb-5">
        <p>Copyright © 2024 - Preskool</p>
      </div>
    </div>
  );
};

export default ComingSoon;
