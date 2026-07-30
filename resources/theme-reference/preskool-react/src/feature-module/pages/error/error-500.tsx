
import ImageWithBasePath from "../../../core/common/imageWithBasePath";
import { Link } from "react-router-dom";
import { all_routes } from "../../router/all_routes";

const Error500 = () => {
  const routes = all_routes;
  return (
    <div>
      <div className="row justify-content-center">
        <div className="col-xxl-5 col-xl-5 col-md-6">
          <div className="d-flex flex-column justify-content-between vh-100">
            <div className="text-center p-4">
              <ImageWithBasePath src="assets/img/logo.svg" alt="img" className="img-fluid" />
            </div>
            <div className="d-flex flex-column align-items-center justify-content-center mb-4">
              <div className="mb-4">
                <ImageWithBasePath
                  src="assets/img/authentication/error-500.svg"
                  className="error-img img-fluid"
                  alt="Img"
                />
              </div>
              <h3 className="h2 mb-3">Oops, something went wrong</h3>
              <p className="text-center">
                Server Error 500. We apologise and are fixing the problem.
              </p>
              <Link
                to={routes.adminDashboard}
                className="btn btn-primary d-flex align-items-center"
              >
                <i className="ti ti-arrow-left me-2" />
                Back to Dashboard
              </Link>
            </div>
            <div className="text-center p-4">
              <p>Copyright © 2024 - Preskool</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Error500;
